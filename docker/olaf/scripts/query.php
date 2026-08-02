<?php

// NB: Give this more headroom than OLAF_TIMEOUT so the calling Guzzle client - not this
// script's own execution limit - is what decides when a slow request gives up.
set_time_limit(((int) (getenv('OLAF_TIMEOUT') ?: 120)) + 30);

header('Content-type: text/plain');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
	http_response_code(405);
	echo 'Method not allowed';
	exit;
}

if (! isset($_GET['file']))
{
	http_response_code(400);
	echo 'File not specified';
	exit;
}

$file = $_GET['file'];
$absolute = "/root/audio/$file";

if (! file_exists($absolute))
{
	http_response_code(404);
	echo "File $absolute not found";
	exit;
}

$escaped = escapeshellarg($absolute);

// TODO: Might be able to do without --fragmented since we're only interested in whole songs
echo shell_exec("/usr/local/bin/olaf query --no-identity-match --fragmented $escaped");
