# DB JSON Field for Silverstripe 6

### Requirements
* **silverstripe/framework** ~6.0

### Installation
* Add the following code to the `require` key in `composer.json` file:
```bash
composer require slonline/jsonfield
```

### Usage

#### Datatype
```
$db = [
    ...
    'MyJSON' => DBJSONText::class,
];
```


### AI agents (MCP)

With [`slonline/silverstripe-mcp`](https://github.com/SLONline/silverstripe-mcp) installed, AI agents can read
and write `DBJSONText` fields as JSON values (objects, lists, strings, numbers, …); `null` clears the field.

Describe the expected structure with a JSON Schema so the agent knows what to write. Writes that do not match
the schema are rejected with the path of the first error:

```php
private static array $mcp_json_schemas = [
    'OpeningHours' => [
        'type' => 'object',
        'description' => 'Opening hours per weekday',
        'properties' => [
            'days' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'day' => ['type' => 'string', 'enum' => ['mon', 'tue', 'wed', 'thu', 'fri']],
                        'from' => ['type' => 'string', 'pattern' => '^\\d{2}:\\d{2}$'],
                    ],
                    'required' => ['day', 'from'],
                ],
            ],
        ],
        'required' => ['days'],
    ],
];
```

Without a schema any JSON value is accepted.
