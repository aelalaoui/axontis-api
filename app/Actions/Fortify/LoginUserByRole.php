<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LoginResponse;

class LoginUserByRole implements LoginResponse
{
    public function toResponse($request)
    {
        $user = auth()->user();

        if ($user->isClient()) {
            return redirect()->route('client.home');
        }
        if ($user->isTechnician()) {
            return redirect()->route('crm.dashboard');
        }
        if ($user->isOperator()) {
            return redirect()->route('crm.dashboard');
        }
        if ($user->isManager()) {
            return redirect()->route('crm.dashboard');
        }
        if ($user->isAdministrator()) {
            return redirect()->route('home');
        }

        return redirect('/dashboard');
    }
}
