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

    /**
     * The regex patterns of attributes to remove
     * @var string[]
     */
    private array $attributeRegexes;

    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);
        $this->attributes = $config->getOptionalArrayizeString('attributes', []);
        $this->attributeRegexes = $config->getOptionalArrayizeString('attributeRegexes', []);
    }

    /**
     * @inheritDoc
     */
    public function process(array &$state): void
    {
        $state['Attributes'] = array_diff_key($state['Attributes'], array_flip($this->attributes));

        foreach ($this->attributeRegexes as $regex) {
            foreach ($state['Attributes'] as $attributeName => $values) {
                /** @psalm-suppress  ArgumentTypeCoercion */
                $result = @preg_match($regex, $attributeName);
                if ($result === 1) {
                    unset($state['Attributes'][$attributeName]);
                } elseif ($result === false) {
                    Logger::WARNING("AttributeRemove: invalid regex '$regex' " . preg_last_error_msg());
                }
            }
        }
    }
}
