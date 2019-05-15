<?php

// Metadata in the first folder gets access first, so this entry shouldn't get loaded.
$metadata['http://idp.example.edu/adfs/services/trust'] = array(
    'entityid' => 'http://idp.example.eduadfs/services/trust',
    'SingleSignOnService' =>
        array(
            0 =>
                array(
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://idp.example.eduadfs/ls/override',
                ),
            1 =>
                array(
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                    'Location' => 'https://idp.example.eduadfs/ls/override',
                ),
        ),
);

$metadata['http://alt.example.edu/adfs/services/trust'] = array(
    'entityid' => 'http://alt.example.eduadfs/services/trust',
    'metadata-set' => 'saml20-idp-remote',
    'SingleSignOnService' =>
        array(
            0 =>
                array(
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://alt.example.eduadfs/ls/',
                ),
        ),
    'SingleLogoutService' =>
        array(
            0 =>
                array(
                    'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                    'Location' => 'https://alt.example.eduadfs/ls/',
                ),
        ),
);
