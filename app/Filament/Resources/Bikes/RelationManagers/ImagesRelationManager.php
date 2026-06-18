<?php

namespace App\Filament\Resources\Bikes\RelationManagers;

use App\Models\Rental\BikeImage;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Images';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image_path')
                ->label('Image')
                ->image()
                ->disk(config('filesystems.default'))
                ->directory('bikes')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Preview')
                    ->disk(config('filesystems.default'))
                    ->height(60),
                IconColumn::make('is_primary')->label('Primary')->boolean(),
                TextColumn::make('sort_order')->label('Order'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                // Promote one image to primary; demote the others (single primary per bike).
                Action::make('makePrimary')
                    ->label('Set primary')
                    ->icon('heroicon-m-star')
                    ->visible(fn (BikeImage $record) => ! $record->is_primary)
                    ->action(function (BikeImage $record): void {
                        $record->bike->images()->update(['is_primary' => false]);
                        $record->update(['is_primary' => true]);
                        Notification::make()->title('Primary image set')->success()->send();
                    }),
                DeleteAction::make(),
            ]);
    }
}
