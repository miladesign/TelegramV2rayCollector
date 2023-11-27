<?php
function decode_vmess($vmess_config)
{
    $vmess_data = substr($vmess_config, 8); // remove "vmess://"
    $decoded_data = json_decode(base64_decode($vmess_data), true);
    return $decoded_data;
}

function encode_vmess($config)
{
    $encoded_data = base64_encode(json_encode($config));
    $vmess_config = "vmess://" . $encoded_data;
    return $vmess_config;
}

function remove_duplicate_vmess($input)
{
    $array = explode("\n", $input);
    $result = [];
    
    foreach ($array as $item) {
        $parts = decode_vmess($item);
        
        if ($parts !== NULL) {
            $part_add_port = $parts["add"] . ":" . $parts["port"];
            unset($parts["add"]);
            unset($parts["port"]);
            
            if (count($parts) >= 1) {
                ksort($parts);
                $part_serialize = base64_encode(serialize($parts));
                $result[$part_serialize][] = $part_add_port ?? "";
            }
        }
    }

    $finalResult = [];
    foreach ($result as $serial => $add_ports) {
        $partAfterHash = $add_ports[0] ?? "";
        $part_serialize = unserialize(base64_decode($serial));
        $part_serialize["add"] = explode(":", $partAfterHash)[0] ?? "";
        $part_serialize["port"] = explode(":", $partAfterHash)[1] ?? "";
        $finalResult[] = encode_vmess($part_serialize);
    }

    $output = implode("\n", $finalResult);
    return $output;
}
