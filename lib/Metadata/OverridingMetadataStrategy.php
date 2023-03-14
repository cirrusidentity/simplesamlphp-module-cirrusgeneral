<?php

/**
 * Created by PhpStorm.
 * User: patrick
 * Date: 5/13/19
 * Time: 3:08 PM
 */

namespace SimpleSAML\Module\cirrusgeneral\Metadata;

use SimpleSAML\Metadata\MetaDataStorageSource;
use SimpleSAML\Module\cirrusgeneral\Metadata\Sources\ModifyingMetadataSource;

/**
 * Loads additional metadata from an override set and combines it with
 * the regular metadata.
 */
class OverridingMetadataStrategy implements MetadataModifyStrategy
{
    private MetaDataStorageSource $source;

    /**
     * OverridingMetadataStrategy constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->source = ModifyingMetadataSource::getSource($config['source']);
    }

    /**
     * Combines metadata from an override file with existing metadata
     * @param ?array $metadata The existing metadata
     * @param string $entityId The entity id that is being loaded
     * @param string $set The metadata set
     * @return array|null The new metadata or null if there is none
     * @psalm-return ($metadata is array ? array : null)
     */
    public function modifyMetadata(?array $metadata, string $entityId, string $set): ?array
    {
        if ($metadata == null) {
            return $metadata;
        }
        $overrides = $this->source->getMetaData($entityId, $set . '-override');
        // TODO: remove operational attributes ??
        if (is_array($overrides)) {
            /**
             * @psalm-var array
             */
            return $overrides + $metadata;
        }
        return $metadata;
    }
}
