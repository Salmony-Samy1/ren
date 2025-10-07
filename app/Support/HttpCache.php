<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpCache
{
    public static function makeEtag(array $parts): string
    {
        return sha1(implode('|', array_map(fn($p) => is_scalar($p) ? (string)$p : json_encode($p), $parts)));
    }

    /**
     * Return 304 Not Modified response if client's validators match.
     */
    public static function preconditionCheck(Request $request, ?string $etag, ?Carbon $lastModified, string $visibility = 'private', ?int $sMaxAge = null, int $maxAge = 60): ?Response
    {
        $ifNoneMatch = $request->headers->get('If-None-Match');
        if ($etag && $ifNoneMatch) {
            $clientEtag = trim($ifNoneMatch, '"');
            if ($clientEtag === $etag) {
                $resp = response('')->setStatusCode(304)
                    ->header('ETag', '"'.$etag.'"')
                    ->when($lastModified, function ($resp) use ($lastModified) {
                        $resp->header('Last-Modified', $lastModified->toRfc7231String());
                    });
                $cacheControl = $visibility . ', max-age='.$maxAge.', must-revalidate';
                if ($sMaxAge !== null) { $cacheControl .= ', s-maxage='.$sMaxAge; }
                $resp->header('Cache-Control', $cacheControl);
                return $resp;
            }
        }

        $ifModifiedSince = $request->headers->get('If-Modified-Since');
        if ($lastModified && $ifModifiedSince) {
            try {
                $ims = Carbon::parse($ifModifiedSince);
                if ($lastModified->lessThanOrEqualTo($ims)) {
                    $resp = response('')->setStatusCode(304)
                        ->when($etag, function ($resp) use ($etag) { $resp->header('ETag', '"'.$etag.'"'); })
                        ->header('Last-Modified', $lastModified->toRfc7231String());
                    $cacheControl = $visibility . ', max-age='.$maxAge.', must-revalidate';
                    if ($sMaxAge !== null) { $cacheControl .= ', s-maxage='.$sMaxAge; }
                    $resp->header('Cache-Control', $cacheControl);
                    return $resp;
                }
            } catch (\Throwable $e) {
                // ignore parse errors
            }
        }

        return null;
    }

    public static function withValidators(Response $response, ?string $etag, ?Carbon $lastModified, int $maxAge = 60, string $visibility = 'private', ?int $sMaxAge = null): Response
    {
        if ($etag) {
            $response->header('ETag', '"'.$etag.'"');
        }
        if ($lastModified) {
            $response->header('Last-Modified', $lastModified->toRfc7231String());
        }
        $cacheControl = $visibility . ', max-age='.$maxAge.', must-revalidate';
        if ($sMaxAge !== null) { $cacheControl .= ', s-maxage='.$sMaxAge; }
        $response->header('Cache-Control', $cacheControl);
        return $response;
    }
}

