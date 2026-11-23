<?php
namespace App\Livewire;
use App\Models\{Booking,Package};
use App\Services\PaymentService;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Locked;
use Livewire\Component;
class Checkout extends Component {
 #[Locked] public int $packageId;
 public string $name='',$email='',$phone='',$notes='',$provider='flow';
 public int $travelers=1;
 public bool $acceptTerms=false;
 public function mount(Package $package){abort_unless($package->published,404);$this->packageId=$package->id;if(!$package->price_clp)$this->provider='paypal';}
 public function submit(PaymentService $payments){
  $rateKey='checkout:'.request()->ip();
  if(\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateKey,10)){$this->addError('payment','Demasiados intentos. Espera un minuto antes de continuar.');return;}
  \Illuminate\Support\Facades\RateLimiter::hit($rateKey,60);
  $this->validate(['name'=>'required|string|max:100','email'=>'required|email|max:200','phone'=>'required|string|max:40','notes'=>'nullable|string|max:3000','travelers'=>'required|integer|min:1|max:20','provider'=>'required|in:flow,paypal','acceptTerms'=>'accepted']);
  $package=Package::where('published',true)->findOrFail($this->packageId);
  $unit=$this->provider==='flow'?$package->price_clp:$package->price_usd_cents;
  if(!$unit || !$payments->configured($this->provider)){$this->addError('payment','Este medio de pago todavía no está disponible. Contáctanos para coordinar tu compra.');return;}
  $booking=Booking::create(['package_id'=>$package->id,'package_title'=>$package->title,'name'=>$this->name,'email'=>$this->email,'phone'=>$this->phone,'notes'=>$this->notes,'travelers'=>$this->travelers,'provider'=>$this->provider,'currency'=>$this->provider==='flow'?'CLP':'USD','unit_amount'=>$unit,'total_amount'=>$unit*$this->travelers]);
  try{$url=$payments->start($booking);}catch(\Throwable $e){report($e);$booking->update(['status'=>'failed']);$this->addError('payment','No pudimos iniciar el pago. No se confirmó ningún cobro. Inténtalo nuevamente o contáctanos.');return;}
  return redirect()->away($url);
 }
 public function render(){return view('livewire.checkout',['package'=>Package::findOrFail($this->packageId)])->layout('components.layouts.app',['title'=>'Compra tu experiencia']);}
}
