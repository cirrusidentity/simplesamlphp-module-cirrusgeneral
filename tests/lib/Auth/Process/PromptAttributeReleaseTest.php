<?php

namespace Test\SimpleSAML\Auth\Process;

use CirrusIdentity\SSP\Test\Capture\RedirectException;
use CirrusIdentity\SSP\Test\InMemoryStore;
use CirrusIdentity\SSP\Test\MockHttpBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\State;
use SimpleSAML\Module\cirrusgeneral\Auth\Process\PromptAttributeRelease;
use SimpleSAML\Utils\HTTP;

class PromptAttributeReleaseTest extends TestCase
{
    private array $state = [
        'Attributes' => [
            'someAttribute' => ['val1', 'val2', 'val3'],
            'singleValue' => ['single'],
            'noValues' => [],
        ]
    ];
    /**
     * @var MockObject&HTTP
     */
    private $mockHttp;

    protected function setup(): void
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(dirname(dirname(__DIR__))) . '/config');
        $this->mockHttp = MockHttpBuilder::createHttpMockFromTestCase($this);
    }

    protected function tearDown(): void
    {
        InMemoryStore::clearInternalState();
    }

    public function testUserHasNoAttributeToPrompt(): void
    {
        $expectedState = $this->state;
        $config = [
            'attribute' => 'singleValue',
            'labels' => []
        ];
        $filter = new PromptAttributeRelease($config, null);
        $filter->setHttp($this->mockHttp);
        $filter->process($this->state);
        // Confirming the authproc did not do anything
        $this->assertEquals($expectedState, $this->state);
    }

    public function testSingleValueDoesNotPrompt(): void
    {
        $expectedState = $this->state;
        $config = [
            'attribute' => 'singleValue',
            'labels' => []
        ];
        $filter = new PromptAttributeRelease($config, null);
        $filter->setHttp($this->mockHttp);
        $filter->process($this->state);
        // Confirming the authproc did not do anything
        $this->assertEquals($expectedState, $this->state);
    }

    public function testNoValueDoesNotPrompt(): void
    {
        $expectedState = $this->state;
        $config = [
            'attribute' => 'noValues',
            'labels' => []
        ];
        $filter = new PromptAttributeRelease($config, null);
        $filter->setHttp($this->mockHttp);
        $filter->process($this->state);
        // Confirming the authproc did not do anything
        $this->assertEquals($expectedState, $this->state);
    }

    /**
     * @return void
     */
    public function testThatPromptSetStateAndRedirects()
    {
        $expectedUrl = 'http://localhost/simplesaml/module.php/cirrusgeneral/prompt';
        $config = [
            'attribute' => 'someAttribute',
            'labels' => [
                'val1' => 'Value',
                'val3' => 'ValueB'
            ]
        ];
        $filter = new PromptAttributeRelease($config, null);
        $stateId = null;
        try {
            $filter->setHttp($this->mockHttp);
            $filter->process($this->state);
            $this->fail('Redirect exception expected');
        } catch (RedirectException $e) {
            $this->assertEquals('redirectTrustedURL', $e->getMessage());
            $this->assertEquals(
                $expectedUrl,
                $e->getUrl(),
                "First argument should be the redirect url"
            );
            $this->assertArrayHasKey('StateId', $e->getParams(), "StateId is added");
            $stateId = $e->getParams()['StateId'];
        }
        $expectedPromptState = [
            'attributeName' => 'someAttribute',
            'values' => ['val1', 'val2', 'val3'],
            'attributeLabels' => [
                'val1' => 'Value',
                'val3' => 'ValueB'
            ],
            'displayAttributeValue' => true
        ];
        $storedState = State::loadState($stateId, PromptAttributeRelease::$STATE_STAGE);
        $this->assertEquals($expectedPromptState, $storedState['cirrusgeneral:prompt']);
    }
}
