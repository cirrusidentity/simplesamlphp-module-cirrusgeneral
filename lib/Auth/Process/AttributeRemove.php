<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;

/**
 * Similar to AttributeLimit but instead of creating an allow list of attributes to release, it
 * is a deny list of attributes to remove. Useful when used with Azure AD since Azure AD releases
 * several additional claims (like tenant) in addition to claims configured by the user.
 * Class AttributeRemove
 * @package SimpleSAML\Module\cirrusgeneral\Auth\Process
 */
class AttributeRemove extends ProcessingFilter
{

    /**
     * The names of attributes to remove
     * @var string[]
     */
    private array $attributes;

    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);
        $this->attributes = $config->getArrayizeString('attributes');
    }

    /**
     * @inheritDoc
     */
    public function process(&$request)
    {
        $request['Attributes'] = array_diff_key($request['Attributes'], array_flip($this->attributes));
    }
}
