<x-filament-widgets::widget>
    @if( $transmitting )
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.heading>
                Broadcast active!
            </flux:callout.heading>
            <flux:callout.text>
                Stream is being transmitted.
            </flux:callout.text>
            <flux:button
                variant="danger"
                class="self-start"
                wire:click="restart"
                wire:confirm="Are you sure you want to restart the broadcast? This will briefly interrupt the stream."
            >
                <div class="flex items-center gap-2">
                    <flux:icon.arrow-path />
                    Restart Broadcast
                </div>
            </flux:button>
        </flux:callout>
    @else
        <flux:callout variant="danger" icon="x-circle">
            <flux:callout.heading>
                Broadcast inactive
            </flux:callout.heading>
            @if(!$isConfigured || !$hasTracks)
                <flux:callout.text>
                    The broadcast cannot be started:
                    <div class="prose" style="--tw-prose-body: inherit; --tw-prose-bullets: var(--color-gray-500); font-size: inherit;">
                        <ul>
                            @if( !$isConfigured )
                                <li>
                                    The stream is not configured.
                                </li>
                            @endif
                            @if( !$hasTracks )
                                <li>
                                    There are no tracks in your library.
                                </li>
                            @endif
                        </ul>
                    </div>
                </flux:callout.text>
            @endif
            <flux:button 
                variant="primary" 
                class="self-start"
                wire:click="broadcast"
                :disabled="!($hasTracks && $isConfigured)"
            >
                <div class="flex items-center gap-2">
                    <flux:icon.play />
                    Start Broadcast
                </div>
            </flux:button>
        </flux:callout>
    @endif
</x-filament-widgets::widget>
