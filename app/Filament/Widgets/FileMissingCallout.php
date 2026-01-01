<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class FileMissingCallout extends Widget
{
	protected string $view = 'filament.widgets.file-missing-callout';

	protected array|string|int $columnSpan = 2;
}
