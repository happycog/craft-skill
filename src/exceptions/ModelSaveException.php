<?php

namespace happycog\craftmcp\exceptions;

use craft\base\Model;

class ModelSaveException extends \Exception
{
    private Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        
        // Auto-generate context from class name
        $className = get_class($model);
        $shortClassName = substr($className, strrpos($className, '\\') + 1);
        
        // Convert PascalCase to space-separated words
        $context = preg_replace('/(?<!^)([A-Z])/', ' $1', $shortClassName) ?? $shortClassName;
        $context = strtolower(trim($context));
        
        $errors = $model->getErrors();
        $errorMessages = [];
        
        foreach ($errors as $attribute => $attributeErrors) {
            foreach ($attributeErrors as $error) {
                $errorMessages[] = "{$attribute}: {$error}";
            }
        }
        
        $message = empty($errorMessages) 
            ? "Failed to save {$context}"
            : "Failed to save {$context}: " . implode(', ', $errorMessages);
            
        parent::__construct($message);
    }

    public function getModel(): Model
    {
        return $this->model;
    }
}