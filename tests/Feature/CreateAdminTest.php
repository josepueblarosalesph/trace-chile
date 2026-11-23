<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_email_is_trimmed_and_normalized(): void
    {
        $this->artisan('trace:admin')
            ->expectsQuestion('Nombre', ' Ana ')
            ->expectsQuestion('Correo', ' Admin@Example.com ')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'secure-password-123')
            ->assertSuccessful();

        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertSame('Ana', $user->name);
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('secure-password-123', $user->password));
    }

    public function test_invalid_email_can_be_corrected(): void
    {
        $this->artisan('trace:admin')
            ->expectsQuestion('Nombre', 'Ana')
            ->expectsQuestion('Correo', 'correo-invalido')
            ->expectsOutput('El correo no tiene un formato válido. Ejemplo: nombre@dominio.cl')
            ->expectsQuestion('Correo', 'ana@example.com')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'secure-password-123')
            ->assertSuccessful();
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com', 'is_admin' => true]);
    }

    public function test_duplicate_email_is_reported_separately(): void
    {
        User::create(['name' => 'Existing', 'email' => 'used@example.com', 'password' => 'existing-password']);
        $this->artisan('trace:admin')
            ->expectsQuestion('Nombre', 'Ana')
            ->expectsQuestion('Correo', 'used@example.com')
            ->expectsOutput('Ya existe una cuenta con ese correo. Usa otro correo.')
            ->expectsQuestion('Correo', 'ana@example.com')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'secure-password-123')
            ->assertSuccessful();
        $this->assertDatabaseCount('users', 2);
    }
}
