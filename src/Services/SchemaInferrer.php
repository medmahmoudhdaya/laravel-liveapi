<?php

namespace Zidbih\LiveApi\Services;

class SchemaInferrer
{
    public function merge(array $existing, array $snapshot): array
    {
        $responses = $existing['responses'] ?? [];
        $statusCode = (string) $snapshot['status'];

        $responses[$statusCode] = $this->mergeResponse(
            $responses[$statusCode] ?? [],
            $snapshot['response_body']
        );

        return [
            'method' => $snapshot['method'],
            'uri' => $snapshot['uri'],
            'authenticated' => $snapshot['authenticated'],
            'query_params' => $this->mergeParameters($existing['query_params'] ?? [], $snapshot['query_params']),
            'request_body' => $this->inferSchema($existing['request_body'] ?? [], $snapshot['request_body']),
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
                    )
                ]
            ]
        ];
    }

    protected function inferSchema(array $schema, mixed $data): array
    {
        $type = $this->getJsonType($data);

        if (empty($schema)) {
            return $this->generateBaseSchema($data);
        }

        // Wide types: If it was an integer and now it's a float, it becomes a 'number'
        if ($schema['type'] !== $type) {
            $schema['type'] = $this->resolveTypeConflict($schema['type'], $type);
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

            // If a previously required key is missing in the new data, remove it from 'required'
            $schema['required'] = array_values(array_intersect($schema['required'], array_keys($data)));
        }

        if ($type === 'array' && !empty($data)) {
            $schema['items'] = $this->inferSchema($schema['items'] ?? [], $data[0] ?? null);
        }

        return $schema;
    }

    protected function generateBaseSchema(mixed $data): array
    {
        $type = $this->getJsonType($data);
        $schema = ['type' => $type];

        if ($type === 'object') {
            $schema['properties'] = [];
            $schema['required'] = array_keys($data);
            foreach ($data as $key => $value) {
                $schema['properties'][$key] = $this->generateBaseSchema($value);
            }
        } elseif ($type === 'array') {
            $schema['items'] = $this->generateBaseSchema($data[0] ?? null);
        }

        return $schema;
    }

    protected function getJsonType(mixed $data): string
    {
        if (is_null($data)) return 'null';
        if (is_int($data)) return 'integer';
        if (is_float($data)) return 'number';
        if (is_bool($data)) return 'boolean';
        if (is_array($data)) {
            return array_is_list($data) ? 'array' : 'object';
        }
        return 'string';
    }

    protected function resolveTypeConflict(string $old, string $new): string
    {
        $types = [$old, $new];
        if (in_array('number', $types) && in_array('integer', $types)) return 'number';
        if (in_array('null', $types)) return $old === 'null' ? $new : $old; // Handle nullability later via 'nullable' key
        return 'string'; // Default fallback
    }

    protected function mergeParameters(array $existing, array $new): array
    {
        foreach ($new as $key => $value) {
            $existing[$key] = [
                'type' => $this->getJsonType($value),
                'example' => $value
            ];
        }
        return $existing;
    }
}