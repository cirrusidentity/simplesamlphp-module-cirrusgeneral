<?php

namespace SimpleSAML\Module\cirrusgeneral\Controller;

use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error\BadRequest;
use SimpleSAML\Logger;
use SimpleSAML\Module\cirrusgeneral\Auth\Process\PromptAttributeRelease;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

class Prompt
{
    /** @var Configuration */
    protected Configuration $config;

    /** @var Session */
    protected Session $session;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration and session for the controllers implemented here.
     *
     * @param Configuration $config The configuration to use.
     * @param Session $session The current user session.
     *
     * @throws \Exception
     */
    public function __construct(
        Configuration $config,
        Session $session
    ) {
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * @param Request $request
     * @return Response|Template|void
     */
    public function prompt(Request $request)
    {

        $stateId = $request->query->get('StateId');
        if (!is_string($stateId)) {
            throw new BadRequest(
                'Missing required StateId query parameter or is not a string.'
            );
        }

        $state = State::loadState($stateId, PromptAttributeRelease::$STATE_STAGE);

        $name = $request->query->get('name');
        $value = $request->query->get('value');
        // Check if user submitted or is viewing
        if ($request->query->has('name') || $request->query->has('value')) {
            if (is_string($name) && is_string($value)) {
                return self::processResponse($state, $name, $value);
            } else {
                return self::generateTemplate($state, 'Invalid value for name or value');
            }
        } else {
            return self::generateTemplate($state);
        }
    }

    /**
     * @param array $state
     * @param string $attributeName
     * @param string $attributeValue
     * @return Response|void
     */
    public static function processResponse(array $state, string $attributeName, string $attributeValue)
    {
        Assert::keyExists($state, 'Attributes');
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
        //TODO: move to controller
        $globalConfig = Configuration::getInstance();
        $t = new Template($globalConfig, 'cirrusgeneral:prompt');
        $t->data['stateId'] = $state[State::ID];
        $t->data['attributeName'] = $state['cirrusgeneral:prompt']['attributeName'];
        $t->data['attributeValues'] = $state['cirrusgeneral:prompt']['values'];
        $t->data['attributeLabels'] = $state['cirrusgeneral:prompt']['attributeLabels'];
        $t->data['displayAttributeValue'] = $state['cirrusgeneral:prompt']['displayAttributeValue'];
        if ($errorMessage) {
            $t->data['errorMessage'] = $errorMessage;
        }
        return $t;
    }
}
