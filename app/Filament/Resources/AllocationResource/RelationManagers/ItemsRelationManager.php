<?php

namespace App\Filament\Resources\AllocationResource\RelationManagers;

use App\Imports\AllocationItemImport;
use App\Models\Inventory;
use App\Models\Stock;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'barcode';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('allocation.items_title');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('barcode')
                    ->label(__('general.barcode'))
                    ->options(fn() => Inventory::orderBy('barcode')->pluck('barcode', 'barcode'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateHydrated(function (?string $state, Set $set) {
                        if (! $state) return;
                        $inv = Inventory::where('barcode', $state)->first();
                        if ($inv) {
                            $set('article', $inv->article);
                            $set('color', $inv->color);
                            $set('size', $inv->size);
                        }
                        $stock = Stock::where('barcode', $state)->orderByDesc('qty')->first();
                        if ($stock) {
                            $set('location', $stock->location);
                            $set('bin', $stock->bin);
                        }
                    })
                    ->afterStateUpdated(function (?string $state, Set $set) {
                        $inv = $state ? Inventory::where('barcode', $state)->first() : null;
                        $set('article', $inv?->article);
                        $set('color', $inv?->color);
                        $set('size', $inv?->size);

                        $stock = $state ? Stock::where('barcode', $state)->orderByDesc('qty')->first() : null;
                        $set('location', $stock?->location);
                        $set('bin', $stock?->bin);
                    }),

                TextInput::make('article')->label(__('general.article'))->disabled()->dehydrated(false),
                TextInput::make('color')->label(__('general.color'))->disabled()->dehydrated(false),
                TextInput::make('size')->label(__('general.size'))->disabled()->dehydrated(false),

                TextInput::make('qty')->label(__('general.qty'))->numeric()->required()->minValue(1),
                TextInput::make('location')->label(__('general.location')),
                TextInput::make('bin')->label(__('general.bin')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('barcode')->label(__('general.barcode')),
                TextColumn::make('inventory.article')->label(__('general.article')),
                TextColumn::make('inventory.color')->label(__('general.color')),
                TextColumn::make('inventory.size')->label(__('general.size')),
                TextColumn::make('qty')->label(__('general.qty')),
                TextColumn::make('location')->label(__('general.location')),
                TextColumn::make('bin')->label(__('general.bin')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label(__('allocation.add_item')),

                Tables\Actions\Action::make('import_excel')
                    ->label(__('general.import_excel'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->modalHeading(__('allocation.import_heading'))
                    ->modalDescription(__('allocation.import_desc'))
                    ->modalSubmitActionLabel(__('allocation.import_submit'))
                    ->form([
                        FileUpload::make('file')
                            ->label(__('general.import_excel') . ' (.xlsx)')
                            ->disk('public')
                            ->directory('imports')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $path   = Storage::disk('public')->path($data['file']);
                        $import = new AllocationItemImport($this->ownerRecord->id);

                        Excel::import($import, $path);

                        Storage::disk('public')->delete($data['file']);

                        $msg = __('allocation.import_success', ['count' => $import->imported]);
                        if ($import->skipped) {
                            $msg .= __('allocation.import_skipped', ['skipped' => $import->skipped]);
                        }

                        Notification::make()
                            ->title($msg)
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
