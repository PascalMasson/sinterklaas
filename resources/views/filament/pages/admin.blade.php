<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Cadeaus
        </x-slot>

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
