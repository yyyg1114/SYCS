<?php

class I18n
{
    private $translations = [];
    private $currentLang = 'ja';
    private static $instance = null;

    /**
     * Get the singleton instance of I18n
     */
    public static function getInstance($lang = null)
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init($lang);
        }
        return self::$instance;
    }

    /**
     * Initialize translations
     */
    public function init($lang = null)
    {
        // 1. Determine language (Order: Argument > Session > Cookie > Default 'ja')
        if (!$lang) {
            $lang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'ja';
        }

        $this->currentLang = $lang;

        // 2. Persist to session and cookie
        $_SESSION['lang'] = $lang;
        if (!isset($_COOKIE['lang']) || $_COOKIE['lang'] !== $lang) {
            setcookie('lang', $lang, time() + (86400 * 30), "/"); // 30 days
        }

        // 3. Load translation file
        $path = __DIR__ . '/../frontend/locales/' . $lang . '.json';
        if (file_exists($path)) {
            $json = file_get_contents($path);
            $this->translations = json_decode($json, true) ?: [];
        } else if ($lang !== 'ja') {
            // Fallback to Japanese if translation file doesn't exist
            $this->init('ja');
        }
    }

    /**
     * Get translation by key
     */
    public function t($key, $default = null)
    {
        return $this->translations[$key] ?? ($default ?: $key);
    }

    /**
     * Get all translations
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Get current language
     */
    public function getCurrentLang()
    {
        return $this->currentLang;
    }
}

/**
 * Global translation helper function
 */
function __($key, $default = null)
{
    return I18n::getInstance()->t($key, $default);
}
