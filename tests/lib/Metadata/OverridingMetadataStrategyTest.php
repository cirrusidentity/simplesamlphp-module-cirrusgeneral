<?php

namespace Test\SimpleSAML\Metadata;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\cirrusgeneral\Metadata\OverridingMetadataStrategy;

/**
 * @psalm-suppress PossiblyNullArrayAccess metadata is an array, we want to test without these warnings
 */
class OverridingMetadataStrategyTest extends TestCase
{
    private $noMatchMetadata = [
        'entityid' => 'http://nomatch.example.edu/adfs/services/trust',
        'metadata-set' => 'saml20-idp-remote',
        'NameIDFormats' =>
            [
                0 => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            ],
    ];

    private $metadata = [
        'entityid' => 'http://idp.example.edu/adfs/services/trust',
        'metadata-set' => 'saml20-idp-remote',
        'SingleSignOnService' =>
            [
                0 =>
                    [
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://stsdev.example.edu/SSO',
                    ],
            ],
            'SingleLogoutService' =>
            [
                0 =>
                    [
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://stsdev.example.edu/SLO',
                    ],
            ],
            'NameIDFormats' =>
            [
                0 => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
                1 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
                2 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
            ],
            'keys' =>
            [
                0 =>
                    [
                        'encryption' => true,
                        'signing' => false,
                        'type' => 'X509Certificate',
                        'X509Certificate' => 'MIIC3jCCAcagAwsomekey'
                    ],
                1 =>
                    [
                        'encryption' => false,
                        'signing' => true,
                        'type' => 'X509Certificate',
                        'X509Certificate' => 'MIIC2DCCAcCgAwsomekey'
                    ],
            ]
    ];

    private $config = [
        'source' => ['type' => 'flatfile', 'directory' => __DIR__ . '/Sources/overrideMetadata'],

    ];

    protected function setup(): void
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(__DIR__, 2) . '/config');
    }

    public function testMatchHasAttributeAdjusted(): void
    {
        $strategy = new OverridingMetadataStrategy($this->config);
        $this->assertNotEquals('customFormat', $this->metadata['NameIDFormats'][0]);
        $postMetadata = $strategy->modifyMetadata(
            $this->metadata,
            'http://idp.example.edu/adfs/services/trust',
            'saml20-idp-remote'
        );
        $this->assertEquals('customFormat', $postMetadata['NameIDFormats'][0]);
    }

    public function testNullHandled(): void
    {
        $strategy = new OverridingMetadataStrategy($this->config);
        $postMetadata = $strategy->modifyMetadata(
            null,
            'http://idp.example.edu/adfs/services/trust',
            'saml20-idp-remote'
        );
        $this->assertNull($postMetadata);
    }

    /**
     * @dataProvider noAdjustmentProvider
     *
     * @param array $metadata The metadata to test
     * @param string $set The set
     */
    public function testNoAdjustmentsForNonMatch($metadata, $set): void
    {
        $strategy = new OverridingMetadataStrategy($this->config);
        $postMetadata = $strategy->modifyMetadata($metadata, $metadata['entityid'], $set);
        $this->assertEquals(
            'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            $postMetadata['NameIDFormats'][0],
            'The metadata is unaltered'
        );
    }

    public function noAdjustmentProvider(): array
    {
        return [
            [$this->metadata, 'some-set'],
            [$this->noMatchMetadata, 'some-set'],
            [$this->noMatchMetadata, 'saml20-idp-remote'],
        ];
    }
}
