<?php

namespace Test\SimpleSAML\Auth\Process;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\cirrusgeneral\Auth\Process\AttributeValueMapper;

class AttributeValueMapperTest extends TestCase
{
    public function setup()
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(dirname(dirname(__DIR__))) . '/config');
    }

    public function testValueMap()
    {
        $config = [
            // Files with main mappings
            'csvFile' => dirname(__DIR__, 3) . '/config/mapping-test.csv',
            'mappingLookup' => [
                // source attribute name
                'inc-eduPersonEntitlement' => [
                    // source value
                    'inc-eduPersonEntitlement-everfi' => [
                        // dest attribute      =>   [ dest val1, dest val2]
                        'eduPersonEntitlement' => ['ExtraValueMapping'],
                        'anotherMapping' => ['anotherValue'],
                    ]
                ],
                'name' => [
                    'abc' => [
                        'other' => ['extraOther']
                    ]
                ]
            ]
        ];

        $state = [
            'Attributes' => [
                'inc-eduPersonEntitlement' =>
                    ['val1', 'inc-eduPersonEntitlement-everfi', 'inc-eduPersonEntitlement-cayuse24'],
                'group-med' => ['newStudent', 'med-student'],
                'group-test' => ['newStudent', 'incoming-Student'],
                'name' => ['abc'],
                'other' => ['123'],
            ]
        ];

        $expectedAttributes = [
            // Existing attributes expected to stay
            'inc-eduPersonEntitlement' =>
                ['val1', 'inc-eduPersonEntitlement-everfi', 'inc-eduPersonEntitlement-cayuse24'],
            'group-med' => ['newStudent', 'med-student'],
            'group-test' => ['newStudent', 'incoming-Student'],
            'name' => ['abc'],
            // other gets an addition
            'other' => ['123', 'extraOther'],
            // Other attributes created from csv and mapping lookup
            'eduPersonEntitlement' => [
                'ExtraValueMapping',
                'http://alcoholedu.com/',
                'urn:mace:example.edu:eds:entitlement:exmp4ff9'
            ],
            'eduPersonAffiliation' => ['student', 'member', 'other'],
            'entitlement' => [
                'urn:blah:blahstudent-app',
                'https://example.com/checkfinacialAid',
                'urn:med:student-app'
            ],
            'anotherMapping' => ['anotherValue'],
        ];

        $filter = new AttributeValueMapper($config, null);
        $filter->process($state);
        $this->assertEquals($expectedAttributes, $state['Attributes']);
    }
}
