<?php
header("Content-type: application/json;"); // Set response content type as JSON

include "modules/get_data.php"; // Include the get_data module
include "modules/config.php"; // Include the config module
include "modules/ranking.php"; // Include the ranking module

function deleteFolder($folder) {
    if (!is_dir($folder)) {
        return;
    }
    $files = glob($folder . '/*');
    foreach ($files as $file) {
        is_dir($file) ? deleteFolder($file) : unlink($file);
    }
    rmdir($folder);
}

function deleteFile($file) {
    if (file_exists($file)) {
        unlink($file);
    }
}

function process_mix_json($input, $name)
{
    $mix_data_json = json_encode($input, JSON_PRETTY_PRINT); // Encode input array to JSON with pretty printing
    $mix_data_decode = json_decode($mix_data_json); // Decode the JSON into an object or array
    usort($mix_data_decode, "compare_time"); // Sort the decoded data using the "compare_time" function
    $mix_data_json = json_encode(
        $mix_data_decode,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ); // Re-encode the sorted data to JSON with pretty printing and Unicode characters not escaped
    $mix_data_json = urldecode($mix_data_json);
    $mix_data_json = str_replace("amp;", "", $mix_data_json); // Replace HTML-encoded ampersands with regular ampersands
    $mix_data_json = str_replace("\\", "", $mix_data_json); // Remove backslashes from the JSON string
    file_put_contents($name, $mix_data_json); // Save the JSON data to a file with the specified name
}

function fast_fix($input){
    $input = urldecode($input);
    $input = str_replace("amp;", "", $input);
    return $input;
}

function config_array($input){
    return array_map(function ($object) {
    return $object["config"];
}, $input);
}

$raw_url_base =
    "https://raw.githubusercontent.com/miladesign/TelegramV2rayCollector/main"; // Define the base URL for fetching raw data

$vmess_data = []; // Initialize an empty array for vmess data
$trojan_data = []; // Initialize an empty array for trojan data
$vless_data = []; // Initialize an empty array for vless data
$shadowsocks_data = []; // Initialize an empty array for shadowsocks data
$tuic_data = []; // Initialize an empty array for tuic data
$hy2_data = []; // Initialize an empty array for hy2 data


//Get data from channels
foreach ($Types as $channelUsername => $type_array) {
    $count = count($type_array);
    for ($type_count = $count - 1; $type_count >= 0; $type_count--) {
        $current_type = $type_array[$type_count];
        if ($current_type === "vmess") {
                // Merge the results of `get_config` function with $vmess_data array
                $vmess_data = array_merge(
                    $vmess_data,
                    /** @scrutinizer ignore-call */ 
                    get_config($channelUsername, $current_type)
                );
        } 
        if ($current_type === "vless") {
                // Merge the results of `get_config` function with $vless_data array
                $vless_data = array_merge(
                    $vless_data,
                    /** @scrutinizer ignore-call */
                    get_config($channelUsername, $current_type)
                );
        } 
        if ($current_type === "trojan") {
                // Merge the results of `get_config` function with $trojan_data array
                $trojan_data = array_merge(
                    $trojan_data,
                    /** @scrutinizer ignore-call */
                    get_config($channelUsername, $current_type)
                );
        } 
        if($current_type === "ss") {
                // Merge the results of `get_config` function with $shadowsocks_data array
                $shadowsocks_data = array_merge(
                    $shadowsocks_data,
                    /** @scrutinizer ignore-call */
                    get_config($channelUsername, $current_type)
                );
        } 
        if ($current_type === "tuic") {
                // Merge the results of `get_config` function with $tuic_data array
                $tuic_data = array_merge(
                    $tuic_data,
                    /** @scrutinizer ignore-call */
                    get_config($channelUsername, $current_type)
                );
        }
        if ($current_type === "hy2") {
            // Merge the results of `get_config` function with $tuic_data array
            $hy2_data = array_merge(
                $hy2_data,
                /** @scrutinizer ignore-call */
                get_config($channelUsername, $current_type)
            );
    }
    }
}

// Extract the "config" value from each object in $type_data and store it in $type_array
$vmess_array = config_array($vmess_data);
$vless_array = config_array($vless_data);
$trojan_array = config_array($trojan_data);
$shadowsocks_array = config_array($shadowsocks_data);
$tuic_array = config_array($tuic_data);
$hy2_array = config_array($hy2_data);

$fixed_string_vmess = remove_duplicate_vmess(implode("\n", $vmess_array));
$fixed_string_vmess_array = explode("\n", $fixed_string_vmess);
$json_vmess_array = [];

$added_configs_vmess = [];
// Iterate over $vmess_data and $fixed_string_vmess_array to find matching configurations
foreach ($vmess_data as $vmess_config_data) {
    foreach ($fixed_string_vmess_array as $vmess_config) {
        $decoded_vmess_config = decode_vmess($vmess_config);
        $decoded_vmess_data_config = decode_vmess($vmess_config_data["config"]);

        if ($decoded_vmess_config["ps"] === $decoded_vmess_data_config["ps"] &&
            !in_array($decoded_vmess_data_config["ps"], $added_configs_vmess)) {
            // Add matching configuration to $json_vmess_array
            $json_vmess_array[] = $vmess_config_data;
            $added_configs_vmess[] = $decoded_vmess_data_config["ps"]; // Mark as added
        }
    }
}

$string_vless = fast_fix(implode("\n", $vless_array));
$fixed_string_vless = remove_duplicate_xray($string_vless, "vless");
$fixed_string_vless_array = explode("\n", $fixed_string_vless);
$json_vless_array = [];

