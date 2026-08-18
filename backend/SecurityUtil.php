<?php

class SecurityUtil
{
    /**
     * Send standard security headers
     */
    public static function sendSecurityHeaders()
    {
        if (headers_sent()) {
            return;
        }

        // Prevent clickjacking
        header("X-Frame-Options: SAMEORIGIN");

        // Prevent MIME type sniffing
        header("X-Content-Type-Options: nosniff");

        // Content Security Policy
        // Note: 'unsafe-inline' is currently needed for some styles/scripts. 
        // Ideally should be removed in favor of nonces or separate files.
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://cdn.socket.io https://unpkg.com 'unsafe-inline'; style-src 'self' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://unpkg.com 'unsafe-inline'; font-src https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://cdnjs.cloudflare.com https://cdn.socket.io https://unpkg.com http://localhost:3000 ws://localhost:3000 wss://localhost:3000 https://*.ngrok-free.app;");

        // HSTS (HTTP Strict Transport Security) - Only if HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
    }

    /**
     * MIME types allowlist strict check
     * map extension => mime
     */
    private const ALLOWED_MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
        // Audio
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        // Video
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogv' => 'video/ogg',
        'mov' => 'video/quicktime',
        'avi' => 'video/x-msvideo',
    ];

    /**
     * Validate file extension and MIME type using finfo
     */
    public static function validateFile(string $filePath, string $extension): bool
    {
        $ext = strtolower($extension);
        if (!array_key_exists($ext, self::ALLOWED_MIME_TYPES)) {
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($filePath);

        // Special handling for text files which might be detected as x-empty or other variations
        if ($ext === 'txt' && strpos($realMime, 'text/') === 0) {
            return true;
        }

        return $realMime === self::ALLOWED_MIME_TYPES[$ext];
    }

    /**
     * Strictly sanitize SVG content
     * Removes dangerous tags and attributes based on a deny-list and strict parsing
     */
    public static function sanitizeSVG(string $content): string
    {
        // Suppress XML parsing errors
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        // Safety: prevent external entity loading (XXE) and network access.
        // For PHP < 8.0 we disable the global entity loader; in PHP 8+ this is deprecated
        // and entity loader is effectively disabled by using LIBXML_NONET and not expanding
        // entities (i.e. avoiding LIBXML_NOENT).
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;

        $disabledEntityLoaderPreviousState = null;
        if (PHP_VERSION_ID < 80000 && function_exists('libxml_disable_entity_loader')) {
            $disabledEntityLoaderPreviousState = libxml_disable_entity_loader(true);
        }

        // Load XML safely
        // options: no blanks, no network, compact parsing
        if (!$dom->loadXML($content, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT)) {
            libxml_clear_errors();
            if ($disabledEntityLoaderPreviousState !== null && function_exists('libxml_disable_entity_loader')) {
                libxml_disable_entity_loader($disabledEntityLoaderPreviousState);
            }
            return ''; // Failed to parse
        }

        if ($disabledEntityLoaderPreviousState !== null && function_exists('libxml_disable_entity_loader')) {
            libxml_disable_entity_loader($disabledEntityLoaderPreviousState);
        }

        // Define allow-lists (whitelists)
        $allowedTags = [
            'svg','g','defs','symbol','use','title','desc','metadata','view',
            'rect','circle','ellipse','line','polyline','polygon','path',
            'text','tspan','tref','switch','image','clipPath','mask','pattern',
            'linearGradient','radialGradient','stop','filter'
        ];

        $allowedAttributes = [
            // Common geometry / presentation
            'id','class','width','height','x','y','cx','cy','r','rx','ry','d',
            'x1','y1','x2','y2','points','viewbox','viewBox','preserveAspectRatio',
            'transform','fill','stroke','stroke-width','opacity','fill-opacity','stroke-opacity',
            'offset','stop-color','stop-opacity','gradientunits','gradientTransform',
            // Namespaces
            'xmlns','xmlns:xlink',
            // Linking
            'href','xlink:href'
        ];

        self::cleanNode($dom->documentElement, $allowedTags, $allowedAttributes);

        return $dom->saveXML();
    }

    private static function cleanNode(DOMNode $node, array $allowedTags, array $allowedAttributes)
    {
        // If this is an element, enforce allowed tag list
        if ($node instanceof DOMElement) {
            $tag = strtolower($node->nodeName);

            if (!in_array($tag, $allowedTags, true)) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
                return;
            }

            // Clean attributes by allow-list
            if ($node->hasAttributes()) {
                $attrsToRemove = [];
                foreach ($node->attributes as $attr) {
                    $name = strtolower($attr->nodeName);

                    // Disallow event handlers and style attribute
                    if (strpos($name, 'on') === 0 || $name === 'style') {
                        $attrsToRemove[] = $attr->nodeName;
                        continue;
                    }

                    // Only keep attributes that are in allowedAttributes
                    if (!in_array($name, array_map('strtolower', $allowedAttributes), true)) {
                        $attrsToRemove[] = $attr->nodeName;
                        continue;
                    }

                    // Disallow dangerous protocols in attribute values
                    if (preg_match('/^\s*(javascript|vbscript):/i', $attr->nodeValue)) {
                        $attrsToRemove[] = $attr->nodeName;
                        continue;
                    }

                    // For href/xlink:href allow only internal fragment references (starting with '#') or empty
                    if ($name === 'href' || $name === 'xlink:href') {
                        $val = trim($attr->nodeValue);
                        if ($val === '' || strpos($val, '#') === 0) {
                            // allowed
                        } else {
                            // reject any urls with scheme (contains ':') or absolute http(s)
                            if (preg_match('/^[a-z0-9+.-]+:/i', $val) || preg_match('#^https?://#i', $val)) {
                                $attrsToRemove[] = $attr->nodeName;
                                continue;
                            }
                            // If it's a relative path without scheme, be conservative and remove
                            $attrsToRemove[] = $attr->nodeName;
                            continue;
                        }
                    }
                }

                foreach ($attrsToRemove as $name) {
                    $node->removeAttribute($name);
                }
            }
        }

        // Recursively clean children
        if ($node->hasChildNodes()) {
            // Convert to array to avoid modification during iteration issues
            $children = iterator_to_array($node->childNodes);
            foreach ($children as $child) {
                self::cleanNode($child, $allowedTags, $allowedAttributes);
            }
        }
    }

    /**
     * Convert SVG to PNG using Imagick securely
     */
    public static function convertSvgToPng(string $svgPath, string $pngPath): bool
    {
        if (!class_exists('\Imagick')) {
            error_log("Imagick extension not installed. Cannot convert SVG.");
            return false;
        }

        try {
            // Use dynamic instantiation to avoid IDE lint errors when extension is missing
            $imagickClass = '\Imagick';
            $imagick = new $imagickClass();

            // Security configuration
            // Note: Constants might be flagged if extension is missing in IDE, but exist at runtime.
            if (defined('\Imagick::RESOURCETYPE_MEMORY')) {
                $imagick->setResourceLimit(constant('\Imagick::RESOURCETYPE_MEMORY'), 256 * 1024 * 1024); // 256MB
            }
            if (defined('\Imagick::RESOURCETYPE_MAP')) {
                $imagick->setResourceLimit(constant('\Imagick::RESOURCETYPE_MAP'), 256 * 1024 * 1024); // 256MB
            }
            if (defined('\Imagick::RESOURCETYPE_THREAD')) {
                $imagick->setResourceLimit(constant('\Imagick::RESOURCETYPE_THREAD'), 1); // Single thread
            }

            // Disable external resources
            try {
                $imagick->setOption('svg:external-resources', 'false');
            } catch (\Exception $e) {
                // Option might not be supported on all versions
            }

            $pixelClass = '\ImagickPixel';
            $imagick->setBackgroundColor(new $pixelClass('transparent'));
            $imagick->readImage($svgPath);
            $imagick->setImageFormat('png');

            $geo = $imagick->getImageGeometry();
            if ($geo['width'] > 4096 || $geo['height'] > 4096) {
                $imagick->scaleImage(4096, 4096, true);
            }

            $imagick->writeImage($pngPath);
            $imagick->clear();
            $imagick->destroy();
            return true;
        } catch (\Exception $e) {
            error_log("SVG Conversion Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a cryptographically secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Encrypt data using AES-256-CBC
     */
    private static function getEncryptionKey(): string
    {
        $key = getenv('ENCRYPTION_KEY');
        if (!$key) {
            throw new \RuntimeException(
                "SecurityUtil: ENCRYPTION_KEY is not set in environment variables. " .
                    "Set this variable before using encryption features."
            );
        }
        return $key;
    }

    public static function encrypt(string $data): string
    {
        $key = hash('sha256', self::getEncryptionKey(), true);
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    public static function decrypt(string $data): ?string
    {
        $key = hash('sha256', self::getEncryptionKey(), true);
        $decoded = base64_decode($data);
        if (!$decoded || strpos($decoded, '::') === false) return null;

        list($iv, $encrypted) = explode('::', $decoded, 2);
        if (strlen($iv) !== openssl_cipher_iv_length('aes-256-cbc')) return null;

        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted ?: null;
    }

    public static function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
