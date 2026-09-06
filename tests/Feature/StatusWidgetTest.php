<?php

namespace Tests\Feature;

use App\Exceptions\BroadcastSupervisorException;
use App\Facades\BroadcastSupervisor;
use App\Facades\Transmission;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\StatusWidget;
use Livewire\Livewire;
use Tests\AdminTestCase;

class StatusWidgetTest extends AdminTestCase
{
	public function testCanSeeNotPlayingStatus(): void
	{
		Livewire::test(StatusWidget::class)
			->assertSee('Broadcast inactive')
			->assertSee('Start Broadcast');
	}

	public function testCanSeePlayingStatus(): void
	{
		Transmission::expects('running')
			->andReturn(true);

		Livewire::test(StatusWidget::class)
			->assertSee('Broadcast active!');
	}

	public function testDoesNotSeeRestartButtonWhenNotPlaying(): void
	{
		Livewire::test(StatusWidget::class)
			->assertDontSee('Restart Broadcast');
	}

	public function testCanSeeRestartButtonWhenPlaying(): void
	{
		Transmission::expects('running')
			->andReturn(true);

		Livewire::test(StatusWidget::class)
			->assertSee('Restart Broadcast')
			->assertSeeHtml('wire:confirm=');
	}

	public function testRestartingRestartsTheWholeSupervisor(): void
	{
		Transmission::expects('running')
			->andReturn(true);

		BroadcastSupervisor::expects('start')
			->once();

		Livewire::test(StatusWidget::class)
			->call('restart')
			->assertRedirect(Dashboard::getUrl());
	}

	public function testStartingBroadcastStartsTheWholeSupervisor(): void
	{
		BroadcastSupervisor::expects('start')
			->once();

		Livewire::test(StatusWidget::class)
			->call('broadcast')
			->assertRedirect(Dashboard::getUrl());
	}

	public function testRestartShowsAnErrorNotificationWhenTheSupervisorFailsToComeUp(): void
	{
		Transmission::shouldReceive('running')
			->andReturn(true);

		BroadcastSupervisor::expects('start')
			->once()
			->andThrow(new BroadcastSupervisorException('did not come up'));

		Livewire::test(StatusWidget::class)
			->call('restart')
			->assertNotified('Failed to restart the broadcast');
	}
}
