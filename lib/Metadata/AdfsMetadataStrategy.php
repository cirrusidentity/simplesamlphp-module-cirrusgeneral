<?php

namespace SimpleSAML\Module\cirrusgeneral\Metadata;

/**
 * ADFS and Azure AD metadata often needs adjustments to work with SSP, for example the 'disable_scoping'
 * option needs to be added to stop a proxy from sending a scope element to ADFS
 */
class AdfsMetadataStrategy implements MetadataModifyStrategy
{
    private $adfsPattern = '|/adfs/|';
    private $azurePattern = '|^https://sts.windows.net/|';

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
        if ($metadata == null) {
            return $metadata;
        }
        if ($set !== 'saml20-idp-remote') {
            return $metadata;
        }
        if (preg_match($this->adfsPattern, $entityId) === 1) {
            return $this->makeAdfsAdjustments($metadata);
        } elseif (preg_match($this->azurePattern, $entityId) === 1) {
            return $this->makeAzureAdjustments($metadata);
        }
        return $metadata;
    }

    private function makeAdfsAdjustments($metadata)
    {
        // ADFS doesn't like the scoping xml in an AuthnRequest
        $metadata['disable_scoping'] = true;
        return $metadata;
    }

    private function makeAzureAdjustments($metadata)
    {
        // Azure can handle scoping xml in an AuthnRequest however it can error if the SP
        // entity ID is not a valid uri (which isn't a SAML spec requirement).
        $metadata['disable_scoping'] = true;
        return $metadata;
    }
}