$added_configs_vless = [];
// Iterate over $vless_data and $fixed_string_vless_array to find matching configurations
foreach ($vless_data as $vless_config_data) {
    foreach ($fixed_string_vless_array as $vless_config) {
        $parsed_vless_config = parseProxyUrl($vless_config, "vless");
        $parsed_vless_data_config = parseProxyUrl($vless_config_data["config"], "vless");

        if ($parsed_vless_config["hash"] === $parsed_vless_data_config["hash"] &&
            !in_array($parsed_vless_data_config["hash"], $added_configs_vless)) {
            // Add matching configuration to $json_vless_array
            $json_vless_array[] = $vless_config_data;
            $added_configs_vless[] = $parsed_vless_data_config["hash"]; // Mark as added
        }
    }
}

$string_trojan = fast_fix(implode("\n", $trojan_array));
$fixed_string_trojan = remove_duplicate_xray($string_trojan, "trojan");
$fixed_string_trojan_array = explode("\n", $fixed_string_trojan);
$json_trojan_array = [];

$added_configs_trojan = [];
// Iterate over $trojan_data and $fixed_string_trojan_array to find matching configurations
foreach ($trojan_data as $trojan_config_data) {
    foreach ($fixed_string_trojan_array as $key => $trojan_config) {
        $parsed_trojan_config = parseProxyUrl($trojan_config);
        $parsed_trojan_data_config = parseProxyUrl($trojan_config_data["config"]);

        if ($parsed_trojan_config["hash"] === $parsed_trojan_data_config["hash"] &&
            !in_array($parsed_trojan_data_config["hash"], $added_configs_trojan)) {
            // Add matching configuration to $json_trojan_array
            $json_trojan_array[$key] = $trojan_config_data;
            $added_configs_trojan[] = $parsed_trojan_data_config["hash"]; // Mark as added
        }
    }
}

$string_shadowsocks = fast_fix(implode("\n", $shadowsocks_array));
$fixed_string_shadowsocks = remove_duplicate_ss($string_shadowsocks);
$fixed_string_shadowsocks_array = explode("\n", $fixed_string_shadowsocks);
$json_shadowsocks_array = [];

$added_configs_ss = [];
// Iterate over $shadowsocks_data and $fixed_string_shadowsocks_array to find matching configurations
foreach ($shadowsocks_data as $shadowsocks_config_data) {
    foreach ($fixed_string_shadowsocks_array as $shadowsocks_config) {
        $parsed_shadowsocks_config = ParseShadowsocks($shadowsocks_config);
        $parsed_shadowsocks_data_config = ParseShadowsocks($shadowsocks_config_data["config"]);

        if ($parsed_shadowsocks_config["name"] === $parsed_shadowsocks_data_config["name"] &&
            !in_array($parsed_shadowsocks_data_config["name"], $added_configs_ss)) {
            // Add matching configuration to $json_shadowsocks_array
            $json_shadowsocks_array[] = $shadowsocks_config_data;
            $added_configs_ss[] = $parsed_shadowsocks_data_config["name"]; // Mark as added
        }
    }
}

$string_tuic = fast_fix(implode("\n", $tuic_array));
$fixed_string_tuic = remove_duplicate_tuic($string_tuic);
$fixed_string_tuic_array = explode("\n", $fixed_string_tuic);
$json_tuic_array = [];

$added_configs_tuic = [];
// Iterate over $tuic_data and $fixed_string_tuic_array to find matching configurations
foreach ($tuic_data as $tuic_config_data) {
    foreach ($fixed_string_tuic_array as $key => $tuic_config) {
        $parsed_tuic_config = parseTuic($tuic_config);
        $parsed_tuic_data_config = parseTuic($tuic_config_data["config"]);

        if ($parsed_tuic_config["hash"] === $parsed_tuic_data_config["hash"] &&
            !in_array($parsed_tuic_data_config["hash"], $added_configs_tuic)) {
            // Add matching configuration to $json_tuic_array
            $json_tuic_array[$key] = $tuic_config_data;
            $added_configs_tuic[] = $parsed_tuic_data_config["hash"]; // Mark as added
        }
    }
}

$string_hy2 = fast_fix(implode("\n", $hy2_array));
$fixed_string_hy2 = remove_duplicate_hy2($string_hy2);
$fixed_string_hy2_array = explode("\n", $fixed_string_hy2);
$json_hy2_array = [];

$added_configs_hy2 = [];
// Iterate over $hy2_data and $fixed_string_hy2_array to find matching configurations
foreach ($hy2_data as $hy2_config_data) {
    foreach ($fixed_string_hy2_array as $key => $hy2_config) {
        $parsed_hy2_config = parsehy2($hy2_config);
        $parsed_hy2_data_config = parsehy2($hy2_config_data["config"]);

        if ($parsed_hy2_config["hash"] === $parsed_hy2_data_config["hash"] &&
            !in_array($parsed_hy2_data_config["hash"], $added_configs_hy2)) {
            // Add matching configuration to $json_hy2_array
            $json_hy2_array[$key] = $hy2_config_data;
            $added_configs_hy2[] = $parsed_hy2_data_config["hash"]; // Mark as added
        }
    }
}

$mix_data_deduplicate = array_merge(
    $json_vmess_array,
    $json_vless_array,
    $json_trojan_array,
    $json_shadowsocks_array,
    $json_tuic_array,
    $json_hy2_array
);

//process_mix_json($mix_data_deduplicate, "configs.json");