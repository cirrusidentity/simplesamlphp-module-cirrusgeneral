<?php

namespace Test\SimpleSAML\Auth\Process;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\cirrusgeneral\Auth\Process\AttributeRemove;

class AttributeRemoveTest extends TestCase
{
    private array $initialState = [
        'A' => 'B',
        'Attributes' => [
            'attr1' => ['val1', 'val2'],
            'attr2' => ['val3'],
            'prefix.attr' => ['val4'],
            'prefix.attr2' => ['val5']
        ]
    ];
    protected function setup(): void
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(dirname(dirname(__DIR__))) . '/config');
    }

    public function testNoAttributesToRemove(): void
    {

        $config = [
            'attributes' => []
        ];
        $state = $this->initialState;
        $filter = new AttributeRemove($config, null);
        $filter->process($state);
        $this->assertEquals($this->initialState, $state);
    }

    public function testAttributesToRemoveNoMatch(): void
    {

        $config = [
            'attributes' => ['noMatch1', 'noMatch2']
        ];
        $state = $this->initialState;
        $filter = new AttributeRemove($config, null);
        $filter->process($state);
        $this->assertEquals($this->initialState, $state);
    }

    public function testRemoveAllAttributes(): void
    {

        $config = [
            'attributes' => ['attr1', 'attr2', 'prefix.attr', 'prefix.attr2']
        ];
        $state = $this->initialState;
        $filter = new AttributeRemove($config, null);
        $filter->process($state);
        $this->assertEquals([], $state['Attributes']);
    }

    public function testRemoveSingleAttribute(): void
    {

        $config = [
            'attributes' => ['attr2']
        ];
        $state = $this->initialState;
        $filter = new AttributeRemove($config, null);
        $filter->process($state);
        $this->assertEquals(['val1', 'val2'], $state['Attributes']['attr1']);
        $this->assertArrayNotHasKey('attr2', $state['Attributes']);
    }

    public function testRemoveByRegex(): void
    {
        $config = [
            'attributeRegexes' => ['/^prefix\./', 'bad-regex-does-nothing']
        ];
        $state = $this->initialState;
        $filter = new AttributeRemove($config, null);
        $filter->process($state);
        $this->assertEquals(
            [
                'attr1' => ['val1', 'val2'],
                'attr2' => ['val3'],
            ],
            $state['Attributes']
        );
    }
}
