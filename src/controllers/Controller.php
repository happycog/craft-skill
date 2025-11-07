<?php

namespace happycog\craftmcp\controllers;

use CuyZ\Valinor\Definition\Repository\FunctionDefinitionRepository;
use CuyZ\Valinor\Definition\Repository\Reflection\ReflectionAttributesRepository;
use CuyZ\Valinor\Definition\Repository\Reflection\ReflectionFunctionDefinitionRepository;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\Type\Parser\Factory\TypeParserFactory;
use craft\web\Controller as CraftController;
use happycog\craftmcp\exceptions\ValidationException;
use yii\web\Response;

abstract class Controller extends CraftController
{
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = false;

    /**
     * Call a tool with automatic Valinor validation and mapping from request body params.
     *
     * @param callable $tool The tool method to call (e.g., $createSection->create(...))
     * @param array<string, mixed> $params Additional parameters to merge (e.g., path params)
     * @param bool $useQueryParams Whether to use query params instead of body params (for GET requests)
     * @return Response JSON response with tool result or validation errors
     */
    protected function callTool(
        callable $tool,
        array $params = [],
        bool $useQueryParams = false
    ): Response {
        try {
            $sourceArray = $useQueryParams
                ? $this->request->getQueryParams()
                : $this->request->getBodyParams();

            // Merge additional params (e.g., path params) into source array
            $sourceArray = array_merge($sourceArray, $params);

            // Filter out Craft-specific keys that are added automatically
            $ignoredKeys = ['CRAFT_CSRF_TOKEN'];
            $sourceArray = array_diff_key($sourceArray, array_flip($ignoredKeys));

            // Validate all inputs and collect any errors
            $this->validateAllInputs($tool, $sourceArray);

            // Map arguments using argumentsMapper
            // At this point all validation has passed, so mapping should succeed
            $mapper = (new MapperBuilder())
                ->allowPermissiveTypes()
                ->allowScalarValueCasting()
                ->argumentsMapper();

            $arguments = $mapper->mapArguments($tool, $sourceArray);
            $result = $tool(...$arguments);

            return $this->asJson($result);
        } catch (ValidationException $error) {
            $this->response->setStatusCode(400);
            return $this->asJson([
                'error' => $error->getMessage(),
                'errors' => $error->getErrors(),
            ]);
        } catch (MappingError $error) {
            $this->response->setStatusCode(400);
            return $this->asJson(['error' => (string) $error]);
        } catch (\InvalidArgumentException $error) {
            $this->response->setStatusCode(400);
            return $this->asJson(['error' => $error->getMessage()]);
        }
    }

    /**
     * Validate all inputs and collect any validation errors.
     * Checks for: superfluous keys, missing required parameters, and type mismatches (including PHPDoc types).
     *
     * @param callable $tool
     * @param array<string, mixed> $sourceArray
     * @throws ValidationException if validation fails with all collected errors
     */
    private function validateAllInputs(callable $tool, array $sourceArray): void
    {
        $errors = [];

        $functionDefinitionRepository = $this->createFunctionDefinitionRepository();
        $functionDefinition = $functionDefinitionRepository->for($tool);

        // Get list of valid parameter names
        $validParameterNames = [];
        foreach ($functionDefinition->parameters as $parameterDefinition) {
            $validParameterNames[] = $parameterDefinition->name;
        }

        // Check for superfluous keys
        $sourceKeys = array_keys($sourceArray);
        $superfluousKeys = array_diff($sourceKeys, $validParameterNames);

        if (!empty($superfluousKeys)) {
            $errors['_superfluous'] = [
                'Unexpected key(s) ' . implode(', ', array_map(fn($k) => "`$k`", $superfluousKeys)) .
                ', expected ' . implode(', ', array_map(fn($k) => "`$k`", $validParameterNames))
            ];
        }

        // Validate each parameter
        foreach ($functionDefinition->parameters as $parameterDefinition) {
            $paramName = $parameterDefinition->name;
            $paramErrors = [];

            // Check if required parameter is missing
            if (!isset($sourceArray[$paramName]) && !$parameterDefinition->isOptional) {
                $paramErrors[] = 'Required parameter is missing';
            }

            // Check type if parameter is provided
            if (isset($sourceArray[$paramName])) {
                $value = $sourceArray[$paramName];

                // Validate the value against the resolved type (includes PHPDoc types)
                // Try to accept the value with scalar casting enabled for query params
                if (!$this->typeAcceptsWithCasting($parameterDefinition->type, $value)) {
                    $paramErrors[] = sprintf(
                        'Does not accept value of type %s. Expected: %s',
                        get_debug_type($value),
                        $parameterDefinition->type->toString()
                    );
                }
            }

            if (!empty($paramErrors)) {
                $errors[$paramName] = $paramErrors;
            }
        }

        // If we have any errors, throw ValidationException
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Check if a type accepts a value, considering scalar casting.
     * This handles cases like array<string> being acceptable for array<int> when scalar casting is enabled.
     *
     * @param \CuyZ\Valinor\Type\Type $type
     * @param mixed $value
     * @return bool
     */
    private function typeAcceptsWithCasting($type, $value): bool
    {
        // First try direct acceptance
        if ($type->accepts($value)) {
            return true;
        }

        // If it's an array and the parameter expects an array, check if we can cast the elements
        if (is_array($value)) {
            $typeString = $type->toString();

            // Handle array<int> or array<int>|null - check if all values are numeric strings
            if (preg_match('/array<int>/i', $typeString)) {
                foreach ($value as $item) {
                    if (!is_numeric($item) && !is_int($item)) {
                        return false;
                    }
                }
                return true;
            }

            // Handle array<string> - all scalars can be cast to string
            if (preg_match('/array<string>/i', $typeString)) {
                foreach ($value as $item) {
                    if (!is_scalar($item)) {
                        return false;
                    }
                }
                return true;
            }
        }

        // For scalar values, check if casting would work
        if (is_scalar($value)) {
            $typeString = $type->toString();

            // String to int casting
            if ((str_contains($typeString, 'int') || $typeString === 'int') && is_numeric($value)) {
                return true;
            }

            // String to float casting
            if ((str_contains($typeString, 'float') || $typeString === 'float') && is_numeric($value)) {
                return true;
            }

            // String to bool casting
            if ((str_contains($typeString, 'bool') || $typeString === 'bool') && in_array($value, ['1', '0', 'true', 'false', 1, 0, true, false], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a FunctionDefinitionRepository for resolving parameter types from PHPDoc.
     * Uses Valinor's internal type resolution infrastructure.
     *
     * @return FunctionDefinitionRepository
     */
    private function createFunctionDefinitionRepository(): FunctionDefinitionRepository
    {
        static $repository = null;

        if ($repository === null) {
            $typeParserFactory = new TypeParserFactory();

            // Create a minimal AttributesRepository (we don't need to process attributes)
            $attributesRepository = new ReflectionAttributesRepository(
                // We pass a stub ClassDefinitionRepository since we don't need class definitions for function validation
                new class implements \CuyZ\Valinor\Definition\Repository\ClassDefinitionRepository {
                    public function for(\CuyZ\Valinor\Type\ObjectType $type): \CuyZ\Valinor\Definition\ClassDefinition
                    {
                        throw new \RuntimeException('Class definitions not needed for function validation');
                    }
                },
                [] // no allowed attributes
            );

            $repository = new ReflectionFunctionDefinitionRepository(
                $typeParserFactory,
                $attributesRepository
            );
        }

        return $repository;
    }

}
