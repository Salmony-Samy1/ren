<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CSPMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // تطبيق CSP فقط على صفحات الدفع
        if ($this->isPaymentPage($request)) {
            $cspHeader = $this->buildCSPHeader();
            $response->headers->set('Content-Security-Policy', $cspHeader);
            
            // إضافة headers أمنية إضافية
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
        
        return $response;
    }
    
    /**
     * تحديد ما إذا كانت الصفحة صفحة دفع
     */
    private function isPaymentPage(Request $request): bool
    {
        $paymentPaths = [
            '/payment',
            '/checkout',
            '/billing',
            '/pay',
            '/api/app/payments',
            '/api/app/saved-cards'
        ];
        
        $path = $request->path();
        
        foreach ($paymentPaths as $paymentPath) {
            if (str_starts_with($path, ltrim($paymentPath, '/'))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * بناء CSP header
     */
    private function buildCSPHeader(): string
    {
        $policies = [
            // Default source - السماح بالمصادر الأساسية فقط
            "default-src 'self'",
            
            // Scripts - السماح بـ Tap SDK و Google Pay SDK
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
            "https://cdn.gotap.company " .
            "https://pay.google.com " .
            "https://www.google.com " .
            "https://www.gstatic.com",
            
            // Styles - السماح بالأنماط المدمجة والموارد الخارجية الآمنة
            "style-src 'self' 'unsafe-inline' " .
            "https://fonts.googleapis.com " .
            "https://cdnjs.cloudflare.com",
            
            // Fonts - السماح بخطوط Google
            "font-src 'self' " .
            "https://fonts.gstatic.com " .
            "https://cdnjs.cloudflare.com",
            
            // Images - السماح بالصور من المصادر الآمنة
            "img-src 'self' data: blob: " .
            "https://cdn.gotap.company " .
            "https://www.google.com " .
            "https://www.gstatic.com",
            
            // Connect - السماح بالاتصالات مع Tap و Google Pay
            "connect-src 'self' " .
            "https://api.tap.company " .
            "https://pay.google.com " .
            "https://www.google.com " .
            "https://apple-pay-gateway.apple.com " .
            "https://apple-pay-gateway-cert.apple.com",
            
            // Frame - السماح بـ iframes من Tap فقط
            "frame-src 'self' " .
            "https://cdn.gotap.company " .
            "https://pay.google.com",
            
            // Object - منع جميع الكائنات المدمجة
            "object-src 'none'",
            
            // Base - منع تغيير base URL
            "base-uri 'self'",
            
            // Form action - السماح بإرسال النماذج إلى Tap فقط
            "form-action 'self' " .
            "https://api.tap.company",
            
            // Upgrade insecure requests - ترقية الطلبات غير الآمنة
            "upgrade-insecure-requests",
            
            // Block mixed content - منع المحتوى المختلط
            "block-all-mixed-content"
        ];
        
        return implode('; ', $policies);
    }
}