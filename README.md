<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [AttributeSplitter](#attributesplitter)
- [AttributeValueMapper](#attributevaluemapper)
  - [CSV file format](#csv-file-format)
- [ConditionalSetAuthnContext](#conditionalsetauthncontext)
- [Development](#development)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

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
or on php config. Useful when a datasource (for groups, entitlements, etc) contains names and values that need
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