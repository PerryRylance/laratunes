<?php

header('Content-type: text/plain');

if($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

if(!isset($_GET['file']))
{
    http_response_code(400);
    echo "File not specified";
    exit;
}

$file = $_POST['file'];
$absolute = "/root/audio/$file";

if(!file_exists($absolute))
{
    http_response_code(404);
    echo "File $absolute not found";
    exit;
}

$escaped = escapeshellarg($absolute);

echo shell_exec("/usr/local/bin/olaf query --fragmented $escaped");
