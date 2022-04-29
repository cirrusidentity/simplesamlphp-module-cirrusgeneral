<?php

namespace Test\SimpleSAML\Metadata;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Module\cirrusgeneral\Metadata\PhpMetadataStrategy;

class PhpMetadataStrategyTest extends TestCase
{
    private $metadata = [
        'entityid' => 'https://someapp.com',

        'metadata-set' => 'saml20-sp-remote',
        'AssertionConsumerService' =>
            array(
                0 =>
                    array(
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                        'Location' => 'https://someapp.com/auth/saml/callback',
                        'index' => 0,
                        'isDefault' => true,
                    ),
            ),
            'SingleLogoutService' =>
            array(
                0 =>
                    array(
                        'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                        'Location' => 'https://someapp.com/auth/sign_out',
                    ),
            ),
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
            'attributes' =>
            array(
                0 => 'name',
                1 => 'last_name',
                2 => 'first_name',
                3 => 'email',
            ),
            'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic',
    ];


    public function setup()
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(dirname(__DIR__)) . '/config');
    }

    public function testMetadataAdjustment()
    {
        //given:
        // php code that only sets attributes if not defined
        $phpCode = ['code' => '
            if ($set === "saml20-sp-remote") {
                $metadata["attributes"] = $metadata["attributes"] ?? ["attr1", "attr2"];
            } 
        '];
        $strategy = new PhpMetadataStrategy($phpCode);
        //when: running stategy with metadata that has attributes
        $startingAttribute = $this->metadata['attributes'];
        $this->assertNotEmpty($startingAttribute);
        $postMetadata = $strategy->modifyMetadata(
            $this->metadata,
            'https://someapp.com',
            'saml20-sp-remote'
        );
        $this->assertEquals($startingAttribute, $postMetadata['attributes'], 'Attributes dont change');

        //when: running with metadata without attributes
        unset($this->metadata['attributes']);
        $postMetadata = $strategy->modifyMetadata(
            $this->metadata,
            'https://someapp.com',
            'saml20-sp-remote'
        );
        $this->assertNotEquals($startingAttribute, $postMetadata['attributes'], 'Attributes do change');
        $this->assertEquals(["attr1", "attr2"], $postMetadata['attributes'], 'Attributes do change');
    }

    public function testNullHandled()
    {
        $strategy = new PhpMetadataStrategy(['code' => '']);
        $postMetadata = $strategy->modifyMetadata(
            null,
            'http://idp.example.edu/adfs/services/trust',
            'saml20-idp-remote'
        );
        $this->assertNull($postMetadata);
    }
}
