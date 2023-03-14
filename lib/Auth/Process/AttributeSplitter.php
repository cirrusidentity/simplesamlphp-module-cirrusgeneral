<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;

/**
 * Some upstream IdPs provide multi-valued attributes as a comma separated values instead of as multiple values.
 * This filter aid in splitting such values and updating the user's state to have multiple values
 * Class AttributeSplitter
 * @package SimpleSAML\Module\cirrusgeneral\Auth\Process
 */
class AttributeSplitter extends ProcessingFilter
{
    /**
     * The pattern to break the values on.
     * @var string
     */
    private string $delimiter;

    /**
     * The attribute names/keys that need their values split.
     * @var array
     */
    private array $attributes;

    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);
        $this->delimiter = $config->getOptionalString('delimiter', ',');
        $this->attributes = $config->getArrayizeString('attributes');
    }

    /**
     * Process a request.
     *
     * When a filter returns from this function, it is assumed to have completed its task.
     *
     * @param array &$state The request we are currently processing.
     */
    public function process(array &$state): void
    {
        if (!array_key_exists('Attributes', $state)) {
            return;
        }
        $requestAttributes = &$state['Attributes'];
        foreach ($this->attributes as $attributeKey) {
            if (!array_key_exists($attributeKey, $requestAttributes)) {
                continue;
            }
            $splitValues = [];
            foreach ($requestAttributes[$attributeKey] as $splittableValue) {
                $result = explode($this->delimiter, $splittableValue);
                if ($result === false) {
                    Logger::error("Unable to explode attributes on delimiter '{$this->delimiter}'");
                    continue;
                }
                $splitValues = array_merge($splitValues, $result);
            }
            // trim, filter blanks, make unique and renumber indexes
            $requestAttributes[$attributeKey] = array_values(
                array_unique(array_filter(array_map('trim', $splitValues)))
            );
        }
    }
}
