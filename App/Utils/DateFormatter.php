<?php

declare(strict_types=1);

namespace App\Utils;

class DateFormatter
{
    public static function formatDateWithHms(string $dateString): ?string
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $dateString);

        if ($date && $date->format('Y-m-d H:i:s') === $dateString) {
            return $date->format('Y-m-d');
        }

        return null;
    }

    public static function formatDateYmd(string $dateString): ?string
    {
        $date = \DateTime::createFromFormat('Y-m-d', $dateString);

        if ($date && $date->format('Y-m-d') === $dateString) {
            return $date->format('Y-m-d');
        }

        return null;
    }
}
