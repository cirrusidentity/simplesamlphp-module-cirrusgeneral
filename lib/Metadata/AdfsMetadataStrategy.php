<?php

namespace SimpleSAML\Module\cirrusgeneral\Metadata;

/**
 * ADFS metadata oftens needs adjustments to work with SSP, for example the 'disable_scoping'
 * option needs to be added to stopy a proxy from sending a scope element to ADFS
 */
class AdfsMetadataStrategy implements MetadataModifyStrategy
{
    private $adfsPattern = '|/adfs/|';

    /**
     * Adjust certain operational attributes for metadata deemed to be from ADFS.
     * Example: disable sending scope element to ADFS
     * @param array $metadata The existing metadata
     * @param string $entityId The entity id that is being loaded
     * @param string $set The metadata set
     * @return array|null The new metadata or null if there is none
     */
    public function modifyMetadata($metadata, $entityId, $set)
    {
        if ($set !== 'saml20-idp-remote') {
            return $metadata;
        }
        if (preg_match($this->adfsPattern, $entityId) == 1) {
            return $this->makeAdfsAdjustments($metadata);
        }
        return $metadata;
    }

    private function makeAdfsAdjustments($metadata)
    {
        $metadata['disable_scoping'] = true;
        return $metadata;
    }
}
