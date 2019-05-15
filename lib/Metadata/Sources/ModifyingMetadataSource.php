<?php

namespace SimpleSAML\Module\cirrusgeneral\Metadata\Sources;

use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Module;
use SimpleSAML\Module\cirrusgeneral\Metadata\MetadataModifyStrategy;
use SimpleSAML_Metadata_MetaDataStorageSource;

/**
 * Metadata source that can delegate to other sources and then adjust the loaded metadata
 */
class ModifyingMetadataSource extends \SimpleSAML_Metadata_MetaDataStorageSource
{
    /**
     * The list of strategies to run to adjust the metadata
     * @var MetadataModifyStrategy[]
     */
    private $strategies = [];

    /**
     * Sources to delegate to for loading
     * @var SimpleSAML_Metadata_MetaDataStorageSource[]
     */
    private $delegateSources = [];

    public function __construct(array $sourceConfig)
    {
        assert(is_array($sourceConfig));
        $config = \SimpleSAML_Configuration::loadFromArray($sourceConfig);
        foreach ($config->getArray('strategies') as $strategyConfig) {
            $this->strategies[] = $this->resolveStrategy($strategyConfig);
        }

        $this->delegateSources = SimpleSAML_Metadata_MetaDataStorageSource::parseSources($config->getArray('sources'));
    }

    public function getMetaData($index, $set)
    {
        $metadata = null;
        foreach ($this->delegateSources as $source) {
            $metadata = $source->getMetaData($index, $set);
            if (isset($metadata)) {
                break;
            }
        }
        return $this->modifyMetadata($metadata, $index, $set);
    }

    public function modifyMetadata($metadata, $entityId, $set)
    {
        if ($metadata === null) {
            return $metadata;
        }
        foreach ($this->strategies as $strategy) {
            $metadata = $strategy->modifyMetadata($metadata, $entityId, $set);
        }
        return $metadata;
    }

    private function resolveStrategy(array $strategyConfig)
    {
        $type = $strategyConfig['type'];
        try {
            $className = Module::resolveClass(
                $type,
                MetadataModifyStrategy::class
            );
        } catch (\Exception $e) {
            throw new CriticalConfigurationError(
                "Invalid 'type' for metadata strategy. Cannot find strategy '$type'.",
                null
            );
        }
        return new $className($strategyConfig);
    }
}
