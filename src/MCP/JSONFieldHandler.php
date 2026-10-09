<?php

namespace SLONline\ORM\FieldType\MCP;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use SilverStripe\ORM\DataObject;
use SLONline\MCP\Fields\FieldContext;
use SLONline\MCP\Fields\Handlers\AbstractHandler;
use SLONline\ORM\FieldType\DBJSONText;

// slonline/silverstripe-mcp is optional: without it the parent class is missing, so skip the
// declaration and let class_exists() return false instead of failing (e.g. during static analysis)
if (!class_exists(AbstractHandler::class)) {
    return;
}

/**
 * Lets AI agents (MCP) read and write DBJSONText values, validated by an optional JSON Schema.
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2026, SLONline, s.r.o.
 */
class JSONFieldHandler extends AbstractHandler
{
    public function supports(FieldContext $context): int
    {
        return $this->dbFieldIs($context, DBJSONText::class) ? 30 : 0;
    }

    public function describe(FieldContext $context): array
    {
        $schema = $this->schema($context);
        if ($schema) {
            return $schema;
        }

        return [
            'type' => ['object', 'array', 'string', 'number', 'integer', 'boolean', 'null'],
            'description' => 'Any JSON value. Read the current value first and keep its structure.',
        ];
    }

    public function read(FieldContext $context): mixed
    {
        $value = $context->getRecordValue();
        if (!is_string($value) || $value === '') {
            return $value === '' ? null : $value;
        }

        return json_validate($value) ? json_decode($value, true) : $value;
    }

    public function write(FieldContext $context, mixed $value): void
    {
        $schema = $this->schema($context);
        if ($value === [] && ($schema['type'] ?? null) === 'object') {
            $value = new \stdClass();
        }
        if ($schema) {
            $this->validate($context, $value, $schema);
        }
        $json = $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw $this->invalid($context, 'the value cannot be encoded as JSON');
        }
        $this->requireRecord($context)->setField($context->name, $json);
    }

    /**
     * JSON Schema configured for the field in the class's `mcp_json_schemas` config.
     *
     * @return array<string, mixed>|null
     */
    protected function schema(FieldContext $context): ?array
    {
        $schemas = (array) DataObject::singleton($context->class)->config()->get('mcp_json_schemas');
        $schema = $schemas[$context->name] ?? null;

        return is_array($schema) && $schema ? $schema : null;
    }

    /**
     * @param array<string, mixed> $schema
     */
    protected function validate(FieldContext $context, mixed $value, array $schema): void
    {
        $data = json_decode((string) json_encode($value));
        $result = (new Validator())->validate($data, (object) json_decode((string) json_encode($schema)));
        $error = $result->error();
        if (!$error) {
            return;
        }
        $messages = [];
        foreach ((new ErrorFormatter())->format($error, false) as $path => $message) {
            $messages[] = sprintf('%s: %s', $path, $message);
        }
        throw $this->invalid($context, implode('; ', $messages));
    }
}
