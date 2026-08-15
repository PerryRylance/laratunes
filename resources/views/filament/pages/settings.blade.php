<x-filament-panels::page>
    <div class="flex flex-col gap-6">
        <flux:callout :variant="$ytdlpInstalled ? 'success' : 'secondary'" icon="film">
            <flux:callout.heading>
                yt-dlp
            </flux:callout.heading>
            <flux:callout.text>
                @if( $ytdlpInstalled )
                    Version: {{ $ytdlpVersion }}
                    @if( $ytdlpLatestVersion )
                        @if( $ytdlpLatestVersion === $ytdlpVersion )
                            (up to date)
                        @else
                            (latest available: {{ $ytdlpLatestVersion }})
                        @endif
                    @endif
                @else
                    Not installed
                @endif
            </flux:callout.text>
            @if( $ytdlpInstalled )
                <flux:button
                    variant="primary"
                    class="self-start"
                    wire:click="upgradeYtdlp"
                >
                    Upgrade
                </flux:button>
            @else
                <flux:button
                    variant="primary"
                    class="self-start"
                    wire:click="installYtdlp"
                    wire:confirm="yt-dlp should only be used on videos you have permission to download. Continue?"
                >
                    Install
                </flux:button>
            @endif
        </flux:callout>

        <form wire:submit="save">
            {{ $this->form }}
        </form>
    </div>
    <x-filament-actions::modals />
</x-filament-panels::page>
