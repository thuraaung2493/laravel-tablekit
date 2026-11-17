<?php

namespace TableKitDemo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends \Illuminate\Routing\Controller
{
    public function create()
    {
        return view('login');
    }

    public function store(Request $request)
    {
        Auth::loginUsingId(1);
        $request->session()->regenerate();

        return redirect()->route('clients.index');
    }
}
