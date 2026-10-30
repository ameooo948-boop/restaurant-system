<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_a_customer_profile_without_staff_roles(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '01000000000',
            'password' => 'StrongPassword!123',
            'password_confirmation' => 'StrongPassword!123',
        ]);

        $user = User::query()->where('email', 'customer@example.com')->firstOrFail();

        $response->assertRedirect('/account');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $user->id,
            'full_name' => 'Test Customer',
            'phone' => '01000000000',
        ]);
        $this->assertCount(0, $user->roles()->get());
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        User::create([
            'name' => 'Inactive Customer',
            'email' => 'inactive@example.com',
            'password' => 'StrongPassword!123',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'StrongPassword!123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
