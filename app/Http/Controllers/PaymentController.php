<?php
namespace App\Http\Controllers;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
class PaymentController {
 public function flowConfirmation(Request $r,PaymentService $p){$data=$r->validate(['token'=>'required|string|max:200']);$p->reconcileFlow($data['token']);return response('OK');}
 public function flowReturn(Request $r,PaymentService $p){
  $data=$r->validate(['token'=>'required|string|max:200']);
  $b=Booking::where('provider','flow')->where('provider_token',$data['token'])->firstOrFail();
  try{$p->reconcileFlow($data['token']);}catch(\Throwable $e){report($e);}
  return redirect(URL::temporarySignedRoute('payment.status',now()->addDays(7),['booking'=>$b->id]));
 }
 public function paypalReturn(Request $r,Booking $booking,PaymentService $p){
  abort_unless($r->hasValidSignatureWhileIgnoring(['token','PayerID']),403);
  abort_unless($booking->provider==='paypal'&&hash_equals($booking->provider_order_id??'',(string)$r->query('token')),403);
  try{$p->capturePaypal($booking);}catch(\Throwable $e){report($e);return redirect(URL::temporarySignedRoute('payment.status',now()->addDays(7),['booking'=>$booking->id]))->with('payment_notice','Estamos verificando el pago. No vuelvas a pagar mientras confirmamos el resultado.');}
  return redirect(URL::temporarySignedRoute('payment.status',now()->addDays(7),['booking'=>$booking->id]));
 }
 public function status(Booking $booking){return response()->view('payments.status',compact('booking'))->header('Cache-Control','private, no-store')->header('Referrer-Policy','no-referrer');}
}
