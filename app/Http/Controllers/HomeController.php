<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();
        
        // Admin dashboard (level 1)
        if ($user->level == 1) {
            return view('admin.dashboard');
        }
        
        // Regular user dashboard (level 0)
        return view('user.dashboard');
    }
}
