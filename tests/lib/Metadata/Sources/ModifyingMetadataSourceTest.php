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
                    ['type' => 'flatfile', 'directory' => __DIR__ . '/testMetadata'],
                    ['type' => 'flatfile', 'directory' => __DIR__ . '/testMetadata2'],
                ],
                'strategies' => [
                    ['type' => 'SimpleSAML\Module\cirrusgeneral\Metadata\AdfsMetadataStrategy'],
                    [
                        'type' => 'SimpleSAML\Module\cirrusgeneral\Metadata\OverridingMetadataStrategy',
                        'source' => ['type' => 'flatfile', 'directory' => __DIR__ . '/overrideMetadata'],
                    ],
                    [
                        'type' => 'SimpleSAML\Module\cirrusgeneral\Metadata\PhpMetadataStrategy',
                        'code' => '
                             if ($set === "saml20-sp-remote") {
                                $metadata["attributes"] = $metadata["attributes"] ?? ["attr1", "attr2"];
                             } 
                        '
                    ]
                ],
            ]
        ]
    ];

    public function testModifyingMetadataSourceViaHandler(): void
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

    public function testLoadSetViaHandler(): void
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

    public function testNotFoundMetadataViaHandler(): void
    {
        Configuration::loadFromArray($this->config, '[ARRAY]', 'simplesaml');
        $handler = MetaDataStorageHandler::getMetadataHandler();
        $this->expectException(MetadataNotFound::class);
        $handler->getMetaData(
            'http://no-such-entry',
            'saml20-idp-remote'
        );
    }

    public function testLoadMetadataEntities(): void
    {
        $entityIds = [
            'http://alt.example.edu/adfs/services/trust',
            'http://idp.example.edu/adfs/services/trust',
            'http://idp.example.edu/adfs/services/notexist'
        ];
        // Set the config to to use
        Configuration::loadFromArray($this->config, '[ARRAY]', 'simplesaml');
        $handler = MetaDataStorageHandler::getMetadataHandler();
        $metadataSet = $handler->getMetaDataForEntities($entityIds, 'saml20-idp-remote');

        $this->assertArrayHasKey('http://idp.example.edu/adfs/services/trust', $metadataSet);
        $this->assertArrayHasKey('http://alt.example.edu/adfs/services/trust', $metadataSet);
        $this->assertCount(2, $metadataSet);

        $this->assertEquals(
            'https://idp.example.eduadfs/ls/',
            $metadataSet['http://idp.example.edu/adfs/services/trust']['SingleSignOnService'][0]['Location']
        );
    }
}
