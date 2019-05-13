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
