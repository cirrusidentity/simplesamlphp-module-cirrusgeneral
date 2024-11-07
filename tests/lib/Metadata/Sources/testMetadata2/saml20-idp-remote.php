<?php

// Metadata in the first folder gets access first, so this entry shouldn't get loaded.
$metadata['http://idp.example.edu/adfs/services/trust'] = [
    'entityid' => 'http://idp.example.eduadfs/services/trust',
    'SingleSignOnService' =>
        [
            0 =>
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://idp.example.eduadfs/ls/override',
                ],
            1 =>
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    'Location' => 'https://idp.example.eduadfs/ls/override',
                ],
        ],
];

$metadata['http://alt.example.edu/adfs/services/trust'] = [
    'entityid' => 'http://alt.example.eduadfs/services/trust',
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' =>
        [
            0 =>
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://alt.example.eduadfs/ls/',
                ],
        ],
    'SingleLogoutService' =>
        [
            0 =>
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://alt.example.eduadfs/ls/',
                ],
        ],
];
