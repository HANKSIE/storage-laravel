<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthenticatedControllerTest extends TestCase
{
    use RefreshDatabase;

    private $userData = [
        'name' => 'faker',
        'email' => 'iamfaker@gmail.com',
        'password' => 'iamfaker'
    ];

    /**
     * post api/login
     *
     * @return \Illuminate\Testing\TestResponse
     */
    private function login($email, $password)
    {
        return $this->postJson('login',  ['email' => $email, 'password' => $password]);
    }

    private function createUser()
    {
        return User::factory()->create(
            array_merge(
                $this->userData,
                [
                    'password' => Hash::make($this->userData['password'])
                ]
            )
        );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_login_success()
    {
        $this->createUser();
        $this->assertDatabaseHas('users', ['email' => $this->userData['email']]);
        $response = $this->login($this->userData['email'], $this->userData['password']);

        $response->assertOk();
        $response->assertJsonStructure(['token']);
    }

    public function test_logout()
    {
        $response = $this->actingAs($this->createUser())->get('logout');
        $response->assertNoContent();
    }
}
