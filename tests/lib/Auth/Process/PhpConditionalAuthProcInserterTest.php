<?php

namespace Test\SimpleSAML\Auth\Process;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Module\cirrusgeneral\Auth\Process\AttributeRemove;
use SimpleSAML\Module\cirrusgeneral\Auth\Process\PhpConditionalAuthProcInserter;
use SimpleSAML\Module\core\Auth\Process\AttributeAdd;
use SimpleSAML\Module\core\Auth\Process\AttributeLimit;

class PhpConditionalAuthProcInserterTest extends TestCase
{

    /**
     * @dataProvider falseConditionProvider
     * @param array|null $elseAuthProcConfig if any elseAuthproc confiugrations should be sent to filter
     * @param array $expectedClasses The class names expected in the authproc state
     * @return void
     * @throws \SimpleSAML\Error\Exception
     */
    public function testFalseCondition(?array $elseAuthProcConfig, array $expectedClasses): void
    {
        $config = [
            //php code
            'condition' => 'return false;',
            //authprocs
            'authproc' => [
                [
                    'class' => 'core:AttributeAdd',
                    'source' => array('myidp'),
                ],
            ]
        ];
        if (!is_null($elseAuthProcConfig)) {
            $config['elseAuthproc'] = $elseAuthProcConfig;
        }
        $limitConfig = [];
        $state = [
            'Attributes' => [],
            ProcessingChain::FILTERS_INDEX => [
                new AttributeLimit($limitConfig, [])
            ]
        ];
        $filter = new PhpConditionalAuthProcInserter($config, []);
        $filter->process($state);
        $this->assertCount(count($expectedClasses), $state[ProcessingChain::FILTERS_INDEX]);
        $counter = 0;
        foreach ($expectedClasses as $expectedClass) {
            $this->assertInstanceOf($expectedClass, $state[ProcessingChain::FILTERS_INDEX][$counter++]);
        }
    }

    public function falseConditionProvider(): array
    {
        return [
            [null, [AttributeLimit::class]],
            [[], [AttributeLimit::class]],
            [
                [
                    [
                        'class' => 'cirrusgeneral:AttributeRemove',
                    ],
                ],
                [AttributeRemove::class, AttributeLimit::class]
            ]
        ];
    }

    /**
     * @dataProvider trueConditionProvider
     * @param array|null $authProcConfig
     * @param array $expectedClasses
     * @return void
     * @throws \SimpleSAML\Error\Exception
     */
    public function testTrueCondition(?array $authProcConfig, array $expectedClasses)
    {
        $config = [
            //php code
            //phpcs:ignore Generic.Files.LineLength.TooLong
            'condition' => 'return $state["saml:sp:State"]["saml:sp:AuthnContext"] === "https://refeds.org/profile/mfa";',
            'elseAuthproc' => [
                [
                    'class' => 'core:AttributeMap',
                ],
            ]
        ];
        if (!is_null($authProcConfig)) {
            $config['authproc'] = $authProcConfig;
        }
        $limitConfig = [];
        $state = [
            "saml:sp:State" => ['saml:sp:AuthnContext' => 'https://refeds.org/profile/mfa'],
            'Attributes' => [],
            ProcessingChain::FILTERS_INDEX => [
                new AttributeLimit($limitConfig, [])
            ]
        ];
        $filter = new PhpConditionalAuthProcInserter($config, []);
        $filter->process($state);
        $this->assertCount(count($expectedClasses), $state[ProcessingChain::FILTERS_INDEX]);
        $counter = 0;
        foreach ($expectedClasses as $expectedClass) {
            $this->assertInstanceOf($expectedClass, $state[ProcessingChain::FILTERS_INDEX][$counter++]);
        }
    }

    public function trueConditionProvider(): array
    {
        return [
            [null, [AttributeLimit::class]],
            [[], [AttributeLimit::class]],
            [[
                [
                    'class' => 'core:AttributeAdd',
                ]
            ], [AttributeAdd::class, AttributeLimit::class]],
        ];
    }
}
