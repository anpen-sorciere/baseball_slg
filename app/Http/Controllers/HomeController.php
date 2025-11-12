<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // ログインしている場合はメインメニューへ
        if (auth()->check()) {
            return redirect()->route('home');
        }
        
        // ログインしていない場合はタイトル画面
        return view('title');
    }

    public function home()
    {
        // ログイン必須
        if (!auth()->check()) {
            return redirect()->route('title');
        }

        return view('home');
    }
}

