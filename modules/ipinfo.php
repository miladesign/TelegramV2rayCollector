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
        $response = get_remote_data($url);

        // Check for successful response (status code 200)
        if ($response['info']['http_code'] === 200) {
            // Decode the JSON response
            $data = json_decode($response['data']);

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

function get_remote_data($url, $post_paramtrs=false,  $curl_opts=[])	
{ 
	$c = curl_init(); 
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	//if parameters were passed to this function, then transform into POST method.. (if you need GET request, then simply change the passed URL)
	if($post_paramtrs){ curl_setopt($c, CURLOPT_POST,TRUE);  curl_setopt($c, CURLOPT_POSTFIELDS, (is_array($post_paramtrs)? http_build_query($post_paramtrs) : $post_paramtrs) ); }
	curl_setopt($c, CURLOPT_SSL_VERIFYHOST,false); 
	curl_setopt($c, CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($c, CURLOPT_COOKIE, 'CookieName1=Value;'); 
		$headers[]= "User-Agent: Mozilla/5.0 (Windows NT 6.1; rv:76.0) Gecko/20100101 Firefox/76.0";	 $headers[]= "Pragma: ";  $headers[]= "Cache-Control: max-age=0";
		if (!empty($post_paramtrs) && !is_array($post_paramtrs) && is_object(json_decode($post_paramtrs))){ $headers[]= 'Content-Type: application/json'; $headers[]= 'Content-Length: '.strlen($post_paramtrs); }
	curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($c, CURLOPT_MAXREDIRS, 10); 
	//if SAFE_MODE or OPEN_BASEDIR is set,then FollowLocation cant be used.. so...
	$follow_allowed= ( ini_get('open_basedir') || ini_get('safe_mode')) ? false:true;  if ($follow_allowed){curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);}
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 9);
	curl_setopt($c, CURLOPT_REFERER, $url);    
	curl_setopt($c, CURLOPT_TIMEOUT, 60);
	curl_setopt($c, CURLOPT_AUTOREFERER, true);
	curl_setopt($c, CURLOPT_ENCODING, '');
	curl_setopt($c, CURLOPT_HEADER, !empty($extra['return_array']));
	//set extra options if passed
	if(!empty($curl_opts)) foreach($curl_opts as $key=>$value) curl_setopt($c, constant($key), $value);
	$data = curl_exec($c);
	if(!empty($extra['return_array'])) { 
		 preg_match("/(.*?)\r\n\r\n((?!HTTP\/\d\.\d).*)/si",$data, $x); preg_match_all('/(.*?): (.*?)\r\n/i', trim('head_line: '.$x[1]), $headers_, PREG_SET_ORDER); foreach($headers_ as $each){ $header[$each[1]] = $each[2]; }   $data=trim($x[2]); 
	}
	$status=curl_getinfo($c); curl_close($c);
	// if redirected, then get that redirected page
	if($status['http_code']==301 || $status['http_code']==302) { 
		//if we FOLLOWLOCATION was not allowed, then re-get REDIRECTED URL
		//p.s. WE dont need "else", because if FOLLOWLOCATION was allowed, then we wouldnt have come to this place, because 301 could already auto-followed by curl  :)
		if (!$follow_allowed){
			//if REDIRECT URL is found in HEADER
			if(empty($redirURL)){if(!empty($status['redirect_url'])){$redirURL=$status['redirect_url'];}}
			//if REDIRECT URL is found in RESPONSE
			if(empty($redirURL)){preg_match('/(Location:|URI:)(.*?)(\r|\n)/si', $data, $m);	                if (!empty($m[2])){ $redirURL=$m[2]; } }
			//if REDIRECT URL is found in OUTPUT
			if(empty($redirURL)){preg_match('/moved\s\<a(.*?)href\=\"(.*?)\"(.*?)here\<\/a\>/si',$data,$m); if (!empty($m[1])){ $redirURL=$m[1]; } }
			//if URL found, then re-use this function again, for the found url
			if(!empty($redirURL)){$t=debug_backtrace(); return call_user_func( $t[0]["function"], trim($redirURL), $post_paramtrs);}
		}
	}
	// if not redirected,and nor "status 200" page, then error..
	elseif ( $status['http_code'] != 200 ) { $data =  "ERRORCODE22 with $url<br/><br/>Last status codes:".json_encode($status)."<br/><br/>Last data got:$data";}
	//URLS correction
	$answer = ( !empty($extra['return_array']) ? array('data'=>$data, 'header'=>$header, 'info'=>$status) : $data);
	return $answer;      
}     