<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class SessionController extends Controller
{
    public function login(Request $request)
    {
        $token = $request->input('token');        
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'No token provided'], 400);
        }
        
        try {
            $user = JWTAuth::setToken($token)->authenticate();
            
            if ($user && $user->type === 'admin') {
                // حفظ المستخدم في الجلسة مباشرة مع تحسينات
                $request->session()->put('admin_user', $user);
                $request->session()->put('admin_authenticated', true);
                $request->session()->put('admin_login_time', now());
                
                // تسجيل دخول المستخدم في نظام المصادقة العادي
                Auth::login($user);
                
                // تحسين: إعادة استخدام الجلسة الحالية بدلاً من إنشاء جديدة
                $request->session()->regenerateToken();
                
                // إضافة logging فقط عند النجاح لتقليل الضغط
                \Log::info('Admin Login Success', [
                    'user_id' => $user->id,
                    'session_id' => $request->session()->getId()
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'type' => $user->type
                    ]
                ]);
            }
            
            return response()->json(['success' => false, 'message' => 'Invalid user'], 401);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Token validation failed'], 401);
        }
    }
}
