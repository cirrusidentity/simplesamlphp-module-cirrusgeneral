<?php

namespace SimpleSAML\Module\cirrusgeneral\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Configuration;

/**
 * ActiveDirectory's objectSid can be a in a binary format or as a formatted string.
 * Sometimes you'll receive one and expect the other.
 * @package SimpleSAML\Module\cirrusgeneral\Auth\Process
 */
class ObjectSidConverter extends ProcessingFilter
{
    /**
     * @var string The attribute to convert from
     */
    private string $source;

    /**
     * @var string Destination to store after the conversion.
     */
    private string $destination;

    /**
     * @var bool convert formatted SID or base64 binary value
     */
    private bool $toFormattedSid = false;


    public function __construct(&$config, $reserved)
    {
        parent::__construct($config, $reserved);
        $config = Configuration::loadFromArray($config);
        $this->source = $config->getString('source');
        $this->destination = $config->getString('destination');
        $this->toFormattedSid = $config->getOptionalBoolean('toFormattedSid', false);
    }

    /**
     * Process a request.
     *
     * When a filter returns from this function, it is assumed to have completed its task.
     *
     * @param array &$state The request we are currently processing.
     */
    public function process(array &$state): void
    {
        $attribute = $state['Attributes'][$this->source][0] ?? null;
        if (empty($attribute)) {
            return;
        }
        $value = $this->toFormattedSid ? self::convertToFormattedSid($attribute) : self::convertToBase64($attribute);
        $state['Attributes'][$this->destination] = [$value];
    }

    public static function convertToBase64(string $value): string
    {
        // Algorithm https://ldapwiki.com/wiki/ObjectSID
        // https://www.chadsikorra.com/blog/decode-encode-objectsid
        $sid = explode('-', ltrim($value, 'S-'));

        $revisionLevel = (int) array_shift($sid);
        $identifierAuthority = (int) array_shift($sid);
        $subAuthorities = array_map('intval', $sid);

        $binaryString = pack(
            'C2xxNV*',
            $revisionLevel,
            count($subAuthorities),
            $identifierAuthority,
            ...$subAuthorities
        );

        return base64_encode($binaryString);
    }

    public static function convertToFormattedSid(string $b64EncodedValue): string
    {
        // Algorithm https://ldapwiki.com/wiki/ObjectSID
        // https://www.chadsikorra.com/blog/decode-encode-objectsid
        $bytes = base64_decode($b64EncodedValue, true);
        $sid = @unpack('C1rev/C1count/x2/N1id', $bytes);
        $subAuthorities = [];

        if (!isset($sid['id']) || !isset($sid['rev'])) {
            throw new \UnexpectedValueException(
                'The revision level or identifier authority was not found when decoding the SID.'
            );
        }

        $revisionLevel = $sid['rev'];
        $identifierAuthority = $sid['id'];
        $subs = isset($sid['count']) ? $sid['count'] : 0;

        // The sub-authorities depend on the count, so only get as many as the count, regardless of data beyond it
        for ($i = 0; $i < $subs; $i++) {
            $subAuthorities[] = unpack('V1sub', hex2bin(substr(bin2hex($bytes), 16 + ($i * 8), 8)))['sub'];
        }

        return 'S-' . $revisionLevel . '-' . $identifierAuthority . implode(
            preg_filter('/^/', '-', $subAuthorities)
        );
    }
}
