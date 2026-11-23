<?php
use App\Http\Controllers\{AuthController,PaymentController};
use App\Livewire\{Catalog,Checkout,Contact};
use App\Livewire\Admin\{Packages,PackageEditor};
use App\Models\{Package,Booking,Inquiry};
use Illuminate\Support\Facades\Route;
Route::get('/',fn()=>view('pages.home',['packages'=>Package::where('published',true)->orderByDesc('featured')->latest()->take(3)->get()]))->name('home');
Route::get('/viajes',Catalog::class)->name('packages');
Route::get('/viajes/{package:slug}',function(Package $package){abort_unless($package->published,404);return view('pages.package',compact('package'));})->name('package');
Route::get('/viajes/{package:slug}/comprar',Checkout::class)->name('checkout');
Route::get('/viaje-a-medida',Contact::class)->name('contact');
Route::view('/nosotros','pages.about')->name('about');
Route::view('/condiciones','pages.terms')->name('terms');
Route::view('/privacidad','pages.privacy')->name('privacy');
Route::middleware('guest')->group(function(){Route::view('/admin/login','auth.login')->name('login');Route::post('/admin/login',[AuthController::class,'login'])->middleware('throttle:10,1');});
Route::post('/admin/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::prefix('admin')->middleware(['auth','admin'])->group(function(){
 Route::get('/',fn()=>view('pages.dashboard',['packageCount'=>Package::count(),'paidCount'=>Booking::where('status','paid')->count(),'inquiryCount'=>Inquiry::count(),'bookings'=>Booking::latest()->take(8)->get()]))->name('admin.dashboard');
 Route::get('/paquetes',Packages::class)->name('admin.packages');
 Route::get('/paquetes/crear',PackageEditor::class)->name('admin.packages.create');
 Route::get('/paquetes/{package:slug}/editar',PackageEditor::class)->name('admin.packages.edit');
 Route::get('/reservas',fn()=>view('pages.bookings',['bookings'=>Booking::latest()->paginate(20)]))->name('admin.bookings');
 Route::get('/consultas',fn()=>view('pages.inquiries',['inquiries'=>Inquiry::latest()->paginate(20)]))->name('admin.inquiries');
});
Route::post('/payments/flow/confirmation',[PaymentController::class,'flowConfirmation'])->middleware('throttle:120,1')->name('flow.confirmation');
Route::match(['get','post'],'/payments/flow/return',[PaymentController::class,'flowReturn'])->middleware('throttle:60,1')->name('flow.return');
Route::get('/payments/paypal/{booking}/return',[PaymentController::class,'paypalReturn'])->middleware('throttle:20,1')->name('paypal.return');
Route::get('/compra/{booking}',[PaymentController::class,'status'])->middleware(['signed','throttle:60,1'])->name('payment.status');
