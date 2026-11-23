<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
class AuthController {
 public function login(Request $request){
  $data=$request->validate(['email'=>'required|email','password'=>'required|string']);
  $key='admin-login:'.strtolower($data['email']).'|'.$request->ip();
  if(RateLimiter::tooManyAttempts($key,5)) throw ValidationException::withMessages(['email'=>'Demasiados intentos. Intenta nuevamente en un minuto.']);
  if(!Auth::attempt([...$data,'is_admin'=>true])){RateLimiter::hit($key,60);throw ValidationException::withMessages(['email'=>'Correo o contraseña incorrectos.']);}
  RateLimiter::clear($key);$request->session()->regenerate();return redirect()->intended('/admin');
 }
 public function logout(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect('/');}
}
