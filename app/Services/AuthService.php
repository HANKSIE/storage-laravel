<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * @param array $data
     * @param \Illuminate\Http\UploadedFile|\Illuminate\Http\UploadedFile[]|array|null $avatar
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function register($data, $avatar = null)
    {
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        //save avatar

        return $user;
    }
}
