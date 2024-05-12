@php
    $lot = \App\Models\User::where("id", auth()->user()->lot_id)->first();
@endphp
<x-filament-panels::page>
    <span>Ik heb het lootje van:</span>
    <x-filament::input.wrapper>
        <x-filament::input.select wire:model.live="selectedLotID">
            <option value="">Kies een lootje</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected($user->id == $mijnLotID)>{{ $user->name }}</option>
            @endforeach
        </x-filament::input.select>
    </x-filament::input.wrapper>
    <div>
        <x-filament::button tag="a"
                          :href='\App\Filament\Resources\CadeauResource::getUrl(parameters: ["listId" => $lot->id])'>
            Lijstje bekijken
        </x-filament::button>
        <x-filament::button tag="a"
                          :href='\App\Filament\Resources\FopperResource::getUrl(parameters: ["targetId" => $lot->id])'>
            Foppers bekijken
        </x-filament::button>
    </div>
</x-filament-panels::page>
