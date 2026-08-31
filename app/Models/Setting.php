<?php

declare(strict_types=1);

namespace App\Models;

final class Setting extends Model
{
    protected string $table = 'settings';

    private static ?array $cache = null;

    public static function map(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            $rows = (new self())->all('setting_key ASC');
            $map = [];
            foreach ($rows as $row) {
                $map[$row['setting_key']] = $row['setting_value'];
            }
            self::$cache = $map;
        } catch (\Throwable) {
            self::$cache = [];
        }

        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $map = self::map();
        if (!array_key_exists($key, $map) || $map[$key] === null || $map[$key] === '') {
            return $default;
        }
        return $map[$key];
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    public function setMany(array $pairs): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ($pairs as $key => $value) {
            $stmt->execute([(string) $key, (string) $value]);
        }
        self::clearCache();
    }
}
