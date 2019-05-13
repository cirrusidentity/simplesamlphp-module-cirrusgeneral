<?php

namespace SimpleSAML\Module\cirrusgeneral\Metadata\Sources;

use PHPUnit\Framework\TestCase;

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
                    ['type' => 'SimpleSAML\Module\cirrusgeneral\Metadata\AdfsMetadataStrategy']
                ],
            ]
        ]
    ];

    public function testModifyingMetadataSourceViaHandler()
    {
        // Set the config to to use
        \SimpleSAML_Configuration::loadFromArray($this->config, '[ARRAY]', 'simplesaml');
        $handler = \SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
        $metadata = $handler->getMetaData(
            'http://idp.example.edu/adfs/services/trust',
            'saml20-idp-remote'
        );

        $this->assertTrue($metadata['disable_scoping']);
        $this->assertEquals('https://idp.example.eduadfs/ls/', $metadata['SingleSignOnService'][0]['Location']);
    }

    public function testNotFoundMetadataViaHandler()
    {
        \SimpleSAML_Configuration::loadFromArray($this->config, '[ARRAY]', 'simplesaml');
        $handler = \SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
        $this->expectException(\SimpleSAML_Error_MetadataNotFound::class);
        $handler->getMetaData(
            'http://no-such-entry',
            'saml20-idp-remote'
        );
    }
}
