<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class Booking extends Model {
 use HasUuids;
 protected $guarded=['id'];
 protected $hidden=['provider_token'];
 protected function casts(): array {return ['paid_at'=>'datetime','receipt_sent_at'=>'datetime','total_amount'=>'integer','unit_amount'=>'integer','travelers'=>'integer'];}
 public function package(){return $this->belongsTo(Package::class);}
 public function getDisplayTotalAttribute(): string {return $this->currency==='CLP' ? '$'.number_format($this->total_amount,0,',','.').' CLP' : 'US$'.number_format($this->total_amount/100,2,',','.');}
 public function getStatusLabelAttribute(): string {return match($this->status){'paid'=>'Pagada · pendiente de coordinación','failed'=>'Pago rechazado','cancelled'=>'Pago cancelado',default=>'Pendiente de pago'};}
}
