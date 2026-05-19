<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int    $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('navigation.users');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.settings');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return (auth()->user()?->isSuperAdmin() ?? false)
            && auth()->id() !== $record->id;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('users.name'))
                    ->required(),

                TextInput::make('email')
                    ->label(__('users.email'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),

                Select::make('role')
                    ->label(__('users.role'))
                    ->options([
                        'super_admin' => __('users.roles.super_admin'),
                        'admin'       => __('users.roles.admin'),
                        'allocator'   => __('users.roles.allocator'),
                    ])
                    ->required()
                    ->default('admin'),

                Select::make('locale')
                    ->label(__('users.preferred_language'))
                    ->options([
                        'id' => 'Bahasa Indonesia',
                        'en' => 'English',
                    ])
                    ->default('id')
                    ->required(),

                TextInput::make('password')
                    ->label(__('users.password'))
                    ->password()
                    ->revealable()
                    ->required(fn($context) => $context === 'create')
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->placeholder(fn($context) => $context === 'edit' ? __('users.password_placeholder') : null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('users.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('users.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label(__('users.role'))
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state instanceof UserRole ? $state->value : $state) {
                        'super_admin' => __('users.roles.super_admin'),
                        'admin'       => __('users.roles.admin'),
                        'allocator'   => __('users.roles.allocator'),
                        default       => $state,
                    })
                    ->color(fn($state) => match ($state instanceof UserRole ? $state->value : $state) {
                        'super_admin' => 'danger',
                        'admin'       => 'warning',
                        'allocator'   => 'success',
                        default       => 'gray',
                    }),

                TextColumn::make('locale')
                    ->label(__('users.preferred_language'))
                    ->formatStateUsing(fn($state) => $state === 'id' ? 'Bahasa Indonesia' : 'English')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label(__('users.created'))
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(User $record) => auth()->id() !== $record->id),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
