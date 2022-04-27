<?php

namespace Test\SimpleSAML\Auth\Process;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Module\cirrusgeneral\Auth\Process\PhpConditionalAuthProcInserter;
use SimpleSAML\Module\core\Auth\Process\AttributeAdd;
use SimpleSAML\Module\core\Auth\Process\AttributeLimit;

class PhpConditionalAuthProcInserterTest extends TestCase
{

    public function testFalseConditionMakesNoChange()
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
        $limitConfig = [];
        $state = [
            'Attributes' => [],
            ProcessingChain::FILTERS_INDEX => [
                new AttributeLimit($limitConfig, [])
            ]
        ];
        $filter = new PhpConditionalAuthProcInserter($config, []);
        $filter->process($state);
        // Confirm no changes to authproc filters
        $this->assertCount(1, $state[ProcessingChain::FILTERS_INDEX]);
        $this->assertInstanceOf(AttributeLimit::class, $state[ProcessingChain::FILTERS_INDEX][0]);
    }

    public function testTrueConditionInsertsFilters()
    {
        $config = [
            //php code
            'condition' => 'return $state["saml:sp:AuthnContext"] !== "https://refeds.org/profile/mfa";',
            //authprocs
            'authproc' => [
                [
                    'class' => 'core:AttributeAdd',
                    'mfaStillRequired' => array('true'),
                ],
            ]
        ];
        $limitConfig = [];
        $state = [
            'saml:sp:AuthnContext' => 'someContext',
            'Attributes' => [],
            ProcessingChain::FILTERS_INDEX => [
                new AttributeLimit($limitConfig, [])
            ]
        ];
        $filter = new PhpConditionalAuthProcInserter($config, []);
        $filter->process($state);
        // Confirm no changes to authproc filters
        $this->assertCount(2, $state[ProcessingChain::FILTERS_INDEX]);
        $this->assertInstanceOf(AttributeAdd::class, $state[ProcessingChain::FILTERS_INDEX][0]);
        $this->assertInstanceOf(AttributeLimit::class, $state[ProcessingChain::FILTERS_INDEX][1]);
    }
}
