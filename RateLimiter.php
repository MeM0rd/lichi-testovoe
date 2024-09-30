<?php

class RateLimiter {
    private int $limit;
    private int $interval;

    private $cache; // Абстрактный кэш

    public function __construct(int $limit = 10, int $interval = 60) {
        $this->limit = $limit;
        $this->interval = $interval;
    }

    public function handle()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = 'rate_limit_' . $ip;

        $data = $this->cache->get($key) ?? ['attempts' => 0, 'deadline' => time() + $this->interval];

        if (time() > $data['deadline']) {
            $data = ['attempts' => 0, 'deadline' => time() + $this->interval];
        }

        if ($data['attempts'] >= $this->limit) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests.']);
            return false;
        }

        return $this->cache->store($key, $data);
    }
}
