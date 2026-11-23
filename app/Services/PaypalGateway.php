<?php
namespace App\Services;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
class PaypalGateway {
 private function client(){
  $base=rtrim(config('services.paypal.url'),'/');
  $token=Http::asForm()->withBasicAuth(config('services.paypal.client_id'),config('services.paypal.secret'))->timeout(25)->post($base.'/v1/oauth2/token',['grant_type'=>'client_credentials'])->throw()->json('access_token');
  return Http::withToken($token)->acceptJson()->timeout(25)->connectTimeout(10)->baseUrl($base);
 }
 public function create(Booking $b): array {return $this->client()->withHeaders(['PayPal-Request-Id'=>$b->id])->post('/v2/checkout/orders',[
  'intent'=>'CAPTURE','purchase_units'=>[['reference_id'=>$b->id,'custom_id'=>$b->id,'description'=>mb_substr($b->package_title,0,127),'amount'=>['currency_code'=>'USD','value'=>number_format($b->total_amount/100,2,'.','')]]],
  'payment_source'=>['paypal'=>['experience_context'=>['brand_name'=>'Trace Chile Expeditions','user_action'=>'PAY_NOW','return_url'=>URL::temporarySignedRoute('paypal.return',now()->addHours(24),['booking'=>$b->id]),'cancel_url'=>URL::temporarySignedRoute('payment.status',now()->addDays(7),['booking'=>$b->id])]]],
 ])->throw()->json();}
 public function capture(Booking $b): array {return $this->client()->withHeaders(['PayPal-Request-Id'=>'capture-'.$b->id])->withBody('{}','application/json')->post('/v2/checkout/orders/'.rawurlencode($b->provider_order_id).'/capture')->throw()->json();}
 public function status(Booking $b): array {return $this->client()->get('/v2/checkout/orders/'.rawurlencode($b->provider_order_id))->throw()->json();}
}
