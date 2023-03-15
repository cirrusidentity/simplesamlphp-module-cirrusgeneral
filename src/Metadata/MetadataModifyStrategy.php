<?php

namespace SimpleSAML\Module\cirrusgeneral\Metadata;

interface MetadataModifyStrategy
{
    /**
     * @param ?array $metadata The existing metadata
     * @param string $entityId The entity id that is being loaded
     * @param string $set The metadata set
     * @return array|null The new metadata or null if there is none
     */
    public function modifyMetadata(?array $metadata, string $entityId, string $set): ?array;
}
