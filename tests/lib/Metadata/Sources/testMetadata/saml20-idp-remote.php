<?php

$metadata['http://idp.example.edu/adfs/services/trust'] = [
    'entityid' => 'http://idp.example.eduadfs/services/trust',
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' =>
        [
            0 =>
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://idp.example.eduadfs/ls/',
                ],
            1 =>
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    'Location' => 'https://idp.example.eduadfs/ls/',
                ],
        ],
    'SingleLogoutService' =>
        [
            0 =>
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://idp.example.eduadfs/ls/',
                ],
            1 =>
                [
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    'Location' => 'https://idp.example.eduadfs/ls/',
                ],
        ],
    'ArtifactResolutionService' =>
        [],
    'NameIDFormats' =>
        [
            0 => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            1 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
            2 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
        ],
    'keys' =>
        [
            0 =>
                [
                    'encryption' => true,
                    'signing' => false,
                    'type' => 'X509Certificate',
                    'X509Certificate' => 'MIIC4jCCAcqgAwIBAgItest'
                ],
            1 =>
                [
                    'encryption' => false,
                    'signing' => true,
                    'type' => 'X509Certificate',
                    'X509Certificate' => 'MIIC3DCCAcSgAwIBAgIQtest'
                ],
        ],
];
