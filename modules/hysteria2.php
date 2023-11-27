<?php

function parseHy2 ($config_str) {
    $parsedUrl = parse_url($config_str);

    // Extract the parameters from the query string
    $params = [];
    if (isset($parsedUrl["query"])) {
        parse_str($parsedUrl["query"], $params);
    }

    // Construct the output object
    $output = [
        "protocol" => "hy2",
        "username" => isset($parsedUrl["user"]) ? $parsedUrl["user"] : "",
        "hostname" => isset($parsedUrl["host"]) ? $parsedUrl["host"] : "",
        "port" => isset($parsedUrl["port"]) ? $parsedUrl["port"] : "",
        "params" => $params,
        "hash" => isset($parsedUrl["fragment"]) ? $parsedUrl["fragment"] : "",
    ];

    return $output;

}

function buildHy2($obj)
{
    $url = "hy2://";
    $url .= addUsernameAndPassword($obj);
    $url .= $obj["hostname"];
    $url .= addPort($obj);
    $url .= addParams($obj);
    $url .= addHash($obj);
    return $url;
}

function remove_duplicate_hy2($input)
{
    $array = explode("\n", $input);
    $result = [];

    foreach ($array as $item) {
        $parts = parseHy2($item);
        $part_host_port = $parts["hostname"] . ":" . $parts["port"];
        unset($parts["hostname"]);
        unset($parts["port"]);
        ksort($parts["params"]);
        $part_serialize = base64_encode(serialize($parts));
        $result[$part_serialize][] = $part_host_port ?? "";
    }

    $finalResult = [];
    foreach ($result as $url => $parts) {
        $partAfterHash = $parts[0] ?? "";
        $part_serialize = unserialize(base64_decode($url));
        $part_serialize["hostname"] = explode(":", $partAfterHash)[0] ?? "";
        $part_serialize["port"] = explode(":", $partAfterHash)[1] ?? "";
        $finalResult[] = buildHy2($part_serialize);
    }

    $output = implode("\n", $finalResult);
    return $output;
}
