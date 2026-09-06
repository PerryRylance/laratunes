<?php

namespace App\Filament\Widgets;

use App\Exceptions\BroadcastSupervisorException;
use App\Facades\BroadcastSupervisor;
use App\Facades\Transmission;
use App\Filament\Pages\Dashboard;
use App\Models\Setting;
use App\Models\Track;
use Filament\Notifications\Notification;
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
		return $this->startSupervisor();
	}

	public function restart()
	{
		return $this->startSupervisor();
	}

	// NB: Both actions go through the same start() - it always stops any previous broadcast
	// supervisor (and any of its stray ffmpeg processes) before starting a fresh one, so a
	// "restart" can't leave the old buffering/transmission processes running alongside the new
	// ones, and a "start" clicked while one is already stuck can't spawn a duplicate
	private function startSupervisor()
	{
		try
		{
			BroadcastSupervisor::start();
		}
		catch (BroadcastSupervisorException $exception)
		{
			Notification::make()
				->danger()
				->title('Failed to restart the broadcast')
				->body($exception->getMessage())
				->persistent()
				->send();

			return null;
		}

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
