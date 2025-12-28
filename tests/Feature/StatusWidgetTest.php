<?php

namespace Tests\Feature;

use App\Filament\Widgets\StatusWidget;
use Livewire\Livewire;
use Tests\AdminTestCase;

class StatusWidgetTest extends AdminTestCase
{
    public function testCanSeeNotPlayingStatus(): void
    {
        Livewire::test(StatusWidget::class)
            ->assertSee('not broadcasting');
    }

    public function testCanSeePlayingStatus(): void
    {

    }
}
