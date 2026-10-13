<?php

namespace App\Filament\Widgets;

use App\Models\GroupApprovalRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecentRequestsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return (bool) ($user && ! $user->isOperator());
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();

        $query = GroupApprovalRequest::query()->latest()->limit(5);

        if ($user && ! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('approver_email', $user->email)
                    ->orWhereNull('approver_email')
                    ->orWhere('requester_id', $user->id);
            });
        }

        return $table
            ->query($query)
            ->heading('Recent Access Requests')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date Requested')
                    ->icon('heroicon-m-calendar')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('requester_name')
                    ->label('Requester')
                    ->icon('heroicon-m-shield-check')
                    ->sortable(),

                TextColumn::make('target_username')
                    ->label('Target AD User')
                    ->icon('heroicon-m-user')
                    ->badge()
                    ->color('info'),

                TextColumn::make('target_group_name')
                    ->label('Target Group')
                    ->icon('heroicon-m-user-group')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}
