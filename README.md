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
    ]

```
