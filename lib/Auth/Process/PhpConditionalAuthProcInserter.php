<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;
use SimpleSAML\Module\cirrusgeneral\Auth\AuthProcRuleInserter;

/**
 * Conditionally create new authproc filters at the location of this filter
 */
class PhpConditionalAuthProcInserter extends BaseConditionalAuthProcInserter
{
    private string $condition;


    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $conf = Configuration::loadFromArray($config);
        $this->condition = $conf->getString('condition');
    }

    protected function checkCondition(array &$state): bool
    {
        $function = /** @return bool */ function (
            array &$attributes,
            array &$state
        ) {
            return eval($this->condition);
        };
        return $function($state['Attributes'], $state) === true;
    }
}
