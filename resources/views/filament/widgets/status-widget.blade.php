<x-filament-widgets::widget>
    @if( $transmitting )
        <flux:callout variant="success" icon="check-circle" heading="The stream is broadcasting!" />
    @else
        <flux:callout variant="danger" icon="x-circle" heading="The stream is not broadcasting presently.">
            <flux:button 
                variant="primary" 
                class="self-start"
                wire:click="broadcast"
            >
                <div class="flex items-center gap-2">
                    <flux:icon.play />
                    Start Broadcast
                </div>
            </flux:button>
        </flux:callout>
    @endif
</x-filament-widgets::widget>
