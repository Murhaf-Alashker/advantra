<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class VerifyGoogleRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $referer = $request->headers->get('referer');

        if (!$referer || !str_contains($referer, 'google.com')) {
            throw new \Exception(__('message.invalid_google_redirect'),ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);

        }

        return $next($request);
    }
}
