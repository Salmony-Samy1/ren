<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Display the admin login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming admin authentication request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
    
        $user = User::where('email', $credentials['email'])
                      ->where('type', 'admin')
                      ->first();
    
        if ($user && Hash::check($credentials['password'], $user->password)) {
            // --------------------------------------------------------
            // الحل الجذري: استخدام دالة تسجيل الدخول القياسية
            // --------------------------------------------------------
            Auth::guard('web')->login($user); 
            
            // تسجيل وقت تسجيل الدخول للتحقق من انتهاء الصلاحية
            $request->session()->put('admin_login_time', now());
            
            $request->session()->regenerate();
    
            return redirect()->intended('/admin/dashboard');
        }
    
        return back()->withErrors([
            'email' => 'بيانات تسجيل الدخول غير صحيحة.',
        ])->onlyInput('email');
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}