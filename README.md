<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [ModifyingMetadataSource](#modifyingmetadatasource)
  - [Strategies](#strategies)
    - [AdfsMetadataStrategy](#adfsmetadatastrategy)
    - [OverridingMetadataStrategy](#overridingmetadatastrategy)
- [AttributeSplitter](#attributesplitter)
- [AttributeValueMapper](#attributevaluemapper)
  - [CSV file format](#csv-file-format)
- [ConditionalSetAuthnContext](#conditionalsetauthncontext)
- [Development](#development)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# ModifyingMetadataSource

There is often a need to adjust the metadata for an entityId to fix certain values, or to add
SSP specific config items. This is difficult to achieve if metadata is loaded from a remote source.
The `ModifyingMetadataSource` allows you to configure different strategies to change the metadata that is loaded.
The source delegates to other sources (like mdq or the serialize source) and then
edits the metadata before returning it.

```php
 'metadata.sources' => [
            [
                'type' => 'SimpleSAML\Module\cirrusgeneral\Metadata\Sources\ModifyingMetadataSource',
                // Any sources that you want to delegate to
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
                    // some other strategy
                    // ['type' => 'Myclass', 'configOption1' => true],
                ],
            ],
            // Any sources that you don't want to pass to the modifying strategis
            // [ 'type' => 'flatfile' ],
        ]
```
## Strategies

### AdfsMetadataStrategy

Add `disable_scoping` to any metadata that looks like ADFS

### OverridingMetadataStrategy

Load additonal metadata from a source and combine it with the main metadata using +.
A `flatfile` override strategy for `saml-sp-remote` would look in the file `saml-sp-remote-override.php`
and then return the metadata as `$overrideMetadata + $unalteredMetadata` which will keep
keys from the override metadata if the same key exists in the regular metadata.

# AttributeSplitter

This AuthProc filter will split an attributes values on a delimiter and turn it into an array.
Some systems stores the multi valued attributes, such as `eduPersonAffiliation`, as a comma delimited list - `student,member`
This filter will split that into multiple values

Usage:

```php
// In your authProc config
    20 => [
        'class' => 'cirrusgeneral:AttributeSplitter',
        'delimiter' =>  ',',  // Optional. Default is comma
        'attributes' => ['eduPersonAffiliation', 'urn:oid:1.3.6.1.4.1.5923.1.1.1.1'],
    ]

```

# AttributeValueMapper

Maps a source attribute name and value to a new attribute name and values(s) based on `csv` file or
or in php config. Useful when a datasource (for groups, entitlements, etc) contains names and values that need
to be mapped to something new.

```php
// In your authProc config
    20 => [
        'class' => 'cirrusgeneral:AttributeValueMapper',
        'csvFile' =>  '/patch/to/csv',
         'mappingLookup' => [
                // source attribute name
                'inc-eduPersonEntitlement' => [
                     // source value
                     'inc-eduPersonEntitlement-everfi' => [
                           // dest attribute      =>   [ dest val1, dest val2]
                           'eduPersonEntitlement' => ['val1', 'val2'],
                           'localEntitlement' => ['anotherValue'],
                     ]
                ],
         ]
    ]

```

## CSV file format

Each line contains a source attribute and value, and if a user has an attribute of that value
then they get the resulting `destinationAttribute` populated with the `destinationValue`.
Duplicates are removed

```csv
sourceAttribute,sourceValue,destinationAttribute,destinationValue
group-test,newStudent,eduPersonAffiliation,student
group-med,newStudent,eduPersonAffiliation,student
group-test,newStudent,eduPersonAffiliation,member
group-test,oldStudent,eduPersonAffiliation,alumni
group-test,incoming-Student,eduPersonAffiliation,student
group-test,incoming-Student,entitlement,https://example.com/checkfinacialAid
group-test,newStudent,entitlement,urn:blah:blahstudent-app
group-med,med-student,eduPersonAffiliation,student
group-med,med-student,eduPersonAffiliation,other
group-med,newStudent,entitlement,urn:med:student-app
group-med,faculty,entitlement,urn:med:faculty-app
```

# ConditionalSetAuthnContext

This AuthProc filter allows you to assert a specific `authnContextClassRef` if value in
the users state equals some expected value. For example some upstream systems may indicate
the user was required to perform MFA by setting an attribute on the user. This filter will allow
you to assert `https://refeds.org/profile/mfa` if that attribute is present.

Usage:
```php
// In your authProc config
    20 => [
        'class' => 'cirrusgeneral:ConditionalSetAuthnContext',
        'path' => ['Attributes', 'mfaActivated'], // The path of keys to traverse in the request state,
        'value' =>  'true',  // Using the string 'true' rather than a boolean true
        'contextToAssert' => 'https://refeds.org/profile/mfa',
        'ignoreForEntities' => ['match1', 'match2', 'other']
    ]

```

# Development

Run `phpcs` to check code style

    ./vendor/bin/phpcs --standard=PSR2 lib/ tests/

Run `phpunit` to test

    ./vendor/bin/phpunit

You can auto correct some findings from phpcs. It is recommended you do this after stage your changes (or maybe even commit) since there is a non-trivial chance it will just mess up your code.

    ./vendor/bin/phpcbf --ignore=somefile.php --standard=PSR2 lib/ tests/