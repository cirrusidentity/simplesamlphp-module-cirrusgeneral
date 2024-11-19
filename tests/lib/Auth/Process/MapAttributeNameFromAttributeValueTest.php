<?php

namespace lib\Auth\Process;

use SimpleSAML\Module\cirrusgeneral\Auth\Process\MapAttributeNameFromAttributeValue;

class MapAttributeNameFromAttributeValueTest extends \PHPUnit\Framework\TestCase
{
    public function mapAttributeNameProvider(): array
    {
        return [
            // no attribute picked
            [
                'attributes' => [
                    'attr' => 'val1'
                ],
                'expectedAttributes' => [
                    'attr' => 'val1'

                ]
            ],
            // chosen ID has no extra attributes
            [
                'attributes' => [
                    'studentIds' => ['abc', 'xyz'],
                    'pickedId' => 'xyz',
                    'student.abc' => 'Bob'
                ],
                'expectedAttributes' => [
                    'studentIds' => ['abc', 'xyz'],
                    'pickedId' => 'xyz',
                    'student.abc' => 'Bob'
                ]
            ],
            // chosen ID HAS extra attributes
            [
                'attributes' => [
                    'studentIds' => ['abc', 'xyz'],
                    'pickedId' => 'abc',
                    'student.abc' => 'Bob',
                ],
                'expectedAttributes' => [
                    'studentIds' => ['abc', 'xyz'],
                    'pickedId' => 'abc',
                    'student.abc' => 'Bob',
                    'student' => 'Bob'
                ]
            ],
            // chosen ID HAS extra attributes and there is existing destination attribute
            [
                'attributes' => [
                    'studentIds' => ['abc', 'xyz'],
                    'pickedId' => 'abc',
                    'student.abc' => 'Bob',
                    'student' => 'Not set yet'
                ],
                'expectedAttributes' => [
                    'studentIds' => ['abc', 'xyz'],
                    'pickedId' => 'abc',
                    'student.abc' => 'Bob',
                    'student' => 'Bob'
                ]
            ],
        ];
    }

    /**
     * @dataProvider mapAttributeNameProvider
     * @return void
     */
    public function testMapAttributeNameFromFoundValue(array $attributes, array $expectedAttributes): void
    {
        $config = [
            'valueAttribute' => 'pickedId',
            'destinationAttribute' => 'student',
            'srcAttributePrefix' => 'student.'
        ];
        $filter = new MapAttributeNameFromAttributeValue($config, []);
        $state = [
            'Attributes' => \SimpleSAML\Utils\Attributes::normalizeAttributesArray($attributes)
        ];

        $filter->process($state);

        $this->assertEquals(
            \SimpleSAML\Utils\Attributes::normalizeAttributesArray($expectedAttributes),
            $state['Attributes']
        );
    }
}
