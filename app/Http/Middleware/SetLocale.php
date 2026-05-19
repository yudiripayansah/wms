<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    protected array $supported = ['id', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        // Auth user preference takes priority over session
        if (auth()->check()) {
            $userLocale = auth()->user()->locale;
            if ($userLocale && in_array($userLocale, $this->supported)) {
                $locale = $userLocale;
            }
        }

        // Fall back to session locale
        if (! $locale) {
            $sessionLocale = session('locale');
            if ($sessionLocale && in_array($sessionLocale, $this->supported)) {
                $locale = $sessionLocale;
            }
        }

        if ($locale) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
