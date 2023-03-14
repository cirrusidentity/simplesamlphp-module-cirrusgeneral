<?php

/**
 * Created by PhpStorm.
 * User: patrick
 * Date: 9/27/18
 * Time: 4:49 PM
 */

namespace Test\SimpleSAML\Auth\Process;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\cirrusgeneral\Auth\Process\AttributeSplitter;

class AttributeSplitterTest extends TestCase
{
    protected function setup(): void
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(__DIR__, 3) . '/config');
    }

    public function testNoStateAttributes(): void
    {
        $state = ['A' => 'B'];
        $config = [
            'attributes' => ['key0', 'key1', 'key2']
        ];
        $filter = new AttributeSplitter($config, null);
        $filter->process($state);

        $this->assertEquals(['A' => 'B'], $state);
    }

    public function testNoSplitAttributesDefined(): void
    {
        $config = [
        ];
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches("/Could not retrieve the required option 'attributes'/");
        new AttributeSplitter($config, null);
    }

    public function testSplittingOnDefaults(): void
    {
        $state = [
            'Attributes' => [
                'key1' => ['a,b,, long-value,'],
                'key2' => ['a,b', ' ', ',', 'b, no-duplicates', 'with white space'],
                'nochanges' => ["Here, in this value, we shouldn't process"]
            ]
        ];
        $config = [
            'attributes' => ['key0', 'key1', 'key2']
        ];

        $expectedResults = [
            'key1' => ['a', 'b', 'long-value'],
            'key2' => ['a', 'b', 'no-duplicates', 'with white space'],
            'nochanges' => ["Here, in this value, we shouldn't process"]
        ];

        $filter = new AttributeSplitter($config, null);
        $filter->process($state);

        $this->assertEquals($expectedResults, $state['Attributes']);
    }

    public function testAlternateDelimeter(): void
    {
        $state = [
            'Attributes' => [
                'key1' => ['a|b|| long-value|'],
                'nochanges' => ["Here| in this value| we shouldn't process"]
            ]
        ];
        $config = [
            'attributes' => 'key1',
            'delimiter' => '|'
        ];

        $expectedResults = [
            'key1' => ['a', 'b', 'long-value'],
            'nochanges' => ["Here| in this value| we shouldn't process"]
        ];

        $filter = new AttributeSplitter($config, null);
        $filter->process($state);

        $this->assertEquals($expectedResults, $state['Attributes']);
    }
}
