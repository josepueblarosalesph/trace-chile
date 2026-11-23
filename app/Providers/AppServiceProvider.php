<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
class AppServiceProvider extends ServiceProvider
{
 public function register(): void {}
 public function boot(): void {
  Livewire::addPersistentMiddleware([\App\Http\Middleware\AdminOnly::class]);
 }
}
