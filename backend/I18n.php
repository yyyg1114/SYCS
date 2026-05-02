<?php
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Cookie.php';

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
        } else if ($lang !== null) {
            self::$instance->init($lang);
        }
        return self::$instance;
    }

    /**
     * Initialize translations
     *
     * @param string|null $lang Language code to initialize.
     */
    public function init($lang = null)
    {
        // 1. Determine language (Order: Argument > Session > Cookie > Default 'ja')
        if (!$lang) {
            $lang = $this->getSessionLang() ?? $this->getCookieLang() ?? 'ja';
        }

        // Sanitize language code to prevent path traversal
        $lang = preg_replace('/[^a-z0-9_-]/i', '', $lang);
        if (empty($lang)) {
            $lang = 'ja';
        }

        // Avoid re-initializing if the language hasn't changed and we already have translations
        if ($this->currentLang === $lang && !empty($this->translations)) {
            return;
        }

        $this->currentLang = $lang;

        // 2. Persist to session and cookie
        $this->setSessionLang($lang);
        $this->setCookieLang($lang);

        // 3. Load translation file
        $path = __DIR__ . '/../frontend/locales/' . $lang . '.json';
        if (file_exists($path)) {
            $json = file_get_contents($path);
            if ($json !== false) {
                $this->translations = json_decode($json, true) ?: [];
            }
        }

        // Fallback logic
        if (empty($this->translations) && $lang !== 'ja') {
            // Fallback to Japanese if translation fails for another language
            $this->init('ja');
        }
    }

    /**
     * Get language from session safely
     */
    private function getSessionLang()
    {
        return Session::getInstance()->get('lang');
    }

    /**
     * Set language to session safely
     */
    private function setSessionLang($lang)
    {
        Session::getInstance()->set('lang', $lang);
    }

    /**
     * Get language from cookie safely
     */
    private function getCookieLang()
    {
        return Cookie::getInstance()->get('lang');
    }

    /**
     * Set language to cookie safely
     */
    private function setCookieLang($lang)
    {
        if (Cookie::getInstance()->get('lang') === $lang) {
            return;
        }

        Cookie::getInstance()->set('lang', $lang, 86400 * 30, "/"); // 30 days
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
