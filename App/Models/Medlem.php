<?php

declare(strict_types=1);

namespace App\Models;

class Medlem
{
    // Class properties
    public int $id;
    public ?string $fodelsedatum;
    public ?string $fornamn;
    public string $efternamn;
    public ?string $email;
    public ?string $mobil;
    public ?string $telefon;
    public ?string $adress;
    public ?string $postnummer;
    public ?string $postort;
    public ?string $kommentar;
    // User preferences all booleans with default values
    public bool $godkant_gdpr = false;
    public bool $pref_kommunikation = true;
    public bool $foretag = false;
    public bool $standig_medlem = false;
    public bool $skickat_valkomstbrev = false;
    public bool $isAdmin = false;
    // User login
    public ?string $password = null;
    //Fetched from Roller table
    public array $roller = [];
    // Timestamps
    public string $created_at = '';
    public string $updated_at = '';

    public function __construct()
    {
        // Pure data object - no dependencies
    }

    public function getNamn(): string
    {
        return $this->fornamn . " " . $this->efternamn;
    }

    public function updateMedlemRoles(array $newRoleIds): void
    {
        // Remove roles that no longer exist
        $rolesToRemove = array_diff(array_column($this->roller, 'roll_id'), $newRoleIds);
        foreach ($rolesToRemove as $roleId) {
            $key = array_search($roleId, array_column($this->roller, 'roll_id'));
            unset($this->roller[$key]);
        }

        // Add new roles
        foreach ($newRoleIds as $roleId) {
            if (!in_array($roleId, array_column($this->roller, 'roll_id'))) {
                $this->roller[] = ['roll_id' => $roleId];
            }
        }
    }

    public function hasRole(string $searchRole): bool
    {
        $roleIds = array_column($this->roller, 'roll_id');
        return in_array($searchRole, $roleIds);
    }
}
