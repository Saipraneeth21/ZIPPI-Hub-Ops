<?php

namespace App\Filament\Hub\Support;

use App\Models\Rental\Bike;
use App\Models\Rental\Booking;
use App\Services\Rental\HubOpsService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Throwable;

/**
 * Hub Operations workflows as Filament actions. Each delegates to HubOpsService
 * (→ BookingService) so the /hub panel and the Hub Ops API share one code path
 * and no business logic is duplicated.
 */
final class HubActions
{
    private static function staffId(): ?int
    {
        return auth('hub')->user()?->id;
    }

    /** Customer / bike / KYC summary shown atop the handover & return modals. */
    private static function bookingContext(Booking $record): string
    {
        $record->loadMissing(['user.profile', 'bike']);
        $kyc = $record->user?->profile?->kyc_status ?? 'none';

        return implode(' · ', array_filter([
            $record->booking_code,
            $record->user?->name ? "Customer: {$record->user->name} ({$record->user->mobile})" : null,
            $record->bike ? "Bike: {$record->bike->name} ({$record->bike->registration_no})" : null,
            'KYC: '.ucfirst($kyc),
            'Due: '.$record->end_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
        ]));
    }

    /** Pickup handover for a confirmed booking → unlock + capture. */
    public static function handover(): Action
    {
        return Action::make('handover')
            ->label('Hand over')
            ->icon('heroicon-m-key')
            ->color('primary')
            ->visible(fn (Booking $record) => $record->status === 'confirmed')
            ->modalHeading('Pickup handover')
            ->modalDescription(fn (Booking $record) => self::bookingContext($record))
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Toggle::make('checklist.bike_inspected')->label('Bike inspected')->default(false),
                Toggle::make('checklist.helmet_issued')->label('Helmet issued')->default(false),
                TextInput::make('battery_percent')->label('Battery %')
                    ->numeric()->minValue(0)->maxValue(100)->suffix('%'),
                Textarea::make('notes')->label('Notes')->maxLength(1000),
                FileUpload::make('photos')->label('Bike photos before handover')
                    ->image()->multiple()->disk('public')->directory('hub/handovers')
                    ->required()->minFiles(1)->maxFiles(6)
                    ->helperText('At least one photo of the bike is required before handover.'),
            ]))
            ->action(function (Booking $record, array $data, HubOpsService $hubOps): void {
                try {
                    $hubOps->handover($record, self::staffId(), [
                        'battery_percent' => $data['battery_percent'] ?? null,
                        'checklist' => $data['checklist'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'photos' => $data['photos'] ?? [],
                    ]);
                } catch (Throwable $e) {
                    Notification::make()->title('Cannot hand over')->body($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Bike handed over')->success()->send();
            });
    }

    /** Complete return for an active booking → returnBike + capture. */
    public static function completeReturn(): Action
    {
        return Action::make('completeReturn')
            ->label('Complete return')
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('warning')
            ->visible(fn (Booking $record) => $record->status === 'active')
            ->modalHeading('Bike return')
            ->modalDescription(fn (Booking $record) => self::bookingContext($record))
            ->schema(fn (Schema $schema): Schema => $schema->components([
                TextInput::make('odometer_reading')->label('Odometer (km)')->numeric()->minValue(0),
                TextInput::make('battery_percent')->label('Battery %')
                    ->numeric()->minValue(0)->maxValue(100)->suffix('%'),
                Textarea::make('damage_notes')->label('Damage notes')->maxLength(1000),
                FileUpload::make('photos')->label('Return photos')
                    ->image()->multiple()->disk('public')->directory('hub/returns')->maxFiles(6),
            ]))
            ->action(function (Booking $record, array $data, HubOpsService $hubOps): void {
                try {
                    $outcome = $hubOps->completeReturn($record, self::staffId(), [
                        'odometer_reading' => $data['odometer_reading'] ?? null,
                        'battery_percent' => $data['battery_percent'] ?? null,
                        'damage_notes' => $data['damage_notes'] ?? null,
                        'photos' => $data['photos'] ?? [],
                    ]);
                } catch (Throwable $e) {
                    Notification::make()->title('Cannot complete return')->body($e->getMessage())->danger()->send();

                    return;
                }

                $result = $outcome['result'];
                Notification::make()
                    ->title('Return completed')
                    ->body('Late penalty: ₹'.number_format($result['late_penalty'] / 100, 2)
                        .' · Deposit refund: ₹'.number_format($result['deposit_refund'] / 100, 2))
                    ->success()
                    ->send();
            });
    }

    /** Report maintenance for a bike. */
    public static function reportMaintenance(): Action
    {
        return Action::make('reportMaintenance')
            ->label('Report maintenance')
            ->icon('heroicon-m-wrench-screwdriver')
            ->color('gray')
            ->modalHeading('Maintenance report')
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Select::make('category')
                    ->options(['Battery' => 'Battery', 'Tyre' => 'Tyre', 'Brake' => 'Brake',
                        'Motor' => 'Motor', 'Body Damage' => 'Body Damage', 'Other' => 'Other'])
                    ->required()->native(false),
                Select::make('status')
                    ->options(['open' => 'Open', 'in_progress' => 'In Progress', 'completed' => 'Completed'])
                    ->default('open')->required()->native(false),
                Textarea::make('description')->label('Description')->maxLength(2000),
                FileUpload::make('photos')->label('Photos')
                    ->image()->multiple()->disk('public')->directory('hub/maintenance')->maxFiles(6),
            ]))
            ->action(function (Bike $record, array $data, HubOpsService $hubOps): void {
                $hubOps->reportMaintenance($record->id, self::staffId(), [
                    'category' => $data['category'],
                    'status' => $data['status'] ?? 'open',
                    'description' => $data['description'] ?? null,
                    'photos' => $data['photos'] ?? [],
                ]);

                Notification::make()->title('Maintenance ticket created')->success()->send();
            });
    }

    /** Report an incident for a bike. */
    public static function reportIncident(): Action
    {
        return Action::make('reportIncident')
            ->label('Report incident')
            ->icon('heroicon-m-exclamation-triangle')
            ->color('danger')
            ->modalHeading('Incident report')
            ->schema(fn (Schema $schema): Schema => $schema->components([
                Select::make('incident_type')
                    ->options(['Accident' => 'Accident', 'Breakdown' => 'Breakdown',
                        'Damage' => 'Damage', 'Theft' => 'Theft'])
                    ->required()->native(false),
                Select::make('severity')
                    ->options(['minor' => 'Minor', 'moderate' => 'Moderate',
                        'severe' => 'Severe', 'total_loss' => 'Total loss'])
                    ->default('minor')->required()->native(false),
                Textarea::make('description')->label('Description')->required()->maxLength(2000),
                FileUpload::make('photos')->label('Photos')
                    ->image()->multiple()->disk('public')->directory('hub/incidents')->maxFiles(6),
            ]))
            ->action(function (Bike $record, array $data, HubOpsService $hubOps): void {
                $hubOps->reportIncident($record->id, self::staffId(), [
                    'incident_type' => $data['incident_type'],
                    'severity' => $data['severity'] ?? 'minor',
                    'description' => $data['description'],
                    'reported_by' => auth('hub')->user()?->name,
                    'photos' => $data['photos'] ?? [],
                ]);

                Notification::make()->title('Incident reported')->success()->send();
            });
    }
}
