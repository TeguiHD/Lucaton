<?php

if (!function_exists('asset_url')) {
    /**
     * Build an absolute asset URL that works whether the application is served from
     * the project root or directly from /public. It also appends a cache-busting
     * query string using ASSET_VERSION when available.
     */
    function asset_url(string $path, bool $withVersion = true): string
    {
        $normalizedPath = ltrim($path, '/');

        if ($normalizedPath === '') {
            return rtrim(APP_URL, '/') . '/';
        }

        if (strpos($normalizedPath, 'assets/') === 0) {
            $normalizedPath = substr($normalizedPath, 7);
        }

        if (strpos($normalizedPath, 'public/assets/') === 0) {
            $normalizedPath = substr($normalizedPath, 14);
        }

        $publicIndex = realpath(ROOT_PATH . '/public/index.php');
        $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $servingFromPublic = $publicIndex !== false
            && $scriptFilename !== ''
            && realpath($scriptFilename) === $publicIndex;

        $baseSegment = $servingFromPublic ? 'assets/' : 'public/assets/';
        $url = rtrim(APP_URL, '/') . '/' . $baseSegment . $normalizedPath;

        if ($withVersion && defined('ASSET_VERSION') && ASSET_VERSION !== '') {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . rawurlencode('v') . '=' . rawurlencode(ASSET_VERSION);
        }

        return $url;
    }
}
