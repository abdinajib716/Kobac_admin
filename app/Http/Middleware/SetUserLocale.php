<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    /**
     * Resolve locale from user preference or request hints.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('localization.supported_locales', ['en' => []]));
        $fallback = config('app.fallback_locale', 'en');
        $locale = $fallback;

        $userLocale = $request->user()?->preferred_locale;
        if ($userLocale && in_array($userLocale, $supported, true)) {
            $locale = $userLocale;
        } else {
            $headerLocale = $request->header('X-Locale');
            $queryLocale = $request->query('locale');
            $acceptLocale = $this->extractFromAcceptLanguage($request->header('Accept-Language'));

            foreach ([$queryLocale, $headerLocale, $acceptLocale] as $candidate) {
                if ($candidate && in_array($candidate, $supported, true)) {
                    $locale = $candidate;
                    break;
                }
            }
        }

        app()->setLocale($locale);
        $request->attributes->set('locale', $locale);

        return $next($request);
    }

    private function extractFromAcceptLanguage(?string $header): ?string
    {
        if (!$header) {
            return null;
        }

        $parts = explode(',', $header);
        if (empty($parts)) {
            return null;
        }

        $first = strtolower(trim($parts[0]));
        if ($first === '') {
            return null;
        }

        return substr($first, 0, 2);
    }
}

