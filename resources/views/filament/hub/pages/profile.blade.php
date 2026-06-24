<x-filament-panels::page>
    <form wire:submit="saveAction" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            {{ $this->saveAction }}
        </div>
    </form>

    <form wire:submit="changePasswordAction" class="space-y-6">
        {{ $this->passwordForm }}

        <div class="flex justify-end">
            {{ $this->changePasswordAction }}
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
