<?php

namespace SimpleSAML\Module\cirrusgeneral\Metadata;

/**
 * Allow running configured php code to adjust metadata
 */
class PhpMetadataStrategy implements MetadataModifyStrategy
{
    /**
     * The PHP code that should be run.
     *
     * @var string
     */
    private $code;

    /**
     * PhpMetadataStrategy constructor.
     * @param array $config
     */
    public function __construct($config)
    {
        $this->code = $config['code'];
    }


    /**
     * @param ?array $metadata The existing metadata
     * @param string $entityId The entity id that is being loaded
     * @param string $set The metadata set
     * @return array|null The new metadata or null if there is none
     */
    public function modifyMetadata(?array $metadata, string $entityId, string $set): ?array
    {
        if ($metadata === null) {
            return null;
        }
        /**
         * @param array &$metadata The metadata array
         * @param string $entityId The entity Id
         * @param string $set The metadata set
         */
        $function = /** @return void */ function (
            array &$metadata,
            string $entityId,
            string $set
        ) {
            eval($this->code);
        };
        $function($metadata, $entityId, $set);

        return $metadata;
    }
}
