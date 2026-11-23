<?php
namespace App\Console\Commands;
use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Console\Command;
class ReconcilePayments extends Command {
 protected $signature='trace:reconcile-payments';
 protected $description='Verificar pagos pendientes y reintentar correos de confirmación';
 public function handle(PaymentService $p): int {
  Booking::where('status','pending')->whereNotNull('provider_order_id')->where('created_at','>=',now()->subDays(30))->orderBy('created_at')->chunk(50,function($bookings)use($p){foreach($bookings as $b){try{$b->provider==='flow'?$p->reconcileFlow($b->provider_token):$p->reconcilePaypal($b);}catch(\Throwable $e){report($e);$this->warn('No se pudo verificar '.$b->id);}}});
  Booking::whereNotNull('paid_at')->whereNull('receipt_sent_at')->chunk(50,function($bookings)use($p){foreach($bookings as $b)$p->sendReceipt($b);});
  return self::SUCCESS;
 }
}
