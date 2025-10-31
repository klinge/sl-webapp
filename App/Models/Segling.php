<?php

declare(strict_types=1);

namespace App\Models;

class Segling
{
    public int $id;
    public string $start_dat;
    public string $slut_dat;
    public string $skeppslag;
    public ?string $kommentar;
    public array $deltagare = [];
    public string $created_at;
    public string $updated_at;

    public function __construct(
        int $id = 0,
        string $start_dat = '',
        string $slut_dat = '',
        string $skeppslag = '',
        ?string $kommentar = null,
        array $deltagare = [],
        string $created_at = '',
        string $updated_at = ''
    ) {
        $this->id = $id;
        $this->start_dat = $start_dat;
        $this->slut_dat = $slut_dat;
        $this->skeppslag = $skeppslag;
        $this->kommentar = $kommentar;
        $this->deltagare = $deltagare;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public function getDeltagareByRoleName(string $targetRole): array
    {
        $results = [];

        foreach ($this->deltagare as $crewMember) {
            if ($crewMember['roll_namn'] === $targetRole) {
                $results[] = [
                    'id' => $crewMember['medlem_id'],
                    'fornamn' => $crewMember['fornamn'],
                    'efternamn' => $crewMember['efternamn']
                ];
            }
        }
        return $results;
    }
}
