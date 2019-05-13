<?php

namespace Test\SimpleSAML\Metadata;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\cirrusgeneral\Metadata\OverridingMetadataStrategy;

class OverridingMetadataStrategyTest extends TestCase
{
    private $noMatchMetadata = [
        'entityid' => 'http://nomatch.example.edu/adfs/services/trust',
        'metadata-set' => 'saml20-idp-remote',
        'NameIDFormats' =>
            array(
                0 => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            ),
    ];

    private $metadata = [
        'entityid' => 'http://idp.example.edu/adfs/services/trust',
        'metadata-set' => 'saml20-idp-remote',
        'SingleSignOnService' =>
            array(
                0 =>
                    array(
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://stsdev.example.edu/SSO',
                    ),
            ),
            'SingleLogoutService' =>
            array(
                0 =>
                    array(
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://stsdev.example.edu/SLO',
                    ),
            ),
            'NameIDFormats' =>
            array(
                0 => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
                1 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
                2 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
            ),
            'keys' =>
            array(
                0 =>
                    array(
                        'encryption' => true,
                        'signing' => false,
                        'type' => 'X509Certificate',
                        'X509Certificate' => 'MIIC3jCCAcagAwsomekey'
                    ),
                1 =>
                    array(
                        'encryption' => false,
                        'signing' => true,
                        'type' => 'X509Certificate',
                        'X509Certificate' => 'MIIC2DCCAcCgAwsomekey'
                    ),
            )
    ];

    private $config = [
        'source' => array('type' => 'flatfile', 'directory' => __DIR__ . '/Sources/overrideMetadata'),

    ];

    public function setup()
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(dirname(__DIR__)) . '/config');
    }

    public function testMatchHasAttributeAdjusted()
    {
        $strategy = new OverridingMetadataStrategy($this->config);
        $this->assertArrayNotHasKey('disable_scoping', $this->metadata);
        $postMetadata = $strategy->modifyMetadata(
            $this->metadata,
            'http://idp.example.edu/adfs/services/trust',
            'saml20-idp-remote'
        );
        $this->assertEquals('customFormat', $postMetadata['NameIDFormats'][0]);
    }

    /**
     * @dataProvider noAdjustmentProvider
     * @param array $metadata The metadata to test
     * @param string $set The set
     */
    public function testNoAdjustmentsForNonMatch($metadata, $set)
    {
        $strategy = new OverridingMetadataStrategy($this->config);
        $postMetadata = $strategy->modifyMetadata($metadata, $metadata['entityid'], $set);
        $this->assertEquals(
            'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            $postMetadata['NameIDFormats'][0],
            'The metadata is unaltered'
        );
    }

    public function noAdjustmentProvider()
    {
        return [
            [$this->metadata, 'some-set'],
            [$this->noMatchMetadata, 'some-set'],
            [$this->noMatchMetadata, 'saml20-idp-remote'],
        ];
    }
}
