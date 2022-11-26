<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;

/**
 * Class ConditionalSetAuthnContext
 * Conditionaly set the authn context based on other attributes.
 * @package SimpleSAML\Module\cirrusgeneral\Auth\Process
 */
class ConditionalSetAuthnContext extends ProcessingFilter
{
    /**
     * @var string[] the path through the request to get the values
     */
    private $path;

    /**
     * @var string the value to compare against
     */
    private $value;

    /**
     * @var string the value to assert for a context
     */
    private $contextToAssert;

    /**
     * @var array Entities that we shouldn't run this filter on.
     */
    private $ignoreForEntities;

    /**
     * The context to set if $state does not have a value at the path with a matching value
     * @var string|null
     */
    private ?string $elseContextToAssert;

    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);
        $this->path = $config->getArrayizeString('path', ',');
        $this->value = $config->getValue('value');
        $this->contextToAssert = $config->getString('contextToAssert');
        $this->elseContextToAssert = $config->getString('elseContextToAssert', null);
        $this->ignoreForEntities = $config->getArray('ignoreForEntities', []);
    }


    /**
     * Adjusts the authncontext if the the user attributes matches the above
     *
     * @param array &$request The request we are currently processing.
     */
    public function process(&$request)
    {
        $spEntityId = $request['Destination']['entityid'] ?? 'no-sp-entity-id';
        if (in_array($spEntityId, $this->ignoreForEntities)) {
            Logger::debug("No authn context changes for '$spEntityId'");
            return;
        }
        $traversedValue = $request;
        foreach ($this->path as $key) {
            if (!is_array($traversedValue)) {
                Logger::warning("Traversed path encountered non array when looking for key '$key'");
                return;
            }
            if (!array_key_exists($key, $traversedValue)) {
                return;
            }
            $traversedValue = $traversedValue[$key];
        }
        if (!is_array($traversedValue)) {
            // arrayify the values to make processing consistent
            $traversedValue = [$traversedValue];
        }
        foreach ($traversedValue as $toCheck) {
            if ($toCheck === $this->value) {
                $request['saml:AuthnContextClassRef'] = $this->contextToAssert;
                return;
            }
        }

        if (!is_null($this->elseContextToAssert)) {
            $request['saml:AuthnContextClassRef'] = $this->elseContextToAssert;
        }
    }
}
