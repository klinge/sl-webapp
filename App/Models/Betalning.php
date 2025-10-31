<?php

declare(strict_types=1);

namespace App\Models;

class Betalning
{
    // object properties
    public int $id;
    public int $medlem_id;
    public float $belopp;
    public string $datum;
    public int $avser_ar;
    public string $kommentar = '';
    public string $created_at = '';
    public string $updated_at = '';

    public function __construct()
    {
        // Pure data object - no dependencies
    }


}
