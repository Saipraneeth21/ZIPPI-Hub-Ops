<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CouponForm
{
    /** Rupees/percent <-> stored integer (both are ×100: paise, or percent basis points). */
    private static function scaled(TextInput $input): TextInput
    {
        return $input
            ->numeric()
            ->minValue(0)
            ->formatStateUsing(fn (?int $state) => $state !== null ? $state / 100 : null)
            ->dehydrateStateUsing(fn ($state) => $state === null || $state === ''
                ? null
                : (int) round(((float) $state) * 100));
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Coupon')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->maxLength(40)
                        ->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (string $state) => strtoupper($state)),

                    Select::make('type')
                        ->options(['percent' => 'Percent (%)', 'flat' => 'Flat (₹)'])
                        ->default('flat')
                        ->live()
                        ->required(),

                    self::scaled(TextInput::make('value'))
                        ->label(fn ($get) => $get('type') === 'percent' ? 'Discount (%)' : 'Discount (₹)')
                        ->prefix(fn ($get) => $get('type') === 'percent' ? null : '₹')
                        ->suffix(fn ($get) => $get('type') === 'percent' ? '%' : null)
                        ->required(),

                    self::scaled(TextInput::make('max_discount'))
                        ->label('Max discount (₹)')
                        ->prefix('₹')
                        ->helperText('Caps a percent discount. Leave blank for none.')
                        ->visible(fn ($get) => $get('type') === 'percent'),

                    self::scaled(TextInput::make('min_booking_amount'))
                        ->label('Min booking amount (₹)')
                        ->prefix('₹'),
                ]),

            Section::make('Limits & Validity')
                ->columns(2)
                ->schema([
                    TextInput::make('usage_limit_total')->numeric()->minValue(0)->placeholder('Unlimited'),
                    TextInput::make('usage_limit_per_user')->numeric()->minValue(1)->default(1)->required(),
                    DateTimePicker::make('valid_from')->required()->default(now()),
                    DateTimePicker::make('valid_to')->required()->after('valid_from'),
                    Toggle::make('is_active')->default(true)->inline(false),
                ]),
        ]);
    }
}
