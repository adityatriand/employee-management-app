<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showLoginForm(Request $request)
    {
        $workspaceSlug = $request->route('workspace');
        
        if ($workspaceSlug) {
            $workspace = Workspace::where('slug', $workspaceSlug)->first();
            if (!$workspace) {
                abort(404, 'Workspace not found');
            }
            return view('auth.login', compact('workspace'));
        }

        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        $workspaceSlug = $request->route('workspace');
        
        if ($workspaceSlug) {
            $workspace = Workspace::where('slug', $workspaceSlug)->first();
            if (!$workspace) {
                return back()->withErrors(['email' => 'Workspace tidak ditemukan']);
            }

            // Check if user exists and belongs to workspace
            $user = \App\Models\User::where('email', $request->email)
                ->where('workspace_id', $workspace->id)
                ->first();

            if ($user && \Hash::check($request->password, $user->password)) {
                Auth::login($user, $request->filled('remember'));
                return redirect()->route('workspace.dashboard', ['workspace' => $workspaceSlug]);
            }

            return back()->withErrors([
                'email' => 'Email atau password salah, atau Anda tidak memiliki akses ke workspace ini.',
            ]);
        }

        // Fallback to default login
        if (method_exists($this, 'hasTooManyLoginAttempts') && $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            $user = Auth::user();
            
            // Check if user has workspace
            if ($user->workspace_id) {
                return redirect()->route('workspace.dashboard', ['workspace' => $user->workspace->slug]);
            }
            
            // Redirect to workspace setup if no workspace
            return redirect()->route('workspace.setup');
        }

        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $workspaceSlug = $request->route('workspace');
        
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($workspaceSlug) {
            return redirect()->route('workspace.login', ['workspace' => $workspaceSlug]);
        }

        return redirect('/');
    }
}

