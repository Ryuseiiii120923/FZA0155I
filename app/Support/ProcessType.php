<?php

namespace App\Support;

final class ProcessType
{
    public const HF     = 'HF';
    public const HF_SUB = 'HF Sub';

    public static function isSub(?string $process): bool
    {
        return $process === self::HF_SUB;
    }
}