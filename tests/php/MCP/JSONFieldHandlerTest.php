<?php

namespace SLONline\ORM\FieldType\Tests\MCP;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SLONline\MCP\Exception\MCPException;
use SLONline\MCP\Exposure\ClassExposure;
use SLONline\MCP\Fields\FieldContext;
use SLONline\MCP\Fields\FieldHandler;
use SLONline\MCP\Fields\FieldHandlerRegistry;

/**
 * Tests the MCP field handler for JSON fields.
 *
 * @author    Lubos Odraska <odraska@slonline.sk>
 * @copyright Copyright (c) 2026, SLONline, s.r.o.
 */
class JSONFieldHandlerTest extends SapphireTest
{
    protected static $extra_dataobjects = [JSONTestRecord::class];

    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        if (!interface_exists(FieldHandler::class)) {
            $this->markTestSkipped('slonline/silverstripe-mcp is not installed');
        }
        Config::modify()->set(ClassExposure::class, 'allowed_classes', ['JSON' => JSONTestRecord::class]);
        ClassExposure::singleton()->flush();
        FieldHandlerRegistry::singleton()->flush();
    }

    /**
     * @return array{0: string, 1: FieldHandler, 2: FieldContext}
     */
    private function field(JSONTestRecord $record, string $name): array
    {
        $registry = FieldHandlerRegistry::singleton();
        $context = $registry->getFieldContext(JSONTestRecord::class, $name)->withRecord($record);
        [$key, $handler] = $registry->resolve($context);

        return [$key, $handler, $context];
    }

    public function testHandlerAndSchema(): void
    {
        $record = JSONTestRecord::create();
        [$key, $handler, $context] = $this->field($record, 'Data');
        $this->assertSame('json', $key);
        $this->assertContains('object', $handler->describe($context)['type']);
        [$key, $handler, $context] = $this->field($record, 'Opening');
        $this->assertSame('json', $key);
        $this->assertSame('Opening hours per weekday', $handler->describe($context)['description']);
    }

    public function testRoundTrip(): void
    {
        $record = JSONTestRecord::create();
        [, $handler, $context] = $this->field($record, 'Data');
        $value = ['name' => 'Typofix – ľ', 'tags' => [1, 2], 'url' => 'https://x.sk/a'];
        $handler->write($context, $value);
        $this->assertSame('{"name":"Typofix – ľ","tags":[1,2],"url":"https://x.sk/a"}', $record->getField('Data'));
        $record->write();

        $record = JSONTestRecord::get()->byID($record->ID);
        [, $handler, $context] = $this->field($record, 'Data');
        $this->assertSame($value, $handler->read($context));
    }

    public function testScalarStringsStayStrings(): void
    {
        $record = JSONTestRecord::create();
        [, $handler, $context] = $this->field($record, 'Data');
        $handler->write($context, '123');
        $this->assertSame('"123"', $record->getField('Data'));
        $this->assertSame('123', $handler->read($context));
        $handler->write($context, null);
        $this->assertNull($handler->read($context));
    }

    public function testSchemaValidation(): void
    {
        $record = JSONTestRecord::create();
        [, $handler, $context] = $this->field($record, 'Opening');
        $handler->write($context, ['days' => [['day' => 'mon', 'from' => '09:00']]]);
        $this->assertSame(['days' => [['day' => 'mon', 'from' => '09:00']]], $handler->read($context));

        try {
            $handler->write($context, ['days' => [['day' => 'sun', 'from' => '9']]]);
            $this->fail('expected an exception');
        } catch (MCPException $e) {
            $this->assertStringContainsString('/days/0/day', $e->getMessage());
        }
        $this->assertSame(['days' => [['day' => 'mon', 'from' => '09:00']]], $handler->read($context));
    }

    public function testEmptyObject(): void
    {
        $record = JSONTestRecord::create();
        [, $handler, $context] = $this->field($record, 'Opening');
        $this->expectException(MCPException::class);
        $this->expectExceptionMessage('days');
        $handler->write($context, []);
    }
}
