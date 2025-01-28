<?php

class ALM
{

    public function get_participants( $query ) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://zimmer.zimvie-test.atmoapps.net/alm/participants/search?query=' . urlencode( $query ) . '&limit=10',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'x-api-key:XWaL7fi9sU16q5ubFu7l89TXcqgmfx7R10hQN67J'
            ),
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $rval = array('success' => true, 'status_code' => $http_code);

        if ($http_code === 200) {
            $rval['data'] = json_decode($response);
        } else {
            $rval['success'] = false;
            $rval['data'] = curl_error($curl);
        }

        curl_close($curl);

        return $rval;
    }

    public function get_individual( $email ) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://zimmer.zimvie-test.atmoapps.net/alm/participants/' .$email,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'x-api-key:XWaL7fi9sU16q5ubFu7l89TXcqgmfx7R10hQN67J'
            ),
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $rval = array('success' => true, 'status_code' => $http_code);

        if ($http_code === 200) {
            $rval['data'] = json_decode($response);
        } else {
            $rval['success'] = false;
            $rval['data'] = curl_error($curl);
        }

        curl_close($curl);

        return $rval;
    }
}
