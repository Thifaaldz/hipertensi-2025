<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PredictionResource\Pages;
use App\Models\Prediction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PredictionResource extends Resource
{
    protected static ?string $model = Prediction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Prediksi Hipertensi';
    protected static ?string $pluralLabel = 'Prediksi';
    protected static ?string $modelLabel = 'Prediksi';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('kecamatan')->required(),
            Forms\Components\TextInput::make('wilayah'),
            Forms\Components\TextInput::make('tahun'),
            Forms\Components\TextInput::make('persentase'),
            Forms\Components\Select::make('prioritas')
                ->options([
                    'Prioritas' => 'Prioritas',
                    'Tidak' => 'Tidak',
                ]),
            Forms\Components\TextInput::make('predicted_route'),
            Forms\Components\DatePicker::make('focus_date'),
            Forms\Components\TextInput::make('lat'),
            Forms\Components\TextInput::make('lon'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kecamatan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('wilayah'),

                Tables\Columns\TextColumn::make('tahun')
                    ->sortable(),

                Tables\Columns\TextColumn::make('persentase')
                    ->label('Persentase')
                    ->formatStateUsing(function ($state) {
                        if (! is_numeric($state)) {
                            return '-';
                        }

                        return number_format($state, 2) . '%';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('prioritas'),

                Tables\Columns\TextColumn::make('predicted_route'),

                Tables\Columns\TextColumn::make('focus_date')
                    ->date(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('tahun')
                    ->label('Filter Tahun')
                    ->options(fn () =>
                        Prediction::query()
                            ->select('tahun')
                            ->distinct()
                            ->pluck('tahun', 'tahun')
                            ->toArray()
                    ),
            ])

            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPredictions::route('/'),
            'create' => Pages\CreatePrediction::route('/create'),
            'edit' => Pages\EditPrediction::route('/{record}/edit'),
        ];
    }
}
