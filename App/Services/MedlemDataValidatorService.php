<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Medlem;
use App\Utils\Sanitizer;
use App\Utils\Session;

class MedlemDataValidatorService
{
    /**
     * @var array<string, string|array<int, string>>
     */
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

    /**
     * @var array<int, string>
     */
    private array $requiredFields = ['fornamn', 'efternamn', 'fodelsedatum'];

    /**
     * @var array<int, string>
     */
    private array $booleanFields = ['godkant_gdpr', 'pref_kommunikation', 'isAdmin', 'foretag', 'standig_medlem', 'skickat_valkomstbrev'];

    private bool $emailChanged = false;

    /**
     * Validates and prepares member data from POST data.
     *
     * @param Medlem $medlem Member object to update
     * @param array<string, mixed> $postData POST data from form
     * @return bool True if validation passed and data was prepared
     */
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

    /**
     * Validates that all required fields are present and not empty.
     *
     * @param array<string, mixed> $cleanValues Sanitized form data
     * @return bool True if all required fields are valid
     */
    private function validateRequiredFields(array $cleanValues): bool
    {
        /** @var array<int, string> $errors */
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

    /**
     * Updates member object fields with sanitized values.
     *
     * @param Medlem $medlem Member object to update
     * @param array<string, mixed> $cleanValues Sanitized form data
     * @return void
     */
    private function updateMedlemFields(Medlem $medlem, array $cleanValues): void
    {
        foreach ($cleanValues as $key => $value) {
            if (property_exists($medlem, $key) && !in_array($key, $this->booleanFields)) {
                // Check if email is being updated to a different value
                if ($key === 'email' && isset($medlem->email) && $medlem->email !== $value) {
                    $this->emailChanged = true;
                }
                $medlem->$key = $value;
            }
        }
    }

    /**
     * Updates boolean fields on member object based on presence in form data.
     *
     * @param Medlem $medlem Member object to update
     * @param array<string, mixed> $cleanValues Sanitized form data
     * @return void
     */
    private function updateBooleanFields(Medlem $medlem, array $cleanValues): void
    {
        foreach ($this->booleanFields as $field) {
            $medlem->$field = isset($cleanValues[$field]) ? true : false;
        }
    }

    /**
     * Updates member roles if provided.
     *
     * @param Medlem $medlem Member object to update
     * @param array<int, int> $roller Array of role IDs
     * @return void
     */
    private function updateRoles(Medlem $medlem, array $roller): void
    {
        if (!empty($roller)) {
            $medlem->updateMedlemRoles($roller);
        }
    }

    /**
     * Checks if the email was changed during validation.
     *
     * @return bool True if email was changed
     */
    public function hasEmailChanged(): bool
    {
        return $this->emailChanged;
    }
}
