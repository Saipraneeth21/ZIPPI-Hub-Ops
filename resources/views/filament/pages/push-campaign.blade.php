<x-filament-panels::page>
    <form wire:submit="sendAction" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            {{ $this->sendAction }}
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
