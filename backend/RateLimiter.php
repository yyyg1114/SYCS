<?php
class RateLimiter {
    private $file;
    private $maxAttempts;
    private $windowSeconds;

    public function __construct($maxAttempts = 5, $windowSeconds = 600) {
        $this->file = __DIR__ . '/ratelimit.json';
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    public function check($ip) {
        $data = $this->load();
        $now = time();

        // Cleanup old entries
        foreach ($data as $key => $info) {
            if ($now - $info['start_time'] > $this->windowSeconds) {
                unset($data[$key]);
            }
        }

        if (!isset($data[$ip])) {
            return true;
        }

        if ($data[$ip]['attempts'] >= $this->maxAttempts) {
            return false;
        }

        return true;
    }

    public function increment($ip) {
        $data = $this->load();
        $now = time();

        if (!isset($data[$ip])) {
            $data[$ip] = [
                'attempts' => 1,
                'start_time' => $now
            ];
        } else {
            $data[$ip]['attempts']++;
        }

        $this->save($data);
    }

    public function clear($ip) {
        $data = $this->load();
        if (isset($data[$ip])) {
            unset($data[$ip]);
            $this->save($data);
        }
    }

    private function load() {
        if (!file_exists($this->file)) {
            return [];
        }
        $content = file_get_contents($this->file);
        return json_decode($content, true) ?? [];
    }

    private function save($data) {
        file_put_contents($this->file, json_encode($data));
    }
}
