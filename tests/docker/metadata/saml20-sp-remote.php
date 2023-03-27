<?php

$metadata['https://cirrusgeneral.local.stack-dev.cirrusidentity.com/simplesaml/module.php/saml/sp/metadata.php/sp-auth'] = [
    'SingleLogoutService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'Location' => 'https://cirrusgeneral.local.stack-dev.cirrusidentity.com/simplesaml/module.php/saml/sp/saml2-logout.php/sp-auth',
        ],
    ],
    'AssertionConsumerService' => [
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'Location' => 'https://cirrusgeneral.local.stack-dev.cirrusidentity.com/simplesaml/module.php/saml/sp/saml2-acs.php/sp-auth',
            'index' => 0,
        ],
        [
            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
            'Location' => 'https://cirrusgeneral.local.stack-dev.cirrusidentity.com/simplesaml/module.php/saml/sp/saml2-acs.php/sp-auth',
            'index' => 1,
        ],
    ],
];
