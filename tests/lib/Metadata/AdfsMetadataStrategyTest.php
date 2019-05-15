<?php

namespace Test\SimpleSAML\Metadata;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\cirrusgeneral\Metadata\AdfsMetadataStrategy;

class AdfsMetadataStrategyTest extends TestCase
{

    private $notAdfsMetadata = [
        'entityid' => 'http://sts.example.edu/idp',
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
    private $adfsMetadata = [
        'entityid' => 'http://sts.example.edu/adfs/services/trust',
        'metadata-set' => 'saml20-idp-remote',
        'SingleSignOnService' =>
            array(
                0 =>
                    array(
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://stsdev.example.edu/adfs/ls/',
                    ),
            ),
            'SingleLogoutService' =>
            array(
                0 =>
                    array(
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://stsdev.example.edu/adfs/ls/',
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


    public function testAdfsHasAttributeAdjusted()
    {
        $adfsStrategy = new AdfsMetadataStrategy();
        $this->assertArrayNotHasKey('disable_scoping', $this->adfsMetadata);
        $postMetadata = $adfsStrategy->modifyMetadata(
            $this->adfsMetadata,
            'http://sts.example.edu/adfs/services/trust',
            'saml20-idp-remote'
        );
        $this->assertTrue($postMetadata['disable_scoping'], 'ADFS does not support scoping');
        unset($postMetadata['disable_scoping']);
        $this->assertEquals($this->adfsMetadata, $postMetadata, 'The rest of the metadata is unaltered');
    }

    public function testNullHandled()
    {
        $adfsStrategy = new AdfsMetadataStrategy();
        $postMetadata = $adfsStrategy->modifyMetadata(
            null,
            'http://sts.example.edu/adfs/services/trust',
            'saml20-idp-remote'
        );
        $this->assertNull($postMetadata);
    }

    /**
     * @dataProvider noAdjustmentProvider
     * @param array $metadata The metadata to test
     * @param string $set The set
     */
    public function testNoAdjustmentsForNonAdfsOrNonIdpRemote($metadata, $set)
    {
        $adfsStrategy = new AdfsMetadataStrategy();
        $postMetadata = $adfsStrategy->modifyMetadata($metadata, $metadata['entityid'], $set);
        $this->assertArrayNotHasKey('disable_scoping', $postMetadata);
        $this->assertEquals($metadata, $postMetadata, 'The metadata is unaltered');
    }

    public function noAdjustmentProvider()
    {
        return [
            [$this->adfsMetadata, 'some-set'],
            [$this->notAdfsMetadata, 'some-set'],
            [$this->notAdfsMetadata, 'saml20-idp-remote'],
        ];
    }
}
