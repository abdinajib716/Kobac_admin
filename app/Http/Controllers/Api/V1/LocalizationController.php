<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocalizationController extends BaseController
{
    /**
     * List supported languages for Flutter language picker.
     * GET /api/v1/localization/languages
     */
    public function languages(): JsonResponse
    {
        $supported = config('localization.supported_locales', []);
        $current = app()->getLocale();

        $data = collect($supported)->map(function (array $meta, string $code) use ($current) {
            return [
                'code' => $code,
                'name' => $meta['name'] ?? strtoupper($code),
                'native_name' => $meta['native_name'] ?? strtoupper($code),
                'rtl' => (bool) ($meta['rtl'] ?? false),
                'is_current' => $current === $code,
            ];
        })->values();

        return $this->success([
            'current_locale' => $current,
            'fallback_locale' => config('app.fallback_locale', 'en'),
            'languages' => $data,
        ]);
    }

    /**
     * Fetch translation payload for mobile app runtime.
     * GET /api/v1/localization/translations?locale=so&namespace=mobile
     */
    public function translations(Request $request): JsonResponse
    {
        $supported = array_keys(config('localization.supported_locales', []));

        $validator = Validator::make($request->all(), [
            'locale' => 'nullable|string|in:' . implode(',', $supported),
            'namespace' => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            return $this->error(__('mobile.errors.validation_failed'), 'VALIDATION_ERROR', 422, [
                'errors' => $validator->errors(),
            ]);
        }

        $locale = $request->get('locale', app()->getLocale());
        $namespace = $request->get('namespace', config('localization.default_namespace', 'mobile'));

        $fallbackLocale = config('app.fallback_locale', 'en');
        $fallbackTranslations = $this->loadTranslationFile($fallbackLocale, $namespace);
        $localeTranslations = $this->loadTranslationFile($locale, $namespace);
        $mergedTranslations = array_replace_recursive($fallbackTranslations, $localeTranslations);

        return $this->success([
            'locale' => $locale,
            'namespace' => $namespace,
            'fallback_locale' => $fallbackLocale,
            'version' => md5(json_encode($mergedTranslations)),
            'translations' => $mergedTranslations,
        ]);
    }

    private function loadTranslationFile(string $locale, string $namespace): array
    {
        $path = lang_path($locale . DIRECTORY_SEPARATOR . $namespace . '.php');

        if (!file_exists($path)) {
            return [];
        }

        $translations = require $path;

        return is_array($translations) ? $translations : [];
    }
}

