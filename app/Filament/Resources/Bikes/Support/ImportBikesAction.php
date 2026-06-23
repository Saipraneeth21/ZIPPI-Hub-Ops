<?php

namespace App\Filament\Resources\Bikes\Support;

use App\Models\Rental\Bike;
use App\Models\Rental\BikeCategory;
use App\Models\Rental\City;
use App\Models\Rental\Hub;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk-create bikes from a CSV upload. Category / Home hub / City are matched
 * by name (case-insensitive); security deposit is entered in rupees.
 */
final class ImportBikesAction
{
    /** Columns the importer understands (header row, case-insensitive). */
    private const COLUMNS = [
        'name', 'registration_no', 'category', 'hub', 'city',
        'fuel_type', 'year', 'color', 'security_deposit', 'iot_device_id', 'status',
    ];

    public static function make(): Action
    {
        return Action::make('importBikes')
            ->label('Import CSV')
            ->icon('heroicon-m-arrow-up-tray')
            ->color('gray')
            ->visible(fn () => auth()->user()?->can('bikes.manage') ?? false)
            ->modalHeading('Import bikes from CSV')
            ->modalDescription('Required columns: name, registration_no, category, hub, city. Optional: fuel_type, year, color, security_deposit (₹), iot_device_id, status. Category / hub / city are matched by name.')
            ->modalSubmitActionLabel('Import')
            ->schema(fn (Schema $schema): Schema => $schema->components([
                FileUpload::make('file')
                    ->label('CSV file')
                    ->required()
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                    ->disk('local')
                    ->directory('imports'),
            ]))
            ->action(function (array $data): void {
                self::import($data['file']);
            });
    }

    /** A separate header action that downloads a ready-to-fill template. */
    public static function template(): Action
    {
        return Action::make('bikesTemplate')
            ->label('CSV template')
            ->icon('heroicon-m-arrow-down-tray')
            ->color('gray')
            ->link()
            ->action(function (): StreamedResponse {
                $rows = [
                    self::COLUMNS,
                    ['Scooter A1', 'TS09AB1234', 'Scooter', 'Hitech City Hub', 'Hitech City', 'petrol', '2024', 'White', '2000', '', 'available'],
                ];

                return response()->streamDownload(function () use ($rows) {
                    $out = fopen('php://output', 'w');
                    foreach ($rows as $row) {
                        fputcsv($out, $row, ',', '"', '');
                    }
                    fclose($out);
                }, 'bikes-import-template.csv', ['Content-Type' => 'text/csv']);
            });
    }

    private static function import(string $storedPath): void
    {
        $path = Storage::disk('local')->path($storedPath);

        $handle = fopen($path, 'r');
        if ($handle === false) {
            Notification::make()->title('Could not read the uploaded file')->danger()->send();

            return;
        }

        // Header row → normalised keys.
        $header = fgetcsv($handle, null, ',', '"', '');
        if ($header === false) {
            fclose($handle);
            Storage::disk('local')->delete($storedPath);
            Notification::make()->title('The CSV is empty')->danger()->send();

            return;
        }
        $header = array_map(fn ($h) => str_replace(' ', '_', strtolower(trim((string) $h))), $header);

        $created = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $rowNumber++;

            // Skip fully blank lines.
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $values = @array_combine($header, array_pad($row, count($header), null));
            $get = fn (string $key) => trim((string) ($values[$key] ?? ''));

            $name = $get('name');
            $registration = $get('registration_no');
            $categoryName = $get('category');
            $hubName = $get('hub');
            $cityName = $get('city');

            if ($name === '' || $registration === '' || $categoryName === '' || $hubName === '' || $cityName === '') {
                $errors[] = "Row {$rowNumber}: missing required field (name / registration_no / category / hub / city).";

                continue;
            }

            $category = BikeCategory::whereRaw('lower(name) = ?', [strtolower($categoryName)])->first();
            $hub = Hub::whereRaw('lower(name) = ?', [strtolower($hubName)])->first();
            $city = City::whereRaw('lower(name) = ?', [strtolower($cityName)])->first();

            if (! $category || ! $hub || ! $city) {
                $errors[] = "Row {$rowNumber}: unknown "
                    . (! $category ? "category '{$categoryName}' " : '')
                    . (! $hub ? "hub '{$hubName}' " : '')
                    . (! $city ? "city '{$cityName}'" : '');

                continue;
            }

            if (Bike::where('registration_no', $registration)->exists()) {
                $errors[] = "Row {$rowNumber}: registration '{$registration}' already exists.";

                continue;
            }

            $fuel = strtolower($get('fuel_type'));

            Bike::create([
                'name' => $name,
                'registration_no' => $registration,
                'category_id' => $category->id,
                'hub_id' => $hub->id,
                'city_id' => $city->id,
                'fuel_type' => in_array($fuel, ['petrol', 'electric'], true) ? $fuel : 'petrol',
                'year' => $get('year') !== '' ? (int) $get('year') : null,
                'color' => $get('color') !== '' ? $get('color') : null,
                'security_deposit_amount' => $get('security_deposit') !== ''
                    ? (int) round(((float) $get('security_deposit')) * 100)
                    : 0,
                'iot_device_id' => $get('iot_device_id') !== '' ? $get('iot_device_id') : null,
                'status' => $get('status') !== '' ? strtolower($get('status')) : 'available',
            ]);
            $created++;
        }

        fclose($handle);
        Storage::disk('local')->delete($storedPath);

        $notification = Notification::make()
            ->title("Imported {$created} bike(s)")
            ->body($errors === [] ? null : (count($errors) . ' row(s) skipped: ' . implode(' ', array_slice($errors, 0, 5))));

        ($errors === [] ? $notification->success() : $notification->warning())->send();
    }
}
