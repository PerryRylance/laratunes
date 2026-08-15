<?php

return [

	'binary_path' => env('YTDLP_BINARY_PATH', '/usr/local/bin/yt-dlp'),

	'download_url' => env('YTDLP_DOWNLOAD_URL', 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp'),

	'latest_version_url' => env('YTDLP_LATEST_VERSION_URL', 'https://api.github.com/repos/yt-dlp/yt-dlp/releases/latest'),

	'timeout' => env('YTDLP_TIMEOUT', 120),

];
