<?php
namespace App\Livewire;
use App\Models\Inquiry;
use Livewire\Component;
class Contact extends Component {
 public string $name='',$email='',$destination='',$message='',$website='';
 public bool $sent=false;
 public function submit(){
  $rateKey='inquiry:'.request()->ip();
  if(\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateKey,5)){$this->addError('message','Demasiados intentos. Espera un minuto antes de continuar.');return;}
  \Illuminate\Support\Facades\RateLimiter::hit($rateKey,60);
  $data=$this->validate(['name'=>'required|string|max:100','email'=>'required|email|max:200','destination'=>'nullable|string|max:120','message'=>'required|string|min:15|max:5000','website'=>'max:0']);
  unset($data['website']);Inquiry::create($data);$this->reset('name','email','destination','message');$this->sent=true;
 }
 public function render(){return view('livewire.contact')->layout('components.layouts.app',['title'=>'Diseñemos tu viaje']);}
}
