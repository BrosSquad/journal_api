<?php

namespace App\Services;

use App\DTO\LoginDTO;
use App\Exceptions\UserNotVerifiedException;
use App\Models\User;

class LoginService
{
    /**
     * @throws \App\Exceptions\UserNotVerifiedException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     *
     * @return \App\Models\User | null
     */
    public function login(LoginDTO $loginDTO)
    {
        $user = User::query()->where('email', '=', $loginDTO->email)->firstOrFail();

        if (!$user->verified) {
            throw new UserNotVerifiedException();
        }

        if (!password_verify($loginDTO->password, $user->makeVisible('password')->password)) {
            return null;
        }

        return $user->makeHidden('password');
    }
}