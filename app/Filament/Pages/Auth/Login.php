<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseAuth;
use Illuminate\Validation\ValidationException;

class Login extends BaseAuth
{
    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getNameFormComponent(),
        ])
            ->statePath("data");
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
            "data.login" => __("filament-panels::pages/auth/login.nessages.failed")
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
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

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
