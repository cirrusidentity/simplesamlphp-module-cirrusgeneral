<?php

namespace lib\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\NoState;
use SimpleSAML\Module\cirrusgeneral\Auth\Process\PromptAttributeRelease;
use SimpleSAML\Module\cirrusgeneral\Controller\Prompt;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

class PromptTest extends TestCase
{
    private Prompt $prompt;

    private static ?array $postProcessingChainState = null;

    protected function setup(): void
    {
        self::$postProcessingChainState = null;
        Configuration::clearInternalState();
        $this->prompt = new Prompt(Configuration::getInstance(), $this->createMock(Session::class));
    }
    public function testHandleRequestNoState(): void
    {
        $this->expectException(NoState::class);
        $request = Request::create(
            '/simplesaml/module.php/cirrusgeneral/prompt.php',
            'GET',
            ['StateId' => 'myStateId']
        );

        $this->prompt->prompt($request);
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

        $response = $this->prompt->prompt($request);
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
            ['Invalid value for name or value', null, null],
            ['Invalid attribute selected', '', ''],
            ['Invalid value for name or value', 'wrongAttribute', null],
            ['Invalid attribute selected', 'wrongAttribute', ''],
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
            '/simplesaml/module.php/cirrusgeneral/prompt',
            'GET',
            $queryParams
        );
        $response = $this->prompt->prompt($request);
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
            '/simplesaml/module.php/cirrusgeneral/prompt',
            'GET',
            $queryParams
        );
        // On successful processing of the submission the rest of authprocs run and user is redirect
        try {
            $this->prompt->prompt($request);
            $this->fail('Exception expected');
        } catch (\Exception $e) {
            $this->assertEquals('Processing Chain Complete', $e->getMessage());
        }
        $this->assertNotNull(self::$postProcessingChainState);
        /** @psalm-suppress PossiblyNullArrayAccess */
        $this->assertEquals(['val2'], self::$postProcessingChainState['Attributes']['someAttribute']);
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
            'ReturnCall' => [self::class, 'processingChainComplete']
        ];
        $stateId = State::saveState($state, PromptAttributeRelease::$STATE_STAGE);
        return $stateId;
    }

    public static function processingChainComplete(array $state)
    {
        self::$postProcessingChainState = $state;
        throw new \Exception('Processing Chain Complete');
    }
}
