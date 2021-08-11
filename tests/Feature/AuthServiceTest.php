<?php

namespace Tests\Feature;

use App\Helpers\UrlHelper;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Support\Str;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;
    private $AuthService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->AuthService = $this->app->make(AuthService::class);
    }

    public function test_register()
    {
        $userData = [
            'name' => 'someone',
            'password' => 'password',
            'email' => 'someone@gmail.com'
        ];
        $user = $this->AuthService->register($userData);

        $this->assertDatabaseHas('users', ['email' => $userData['email']]);
    }
}
