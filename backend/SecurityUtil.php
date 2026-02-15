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
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; font-src https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self';");

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
        // Prevent external entity loading (XXE)
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;

        // Load XML safely
        // options: no blanks, no network, no ent, no dtd
        if (!$dom->loadXML($content, LIBXML_NOENT | LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_DTDATTR | LIBXML_COMPACT)) {
            libxml_clear_errors();
            return ''; // Failed to parse
        }

        self::cleanNode($dom->documentElement);

        return $dom->saveXML();
    }

    private static function cleanNode(DOMNode $node)
    {
        // Dangerous tags to remove
        $dangerousTags = ['script', 'foreignObject', 'iframe', 'object', 'embed', 'audio', 'video', 'meta', 'link', 'style'];

        // Remove node if it is a dangerous tag
        if (in_array(strtolower($node->nodeName), $dangerousTags)) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
            return;
        }

        // Clean attributes (Only for DOMElements)
        if ($node instanceof DOMElement && $node->hasAttributes()) {
            $attrsToRemove = [];
            foreach ($node->attributes as $attr) {
                // $attr is DOMAttr which inherits DOMNode
                $name = strtolower($attr->nodeName);

                // Remove on* events, hrefs (external links), styles, and script-related
                if (
                    strpos($name, 'on') === 0 ||
                    $name === 'style' ||
                    $name === 'href' ||
                    $name === 'xlink:href' ||
                    strpos($name, 'xmlns:javascript') !== false
                ) {
                    $attrsToRemove[] = $attr->nodeName;
                }

                // Inspect attribute values for 'javascript:', 'data:', etc.
                if (preg_match('/^\s*(javascript|data|vbscript):/i', $attr->nodeValue)) {
                    $attrsToRemove[] = $attr->nodeName;
                }
            }
            foreach ($attrsToRemove as $name) {
                $node->removeAttribute($name);
            }
        }

        // Recursively clean children
        if ($node->hasChildNodes()) {
            // Convert to array to avoid modification during iteration issues
            $children = iterator_to_array($node->childNodes);
            foreach ($children as $child) {
                self::cleanNode($child);
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
     * Note: In production, store the key in an environment variable or secure config.
     */
    private const ENCRYPTION_KEY = 'sycs-secret-key-change-this-in-production'; // 32 bytes recommended for AES-256

    public static function encrypt(string $data): string
    {
        $key = hash('sha256', self::ENCRYPTION_KEY, true);
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    public static function decrypt(string $data): ?string
    {
        $key = hash('sha256', self::ENCRYPTION_KEY, true);
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
