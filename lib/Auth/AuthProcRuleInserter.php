<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth;

use SimpleSAML\Auth\ProcessingChain;
use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Logger;

/**
 * Aids in letting an authproc filter create and insert additional authproc filters
 */
class AuthProcRuleInserter
{
    /**
     * @param array $state
     * @psalm-param array{"\\\SimpleSAML\\\Auth\\\ProcessingChain.filters": array} $state
     * @param ProcessingFilter[] $authProcs
     */
    public function insertFilters(array &$state, array $authProcs): void
    {
        if (count($authProcs) === 0) {
            return;
        }
        Logger::debug(
            'Adding ' . count($authProcs) . ' additional filters before remaining ' . count(
                $state[ProcessingChain::FILTERS_INDEX]
            )
        );
        array_splice($state[ProcessingChain::FILTERS_INDEX], 0, 0, $authProcs);
    }

    /**
     * @param array $state
     * @psalm-param array{"\\\SimpleSAML\\\Auth\\\ProcessingChain.filters": array} $state
     * @param array $authProcConfigs
     * @return ProcessingFilter[]
     */
    public function createAndInsertFilters(array &$state, array $authProcConfigs): array
    {
        $processingChain = new \ReflectionClass(ProcessingChain::class);
        $parseMethod = $processingChain->getMethod('parseFilterList');
        $parseMethod->setAccessible(true);
        /** @var ProcessingFilter[] $filters */
        $filters = $parseMethod->invoke(null, $authProcConfigs);
        $this->insertFilters($state, $filters);
        return $filters;
    }
}
