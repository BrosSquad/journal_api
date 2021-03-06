<?php

namespace App\Services;

use App\DTO\User\LoginDTO;
use App\Exceptions\UserNotVerifiedException;
use App\Models\User;

class LoginService
{
    /**
     * Logs the user in. Returns an instance of the User model on success or null on failure.
     *
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

        if (!password_verify($loginDTO->password, $user->password)) {
            return null;
        }

        return $user;
    }
}
