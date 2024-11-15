<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    private ?string $labelsFromAttribute = null;

    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);
        $this->attributeName = $config->getString('attribute');
        $this->displayAttributeValue = $config->getBoolean('displayAttributeValue', true);
        $this->labels = $config->getArray('labels', []);
        $this->labelsFromAttribute = $config->getString('labelsFromAttribute', null);

    }

    public function process(&$state)
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

        if (is_string($this->labelsFromAttribute)) {
            if (array_key_exists($this->labelsFromAttribute, $state['Attributes'])) {
                $labelValues = $attributes[$this->labelsFromAttribute];
                $attributeValues = $attributes[$this->attributeName];
                try {
                    $labelMap = array_combine($attributeValues, $labelValues);
                    if ($labelMap === false) {
                        Logger::info('Label and attributes have different number of values. Not setting labels');
                    } else {
                        $this->labels = $labelMap;
                    }
                } catch (\Exception $ex) {
                    // array_combine throws error in php8 if arrays not the same size
                    Logger::info('Label and attributes have different number of values. Not setting labels');
                }

            } else {
                Logger::info('User does not have prompt label attribute ' . $this->labelsFromAttribute);
            }
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
        $url = Module::getModuleURL('cirrusgeneral/prompt.php');
        HTTP::redirectTrustedURL($url, ['StateId' => $id]);
    }


    public static function processResponse(array $state, ?string $attributeName, ?string $attributeValue): Response
    {
        $allowedAttribute =  $state['cirrusgeneral:prompt']['attributeName'];
        if ($attributeName !== $allowedAttribute) {
            Logger::info("prompt: invalid attribute selected. Allowed '$allowedAttribute', selected '$attributeName'");
            return self::generateTemplate($state, "Invalid attribute selected");
        }
        if (!in_array($attributeValue, $state['Attributes'][$attributeName])) {
            Logger::info("prompt: invalid value selected. For '$allowedAttribute' invalid value '$attributeValue'");
            return self::generateTemplate($state, "Invalid value selected");
        }

        $state['Attributes'][$attributeName] = [$attributeValue];
        ProcessingChain::resumeProcessing($state);
        assert(false);
    }

    public static function generateTemplate(array $state, string $errorMessage = null): Template
    {
        $globalConfig = Configuration::getInstance();
        $t = new Template($globalConfig, 'cirrusgeneral:prompt.php');
        $t->data['attributeName'] = $state['cirrusgeneral:prompt']['attributeName'];
        $t->data['attributeValues'] = $state['cirrusgeneral:prompt']['values'];
        $t->data['attributeLabels'] = $state['cirrusgeneral:prompt']['attributeLabels'];
        $t->data['displayAttributeValue'] = $state['cirrusgeneral:prompt']['displayAttributeValue'];
        if ($errorMessage) {
            $t->data['errorMessage'] = $errorMessage;
        }
        return $t;
    }

    public static function handleRequest(Request $request = null): Template
    {
        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        $stateId = $request->query->get('StateId');
        if (is_null($stateId)) {
            throw new BadRequest(
                'Missing required StateId query parameter.'
            );
        }

        $state = State::loadState($stateId, PromptAttributeRelease::$STATE_STAGE);

        // Check if user submitted or is viewing
        if ($request->query->has('name')) {
            return self::processResponse($state, $request->query->get('name'), $request->query->get('value'));
        } else {
            return self::generateTemplate($state);
        }
    }
}
