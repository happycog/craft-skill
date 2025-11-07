<?php

namespace happycog\craftmcp\exceptions;

class ValidationException extends \Exception
{
    /** @var array<string, string[]> Validation errors organized by parameter name */
    private array $errors = [];

    /**
     * @param array<string, string[]> $errors Validation errors organized by parameter name
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;

        $errorMessages = [];

        foreach ($errors as $parameter => $parameterErrors) {
            foreach ($parameterErrors as $error) {
                $errorMessages[] = "{$parameter}: {$error}";
            }
        }

        $message = 'Validation failed: ' . implode('; ', $errorMessages);

        parent::__construct($message);
    }

    /**
     * Get all validation errors organized by parameter name
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific parameter
     *
     * @param string $parameter
     * @return string[]
     */
    public function getErrorsForParameter(string $parameter): array
    {
        return $this->errors[$parameter] ?? [];
    }

    /**
     * Check if a specific parameter has errors
     *
     * @param string $parameter
     * @return bool
     */
    public function hasErrorsForParameter(string $parameter): bool
    {
        return isset($this->errors[$parameter]) && !empty($this->errors[$parameter]);
    }
}
