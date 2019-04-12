<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Module\cirrusgeneral\Utils\AttributeUtils;

/**
 * Allow mapping of both attribute names and values to new attribute names and values.
 * Some idps group management capabilities are limited in how the group names and values are
 * expressed and there is a need to do a large scale mapping to eduPersonEntitlement or other
 * attributes, while also adjusting the values to match urn or other style values
 * @package SimpleSAML\Module\cirrusgeneral\Auth\Process
 */
class AttributeValueMapper extends \SimpleSAML_Auth_ProcessingFilter
{
    /**
     * The csv to use in mapping
     * @var string
     */
    private $fileName;

    /**
     * Look up mappings
     * @var array
     */
    private $mappingLookup = [];

    /**
     * AttributeValueMapper constructor.
     */
    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = \SimpleSAML_Configuration::loadFromArray($config);
        $this->fileName = $config->getString('csvFile', null);
        $this->mappingLookup = $config->getArray('mappingLookup', []);
    }


    /**
     * Process a request.
     *
     * When a filter returns from this function, it is assumed to have completed its task.
     *
     * @param array &$request The request we are currently processing.
     */
    public function process(&$request)
    {
        if (isset($this->fileName)) {
            $csv = fopen($this->fileName, "r");
            while (($data = fgetcsv($csv, 1000, ",")) !== false) {
                // Expect 4 columns
                // sourceAttribute,sourceValue,destinationAttribute,destinationValue
                $this->mappingLookup[trim($data[0])][trim($data[1])][trim($data[2])][] = trim($data[3]);
            }
            fclose($csv);
        }
        $mappedAttributes = [];
        $attributes = $request['Attributes'];
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
        $request['Attributes'] = $attributeUtils->mergeAndUniquify([$request['Attributes'], $mappedAttributes]);
    }
}
