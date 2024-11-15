<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Medlem;
use App\Utils\Sanitizer;
use App\Utils\Session;

class MedlemDataValidatorService
{
    private array $sanitizerRules = [
        'id' => 'int',
        'fodelsedatum' => ['date', 'Y-m-d'],
        'fornamn' => 'string',
        'efternamn' => 'string',
        'email' => 'email',
        'mobil' => 'string',
        'telefon' => 'string',
        'adress' => 'string',
        'postnummer' => 'string',
        'postort' => 'string',
        'kommentar' => 'string',
        'godkant_gdpr' => 'bool',
        'pref_kommunikation' => 'bool',
        'isAdmin' => 'bool',
        'foretag' => 'bool',
        'standig_medlem' => 'bool',
        'skickat_valkomstbrev' => 'bool',
    ];

    private array $requiredFields = ['fornamn', 'efternamn', 'fodelsedatum'];
    private array $booleanFields = ['godkant_gdpr', 'pref_kommunikation', 'isAdmin', 'foretag', 'standig_medlem', 'skickat_valkomstbrev'];
    private bool $emailChanged = false;

    public function validateAndPrepare(Medlem $medlem, array $postData): bool
    {
        $sanitizer = new Sanitizer();
        $cleanValues = $sanitizer->sanitize($postData, $this->sanitizerRules);

        // Save roles before sanitization removes them
        $roller = $postData['roller'] ?? [];

        if (!$this->validateRequiredFields($cleanValues)) {
            return false;
        }

        $this->updateMedlemFields($medlem, $cleanValues);
        $this->updateBooleanFields($medlem, $cleanValues);
        $this->updateRoles($medlem, $roller);

        return true;
    }

    private function validateRequiredFields(array $cleanValues): bool
    {
        $errors = [];
        foreach ($this->requiredFields as $field) {
            if (empty($cleanValues[$field])) {
                $errors[] = $field;
            }
        }

        if ($errors) {
            $errorMsg = "Följande obligatoriska fält måste fyllas i: " . implode(', ', $errors);
            Session::setFlashMessage('error', $errorMsg);
            return false;
        }

        return true;
    }

    private function updateMedlemFields(Medlem $medlem, array $cleanValues): void
    {
        foreach ($cleanValues as $key => $value) {
            if (property_exists($medlem, $key) && !in_array($key, $this->booleanFields)) {
                // Check if email is being updated to a different value
                if ($key === 'email' && $medlem->email !== $value) {
                    $this->emailChanged = true;
                }
                $medlem->$key = $value;
            }
        }
    }

    private function updateBooleanFields(Medlem $medlem, array $cleanValues): void
    {
        foreach ($this->booleanFields as $field) {
            $medlem->$field = isset($cleanValues[$field]) ? true : false;
        }
    }

    private function updateRoles(Medlem $medlem, array $roller): void
    {
        if (!empty($roller)) {
            $medlem->updateMedlemRoles($roller);
        }
    }

    // Add getter for the email changed flag
    public function hasEmailChanged(): bool
    {
        return $this->emailChanged;
    }
}
