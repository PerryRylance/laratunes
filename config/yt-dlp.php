<?php

return [

	// NB: Not /usr/local/bin - that's root-owned in both the sail and production images, and the
	// app runs as an unprivileged user in both, so it can't write a new binary there at runtime.
	'binary_path' => env('YTDLP_BINARY_PATH', storage_path('app/yt-dlp/yt-dlp')),

	'download_url' => env('YTDLP_DOWNLOAD_URL', 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp'),

	'latest_version_url' => env('YTDLP_LATEST_VERSION_URL', 'https://api.github.com/repos/yt-dlp/yt-dlp/releases/latest'),

	'timeout' => env('YTDLP_TIMEOUT', 120),

];
