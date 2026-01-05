<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeOneTimePassword;
use App\Mail\PasswordResetNotification;
use App\Models\Role;
use App\Models\User;
use App\Models\Company;

class SuperUserUserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure migrations have been applied to the in-memory sqlite DB before creating roles
        $this->artisan('migrate');

        // Ensure roles exist for tests
        Role::firstOrCreate(['name' => 'Superuser']);
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Cashier']);
    }

    public function test_superuser_can_create_user_and_email_sent()
    {
        Mail::fake();

        $superRole = Role::where('name', 'Superuser')->first();
        $adminRole = Role::where('name', 'Admin')->first();

        // create superuser and authenticate via sanctum
        $super = User::factory()->create(['role_id' => $superRole->id, 'verified' => true]);
        $this->actingAs($super, 'sanctum');

        $payload = [
            'name' => 'New User',
            'email' => 'newuser@example.test',
            'password' => 'Password123!',
            'role_id' => $adminRole->id,
            'company' => [
                'name' => 'NewCo',
                'email' => 'contact@newco.test',
                'phone' => '+1000000',
                'category' => 'Retail',
                'address' => '123 Street'
            ]
        ];

        $res = $this->postJson('/api/super/users', $payload);
        $res->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.test']);

        $created = User::where('email', 'newuser@example.test')->first();
        Mail::assertSent(WelcomeOneTimePassword::class, function ($m) use ($created) {
            return $m->hasTo($created->email);
        });
    }

    public function test_superuser_reset_password_sends_email()
    {
        Mail::fake();

        $superRole = Role::where('name', 'Superuser')->first();
        $cashierRole = Role::where('name', 'Cashier')->first();

        $super = User::factory()->create(['role_id' => $superRole->id, 'verified' => true]);
        $this->actingAs($super, 'sanctum');

        $target = User::factory()->create(['email' => 'target@example.test', 'role_id' => $cashierRole->id]);

        $res = $this->postJson("/api/super/users/{$target->id}/reset-password");
        $res->assertStatus(200)->assertJsonStructure(['temp_password']);

        Mail::assertSent(PasswordResetNotification::class, function ($m) use ($target) {
            return $m->hasTo($target->email);
        });
    }
}
