<?php

namespace happycog\craftmcp\controllers;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\MapperBuilder;
use craft\web\Controller as CraftController;
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
            $mapper = (new MapperBuilder())
                ->allowPermissiveTypes()
                ->allowScalarValueCasting()
                ->argumentsMapper();
            $sourceArray = $useQueryParams
                ? $this->request->getQueryParams()
                : $this->request->getBodyParams();

            // Merge additional params (e.g., path params) into source array
            $sourceArray = array_merge($sourceArray, $params);

            // Validate no extra keys before mapping
            $this->validateNoExtraKeys($tool, $sourceArray);

            $source = Source::array($sourceArray)->camelCaseKeys();

            // Map arguments and call the tool
            $arguments = $mapper->mapArguments($tool, $source);
            $result = $tool(...$arguments);

            return $this->asJson($result);
        } catch (MappingError $error) {
            $this->response->setStatusCode(400);
            return $this->asJson(['error' => (string) $error]);
        } catch (\InvalidArgumentException $error) {
            $this->response->setStatusCode(400);
            return $this->asJson(['error' => $error->getMessage()]);
        }
    }

    /**
     * Validate that the source array only contains keys that match the tool's parameters.
     *
     * @param callable $tool
     * @param array<string, mixed> $sourceArray
     * @throws \InvalidArgumentException if extra keys are found
     */
    private function validateNoExtraKeys(callable $tool, array $sourceArray): void
    {
        $reflection = new \ReflectionFunction(\Closure::fromCallable($tool));
        $validKeys = [];

        foreach ($reflection->getParameters() as $param) {
            $validKeys[] = $param->getName();
        }

        // Filter out Craft-specific keys that are added automatically
        $ignoredKeys = ['CRAFT_CSRF_TOKEN'];
        $sourceKeys = array_diff(array_keys($sourceArray), $ignoredKeys);

        $extraKeys = array_diff($sourceKeys, $validKeys);

        if (!empty($extraKeys)) {
            throw new \InvalidArgumentException(
                'Invalid parameters: ' . implode(', ', $extraKeys) . '. ' .
                'Valid parameters are: ' . implode(', ', $validKeys)
            );
        }
    }
}
