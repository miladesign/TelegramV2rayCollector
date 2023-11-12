<?php

function is_ip($string)
{
    $ip_pattern = '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/';
    if (preg_match($ip_pattern, $string)) {
        return true;
    } else {
        return false;
    }
}

function ip_info($ip) {
    if (is_ip($ip) === false) {
        $ip_address_array = dns_get_record($ip, DNS_A);
        $randomKey = array_rand($ip_address_array);
        $ip = $ip_address_array[$randomKey]["ip"];
    }

    // List of API endpoints
    $endpoints = [
        'https://api.ipbase.com/v1/json/{ip}',
        'https://ipapi.co/{ip}/json/',
        'https://ipwhois.app/json/{ip}',
        'http://www.geoplugin.net/json.gp?ip={ip}'
    ];

    // Initialize an empty result object
    $result = (object) [
        'ip' => $ip,
        'country_code' => "XX",
        'country_name' => "UNKNOWN"
        // Add more properties as needed
    ];

    // Loop through each endpoint
    foreach ($endpoints as $endpoint) {
        // Construct the full URL
        $url = str_replace('{ip}', $ip, $endpoint);

        // Make the HTTP request
        $response = file_get_contents($url);

        // Check for successful response (status code 200)
        if ($http_response_header[0] === 'HTTP/1.1 200 OK') {
            // Decode the JSON response
            $data = json_decode($response);

            // Extract relevant information and update the result object
            if ($endpoint == $endpoints[0]) {
                // Data from ipbase.com
                $result->country_code = $data->country_code ?? "XX";
                $result->country_name = $data->country_name ?? "UNKNOWN";
            } elseif ($endpoint == $endpoints[1]) {
                // Data from ipapi.co
                $result->country_code = $data->country_code ?? "XX";
                $result->country_name = $data->country_name ?? "UNKNOWN";
            } elseif ($endpoint == $endpoints[2]) {
                // Data from ipwhois.app
                $result->country_code = $data->country_code ?? "XX";
                $result->country_name = $data->country ?? "UNKNOWN";
            } elseif ($endpoint == $endpoints[3]) {
                // Data from geoplugin.net
                $result->country_code = $data->geoplugin_countryCode ?? "XX";
                $result->country_name = $data->geoplugin_countryName ?? "UNKNOWN";
            }

            // Break out of the loop since we found a successful endpoint
            break;
        }
    }

    return $result;
}