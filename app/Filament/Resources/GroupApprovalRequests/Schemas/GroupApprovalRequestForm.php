<?php

namespace App\Filament\Resources\GroupApprovalRequests\Schemas;

use App\Services\ActiveDirectoryService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class GroupApprovalRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $adService = app(ActiveDirectoryService::class);

        return $schema
            ->components([
                Section::make('Access Request Details')
                    ->schema([
                        Hidden::make('requester_id')
                            ->default(fn () => Auth::id()),

                        TextInput::make('requester_name')
                            ->label('Requester')
                            ->prefixIcon('heroicon-m-shield-check')
                            ->default(fn () => Auth::user()?->name ?? 'Portal Admin')
                            ->readOnly(),

                        Select::make('target_username')
                            ->label('Target User')
                            ->prefixIcon('heroicon-m-user')
                            ->options(fn () => $adService->getDirectoryUsers())
                            ->searchable()
                            ->required()
                            ->rules([
                                fn () => function (string $attribute, $value, \Closure $fail) {
                                    if (Auth::check() && ! empty(Auth::user()->username) && strcasecmp(trim($value), trim(Auth::user()->username)) === 0) {
                                        $fail('Segregation of Duties: You cannot submit an access elevation request for your own account.');
                                    }
                                },
                            ]),

                        Select::make('target_group_name')
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
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
