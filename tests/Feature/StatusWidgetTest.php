<?php

namespace Tests\Feature;

use App\Facades\Transmission;
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
}
