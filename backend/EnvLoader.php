<?php
// backend/EnvLoader.php

class EnvLoader
{
    /**
     * Loads environment variables from a .env file into getenv(), $_ENV and $_SERVER.
     * 
     * @param string $path Path to the .env file
     */
    public static function load($path)
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Split by FIRST equals sign
            $items = explode('=', $line, 2);
            if (count($items) !== 2) {
                continue;
            }

            $name = trim($items[0]);
            $value = trim($items[1]);

            // Remove surrounding quotes from value
            $value = trim($value, '"\'');

            // Set environment variable if not already set (or override if desired)
            // Here we prioritize .env values for local development
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Automatically load the .env file from the project root relative to this file
EnvLoader::load(__DIR__ . '/../.env');
