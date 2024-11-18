<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;

class MapAttributeNameFromAttributeValue extends ProcessingFilter
{
    private string $valueAttribute;

    private string $destinationAttribute;

    private string $srcAttributePrefix;

    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);
        $this->valueAttribute = $config->getString('valueAttribute');
        $this->destinationAttribute = $config->getString('destinationAttribute');
        $this->srcAttributePrefix = $config->getString('srcAttributePrefix', '');
    }

    public function process(&$state)
    {
        $attributes = &$state['Attributes'];
        if (!empty($attributes[$this->valueAttribute])) {
            $value = $attributes[$this->valueAttribute][0];
            $srcAttribute = "{$this->srcAttributePrefix}$value";
            $srcAttributeValues = $attributes[$srcAttribute] ?? [];
            if (!empty($srcAttributeValues)) {
                $attributes[$this->destinationAttribute] = $srcAttributeValues;
            }
        }
    }
}
