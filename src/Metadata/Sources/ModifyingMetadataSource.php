<?php

namespace SimpleSAML\Module\cirrusgeneral\Metadata\Sources;

use SimpleSAML\Configuration;
use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Logger;
use SimpleSAML\Metadata\MetaDataStorageSource;
use SimpleSAML\Module;
use SimpleSAML\Module\cirrusgeneral\Metadata\MetadataModifyStrategy;
use SimpleSAML\Utils;

/**
 * Metadata source that can delegate to other sources and then adjust the loaded metadata
 */
class ModifyingMetadataSource extends MetaDataStorageSource
{
    /**
     * The list of strategies to run to adjust the metadata
     * @var MetadataModifyStrategy[]
     */
    private array $strategies = [];

    /**
     * Sources to delegate to for loading
     * @var MetaDataStorageSource[]
     */
    private array $delegateSources;

    public function __construct(array $sourceConfig)
    {
        parent::__construct();
        $config = Configuration::loadFromArray($sourceConfig);
        foreach ($config->getArray('strategies') as $strategyConfig) {
            $this->strategies[] = $this->resolveStrategy($strategyConfig);
        }

        $this->delegateSources = MetaDataStorageSource::parseSources($config->getArray('sources'));
    }

    /**
     * This function loads the metadata for entity IDs in $entityIds. It is returned as an associative array
     * where the key is the entity id. An empty array may be returned if no matching entities were found.
     * Subclasses should override if their getMetadataSet returns nothing or is slow. Subclasses may want to
     * delegate to getMetaDataForEntitiesIndividually if loading entities one at a time is faster.
     * @param string[] $entityIds The entity ids to load
     * @param string $set The set we want to get metadata from.
     * @return array An associative array with the metadata for the requested entities, if found.
     */
    public function getMetaDataForEntities(array $entityIds, string $set): array
    {
        if (empty($entityIds)) {
            return [];
        }

        $result = [];

        $entityIdsFlipped = array_flip($entityIds);
        $timeUtils = new Utils\Time();

        // We do not want to call the getMetdataSet here. If we do we will create an overload
        // since we will have to first load all the sources and then do any of the desired calculations.
        // Our take is to do the calculations as we go and break as soon as we do not have anything more to
        // calculate
        foreach ($this->delegateSources as $source) {
            // entityIds may be reduced to being empty in this loop or already empty
            if (empty($entityIds)) {
                break;
            }

            $entities = $source->getMetadataSet($set);

            $srcList = array_intersect_key($entities, $entityIdsFlipped);
            foreach ($srcList as $key => $le) {
                if (!empty($le['expire']) && $le['expire'] < time()) {
                    unset($srcList[$key]);
                    Logger::warning(
                        'Dropping metadata entity ' . var_export($key, true) . ', expired ' .
                        $timeUtils->generateTimestamp($le['expire']) . '.',
                    );
                    continue;
                }
                // We found the entity id so remove it from the list that needs resolving
                /** @psalm-suppress PossiblyInvalidArrayOffset */
                unset($entityIds[$entityIdsFlipped[$key]], $entityIdsFlipped[$key]);
                /** @psalm-suppress PossiblyInvalidArrayOffset */
                // Add the key to the result set
                $result[$key] = $le;
            }
        }

        return $result;
    }

    public function getMetadataSet(string $set): array
    {
        $result = [];

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

    private function resolveStrategy(array $strategyConfig): MetadataModifyStrategy
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
        /**
         * @psalm-var MetadataModifyStrategy
         */
        return new $className($strategyConfig);
    }
}
