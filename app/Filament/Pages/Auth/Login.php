<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseAuth;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class Login extends BaseAuth
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
            ])
            ->statePath('data');
    }

    public function getCredentialsFromFormData(array $data): array
    {
        return [
            "name" => $data["name"],
        ];
    }

    public function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.name' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    protected function getNameFormComponent()
    {
        return TextInput::make("name")
            ->label("Naam")
            ->placeholder("Voer naam in")
            ->autofocus()
            ->autocomplete()
            ->required()
            ->extraInputAttributes(["tabindex" => "1"]);
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        $user = User::whereName($data['name'])->first();

        if (!$user) {
            $this->throwFailureValidationException();
        }


        Filament::auth()->loginUsingId($user->id);

        session()->regenerate();

        return app(LoginResponse::class);
    }
}
