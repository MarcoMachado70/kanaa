<?php

namespace App\Support;

use RuntimeException;

class DemoSeederSettings
{
    public static function ensureAllowed(): void
    {
        if (app()->environment(['local', 'testing'])) {
            return;
        }

        $password = config('demo.password');
        if (! is_string($password) || mb_strlen(trim($password)) < 12 || $password === 'KanaaDemo2026!') {
            throw new RuntimeException('Para executar seeders de demonstração neste ambiente, defina KANAA_DEMO_PASSWORD com uma senha própria de pelo menos 12 caracteres e atualize o cache de configuração.');
        }
    }

    public static function password(): string
    {
        self::ensureAllowed();

        return config('demo.password') ?: 'KanaaDemo2026!';
    }
}
