<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ETagMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $content = $response->getContent() ?? '';
            $etag = 'W/"' . sha1($content) . '"';
            $lastModified = gmdate('D, d M Y H:i:s') . ' GMT';

            $response->headers->set('ETag', $etag);
            $response->headers->set('Last-Modified', $lastModified);

            $ifNoneMatch = $request->headers->get('If-None-Match');
            if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
                $response->setNotModified();
            }
        }

        return $response;
    }
}

