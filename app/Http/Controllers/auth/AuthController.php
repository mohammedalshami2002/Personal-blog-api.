<?php

namespace App\Http\Controllers\auth\AuthController;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        return User::registerUser($request);
    }

    public function login(Request $request)
    {
        return User::loginUser($request);
    }

    public function logout()
    {
        return User::logoutUser();
    }

    public function verifyEmail(Request $request)
    {
        return User::verifyUserEmail($request);
    }

    public function forgotPassword(Request $request)
    {
        return User::forgotPasswordUser($request);
    }

    public function verifyResetCode(Request $request)
    {
        return User::verifyResetCodeUser($request);
    }

    public function resetPassword(Request $request)
    {
        return User::resetPasswordUser($request);
    }
}
