<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language',config('app.locale'));
        $supportedLocales = ['en', 'ar'];
        if (!in_array($locale, $supportedLocales)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        return $next($request);
    }
}
