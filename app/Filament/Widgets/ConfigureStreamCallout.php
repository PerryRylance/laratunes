<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ConfigureStreamCallout extends Widget
{
	protected string $view = 'filament.widgets.configure-stream-callout';

	protected array|string|int $columnSpan = 'full';

	protected static bool $isLazy = false;

	public static function canView(): bool
	{
		return true;
	}
}
