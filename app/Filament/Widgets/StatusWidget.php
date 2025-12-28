<?php

namespace App\Filament\Widgets;

use App\Facades\Transmission;
use App\Filament\Pages\Dashboard;
use Filament\Widgets\Widget;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class StatusWidget extends Widget
{
    public bool $transmitting;

    protected string $view = 'filament.widgets.status-widget';
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full';

    public function __construct()
    {
        $this->transmitting = Transmission::running();
    }

    public function broadcast()
    {
        chdir(base_path());
        exec("nohup php artisan app:start-broadcast > storage/logs/broadcast.log 2>&1 &", $output, $result);

        // NB: Allow a few seconds for the stream to start
        sleep(5);

        return redirect()->to(Dashboard::getUrl());
    }
}
