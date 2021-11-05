<?php

namespace Test\SimpleSAML\Auth\Process;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\cirrusgeneral\Auth\Process\ObjectSidConverter;

class ObjectSidConverterTest extends TestCase
{
    private string $sid1 = 'S-1-5-21-113936554-849131609-2399885837-221118';
    private string $sid2 = 'S-1-5-21-113936554-849131609-2399885837-159036';

    private string $b641 = 'AQUAAAAAAAUVAAAAqojKBlm4nDINWguPvl8DAA';
    private string $b642 = 'AQUAAAAAAAUVAAAAqojKBlm4nDINWguPPG0CAA';

    /**
     * Test converting b64 values into sids
     */
    public function testConvertToSid(): void
    {
        $this->assertEquals($this->sid1, ObjectSidConverter::convertToFormattedSid($this->b641), $this->sid1);
        $this->assertEquals($this->sid1, ObjectSidConverter::convertToFormattedSid($this->b641 . '=='), $this->sid1);
        $this->assertEquals($this->sid2, ObjectSidConverter::convertToFormattedSid($this->b642), $this->sid2);

        $state = [
            'Attributes' => [
                'b64sid' => [$this->b641]
            ]
        ];
        $config = [
            'source' => 'b64sid',
            'destination' => 'sid',
            'toFormattedSid' => true,
        ];
        $filter = new ObjectSidConverter($config, null);
        $filter->process($state);
        $this->assertEquals($this->sid1, $state['Attributes']['sid'][0]);
    }

    /**
     * Test converting b64 values into sids
     */
    public function testConvertToB64(): void
    {
        // PHP pads the b64 encoding.
        $this->assertEquals($this->b641 . '==', ObjectSidConverter::convertToBase64($this->sid1), $this->sid1);
        $this->assertEquals($this->b642 . '==', ObjectSidConverter::convertToBase64($this->sid2), $this->sid2);

        $state = [
            'Attributes' => [
                'sid' => [$this->sid1]
            ]
        ];
        $config = [
            'source' => 'sid',
            'destination' => 'b64sid',
        ];
        $filter = new ObjectSidConverter($config, null);
        $filter->process($state);
        $this->assertEquals($this->b641 . '==', $state['Attributes']['b64sid'][0]);
    }

    public function testNoAttributeIsNoop()
    {
        $state = [
            'Attributes' => [
                'someAttribute' => ['a']
            ]
        ];
        $config = [
            'source' => 'sid',
            'destination' => 'b64sid',
        ];
        $expectedState = $state;
        $filter = new ObjectSidConverter($config, null);
        $filter->process($state);
        $this->assertEquals($expectedState, $state);
    }
}
