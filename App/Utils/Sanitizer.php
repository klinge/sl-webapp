<?php

declare(strict_types=1);

namespace App\Utils;

/*
* This class is responsible for sanitizing and validating user input
* It provides methods to sanitize and validate user input based on specified rules.
*/

class Sanitizer
{
    //Tells the function what rule to use for sanitizing
    private array $sanitizing_rules = [
        'string' => 'sanitizeString', //FILTER_SANITIZE_STRING us deprecated so use htmlspecialchars() instead
        'email' => FILTER_SANITIZE_EMAIL,
        'int' => FILTER_SANITIZE_NUMBER_INT,
        'float' => FILTER_SANITIZE_NUMBER_FLOAT,
        'url' => FILTER_SANITIZE_URL,
        'date' => 'sanitizeDate',
    ];

    /*
    * Sanitize user inputs based on specified rules.
    * @param array $input The input data to be sanitized.
    * @param array $fieldRules An array specifying the rules for each field.
    * @return array The sanitized input data.
    * @throws \InvalidArgumentException If an invalid sanitization rule is specified.
    */
    public function sanitize(array $input, array $fieldRules): array
    {
        $sanitizedInput = [];

        foreach ($fieldRules as $field => $rule) {
            if (isset($input[$field])) {
                $sanitizedInput[$field] = $this->applySanitizationRule($input[$field], $rule);
            }
        }

        return $sanitizedInput;
    }


    /**
     * Applies a sanitization rule to the given value.
     *
     * @param mixed $value The value to be sanitized
     * @param string|array $rule The sanitization rule to apply
     *
     * @return mixed The sanitized value
     *
     * @throws \InvalidArgumentException If the sanitization rule is invalid or doesn't exist
     */

    private function applySanitizationRule(mixed $value, string|array $rule): mixed
    {
        $value = trim($value);

        $ruleName = is_array($rule) ? $rule[0] : $rule;
        if (!isset($this->sanitizing_rules[$ruleName])) {
            throw new \InvalidArgumentException("Sanitization rule '$ruleName' does not exist.");
        }
        $method = $this->sanitizing_rules[$ruleName];

        // If the method name is a string it should be the name of a class method to call
        if (is_string($method) && method_exists($this, $method)) {
            return $this->$method($value, $rule[1] ?? null);
        } elseif (isset($this->sanitizing_rules[$rule])) {
            return filter_var($value, $this->sanitizing_rules[$rule]);
        } else {
            throw new \InvalidArgumentException("Unknown sanitization rule: $rule");
        }
    }

    private function sanitizeString(string $value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function sanitizeDate($value, $format = 'Y-m-d')
    {
        $date = \DateTime::createFromFormat($format, $value);
        return ($date && $date->format($format) === $value) ? $value : null;
    }
}
