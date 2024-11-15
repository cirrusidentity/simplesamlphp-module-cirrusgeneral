<?php

namespace SimpleSAML\Module\cirrusgeneral\Metadata\Sources;

use SimpleSAML\Configuration;
use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Logger;
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
        assert(is_array($sourceConfig));
        $config = Configuration::loadFromArray($sourceConfig);
        foreach ($config->getArray('strategies') as $strategyConfig) {
            $this->strategies[] = $this->resolveStrategy($strategyConfig);
        }

        $this->delegateSources = MetaDataStorageSource::parseSources($config->getArray('sources'));
    }

    public function getMetadataSet($set)
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

    public function getMetaDataForEntities(array $entityIds, $set): array
    {
        if (empty($entityIds)) {
            return [];
        }

        $result = [];

        $entityIdsFlipped = array_flip($entityIds);

        foreach ($this->delegateSources as $source) {
            // entityIds may be reduced to being empty in this loop or already empty
            if (empty($entityIds)) {
                break;
            }

            $srcList = $source->getMetaDataForEntities($entityIds, $set);
            foreach ($srcList as $key => $le) {
                if (!empty($le['expire']) && $le['expire'] < time()) {
                    unset($srcList[$key]);
                    Logger::warning(
                        "Dropping metadata entity " . var_export($key, true) . ", expired " .
                        Utils\Time::generateTimestamp($le['expire']) . "."
                    );
                    continue;
                }
                // We found the entity id so remove it from the list that needs resolving
                /** @psalm-suppress PossiblyInvalidArrayOffset */
                unset($entityIds[$entityIdsFlipped[$key]]);
                /** @psalm-suppress PossiblyInvalidArrayOffset */
                // Add the key to the result set
                $result[$key] = $le;
            }
        }

        return $result;
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
