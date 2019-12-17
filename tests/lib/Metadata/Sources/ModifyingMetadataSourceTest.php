<?php

namespace SimpleSAML\Module\cirrusgeneral\Metadata\Sources;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error\MetadataNotFound;
use SimpleSAML\Metadata\MetaDataStorageHandler;

class ModifyingMetadataSourceTest extends TestCase
{

    private $config = [
        'metadata.sources' => [
            [
                'type' => 'SimpleSAML\Module\cirrusgeneral\Metadata\Sources\ModifyingMetadataSource',
                'sources' => [
                    array('type' => 'flatfile', 'directory' => __DIR__ . '/testMetadata'),
                    array('type' => 'flatfile', 'directory' => __DIR__ . '/testMetadata2'),
                ],
                'strategies' => [
                    ['type' => 'SimpleSAML\Module\cirrusgeneral\Metadata\AdfsMetadataStrategy'],
                    [
                        'type' => 'SimpleSAML\Module\cirrusgeneral\Metadata\OverridingMetadataStrategy',
                        'source' => array('type' => 'flatfile', 'directory' => __DIR__ . '/overrideMetadata'),
                    ]
                ],
            ]
        ]
    ];

    public function testModifyingMetadataSourceViaHandler()
    {
        // Set the config to to use
        Configuration::loadFromArray($this->config, '[ARRAY]', 'simplesaml');
        $handler = MetaDataStorageHandler::getMetadataHandler();
        $metadata = $handler->getMetaData(
            'http://idp.example.edu/adfs/services/trust',
            'saml20-idp-remote'
        );

        $this->assertTrue($metadata['disable_scoping'], 'Changed by adfs strategy');
        $this->assertEquals('customFormat', $metadata['NameIDFormats'][0], 'Changed by override strategy');
        $this->assertEquals(
            'https://idp.example.eduadfs/ls/',
            $metadata['SingleSignOnService'][0]['Location'],
            'not changed'
        );
    }

    public function testLoadSetViaHandler()
    {
        // Set the config to to use
        Configuration::loadFromArray($this->config, '[ARRAY]', 'simplesaml');
        $handler = MetaDataStorageHandler::getMetadataHandler();
        $metadataSet = $handler->getList('saml20-idp-remote');
        $this->assertArrayHasKey('http://idp.example.edu/adfs/services/trust', $metadataSet);
        $this->assertArrayHasKey('http://alt.example.edu/adfs/services/trust', $metadataSet);

        $this->assertEquals(
            'https://idp.example.eduadfs/ls/',
            $metadataSet['http://idp.example.edu/adfs/services/trust']['SingleSignOnService'][0]['Location']
        );
    }

    public function testNotFoundMetadataViaHandler()
    {
        Configuration::loadFromArray($this->config, '[ARRAY]', 'simplesaml');
        $handler = MetaDataStorageHandler::getMetadataHandler();
        $this->expectException(MetadataNotFound::class);
        $handler->getMetaData(
            'http://no-such-entry',
            'saml20-idp-remote'
        );
    }
}
