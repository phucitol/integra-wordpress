<?php

define('NLM_NPI_URL', 'https://clinicaltables.nlm.nih.gov/api/npi_idv/v3/search');


// National Library of Medicine NPI Integration
//
// Refer to fields documentation at:
// https://clinicaltables.nlm.nih.gov/apidoc/npi_org/v3/doc.html

class NLMNPI
{
    public function GetData($terms, $count = 10, $fields = ['name.prefix', 'name.first', 'name.last', 'addr_practice.line1', 'addr_practice.line2', 'addr_practice.country', 'addr_practice.state', 'addr_practice.city', 'addr_practice.zip', 'addr_mailing.phone', 'provider_type', 'NPI', 'licenses'])
    {
        $request = wp_remote_get(NLM_NPI_URL . '?terms=' . $terms . '&df=' . implode(',', $fields) . '&sf=NPI,name.full,provider_type,addr_practice.full' . '&maxList=' . $count);
        $response = wp_remote_retrieve_body($request);
        $data = json_decode($response);

        return $data;
    }
}
