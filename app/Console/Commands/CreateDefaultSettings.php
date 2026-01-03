<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class CreateDefaultSettings extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:create-default-settings';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Used by the install script to create the default settings';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		foreach ([
			Setting::BROADCAST_VIDEO_WIDTH => 1280,
			Setting::BROADCAST_VIDEO_HEIGHT => 720,
			Setting::BROADCAST_BACKGROUND_PATH => 'default-background.png',
			Setting::BROADCAST_URL => '',
			Setting::NIGHTBOT_API_TOKEN => Setting::generateApiToken(),
		] as $name => $value)
			Setting::create([
				'name' => $name,
				'value' => $value,
			]);
	}
}
