<?php
date_default_timezone_set("Asia/Tehran");

$types = json_decode(file_get_contents(__DIR__ . "/channels.json"), true);

$donated_subscription = [
    "https://yebekhe.000webhostapp.com/donate/donated_servers/donated_server.json"
];
