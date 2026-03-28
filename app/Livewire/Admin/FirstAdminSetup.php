<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\FormsComponent;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin-setup')]
class FirstAdminSetup extends FormsComponent
{
    public ?array $data = [];

    public function mount(): void
    {
        if (User::query()->exists()) {
            abort(404);
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('First administrator')
                    ->description('This appears only while the database is empty.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(12)
                            ->maxLength(255),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        if (User::query()->exists()) {
            abort(404);
        }

        $data = $this->form->getState();

        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
        ])->validate();

        $user = User::query()->create($data);

        Auth::login($user);
        session()->regenerate();

        return redirect()->to(Filament::getUrl() ?? '/admin');
    }

    public function render(): View
    {
        return view('livewire.admin.first-admin-setup');
    }
}
