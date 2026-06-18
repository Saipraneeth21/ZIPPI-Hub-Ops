<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Review')
                ->columns(3)
                ->schema([
                    TextEntry::make('rating')
                        ->formatStateUsing(fn (int $state) => str_repeat('★', $state) . str_repeat('☆', 5 - $state)),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->color(fn (string $state) => $state === 'published' ? 'success' : 'gray'),
                    TextEntry::make('created_at')->label('Submitted')->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata'),
                    TextEntry::make('user.name')->label('Rider'),
                    TextEntry::make('bike.name')->label('Bike'),
                    TextEntry::make('booking.booking_code')->label('Booking')->placeholder('—'),
                    TextEntry::make('comment')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('moderated_by')->label('Moderated by admin #')->placeholder('Not moderated'),
                ]),
        ]);
    }
}
