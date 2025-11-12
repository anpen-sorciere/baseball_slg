<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * ユーザー登録フォームを表示
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * 新規ユーザー登録処理
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user', // 新規登録時は一般ユーザーとして設定
        ]);

        // 自動的にログイン
        Auth::login($user);

        return redirect()->route('home')->with('success', 'アカウント登録が完了しました。');
    }
}

