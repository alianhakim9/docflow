<?php

namespace App\Enums;

enum PolicyType: string
{
    case QUOTA_LIMIT  = 'quota_limit';
    case AMOUNT_THRESHOLD = 'amount_threshold';
    case TIME_BASED = 'time_based';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::QUOTA_LIMIT => 'Batas Kuota',
            self::AMOUNT_THRESHOLD => 'Ambang Batas Jumlah',
            self::TIME_BASED => 'Berbasis Waktu',
            self::CUSTOM => "Kustom"
        };
    }
}
