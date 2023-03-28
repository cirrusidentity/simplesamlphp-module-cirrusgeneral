<?php

/**
 * SAML 2.0 IdP configuration for SimpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-hosted
 */

$metadata['https://cirrusgeneral.local.stack-dev.cirrusidentity.com/simplesaml/saml2/idp/metadata.php'] = [
    /*
     * The hostname of the server (VHOST) that will use this SAML entity.
     *
     * Can be '__DEFAULT__', to use this entry by default.
     */
    'host' => '__DEFAULT__',

    // X.509 key and certificate. Relative to the cert directory.
    'privatekey' => 'server.pem',
    'certificate' => 'server.crt',

    /*
     * Authentication source to use. Must be one that is configured in
     * 'config/authsources.php'.
     */
    'auth' => 'idp-auth',

    'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
    'authproc' => [
        // Prompt
        100 => [
            'class' => 'cirrusgeneral:PromptAttributeRelease',
            'attribute' => 'eduPersonAffiliation',
            // optional labels to prefix in front of values
            'labels' => [
                'student' => 'Student Role',
                'member' => 'Generic Role'
                // any other values don't get a label and are shown as the plain value in the UI
            ]
        ],
        // Demo custom message per attribute
        110 => [
            'class' => 'cirrusgeneral:PromptAttributeRelease',
            'attribute' => 'sampleAttributeForMsgOverride',
        ],
    ],

];
