<?php

return [

	// NB: How long BroadcastSupervisorService will wait for buffering, transmission and
	// monitoring to all report as running before giving up and throwing
	'startup_timeout' => env('BROADCAST_STARTUP_TIMEOUT', 15),

];
