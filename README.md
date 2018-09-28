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