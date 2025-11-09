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
    /** @var array<int, array<string, mixed>> */
    public array $deltagare = [];
    public string $created_at;
    public string $updated_at;

    /**
     * Constructor for Segling class
     *
     * @param int $id
     * @param string $start_dat
     * @param string $slut_dat
     * @param string $skeppslag
     * @param string|null $kommentar
     * @param array<int, array<string, mixed>> $deltagare
     * @param string $created_at
     * @param string $updated_at
     */
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

    /**
     * Fetches deltagare on a segling that has a specific role
     *
     * @param string $targetRole The role to search for
     * @return array<int, mixed> Array of deltagare with the specified role
     */
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
