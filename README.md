<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Installation](#installation)
- [ModifyingMetadataSource](#modifyingmetadatasource)
  - [Strategies](#strategies)
    - [AdfsMetadataStrategy](#adfsmetadatastrategy)
    - [OverridingMetadataStrategy](#overridingmetadatastrategy)
    - [PhpMetadtaStrategy](#phpmetadtastrategy)
- [AttributeSplitter](#attributesplitter)
- [AttributeValueMapper](#attributevaluemapper)
  - [CSV file format](#csv-file-format)
- [PromptAttributeRelease](#promptattributerelease)
- [ConditionalSetAuthnContext](#conditionalsetauthncontext)
- [AttributeRemove](#attributeremove)
- [ObjectSidConverter](#objectsidconverter)
- [Conditional AuthProc Insertion](#conditional-authproc-insertion)
  - [PhpConditionalAuthProcInserter](#phpconditionalauthprocinserter)
- [Development](#development)
- [Exploring with Docker](#exploring-with-docker)
  - [Things to try](#things-to-try)
    - [Attribute prompt/picker](#attribute-promptpicker)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# Installation

    composer require cirrusidentity/simplesamlphp-module-cirrusgeneral

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
                    ],
                    [
                        'type' => 'SimpleSAML\Module\cirrusgeneral\Metadata\PhpMetadataStrategy',
                        // Run php code to edit the metadat. Defined variables are $metadata, $set, and $entityId
                        'code' => '
                             if ($set === "saml20-sp-remote") {
                                $metadata["attributes"] = $metadata["attributes"] ?? ["attr1", "attr2"];
                             } 
                        '
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
A `flatfile` override strategy for `saml20-sp-remote` would look in the file `saml20-sp-remote-override.php`
and then return the metadata as `$overrideMetadata + $unalteredMetadata` which will keep
keys from the override metadata if the same key exists in the regular metadata.

### PhpMetadtaStrategy

This strategy allows you to run php code snippets to adjust metadata. Your code will have 3 variables available:
 array $metadata, string $set, and string $entityId.  $metadata will contain the current metadata for the entity and your code can
make changes to this array.

If you have complex code logic you are better off creating your own strategy with unit tests.

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

# PromptAttributeRelease

If a user has a multi-valued attribute and the SP can only use/expects one value, then the `PromptAttributeRelease`
filter can prompt the user to pick which value should be released to the SP.
An example, an SP has different functionality depending on the `eduPersonAffiliation` value. This filter
would allow user to select which of their affiliations to release.

```php
// In your authProc config
    20 => [
        'class' => 'cirrusgeneral:PromptAttributeRelease',
         'attribute' => 'eduPersonAffiliation',
         // optional labels to prefix in front of values
         'labels' => [
             'student' => 'Student Role',
             'member'  => 'Generic Role'
             // any other values don't get a label and are shown as the plain value in the UI
         ]
         // optional: should the attribute value be shown after the label? defaults to true
         'displayAttributeValue' => false
```

# ConditionalSetAuthnContext

This AuthProc filter allows you to assert a specific `authnContextClassRef` if value in
the users state equals some expected value. For example some upstream systems may indicate
the user was required to perform MFA by setting an attribute on the user. This filter will allow
you to assert `https://refeds.org/profile/mfa` if that attribute is present.

Usage:
```php
// In your authProc config of your IdP
    20 => [
        'class' => 'cirrusgeneral:ConditionalSetAuthnContext',
        'path' => ['Attributes', 'mfaActivated'], // The path of keys to traverse in the request state,
        'value' =>  'true',  // Using the string 'true' rather than a boolean true
        'contextToAssert' => 'https://refeds.org/profile/mfa',
        'ignoreForEntities' => ['match1', 'match2', 'other']

        // Optional context to assert if there is no match
        // 'elseContextToAssert' => 'https://refeds.org/profile/sfa'
    ]

// Example for Okta
      25 => array(
                    'class' => 'cirrusgeneral:ConditionalSetAuthnContext',
                    'path' => ['Attributes', 'session.amr'],
                    'value' => 'mfa',
                    'contextToAssert' => 'https://refeds.org/profile/mfa',
                ),

// Exmample for Aure AD
      49 => array(
                    'class' => 'cirrusgeneral:ConditionalSetAuthnContext',
                    'path' => ['Attributes', 'http://schemas.microsoft.com/claims/authnmethodsreferences'],
                    'value' => 'http://schemas.microsoft.com/claims/multipleauthn',
                    'contextToAssert' => 'https://refeds.org/profile/mfa',
                ),
```

# AttributeRemove

This AuthProc filter allows you to define attributes that should always be removed. We use it with
AzureAD since it always returns certain extra attributes (such as tenantId )that we want removed from the
users attributes.

Usage:
```php
// In your authProc config
    20 => [
        'class' => 'cirrusgeneral:AttributeRemove',
        'attributes' => ['http://schemas.microsoft.com/identity/claims/tenantid', 'http://schemas.microsoft.com/identity/claims/objectidentifier'],
        'attributeRegexes' => ['/^operational/']
    ]
```

# ObjectSidConverter

ActiveDirectory's objectSid can be a in a binary format or as a formatted string. Sometimes you'll receive one and expect the other.

# Conditional AuthProc Insertion

There are use cases where you want to run a set of authproc filters, but only if a certain condition is met when a user is
logging in. Not all authproc filters support conditional use. Subclasses of `BaseConditionalAuthProcInserter`
allow you to insert an arbitrary number of authproc filters at the `BaseConditionalAuthProcInserter` priority during
authproc processing. This allows you to check things in the user's state prior to creating the filters.

## PhpConditionalAuthProcInserter

`PhpConditionalAuthProcInserter` is an example of defining a boolean expression that determines if the authproc filters
are created. Two variables are available: `array $attributes` and `array $state`

```php
// In your authsources.php or saml20-idp-metadata.php or whereever you define your authprocs
   'authproc' => [
       10 => [
           // a norma authproc
           'core:AttributeMap'
       ],
       20 => [
             'class' => 'cirrusgeneral:PhpConditionalAuthProcInserter',
             //php boolean expression. Two variables are available: $attributes and $state
            'condition' => 'return $state["saml:sp:State"]["saml:sp:AuthnContext"] === "https://refeds.org/profile/mfa";',
             // These will only get created if AuthnContext is refeds MFA, and they will run immediately after
             // PhpConditionalAuthProcInserter
             'authproc' => [
                [
                  'class' => 'core:AttributeAdd',
                  'newAttribute' => array('newValue'),
                ],
                [
                   'class' => 'core:AttributeMap',
                ],
             ],
             // These will only get created if authnContext is not refeds MFA
             'elseAuthproc' => [
                [
                  'class' => 'somemodule:PerformMfa',
                ],
                [
                   'class' => 'somemodule:SetRefedsMfa',
                ],
             ]
       ],
       30 => [
          // another normal authproc
          'core:AttributeMap'
       ]


   ]

```

# Development

Run `phpcs` to check code style

    ./vendor/bin/phpcs

Run `phpunit` to test

    ./vendor/bin/phpunit

You can auto correct some findings from phpcs. It is recommended you do this after stage your changes (or maybe even commit) since there is a non-trivial chance it will just mess up your code.

    ./vendor/bin/phpcbf

Run psalm to find issues

    ./vendor/bin/psalm   --no-cache


# Exploring with Docker

You can explore these features with Docker.

```bash

docker run -d --name ssp-cirrusgeneral \
   --mount type=bind,source="$(pwd)",target=/var/simplesamlphp/staging-modules/cirrusgeneral,readonly \
  -e STAGINGCOMPOSERREPOS=cirrusgeneral \
  -e COMPOSER_REQUIRE="cirrusidentity/simplesamlphp-module-cirrusgeneral:@dev" \
  -e SSP_ENABLED_MODULES="cirrusgeneral" \
  --mount type=bind,source="$(pwd)/tests/docker/metadata/",target=/var/simplesamlphp/metadata/,readonly \
  --mount type=bind,source="$(pwd)/tests/docker/authsources.php",target=/var/simplesamlphp/config/authsources.php,readonly \
  --mount type=bind,source="$(pwd)/tests/docker/config-override.php",target=/var/simplesamlphp/config/config-override.php,readonly \
  --mount type=bind,source="$(pwd)/tests/docker/cert/",target=/var/simplesamlphp/cert/,readonly \
   -p 443:443 cirrusid/simplesamlphp:v2.0.0
```

Then log in as `admin:secret` to https://cirrusgeneral.local.stack-dev.cirrusidentity.com/simplesaml/module.php/core/frontpage_welcome.php
to confirm things work.

## Things to try

### Attribute prompt/picker

The Idp has the `PromptAttributeRelease` authproc filter enabled for the `eduPersonAffiliation` attribute.
If a user has more than one value they will need to pick which value to release. See `authsources.php` for the available users.
To make the IdP run it's authproc filters you need to send a login from an SP, and the [sp-auth source](https://cirrusgeneral.local.stack-dev.cirrusidentity.com/simplesaml/module.php/admin/test/sp-auth)
will do that login.