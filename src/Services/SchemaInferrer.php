<?php

declare(strict_types=1);

namespace Zidbih\LiveApi\Services;

final class SchemaInferrer
{
    /**
     * Records a new traffic snapshot and updates the inferred schema.
     */
    public function merge(array $existing, array $snapshot): array
    {
        $responses = $existing['responses'] ?? [];
        $statusCode = (string) $snapshot['status'];

        $responses[$statusCode] = $this->mergeResponse(
            $responses[$statusCode] ?? [],
            $snapshot['response_body']
        );

        // Only infer request body if data exists or we already have a schema for it
        $requestBody = ! empty($snapshot['request_body'])
            ? $this->inferSchema($existing['request_body'] ?? [], $snapshot['request_body'])
            : ($existing['request_body'] ?? []);

        return [
            'method' => $snapshot['method'],
            'uri' => $snapshot['uri'],
            'authenticated' => $snapshot['authenticated'],
            'query_params' => $this->mergeParameters($existing['query_params'] ?? [], $snapshot['query_params']),
            'request_body' => $requestBody,
            'responses' => $responses,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    protected function mergeResponse(array $existingResponse, array $newBody): array
    {
        return [
            'description' => $existingResponse['description'] ?? 'Automated response inference',
            'content' => [
                'application/json' => [
                    'schema' => $this->inferSchema(
                        $existingResponse['content']['application/json']['schema'] ?? [],
                        $newBody
                    ),
                ],
            ],
        ];
    }

    protected function inferSchema(array $schema, mixed $data): array
    {
        if (is_null($data) && empty($schema)) {
            return ['type' => 'null'];
        }

        $type = $this->getJsonType($data);

        if (empty($schema)) {
            return $this->generateBaseSchema($data);
        }

        // Logic to fix "String vs Object" conflict:
        // If we already know it's an object (has properties), keep it as an object.
        if (isset($schema['properties']) && $type === 'object') {
            $schema['type'] = 'object';
        } elseif (($schema['type'] ?? '') !== $type) {
            $schema['type'] = $this->resolveTypeConflict($schema['type'] ?? 'string', $type);
        }

        if ($type === 'object' && is_array($data)) {
            $schema['properties'] = $schema['properties'] ?? [];
            $schema['required'] = $schema['required'] ?? array_keys($schema['properties']);

            foreach ($data as $key => $value) {
                $schema['properties'][$key] = $this->inferSchema(
                    $schema['properties'][$key] ?? [],
                    $value
                );
            }

            // Remove from required if not present in the current request
            $schema['required'] = array_values(array_intersect($schema['required'], array_keys($data)));
        }

        if ($type === 'array' && ! empty($data)) {
            $schema['items'] = $this->inferSchema($schema['items'] ?? [], $data[0] ?? null);
        }

        return $schema;
    }

    protected function generateBaseSchema(mixed $data): array
    {
        $type = $this->getJsonType($data);
        $schema = ['type' => $type];

        if ($type === 'object' && is_array($data)) {
            $schema['properties'] = [];
            $schema['required'] = array_keys($data);
            foreach ($data as $key => $value) {
                $schema['properties'][$key] = $this->generateBaseSchema($value);
            }
        } elseif ($type === 'array' && is_array($data)) {
            $schema['items'] = $this->generateBaseSchema($data[0] ?? null);
        }

        return $schema;
    }

    protected function getJsonType(mixed $data): string
    {
        if (is_null($data)) {
            return 'null';
        }
        if (is_int($data)) {
            return 'integer';
        }
        if (is_float($data)) {
            return 'number';
        }
        if (is_bool($data)) {
            return 'boolean';
        }
        if (is_array($data)) {
            return array_is_list($data) ? 'array' : 'object';
        }

        return 'string';
    }

    protected function resolveTypeConflict(string|array $old, string $new): string|array
    {
        $existingTypes = is_array($old) ? $old : [$old];
        $types = array_unique(array_merge($existingTypes, [$new]));

        // Handle numeric widening
        if (in_array('number', $types, true) && in_array('integer', $types, true)) {
            $types = array_values(array_filter($types, fn ($t) => $t !== 'integer'));
        }

        // For OpenAPI 3.1, multiple types are allowed.
        // If there's more than one type, return the array (e.g., ["string", "null"])
        return count($types) === 1 ? $types[0] : $types;
    }

    protected function mergeParameters(array $existing, array $new): array
    {
        foreach ($new as $key => $value) {
            $existing[$key] = [
                'type' => $this->getJsonType($value),
                'example' => $value,
            ];
        }

        return $existing;
    }
}
