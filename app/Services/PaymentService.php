<?php
namespace App\Services;
use App\Models\Booking;
use Illuminate\Support\Facades\{DB,Mail};
class PaymentService {
 public function __construct(private FlowGateway $flow,private PaypalGateway $paypal){}
 public function configured(string $provider): bool {if(!config('services.payments_enabled'))return false;return $provider==='flow' ? filled(config('services.flow.key'))&&filled(config('services.flow.secret')) : filled(config('services.paypal.client_id'))&&filled(config('services.paypal.secret'));}
 public function start(Booking $b): string {
  if($b->provider==='flow'){
   $data=$this->flow->create($b);$b->update(['provider_token'=>$data['token'],'provider_order_id'=>(string)$data['flowOrder']]);
   $url=$data['url'].'?token='.rawurlencode($data['token']);
  }else{
   $data=$this->paypal->create($b);$b->update(['provider_order_id'=>$data['id']]);
   $url=collect($data['links'])->firstWhere('rel','payer-action')['href']??collect($data['links'])->firstWhere('rel','approve')['href']??null;
  }
  // Only follow HTTPS links returned by the configured payment provider.
  $host=parse_url($url??'',PHP_URL_HOST);
  $allowed=$b->provider==='flow'?['flow.cl','sandbox.flow.cl','www.flow.cl']:['www.paypal.com','www.sandbox.paypal.com','paypal.com','sandbox.paypal.com'];
  if(parse_url($url??'',PHP_URL_SCHEME)!=='https'||!in_array($host,$allowed,true))throw new \RuntimeException('Invalid provider redirect');
  return $url;
 }
 public function reconcileFlow(string $token): Booking {
  $b=Booking::where('provider','flow')->where('provider_token',$token)->firstOrFail();
  $data=$this->flow->status($token);
  if((string)($data['commerceOrder']??'')!==$b->id || (string)($data['flowOrder']??'')!==$b->provider_order_id || ($data['currency']??'')!==$b->currency || !is_numeric($data['amount']??null) || (float)($data['amount']??0)!==(float)$b->total_amount)throw new \RuntimeException('Flow payment does not match booking');
  if((int)$data['status']===2)$this->paid($b,'flow-'.$b->provider_order_id);
  elseif(in_array((int)$data['status'],[3,4],true))Booking::whereKey($b->id)->whereNull('paid_at')->update(['status'=>(int)$data['status']===3?'failed':'cancelled']);
  return $b->fresh();
 }
 public function capturePaypal(Booking $b): Booking {
  abort_unless($b->provider==='paypal'&&$b->provider_order_id,422);
  if($b->paid_at)return $b;
  $data=$this->paypal->status($b);
  if(($data['status']??'')!=='COMPLETED')$data=$this->paypal->capture($b);
  return $this->verifyPaypal($b,$data);
 }
 public function reconcilePaypal(Booking $b): Booking {return $this->verifyPaypal($b,$this->paypal->status($b));}
 private function verifyPaypal(Booking $b,array $data): Booking {
  if(($data['status']??'')!=='COMPLETED')return $b->fresh();
  $unit=$data['purchase_units'][0]??[];$capture=$unit['payments']['captures'][0]??[];
  if(($data['id']??'')!==$b->provider_order_id || ($unit['custom_id']??'')!==$b->id || ($capture['status']??'')!=='COMPLETED' || ($capture['amount']['currency_code']??'')!=='USD' || ($capture['amount']['value']??'')!==number_format($b->total_amount/100,2,'.','') || empty($capture['id']))throw new \RuntimeException('PayPal payment does not match booking');
  $this->paid($b,$capture['id']);return $b->fresh();
 }
 private function paid(Booking $booking,string $capture): void {
  DB::transaction(function()use($booking,$capture){
   $b=Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
   if(!$b->paid_at)$b->update(['status'=>'paid','paid_at'=>now(),'capture_id'=>$capture]);
  });
  $this->sendReceipt($booking->fresh());
 }
 public function sendReceipt(Booking $b): void {
  if(!$b->paid_at||$b->receipt_sent_at)return;
  try {
   Mail::to($b->email)->send(new \App\Mail\BookingReceipt($b));
   if(config('services.contact.email'))Mail::to(config('services.contact.email'))->send(new \App\Mail\BookingReceipt($b,true));
   $b->update(['receipt_sent_at'=>now()]);
  }catch(\Throwable $e){report($e);}
 }
}
