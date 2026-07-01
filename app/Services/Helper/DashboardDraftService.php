<?php

namespace App\Services\Helper;

class DashboardDraftService
{
     protected function key(int $ppf): string
    {
        return "dashboard_draft.$ppf";
    }

    public function put(int $ppf, string $section, array $data): void
    {
        $draft = session($this->key($ppf), []);
        $draft[$section] = $data;
        session([$this->key($ppf) => $draft]);
    }

    public function get(int $ppf): array
    {
        return session($this->key($ppf), []);
    }

    public function clear(int $ppf): void
    {
        session()->forget($this->key($ppf));
    }
}
