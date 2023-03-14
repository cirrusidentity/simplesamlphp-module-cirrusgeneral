<?php

namespace Test\SimpleSAML\Auth\Process;

use CirrusIdentity\SSP\Test\Capture\RedirectException;
use CirrusIdentity\SSP\Test\InMemoryStore;
use CirrusIdentity\SSP\Test\MockHttpBuilder;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\State;
use SimpleSAML\Error\NoState;
use SimpleSAML\Module\cirrusgeneral\Auth\Process\PromptAttributeRelease;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

class PromptAttributeReleaseTest extends TestCase
{
    private array $state = [
        'Attributes' => [
            'someAttribute' => ['val1', 'val2', 'val3'],
            'singleValue' => ['single'],
            'noValues' => [],
        ]
    ];

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
        $expectedUrl = 'http://localhost/simplesaml/module.php/cirrusgeneral/prompt.php';
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

    public function testHandleRequestNoState(): void
    {
        $this->expectException(NoState::class);
        $request = Request::create(
            '/simplesaml/module.php/cirrusgeneral/prompt.php',
            'GET',
            ['StateId' => 'myStateId']
        );

        PromptAttributeRelease::handleRequest($request);
    }

    public function testHandleRequestShowTemplate(): void
    {
        // Setup existing state and process a request
        $stateId = $this->setRequestHandlerState();
        $request = Request::create(
            '/simplesaml/module.php/cirrusgeneral/prompt.php',
            'GET',
            ['StateId' => $stateId]
        );

        $response = PromptAttributeRelease::handleRequest($request);
        $this->assertInstanceOf(Template::class, $response);

        $data = $response->data;
        $this->assertEquals('someAttribute', $data['attributeName']);
        $this->assertEquals(['val1', 'val2', 'val3'], $data['attributeValues']);
        $this->assertEquals([
            'val1' => 'Value',
            'val3' => 'ValueB'
        ], $data['attributeLabels']);
        $this->assertArrayNotHasKey('errorMessage', $data);
    }

    public function invalidSubmitProvider(): array
    {
        return [
            ['Invalid attribute selected', null, null],
            ['Invalid attribute selected', 'wrongAttribute', null],
            ['Invalid value selected', 'someAttribute', 'invalidValue'],
        ];
    }

    /**
     * @dataProvider invalidSubmitProvider
     *
     * @param string $expectedErrorMsg
     * @param string|null $attributeName
     * @param string|null $attributeValue
     *
     * @throws \SimpleSAML\Error\BadRequest
     */
    public function testSubmitWithInvalidData(
        string $expectedErrorMsg,
        string $attributeName = null,
        string $attributeValue = null
    ): void {
        // Setup existing state and process a request
        $stateId = $this->setRequestHandlerState();
        $queryParams = [
            'StateId' => $stateId,
            'name' => $attributeName,
            'value' => $attributeValue
        ];
        $request = Request::create(
            '/simplesaml/module.php/cirrusgeneral/prompt.php',
            'GET',
            $queryParams
        );
        $response = PromptAttributeRelease::handleRequest($request);
        $this->assertInstanceOf(Template::class, $response);

        $data = $response->data;
        $this->assertEquals('someAttribute', $data['attributeName']);
        $this->assertEquals(['val1', 'val2', 'val3'], $data['attributeValues']);
        $this->assertEquals([
            'val1' => 'Value',
            'val3' => 'ValueB'
        ], $data['attributeLabels']);
        $this->assertEquals($expectedErrorMsg, $data['errorMessage']);
    }

    /**
     * @return void
     */
    public function testValidSubmitFiltersAttributes()
    {
        // Setup existing state and process a request
        $stateId = $this->setRequestHandlerState();
        $queryParams = [
            'StateId' => $stateId,
            'name' => 'someAttribute',
            'value' => 'val2'
        ];
        $request = Request::create(
            '/simplesaml/module.php/cirrusgeneral/prompt.php',
            'GET',
            $queryParams
        );
        // On successful processing of the submission the rest of authprocs run and user is redirect
        try {
            PromptAttributeRelease::handleRequest($request);
            $this->fail('Redirect exception expected');
        } catch (RedirectException $e) {
            $this->assertEquals('redirectTrustedURL', $e->getMessage());
            $this->assertEquals('test_finished_authprocs', $e->getUrl());
        }
        $storedState = State::loadState($stateId, ProcessingChain::COMPLETED_STAGE);
        $this->assertEquals(['val2'], $storedState['Attributes']['someAttribute']);
    }

    private function setRequestHandlerState(): string
    {
        $state = [
            'cirrusgeneral:prompt' => [
                'attributeName' => 'someAttribute',
                'values' => ['val1', 'val2', 'val3'],
                'attributeLabels' => [
                    'val1' => 'Value',
                    'val3' => 'ValueB'
                ],
                'displayAttributeValue' => true
            ],
            'Attributes' => [
                'someAttribute' => ['val1', 'val2', 'val3']
            ],
            // Handle continuing processing of chain after valid submission
            '\SimpleSAML\Auth\ProcessingChain.filters' => [],
            'ReturnURL' => 'test_finished_authprocs'
        ];
        $stateId = State::saveState($state, PromptAttributeRelease::$STATE_STAGE);
        return $stateId;
    }
}
