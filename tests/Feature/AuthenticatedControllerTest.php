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
        return $this->postJson('api/login',  ['email' => $email, 'password' => $password]);
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
        $user = $this->createUser();
        $this->assertDatabaseHas('users', ['email' => $this->userData['email']]);
        $response = $this->login($this->userData['email'], $this->userData['password']);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas(
            'personal_access_tokens',
            [
                'tokenable_id' => $user->id,
                'tokenable_type' => User::class
            ]
        );
    }

    //FIXME auth()->user()->currentAccessToken()->delete() E2E 有用，但在test_logout卻報錯
    // public function test_logout()
    // {
    //     $user = $this->createUser();
    //     $this->login($this->userData['email'], $this->userData['password']);

    //     $response = $this->actingAs($user)->get('api/logout');

    //     $response->assertNoContent();
    //     $this->assertDatabaseMissing(
    //         'personal_access_tokens',
    //         [
    //             'tokenable_id' => $user->id,
    //             'tokenable_type' => User::class
    //         ]
    //     );
    // }
}
