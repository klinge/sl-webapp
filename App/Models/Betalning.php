<?php

declare(strict_types=1);

namespace App\Models;

class Betalning
{
    // object properties
    public int $id = 0;
    public int $medlem_id = 0;
    public float $belopp = 0.0;
    public string $datum = '';
    public int $avser_ar = 0;
    public string $kommentar = '';
    public string $created_at = '';
    public string $updated_at = '';

    public function __construct()
    {
        // Pure data object - no dependencies
    }
}
