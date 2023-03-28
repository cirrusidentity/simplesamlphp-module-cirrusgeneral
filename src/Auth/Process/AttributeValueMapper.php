<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Module\cirrusgeneral\Utils\AttributeUtils;

/**
 * Allow mapping of both attribute names and values to new attribute names and values.
 * Some idps group management capabilities are limited in how the group names and values are
 * expressed and there is a need to do a large scale mapping to eduPersonEntitlement or other
 * attributes, while also adjusting the values to match urn or other style values
 * @package SimpleSAML\Module\cirrusgeneral\Auth\Process
 */
class AttributeValueMapper extends ProcessingFilter
{
    /**
     * The csv to use in mapping
     * @var ?string
     */
    private ?string $fileName;

    /**
     * Look up mappings
     * @var array
     */
    private array $mappingLookup = [];

    /**
     * AttributeValueMapper constructor.
     */
    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);
        $this->fileName = $config->getOptionalString('csvFile', null);
        $this->mappingLookup = $config->getOptionalArray('mappingLookup', []);
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
        if (isset($this->fileName)) {
            $csv = fopen($this->fileName, "r");
            while (($data = fgetcsv($csv, 1000, ",")) !== false) {
                if (count($data) < 4) {
                    continue;
                }
                // Expect 4 columns
                // sourceAttribute,sourceValue,destinationAttribute,destinationValue
                $this->mappingLookup[trim($data[0])][trim($data[1])][trim($data[2])][] = trim($data[3]);
            }
            fclose($csv);
        }
        $mappedAttributes = [];
        $attributes = $state['Attributes'];
        foreach ($this->mappingLookup as $sourceAttribute => $mapTargets) {
            if (array_key_exists($sourceAttribute, $attributes)) {
                foreach ($attributes[$sourceAttribute] as $value) {
                    if (array_key_exists($value, $mapTargets)) {
                        foreach ($mapTargets[$value] as $destinationName => $destinationValues) {
                            $mappedAttributes[$destinationName] = array_merge(
                                $mappedAttributes[$destinationName] ?? [],
                                $destinationValues
                            );
                        }
                    }
                }
            }
        }

        $attributeUtils = new AttributeUtils();
        $state['Attributes'] = $attributeUtils->mergeAndUniquify([$state['Attributes'], $mappedAttributes]);
    }
}
