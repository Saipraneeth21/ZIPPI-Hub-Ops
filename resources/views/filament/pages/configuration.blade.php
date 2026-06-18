<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Platform settings</x-slot>
        <x-slot name="description">Sourced from <code>config/rental.php</code> (env-driven, read-only).</x-slot>

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($this->getSettings() as $label => $value)
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd class="mt-1 text-lg font-semibold">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Cancellation policy tiers</x-slot>

        <ul class="space-y-2">
            @foreach ($this->getCancellationTiers() as $tier)
                <li class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-2 dark:border-gray-700">
                    <span>{{ $tier['lead'] }}</span>
                    <span class="font-medium">{{ $tier['refund'] }}</span>
                </li>
            @endforeach
        </ul>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Editing these values</x-slot>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            These are environment/config driven. To make them editable from the dashboard,
            introduce a DB-backed settings table (e.g. <code>spatie/laravel-settings</code>)
            and bind <code>config/rental.php</code> to it. Hubs are already editable under
            <strong>Settings &rarr; Hubs</strong>.
        </p>
    </x-filament::section>
</x-filament-panels::page>
