<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'trace:admin {--email= : Correo del administrador, sin captura interactiva}';
    protected $description = 'Crear una cuenta administradora de Trace Chile';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Nombre'));

        do {
            $input = $this->option('email') ?? $this->ask('Correo');
            $email = mb_strtolower(trim((string) $input));
            $validation = Validator::make(['email' => $email], [
                'email' => 'required|email|max:255|unique:users,email',
            ], [
                'email.required' => 'Ingresa un correo electrónico.',
                'email.email' => 'El correo no tiene un formato válido. Ejemplo: nombre@dominio.cl',
                'email.max' => 'El correo no puede superar los 255 caracteres.',
                'email.unique' => 'Ya existe una cuenta con ese correo. Usa otro correo.',
            ]);
            foreach ($validation->errors()->all() as $error) {
                $this->error($error);
            }
            if ($validation->fails()) {
                if ($this->option('email') !== null) {
                    return self::FAILURE;
                }
                continue;
            }
            if ($this->option('email') !== null || $this->confirm('¿Es correcto este correo: '.$email.'?', false)) {
                break;
            }
        } while (true);

        $password = $this->secret('Contraseña (mínimo 12 caracteres)');
        $validation = Validator::make(compact('name', 'password'), [
            'name' => 'required|max:100',
            'password' => ['required', Password::min(12)],
        ]);
        if ($validation->fails()) {
            foreach ($validation->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        User::create(['name' => $name, 'email' => $email, 'password' => $password, 'is_admin' => true]);
        $this->info('Cuenta creada. Accede a /admin.');
        return self::SUCCESS;
    }
}
