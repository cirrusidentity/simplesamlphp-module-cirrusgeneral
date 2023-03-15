<?php

namespace SimpleSAML\Module\cirrusgeneral\Utils;

class AttributeUtils
{
    /**
     * Combine different attribute arrays together and make the contents unique.
     * @param array $attributeArrays Each element is an array of attributes
     * @return array The resulting merged and unique array
     */
    public function mergeAndUniquify(array $attributeArrays): array
    {
        if (empty($attributeArrays)) {
            return [];
        } elseif (count($attributeArrays) === 1) {
            return $attributeArrays[0];
        }

        $attributesToSend = array_shift($attributeArrays);
        foreach ($attributeArrays as $allowedByFilter) {
            $attributesToSend = array_merge_recursive($attributesToSend, $allowedByFilter);
        }
        foreach ($attributesToSend as $name => $values) {
            // Make the values unique and then renumber the indexes
            $attributesToSend[$name] = array_values(array_unique($values));
        }
        return $attributesToSend;
    }
}
