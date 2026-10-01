<?php

return [

	// NB: How long BroadcastSupervisorService will wait for buffering, transmission and
	// monitoring to all report as running before giving up and throwing
	'startup_timeout' => env('BROADCAST_STARTUP_TIMEOUT', 15),

	// NB: How often app:supervise-broadcast polls BroadcastSupervisor::isFullyRunning()
	'watchdog_check_interval' => env('BROADCAST_WATCHDOG_CHECK_INTERVAL', 10),

	// NB: How long the broadcast may be unhealthy before the watchdog steps in and restarts it -
	// should comfortably exceed how long app:start-broadcast's own internal recovery takes, so a
	// normal in-process recovery isn't mistaken for the broadcast actually being down
	'watchdog_grace_period' => env('BROADCAST_WATCHDOG_GRACE_PERIOD', 120),

];
