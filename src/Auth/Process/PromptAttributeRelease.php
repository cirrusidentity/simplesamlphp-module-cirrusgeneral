<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;

/**
 * Prompt a user to pick which value of a multi-valued attribute to release to an SP.
 * Useful cases where the SP can only handle a single value. Example: an app shows
 * different behavior for student and staff. Someone with both affiliations can now
 * pick which affiliation to release to switch behaviors in the app.
 */
class PromptAttributeRelease extends ProcessingFilter
{
    public static string $STATE_STAGE = 'prompt:request';

    private string $attributeName;
    private bool $displayAttributeValue;
    private array $labels;

    private ?HTTP $http;

    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);
        $this->attributeName = $config->getString('attribute');
        $this->displayAttributeValue = $config->getOptionalBoolean('displayAttributeValue', true);
        $this->labels = $config->getOptionalArray('labels', []);
    }

    public function process(array &$state): void
    {

        $attributes = $state['Attributes'];
        if (!array_key_exists($this->attributeName, $attributes)) {
            // User doesn't have the attribute so don't prompt
            return;
        }
        if (count($attributes[$this->attributeName]) <= 1) {
            // User has a single value, no need to prompt
            return;
        }

        $promptState = [
            'attributeName' => $this->attributeName,
            'values' => $attributes[$this->attributeName],
            'attributeLabels' => $this->labels,
            'displayAttributeValue' => $this->displayAttributeValue,
        ];
        $state['cirrusgeneral:prompt'] = $promptState;

        // Save state and redirect
        $id = State::saveState($state, PromptAttributeRelease::$STATE_STAGE);
        $url = Module::getModuleURL('cirrusgeneral/prompt');
        $this->getHttp()->redirectTrustedURL($url, ['StateId' => $id]);
    }



    public function getHttp(): HTTP
    {

        if (!isset($this->http)) {
            $this->http = new HTTP();
        }
         return $this->http;
    }

    /**
     * @param HTTP|null $http
     */
    public function setHttp(?HTTP $http): void
    {
        $this->http = $http;
    }
}
