<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class Package extends Model {
 protected $guarded=['id'];
 protected function casts(): array {return ['gallery'=>'array','itinerary'=>'array','published'=>'boolean','featured'=>'boolean','price_clp'=>'integer','price_usd_cents'=>'integer'];}
 public function getRouteKeyName(): string {return 'slug';}
 public function imageUrl(?string $path=null): string {
  $path ??= $this->cover;
  if (!$path) return asset('images/landscape.svg');
  if (str_starts_with($path,'https://')) return $path;
  return Storage::disk('public')->url($path);
 }
 public function getDisplayPriceAttribute(): string {
  return $this->price_clp ? '$'.number_format($this->price_clp,0,',','.').' CLP' : ($this->price_usd_cents ? 'US$'.number_format($this->price_usd_cents/100,2,',','.'): 'Consultar');
 }
}
