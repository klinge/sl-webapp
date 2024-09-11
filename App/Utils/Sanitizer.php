<?php

namespace App\Utils;

class Sanitizer
{
    private $rules = [
        'string' => 'sanitizeString', //FILTER_SANITIZE_STRING us deprecated so use htmlspecialchars() instead
        'email' => FILTER_SANITIZE_EMAIL,
        'int' => FILTER_SANITIZE_NUMBER_INT,
        'float' => FILTER_SANITIZE_NUMBER_FLOAT,
        'url' => FILTER_SANITIZE_URL,
        'date' => 'sanitizeDate',
    ];

    public function sanitize(array $input, array $fieldRules)
    {
        $sanitizedInput = [];

        foreach ($fieldRules as $field => $rule) {
            if (isset($input[$field])) {
                $sanitizedInput[$field] = $this->applySanitizationRule($input[$field], $rule);
            }
        }

        return $sanitizedInput;
    }

    private function applySanitizationRule($value, $rule)
    {
        $value = trim($value);

        $ruleName = is_array($rule) ? $rule[0] : $rule;
        if (!isset($this->rules[$ruleName])) {
            throw new \InvalidArgumentException("Sanitization rule '$ruleName' does not exist.");
        }
        $method = $this->rules[$ruleName];

        if (is_string($method)) {
            return $this->$method($value, $rule[1] ?? null);
        } elseif (isset($this->rules[$rule])) {
            return filter_var($value, $this->rules[$rule]);
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
