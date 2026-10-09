<?php

namespace SLONline\ORM\FieldType\Tests\MCP;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SLONline\ORM\FieldType\DBJSONText;

/**
 * Test record with a free JSON field and one described by a JSON Schema.
 *
 * @property string $Title
 * @property string $Data
 * @property string $Opening
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2026, SLONline, s.r.o.
 */
class JSONTestRecord extends DataObject implements TestOnly
{
    private static string $table_name = 'JSONFieldMCPTest_Record';

    private static array $db = [
        'Title' => 'Varchar',
        'Data' => DBJSONText::class,
        'Opening' => 'JSONText',
    ];

    private static array $mcp_json_schemas = [
        'Opening' => [
            'type' => 'object',
            'description' => 'Opening hours per weekday',
            'properties' => [
                'days' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'day' => ['type' => 'string', 'enum' => ['mon', 'tue', 'wed', 'thu', 'fri']],
                            'from' => ['type' => 'string', 'pattern' => '^\d{2}:\d{2}$'],
                        ],
                        'required' => ['day', 'from'],
                    ],
                ],
            ],
            'required' => ['days'],
            'additionalProperties' => false,
        ],
    ];

    public function canView($member = null)
    {
        return true;
    }

    public function canEdit($member = null)
    {
        return true;
    }
}
