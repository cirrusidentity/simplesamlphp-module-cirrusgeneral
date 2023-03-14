<?php

namespace SimpleSAML\Module\cirrusgeneral\Metadata\Sources;

use SimpleSAML\Configuration;
use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Metadata\MetaDataStorageSource;
use SimpleSAML\Module;
use SimpleSAML\Module\cirrusgeneral\Metadata\MetadataModifyStrategy;

/**
 * Metadata source that can delegate to other sources and then adjust the loaded metadata
 */
class ModifyingMetadataSource extends MetaDataStorageSource
{
    /**
     * The list of strategies to run to adjust the metadata
     * @var MetadataModifyStrategy[]
     */
    private $strategies = [];

    /**
     * Sources to delegate to for loading
     * @var MetaDataStorageSource[]
     */
    private $delegateSources = [];

    public function __construct(array $sourceConfig)
    {
        parent::__construct();
        $config = Configuration::loadFromArray($sourceConfig);
        foreach ($config->getArray('strategies') as $strategyConfig) {
            $this->strategies[] = $this->resolveStrategy($strategyConfig);
        }

        $this->delegateSources = MetaDataStorageSource::parseSources($config->getArray('sources'));
    }

    public function getMetadataSet(string $set): array
    {
        $result = array();

        foreach ($this->delegateSources as $source) {
            $srcList = $source->getMetadataSet($set);
            /* $result is the last argument to array_merge because we want the content already
             * in $result to have precedence.
             */
            $result = array_merge($srcList, $result);
        }
        //TODO: decide if a result set should have it's metadata modified
        // or if doing that to an entire set would be too computationally expensive
        return $result;
    }


    public function getMetaData(string $entityId, string $set): ?array
    {
        $metadata = null;
        foreach ($this->delegateSources as $source) {
            $metadata = $source->getMetaData($entityId, $set);
            if (isset($metadata)) {
                break;
            }
        }
        return $this->modifyMetadata($metadata, $entityId, $set);
    }

    /**
     * @param array|null $metadata
     */
    public function modifyMetadata(?array $metadata, string $entityId, string $set)
    {
        if ($metadata === null) {
            return $metadata;
        }
        foreach ($this->strategies as $strategy) {
            $metadata = $strategy->modifyMetadata($metadata, $entityId, $set);
        }
        return $metadata;
    }

    private function resolveStrategy(array $strategyConfig): object
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
