<?php

namespace App\Filament\Hub\Resources\Handovers;

use App\Filament\Hub\Resources\Handovers\Pages\ListHandovers;
use App\Models\Rental\HubHandover;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Handover log: every pickup handover recorded at this hub, with its current
 * status derived from the booking's lifecycle (on rent / returned / cancelled).
 */
class HandoverResource extends Resource
{
    protected static ?string $model = HubHandover::class;

    protected static ?string $slug = 'handovers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Handovers';

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

    /** Human label + color for the handover's downstream status. */
    public static function statusMeta(?string $bookingStatus): array
    {
        return match ($bookingStatus) {
            'active' => ['On rent', 'warning'],
            'completed' => ['Returned', 'success'],
            'cancelled' => ['Cancelled', 'danger'],
            default => [ucfirst((string) $bookingStatus), 'gray'],
        };
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['booking.user', 'booking.bike', 'staff'])
            ->whereHas('booking', fn (Builder $q) => $q->where('pickup_hub_id', auth('hub')->user()?->hub_id));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking.booking_code')->label('Booking ID')->searchable()->weight('medium'),
                TextColumn::make('booking.user.name')->label('Customer')->searchable(),
                TextColumn::make('booking.bike.name')->label('Bike')->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (HubHandover $r) => self::statusMeta($r->booking?->status)[0])
                    ->color(fn (HubHandover $r) => self::statusMeta($r->booking?->status)[1]),
                TextColumn::make('battery_percent')->label('Battery')
                    ->formatStateUsing(fn ($state) => $state !== null ? "{$state}%" : '—'),
                IconColumn::make('checklist_done')
                    ->label('Checklist')
                    ->boolean()
                    ->state(fn (HubHandover $r) => (bool) ($r->checklist['bike_inspected'] ?? false)
                        && (bool) ($r->checklist['helmet_issued'] ?? false)),
                TextColumn::make('staff.name')->label('Handed over by')->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->label('Handed over at')
                    ->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata')->sortable(),
            ])
            ->filters([
                SelectFilter::make('booking_status')
                    ->label('Status')
                    ->options(['active' => 'On rent', 'completed' => 'Returned', 'cancelled' => 'Cancelled'])
                    ->query(fn (Builder $q, array $data) => $q->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $v) => $q->whereHas('booking', fn (Builder $b) => $b->where('status', $v)),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('No handovers yet')
            ->emptyStateDescription('Handovers will appear here once you hand over a bike from Upcoming Pickups.')
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Handover details')
                ->columns(2)
                ->schema([
                    TextEntry::make('booking.booking_code')->label('Booking ID'),
                    TextEntry::make('booking.user.name')->label('Customer'),
                    TextEntry::make('booking.bike.name')->label('Bike'),
                    TextEntry::make('battery_percent')->label('Battery %')->placeholder('—'),
                    TextEntry::make('status')
                        ->label('Status')->badge()
                        ->state(fn (HubHandover $r) => self::statusMeta($r->booking?->status)[0])
                        ->color(fn (HubHandover $r) => self::statusMeta($r->booking?->status)[1]),
                    TextEntry::make('staff.name')->label('Handed over by')->placeholder('—'),
                    TextEntry::make('bike_inspected')->label('Bike inspected')
                        ->state(fn (HubHandover $r) => ($r->checklist['bike_inspected'] ?? false) ? 'Yes' : 'No'),
                    TextEntry::make('helmet_issued')->label('Helmet issued')
                        ->state(fn (HubHandover $r) => ($r->checklist['helmet_issued'] ?? false) ? 'Yes' : 'No'),
                    TextEntry::make('notes')->label('Notes')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('created_at')->label('Handed over at')
                        ->dateTime('d M Y, h:i A')->timezone('Asia/Kolkata'),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListHandovers::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
