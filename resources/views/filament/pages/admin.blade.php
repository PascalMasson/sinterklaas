<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">
            Cadeaus
        </x-slot>

        <form
            wire:submit="saveDatabaseJson"
            id="database-import"
            class="mx-3 mt-3 mb-3"
            enctype="multipart/form-data"
        >
            @csrf

            <label
                class="mb-2 block text-sm font-medium text-gray-900 dark:text-gray-300"
                for="fileupload"
            >
                JSON data selecteren
            </label>

            <input
                wire:model="databaseJson"
                type="file"
                class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-transparent text-sm text-gray-900 focus:outline-none"
                id="fileupload"
                accept=".json"
                name="data"
            />

            <button
                type="submit"
                form="database-import"
                class="mt-2 inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:text-blue-700 focus:ring-2 focus:ring-blue-700 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:hover:text-white dark:focus:text-white dark:focus:ring-blue-500"
            >
                Importeren
            </button>
        </form>

        <x-filament::button wire:click="resetCadeaus()" wire:confirm="Weet je zeker dat je alle cadeaus wil resetten naar vrij?">Reset naar vrij</x-filament::button>
        <x-filament::button wire:click="deletePurchased()">Verwijder gekochte cadeaus</x-filament::button>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Lootjes
        </x-slot>

{{--        <x-filament::button wire:click="draw()" wire:confirm="Weet je zeker dat je nieuwe lootjes wil trekken?">Lootjes trekken</x-filament::button>--}}
        <x-filament::button wire:click="checkLootjes()">Lootjes checken</x-filament::button>
    </x-filament::section>
</x-filament-panels::page>
