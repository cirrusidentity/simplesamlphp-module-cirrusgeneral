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
            'attr2' => ['val3']
        ]
    ];
    public function setup()
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(dirname(dirname(__DIR__))) . '/config');
    }

    public function testNoAttributesToRemove()
    {

        $config = [
            'attributes' => []
        ];
        $state = $this->initialState;
        $filter = new AttributeRemove($config, null);
        $filter->process($state);
        $this->assertEquals($this->initialState, $state);
    }

    public function testAttributesToRemoveNoMatch()
    {

        $config = [
            'attributes' => ['noMatch1', 'noMatch2']
        ];
        $state = $this->initialState;
        $filter = new AttributeRemove($config, null);
        $filter->process($state);
        $this->assertEquals($this->initialState, $state);
    }

    public function testRemoveAllAttributes()
    {

        $config = [
            'attributes' => ['attr1', 'attr2']
        ];
        $state = $this->initialState;
        $filter = new AttributeRemove($config, null);
        $filter->process($state);
        $this->assertEquals([], $state['Attributes']);
    }

    public function testRemoveSingleAttribute()
    {

        $config = [
            'attributes' => ['attr2']
        ];
        $state = $this->initialState;
        $filter = new AttributeRemove($config, null);
        $filter->process($state);
        $this->assertEquals(['attr1' => ['val1', 'val2']], $state['Attributes']);
    }

}
