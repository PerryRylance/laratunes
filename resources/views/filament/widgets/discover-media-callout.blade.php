<x-filament-widgets::widget class="discover-media-callout-widget" wire:poll.5s>
    @if($numQueuedJobs > 0)
        <flux:callout icon="clock">
            <flux:callout.heading>
                Discovery in progress
            </flux:callout.heading>
            <flux:callout.text>
                Your media directory is being scanned for files and tracks are being added to the library.
            </flux:callout.text>
            <section class="progress">
                <progress value={{ $numProcessedFiles }} max={{ $numFiles }}></progress>
                <span>
                    <!-- NB: Some weird styling without wrapping this in a span, probs because the parent is flex -->
                    <flux:icon.loading />
                </span>
                ({{ $numProcessedFiles }} / {{ $numFiles }})
            </section>
        </flux:callout>
    @elseif($numTracks > 0)
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.heading>
                Discovery complete!
            </flux:callout.heading>
            <flux:callout.text>
                There are {{ $numTracks }} tracks in your library.
            </flux:callout.text>
        </flux:callout>
    @else
        <flux:callout variant="warning" icon="x-circle">
            <flux:callout.heading>
                No tracks in library.
            </flux:callout.heading>
            <flux:callout.text>
                Add tracks manually via the <a href="/admin/tracks/create" wire:navigate>Create Track</a> page, or use the button below to discover tracks in your media directory.
            </flux:callout.text>
            <flux:button 
                variant="primary" 
                class="self-start"
                wire:click="discover"
            >
                <div class="flex items-center gap-2">
                    <flux:icon.magnifying-glass />
                    Discover Media
                </div>
            </flux:button>
        </flux:callout>
    @endif
</x-filament-widgets::widget>
