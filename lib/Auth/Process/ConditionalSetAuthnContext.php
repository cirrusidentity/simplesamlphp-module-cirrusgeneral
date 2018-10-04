<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Logger;

/**
 * Class ConditionalSetAuthnContext
 * Conditionaly set the authn context based on other attributes.
 * @package SimpleSAML\Module\cirrusgeneral\Auth\Process
 */
class ConditionalSetAuthnContext extends \SimpleSAML_Auth_ProcessingFilter
{

    /**
     * @var string[] the path through the request to get the values
     */
    private $path;

    /**
     * @var string the value to compare against
     */
    private $value;

    private $contextToAssert;

    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = \SimpleSAML_Configuration::loadFromArray($config);
        $this->path = $config->getArrayizeString('path', ',');
        $this->value = $config->getValue('value');
        $this->contextToAssert = $config->getString('contextToAssert');
    }


    /**
     * Adjusts the authncontext if the the user attributes matches the above
     *
     * @param array &$request The request we are currently processing.
     */
    public function process(&$request)
    {
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
    }
}
