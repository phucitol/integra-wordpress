<?php
// CVENT AUTH
define('CVENT_CLIENT_ID', get_option('cvent_client_id'));
define('CVENT_PASSWORD', get_option('cvent_client_password'));

class CVENT
{

    public function get_token()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-platform.cvent.com/ea/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_USERPWD => CVENT_CLIENT_ID . ":" . CVENT_PASSWORD,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials&client_id=' . CVENT_CLIENT_ID . '&scope=',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
            ),
        ));

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code == 200) {
            $response = json_decode($response);

            if (!json_last_error()) {
                return $response->access_token;
            }
        }

        return null;
    }

    public function get_event_id($eventCode, $token)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-platform.cvent.com/ea/events?filter=' . urlencode('code eq \'' . $eventCode . '\''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token
            ),
        ));


        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);


        if ($http_code == 200) {
            $response = json_decode($response);

            if (!json_last_error()) {
                if ($response->paging->totalCount > 0) {
                    return $response->data[0]->id;
                } else {
                    return null;
                }
            }
        }

        return null;
    }

    public function get_participants($eventCode)
    {

        $token = $this->get_token();
        $eventID = $this->get_event_id($eventCode, $token);

        if (!$eventID) {
            return null;
        }
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-platform.cvent.com/ea/attendees?filter=' . urlencode('event.id eq \'' . $eventID . '\' and status eq \'Accepted\''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token
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
