<?php

namespace SLONline\ORM\FieldType;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Validation\FieldValidation\StringFieldValidator;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\NullableField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBString;

/**
 * Database JSON DB Field, stored in MySQL as JSON
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2025, SLONline, s.r.o.
 */
class DBJSONText extends DBString
{
    private static array $field_validators = [
        StringFieldValidator::class => null,
    ];

    /**
     * (non-PHPdoc)
     * @see DBField::requireField()
     */
    public function requireField(): void
    {
        $charset = Config::inst()->get(MySQLDatabase::class, 'charset');
        $collation = Config::inst()->get(MySQLDatabase::class, 'collation');

        $values = 'JSON NULL';
        if (isset($this->defaultVal)) {
            $values .= ' default ' . DB::get_conn()->quoteString($this->defaultVal);
        }
        DB::require_field($this->tableName, $this->name, $values);
    }

    /**
     * @param string $title
     * @return FormField
     */
    public function scaffoldSearchField($title = null): FormField
    {
        return HiddenField::create($this->getName());
    }

    /**
     * @param string $title
     * @param string $params
     * @return FormField
     */
    public function scaffoldFormField($title = null, $params = null): FormField
    {
        if (!$this->nullifyEmpty) {
            // Allow the user to select if it's null instead of automatically assuming empty string is
            return NullableField::create(TextareaField::create($this->name, $title));
        }
        // Automatically determine null (empty string)
        return TextareaField::create($this->name, $title);
    }

    public function setValue($value, $record = null, $markChanged = true): static
    {
        if (!is_string($value) || !json_validate($value)) {
            $value = json_encode($value);
        }

        return parent::setValue($value, $record, $markChanged);
    }

    public function getValue(): mixed
    {
        if (!is_string($this->value) || !json_validate($this->value)) {
            return $this->value;
        }

        return json_decode($this->value, true);
    }

    public function scalarValueOnly(): bool
    {
        return false;
    }

    public function prepValueForDB($value): array|string|null
    {

        if (!is_string($value) || !json_validate($value)) {
            $value = json_encode($value);
        }

        return $value;
    }
}