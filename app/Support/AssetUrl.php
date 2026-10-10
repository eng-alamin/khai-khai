<?php

namespace App\Support;

/**
 * The website prints image columns straight into <img src="...">, so a
 * column may hold a full URL or a site-relative path. A mobile app has no
 * "current site", so every image must leave the API as an absolute URL.
 */
class AssetUrl
{
    public static function make(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return url('/'.ltrim($path, '/'));
    }
}
