<?php

namespace Test\SimpleSAML\Auth\Process;

use SimpleSAML\Module\cirrusgeneral\Auth\Process\ConditionalSetAuthnContext;

class ConditionalSetAuthnContextTest extends \PHPUnit\Framework\TestCase
{
    public function setup()
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(dirname(dirname(__DIR__))) . '/config');
    }

    /**
     * @param $path array
     * @dataProvider noMatchingStatePathProvider
     */
    public function testNoMatchingStatePath(array $path, ?bool $configureElseContext = false)
    {
        $state = ['A' => 'B', 'B' => ['C' => 'D']];
        $expectedState = $state;
        $config = [
            'path' => $path,
            'value' => 'someValue',
            'contextToAssert' => 'someContext'
        ];
        if ($configureElseContext) {
            $config['elseContextToAssert'] = 'elseContext';
        }
        $filter = new ConditionalSetAuthnContext($config, null);
        $filter->process($state);

        if ($configureElseContext) {
            $this->assertArrayHasKey('saml:AuthnContextClassRef', $state);
            $this->assertEquals('elseContext', $state['saml:AuthnContextClassRef']);
        } else {
            $this->assertEquals($expectedState, $state, 'State should not change');
        }
    }

    public function noMatchingStatePathProvider(): array
    {

        return [
            [[]],
            [[], true],
            [['Z']], // no such path
            [['Z'], true], // no such path
            [['B', 'Z']], // B exists, Z is not a validpath
            [['B', 'Z'], true], // B exists, Z is not a validpath
            [['B', 'D', 'Z']], // attempt to traverse through non-array value
            [['B', 'D', 'Z'], true], // attempt to traverse through non-array value
        ];
    }

    /**
     * @dataProvider assertedContextProvider
     * @param string $value
     * @param null|string $currentContext
     * @param null|string $expectedContext
     * @param null|bool $configureElseContext If true add an else context if there is no matching value
     */
    public function testAssertedContext(
        string $value,
        ?string $currentContext,
        ?string $expectedContext,
        ?bool $configureElseContext = false
    ) {
        $state = [
            'A' => 'B',
            'B' => [
                'C' => ['D', 'F']
            ]
        ];
        if ($currentContext) {
            $state['saml:AuthnContextClassRef'] = $currentContext;
        }
        $config = [
            'path' => ['B', 'C'],
            'value' => $value,
            'contextToAssert' => 'newContext'
        ];
        if ($configureElseContext) {
            $config['elseContextToAssert'] = 'elseContext';
        }
        $filter = new ConditionalSetAuthnContext($config, null);
        $filter->process($state);

        if ($expectedContext) {
            $this->assertArrayHasKey('saml:AuthnContextClassRef', $state);
            $this->assertEquals($expectedContext, $state['saml:AuthnContextClassRef']);
        } else {
            $this->assertArrayNotHasKey('saml:AuthnContextClassRef', $state);
        }
    }

    public function assertedContextProvider(): array
    {
        //TODO: test non array values
        return [
            ['NO', null, null], // no match, no context change
            ['NO', null, 'elseContext', true], // no match, no context change
            ['D', null, 'newContext'],
            ['F', null, 'newContext'],
            ['F', null, 'newContext', false],
            ['NO', 'mycontext', 'mycontext'], // no match, no context change
            ['NO', 'mycontext', 'elseContext', true],
            ['D', 'mycontext', 'newContext'],
            ['F', 'mycontext', 'newContext'],
        ];
    }

    /**
     * @dataProvider spIgnoreListProvider
     * @param null|string $spEntityId
     */
    public function testSpIgnoreList(?string $spEntityId, $expectedContext)
    {
        $state = [
            'keyToCheck' => 'enableContext',
            'saml:AuthnContextClassRef' => 'oldContext',

        ];

        if ($spEntityId) {
            $state['Destination'] = array(
                'metadata-set' => 'saml20-sp-remote',
                'entityid' => $spEntityId,
            );
        }

        $config = [
            'path' => ['keyToCheck'],
            'value' => 'enableContext',
            'contextToAssert' => 'newContext',
            'ignoreForEntities' => ['match1', 'match2', 'other']
        ];

        $filter = new ConditionalSetAuthnContext($config, null);
        $filter->process($state);

        $this->assertArrayHasKey('saml:AuthnContextClassRef', $state);
        $this->assertEquals($expectedContext, $state['saml:AuthnContextClassRef']);
    }

    public function spIgnoreListProvider(): array
    {
        return [
            [null, 'newContext'],
            ['noMatchId', 'newContext'],
            // Ignored sps should get old context
            ['match1', 'oldContext'],
            ['match2', 'oldContext']
        ];
    }
}
