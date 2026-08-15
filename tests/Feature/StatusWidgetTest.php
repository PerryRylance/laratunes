<?php

namespace Tests\Feature;

use App\Facades\Buffer;
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

	public function testRestartingRestartsTheBufferAndTransmissionServices(): void
	{
		Transmission::expects('running')
			->andReturn(true);

		Buffer::expects('restart')
			->once();

		Transmission::expects('restart')
			->once();

		Livewire::test(StatusWidget::class)
			->call('restart')
			->assertRedirect(Dashboard::getUrl());
	}
}
