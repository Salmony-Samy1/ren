<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Only process JSON API responses
        $contentType = $response->headers->get('Content-Type', '');
        if (stripos($contentType, 'application/json') === false) {
            return $response;
        }

        // Compute ETag from response content (weak etag acceptable for JSON bodies)
        $content = $response->getContent() ?? '';
        $etag = 'W/"' . sha1($content) . '"';

        // Attempt to infer Last-Modified from payload updated_at/version if present
        $lastModified = null;
        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            // Support both envelope {data: ...} and plain resource/collection
            $payload = $data['data'] ?? $data;

            $timestamps = [];
            $collectUpdatedAt = function ($item) use (&$timestamps) {
                if (is_array($item)) {
                    if (!empty($item['updated_at'])) {
                        $timestamps[] = strtotime((string)$item['updated_at']) ?: null;
                    } elseif (!empty($item['version'])) {
                        // If version is numeric timestamp
                        $asInt = is_numeric($item['version']) ? (int)$item['version'] : null;
                        if ($asInt) { $timestamps[] = $asInt; }
                    }
                }
            };

            if (is_array($payload)) {
                // If collection
                $isAssoc = array_keys($payload) !== range(0, count($payload) - 1);
                if ($isAssoc) {
                    $collectUpdatedAt($payload);
                } else {
                    foreach ($payload as $row) { $collectUpdatedAt($row); }
                }
            }

            $timestamps = array_filter($timestamps);
            if (!empty($timestamps)) {
                $lastModified = gmdate('D, d M Y H:i:s', max($timestamps)) . ' GMT';
            }
        } catch (\Throwable $e) {
            // Ignore JSON parse errors; proceed without Last-Modified
        }

        // Conditional requests handling
        $ifNoneMatch = $request->headers->get('If-None-Match');
        $ifModifiedSince = $request->headers->get('If-Modified-Since');

        // Set headers
        $response->headers->set('ETag', $etag);
        if ($lastModified) {
            $response->headers->set('Last-Modified', $lastModified);
        }
        $response->headers->set('Cache-Control', $response->headers->get('Cache-Control', 'private, must-revalidate'));

        // Evaluate 304 conditions
        $etagMatches = $ifNoneMatch && trim($ifNoneMatch) === $etag;
        $notModified = false;
        if ($etagMatches) {
            $notModified = true;
        } elseif ($ifModifiedSince && $lastModified) {
            $reqTime = strtotime($ifModifiedSince);
            $resTime = strtotime($lastModified);
            if ($reqTime !== false && $resTime !== false && $reqTime >= $resTime) {
                $notModified = true;
            }
        }

        if ($notModified) {
            $response->setStatusCode(304);
            $response->setContent(null);
            // Remove body-specific headers
            $response->headers->remove('Content-Length');
        }

        return $response;
    }
}

