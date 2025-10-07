<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeInput
{
    public function handle(Request $request, Closure $next)
    {
        // Only sanitize for JSON/form requests
        if (in_array($request->getMethod(), ['POST','PUT','PATCH']) && $request->isJson() || $request->isMethod('post')) {
            $data = $request->all();
            $sanitized = $this->sanitizeArray($data);
            $request->merge($sanitized);
        }
        return $next($request);
    }

    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                // Only sanitize likely text fields to avoid corrupting structured data
                if ($this->shouldSanitizeKey($key)) {
                    // strip all HTML tags, trim whitespace
                    $clean = strip_tags($value);
                    $data[$key] = trim($clean);
                }
            }
        }
        return $data;
    }

    private function shouldSanitizeKey(string $key): bool
    {
        // keys we sanitize by default
        $targets = ['caption','description','title','name','notes','comment'];
        if (in_array($key, $targets, true)) {
            return true;
        }
        // also sanitize dot-suffixed keys like description.en or caption.ar
        foreach ($targets as $t) {
            if (str_ends_with($key, ".{$t}") || str_ends_with($key, "_{$t}") || str_contains($key, ".{$t}")) {
                return true;
            }
        }
        return false;
    }
}

