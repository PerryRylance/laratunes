<?php

namespace App\Filament\Widgets;

use App\Facades\Buffer;
use App\Facades\Transmission;
use App\Filament\Pages\Dashboard;
use App\Models\Setting;
use App\Models\Track;
use Filament\Widgets\Widget;
use Illuminate\View\View;

class StatusWidget extends Widget
{
	public bool $transmitting;

	public bool $isConfigured;

	public int $hasTracks;

	// TOOD: Don't think this is needed?
	protected string $view = 'filament.widgets.status-widget';

	protected static ?int $sort = 0;

	protected int|string|array $columnSpan = 'full';

	protected static bool $isLazy = false;

	public function broadcast()
	{
		chdir(base_path());
		exec('nohup php artisan app:start-broadcast > storage/logs/broadcast.log 2>&1 &', $output, $result);

		$timer = 0;

		while (! Transmission::running() && $timer++ < 10)
			sleep(1);

		// TODO: Flash or return error?
		// TODO: Test out some popular scenarios like connection rejected

		return redirect()->to(Dashboard::getUrl());
	}

	public function restart()
	{
		Buffer::restart();
		Transmission::restart();

		return redirect()->to(Dashboard::getUrl());
	}

	public function render(): View
	{
		$this->hasTracks = Track::exists();
		$this->isConfigured = Setting::isFullyConfigured();
		$this->transmitting = Transmission::running();

		return parent::render();
	}
}
