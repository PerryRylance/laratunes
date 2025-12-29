<?php

namespace App\Filament\Widgets;

use App\Facades\Transmission;
use App\Filament\Pages\Dashboard;
use Filament\Widgets\Widget;

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

        $timer = 0;

        while(!Transmission::running() && $timer++ < 10)
            sleep(1);

        // TODO: Flash or return error?
        // TODO: Test out some popular scenarios like connection rejected

        return redirect()->to(Dashboard::getUrl());
    }
}
