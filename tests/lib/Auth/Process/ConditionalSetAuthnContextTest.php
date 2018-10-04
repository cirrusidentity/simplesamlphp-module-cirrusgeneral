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
    public function testNoMatchingStatePath(array $path)
    {
        $state = ['A' => 'B', 'B' => ['C' => 'D']];
        $expectedState = $state;
        $config = [
            'path' => $path,
            'value' => 'someValue',
            'contextToAssert' => 'someContext'
        ];
        $filter = new ConditionalSetAuthnContext($config, null);
        $filter->process($state);

        $this->assertEquals($expectedState, $state, 'State should not change');
    }

    public function noMatchingStatePathProvider(): array
    {

        return [
            [[]],
            [['Z']], // no such path
            [['B', 'Z']], // B exists, Z is not a validpath
            [['B', 'D', 'Z']], // attempt to traverse through non-array value
        ];
    }

    /**
     * @dataProvider assertedContextProvider
     * @param string $value
     * @param null|string $currentContext
     * @param null|string $expectedContext
     */
    public function testAssertedContext(string $value, ?string $currentContext, ?string $expectedContext)
    {
        $state = ['A' => 'B', 'B' => ['C' => ['D', 'F']]];
        if ($currentContext) {
            $state['saml:AuthnContextClassRef'] = $currentContext;
        }
        $config = [
            'path' => ['B', 'C'],
            'value' => $value,
            'contextToAssert' => 'newContext'
        ];
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
            ['D', null, 'newContext'],
            ['F', null, 'newContext'],
            ['NO', 'mycontext', 'mycontext'], // no match, no context change
            ['D', 'mycontext', 'newContext'],
            ['F', 'mycontext', 'newContext'],
        ];
    }
}
