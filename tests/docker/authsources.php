<?php

$config = [

    // This is a authentication source which handles admin authentication.
    'admin' => [
        'core:AdminPassword',
    ],

    'idp-auth' => [
        'exampleauth:UserPass',
        'student:studentpass' => [
            'uid' => ['student'],
            'eduPersonAffiliation' => ['member', 'student'],
            'sampleAttributeForMsgOverride' => ['sample1', 'encoding&test']
        ],
        'employee:employeepass' => [
            'uid' => ['employee'],
            'eduPersonAffiliation' => ['member', 'employee'],
            'eduPersonEntitlement' => ['urn:example:oidc:manage:client']
        ],
        'member:memberpass' => [
            'uid' => ['member'],
            'eduPersonAffiliation' => ['member'],
            'eduPersonEntitlement' => ['urn:example:oidc:manage:client']
        ],
        'minimal:minimalpass' => [
            'uid' => ['minimal'],
        ],
    ],

    'sp-auth' => [
        'saml:SP',
        'entityID' => 'https://cirrusgeneral.local.stack-dev.cirrusidentity.com/simplesaml/module.php/saml/sp/metadata.php/sp-auth',
        'idp' => 'https://cirrusgeneral.local.stack-dev.cirrusidentity.com/simplesaml/saml2/idp/metadata.php'
    ]
];
