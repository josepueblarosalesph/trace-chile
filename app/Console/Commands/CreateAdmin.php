<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
class CreateAdmin extends Command {
 protected $signature='trace:admin';
 protected $description='Crear una cuenta administradora de Trace Chile';
 public function handle(): int {
  $name=$this->ask('Nombre');$email=$this->ask('Correo');$password=$this->secret('Contraseña (mínimo 12 caracteres)');
  $v=Validator::make(compact('name','email','password'),['name'=>'required|max:100','email'=>'required|email|unique:users,email','password'=>['required',Password::min(12)]]);
  if($v->fails()){foreach($v->errors()->all() as $error)$this->error($error);return self::FAILURE;}
  User::create(['name'=>$name,'email'=>$email,'password'=>$password,'is_admin'=>true]);$this->info('Cuenta creada. Accede a /admin.');return self::SUCCESS;
 }
}
