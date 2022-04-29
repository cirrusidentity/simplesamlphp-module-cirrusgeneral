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

    private $azureMetadata = array (
        'entityid' => 'https://sts.windows.net/ee4b037f-e626-495d-b017-0cc0f7dddb37/',
        'metadata-set' => 'saml20-idp-remote',
        'SingleSignOnService' =>
            array (
                0 =>
                    array (
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://login.microsoftonline.com/ee4b037f-e626-495d-b017-0cc0f7dddb37/saml2',
                    ),
                1 =>
                    array (
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                        'Location' => 'https://login.microsoftonline.com/ee4b037f-e626-495d-b017-0cc0f7dddb37/saml2',
                    ),
            ),
        'SingleLogoutService' =>
            array (
                0 =>
                    array (
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        'Location' => 'https://login.microsoftonline.com/ee4b037f-e626-495d-b017-0cc0f7dddb37/saml2',
                    ),
            ),
        'keys' =>
            array (
                0 =>
                    array (
                        'encryption' => false,
                        'signing' => true,
                        'type' => 'X509Certificate',
                        // phpcs:ignore
                        'X509Certificate' => 'MIIC8DCCAdigAwIBAgIQTjJaoC31qZ1GjW3EWifPIDANBgkqhkiG9w0BAQsFADA0MTIwMAYDVQQDEylNaWNyb3NvZnQgQXp1cmUgRmVkZXJhdGVkIFNTTyBDZXJ0aWZpY2F0ZTAeFw0xOTA2MjYxNzAzNTJaFw0yMjA2MjYxNzAzNTJaMDQxMjAwBgNVBAMTKU1pY3Jvc29mdCBBenVyZSBGZWRlcmF0ZWQgU1NPIENlcnRpZmljYXRlMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAoYMvp6gnkXgxz+D3zQtxdSkQNzPM4JkzI2QsDW5rac91K/aNI0I9txp6NFJBDIHwGf44dFZrJBT9d3dV1GxPiOensksdWOD/4wNK2FEPsEszm14i/1wg9PCinytMCxfQMAcmnKwPF+K9V3XR2lzeoiqCzSYZE98F+7QX3UUjz8yg0BYXShKrIcaGnuG8D4QrfjfF27gqR6WyO3oyXxUml5rcE6tB1zK2j8S4zkfPTH8h580Q7AoyoqwU7eCeRR0goDTNzILsFmDDRL8vXIdmKTj71Z3smb4O1oONCE2eiWvw6Pr2eZwj0klWIeYIYSjFbZqzhT1fjKhbPPeSBJcxdwIDAQABMA0GCSqGSIb3DQEBCwUAA4IBAQAVB7BIhR27h9J6hGkAecoemnQEy3EHcI0EaX8OxJqQzVCvAf4TjRkVoPGPp7CQBzo6RVoL+SZ24OJkdaU34our9sC/5w7lZxn6P+qe/66PjhPWLBGxHQzGk8CYd0zHSb2MEugoqlq78rWQ1uDfFnA3e4WtfQ6WgqquVwEqVjKpopg7pbNX4mQObNrB/dL4C9rreBUwQ5hSKe3/IfbPfsqj3Fdy0WSoEkj7vebvCcHfQwAuwvpaoA8kWcjXh8nxiQrXbln/gzeohUS9gLWZQTMmo5HJngnr1hov37EN6zXEJ3LjZmX+BdKGVQLov5TDvE6Pqqd+/+fqSqUFG2PusCV6',
                    ),
            ),
    );


    public function testAdfsHasAttributeAdjusted()
    {
        $adfsStrategy = new AdfsMetadataStrategy();
        $this->assertArrayNotHasKey('disable_scoping', $this->adfsMetadata);
        $postMetadata = $adfsStrategy->modifyMetadata(
            $this->adfsMetadata,
            $this->adfsMetadata['entityid'],
            'saml20-idp-remote'
        );
        $this->assertTrue($postMetadata['disable_scoping'], 'ADFS does not support scoping');
        unset($postMetadata['disable_scoping']);
        $this->assertEquals($this->adfsMetadata, $postMetadata, 'The rest of the metadata is unaltered');
    }

    public function testAzureHasAttributeAdjusted()
    {
        $adfsStrategy = new AdfsMetadataStrategy();
        $this->assertArrayNotHasKey('disable_scoping', $this->azureMetadata);
        $postMetadata = $adfsStrategy->modifyMetadata(
            $this->azureMetadata,
            $this->azureMetadata['entityid'],
            'saml20-idp-remote'
        );
        $this->assertTrue($postMetadata['disable_scoping'], 'Azure AD is too strict on scoping, and can error');
        unset($postMetadata['disable_scoping']);
        $this->assertEquals($this->azureMetadata, $postMetadata, 'The rest of the metadata is unaltered');
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
