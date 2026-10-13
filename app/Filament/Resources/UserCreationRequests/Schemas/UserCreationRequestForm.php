<?php

namespace App\Filament\Resources\UserCreationRequests\Schemas;

use App\Rules\ActiveDirectoryPasswordRule;
use App\Services\ActiveDirectoryService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserCreationRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $adService = app(ActiveDirectoryService::class);

        return $schema
            ->components([
                Section::make('New User Details')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                Hidden::make('requester_id')
                                    ->default(fn () => Auth::id()),

                                TextInput::make('requester_name')
                                    ->label('Initiated By (Admin)')
                                    ->default(fn () => Auth::user()?->name ?? 'Portal Admin')
                                    ->readOnly(),

                                TextInput::make('full_name')
                                    ->label('Full Name')
                                    ->placeholder('e.g. Ahmad Syahputra or Niklas')
                                    ->prefixIcon('heroicon-m-user')
                                    ->required()
                                    ->live(debounce: 400)
                                    ->afterStateUpdated(function ($get, $set, ?string $state) {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $rawName = trim($state);
                                        // Remove any existing BDS prefix to extract clean name
                                        $cleanName = trim(preg_replace('/^BDS\s+/i', '', $rawName));
                                        $words = preg_split('/\s+/', $cleanName);

                                        $lastName = end($words);
                                        $firstName = count($words) > 1 ? implode(' ', array_slice($words, 0, -1)) : $lastName;

                                        // 1. Display Name: BDS <CleanName>
                                        $displayName = 'BDS '.$cleanName;

                                        // 2. Username: BDS-<LastName> (from last name)
                                        $cleanLastName = preg_replace('/[^A-Za-z0-9]/', '', $lastName);
                                        $username = 'BDS-'.ucfirst(strtolower($cleanLastName));
                                        $upn = "{$username}@cmc.mhe.local";

                                        $set('first_name', $firstName);
                                        $set('last_name', $lastName);
                                        $set('display_name', $displayName);
                                        $set('username', $username);
                                        $set('user_principal_name', $upn);
                                    }),

                                TextInput::make('display_name')
                                    ->label('Display Name')
                                    ->prefixIcon('heroicon-m-identification')
                                    ->required(),

                                TextInput::make('username')
                                    ->label('Username')
                                    ->prefixIcon('heroicon-m-at-symbol')
                                    ->required(),

                                Hidden::make('first_name'),
                                Hidden::make('last_name'),
                                Hidden::make('user_principal_name'),

                                TextInput::make('initial_password')
                                    ->label('Initial Password')
                                    ->prefixIcon('heroicon-m-key')
                                    ->password()
                                    ->revealable()
                                    ->default(fn () => 'Bds#'.Str::random(5).rand(100, 999).'!')
                                    ->required()
                                    ->rules([
                                        fn (Get $get) => new ActiveDirectoryPasswordRule(
                                            username: fn () => $get('username'),
                                            name: fn () => $get('display_name') ?: $get('full_name')
                                        ),
                                    ])
                                    ->helperText('Must be at least 8 characters, include upper, lower, numbers, and symbols, and cannot contain the name or username (Active Directory Policy).'),

                                Select::make('initial_group_name')
                                    ->label('Requested Group')
                                    ->prefixIcon('heroicon-m-user-group')
                                    ->options(fn () => $adService->getRequestableGroups(Auth::user()))
                                    ->default('BDS_Technician')
                                    ->searchable()
                                    ->required()
                                    ->rules([
                                        fn () => function (string $attribute, $value, \Closure $fail) {
                                            if (Auth::check() && ! Auth::user()->isSuperAdmin()) {
                                                if (! in_array($value, ['BDS_Operator', 'BDS_Technician'])) {
                                                    $fail('Administrators are only authorized to request BDS_Operator or BDS_Technician groups.');
                                                }
                                            }
                                        },
                                    ]),

                                Select::make('approver_email')
                                    ->label('Approver (Supervisor)')
                                    ->prefixIcon('heroicon-m-user-circle')
                                    ->options(function () use ($adService) {
                                        $supervisors = $adService->getSupervisors();
                                        if (Auth::check() && ! empty(Auth::user()->email)) {
                                            unset($supervisors[Auth::user()->email]);
                                        }

                                        return $supervisors;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->placeholder('Select Designated Supervisor (Optional)')
                                    ->rules([
                                        fn () => function (string $attribute, $value, \Closure $fail) {
                                            if (! empty($value) && Auth::check() && strcasecmp(trim($value), trim(Auth::user()->email)) === 0) {
                                                $fail('Segregation of Duties: You cannot designate yourself as the approver for your own request.');
                                            }
                                        },
                                    ]),

                                Textarea::make('business_justification')
                                    ->label('Business Justification')
                                    ->placeholder('State the operational reason or work order...')
                                    ->required()
                                    ->minLength(5)
                                    ->rows(2),
                            ]),
                    ]),
            ]);
    }
}
