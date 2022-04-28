<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Module\cirrusgeneral\Auth\AuthProcRuleInserter;

/**
 * Conditionally create new authproc filters at the location of this filter
 */
abstract class BaseConditionalAuthProcInserter extends ProcessingFilter
{

    protected array $authProcs;

    protected array $elseAuthProcs;


    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $conf = Configuration::loadFromArray($config);
        $this->authProcs = $conf->getArray('authproc', []);
        $this->elseAuthProcs = $conf->getArray('elseAuthproc', []);
    }


    public function process(&$state)
    {
        if ($this->shouldAddFilters($state)) {
            $filtersToAdd = $this->authProcs;
        } else {
            $filtersToAdd = $this->elseAuthProcs;
        }
        $ruleInserter = new AuthProcRuleInserter();
        $ruleInserter->createAndInsertFilters($state, $filtersToAdd);
    }

    /**
     * @return bool true indicate filters should be added. false if they should not be added.
     */
    abstract protected function shouldAddFilters(array &$state): bool;
}
