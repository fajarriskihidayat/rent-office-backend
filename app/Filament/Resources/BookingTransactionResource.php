<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingTransactionResource\Pages;
use App\Filament\Resources\BookingTransactionResource\RelationManagers;
use App\Models\BookingTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Twilio\Rest\Client;

class BookingTransactionResource extends Resource
{
    protected static ?string $model = BookingTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('booking_trx_id')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone_number')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->prefix('IDR'),

                Forms\Components\TextInput::make('duration')
                    ->required()
                    ->numeric()
                    ->prefix('Days'),

                Forms\Components\DatePicker::make('started_at')
                    ->required(),

                Forms\Components\DatePicker::make('ended_at')
                    ->required(),

                Forms\Components\Select::make('is_paid')
                    ->options([
                        true => 'Paid',
                        false => 'Not Paid',
                    ])
                    ->required(),

                Forms\Components\Select::make('office_space_id')
                    ->relationship('officeSpace', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('booking_trx_id')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('officeSpace.name'),

                Tables\Columns\TextColumn::make('started_at')
                    ->date(),

                Tables\Columns\IconColumn::make('is_paid')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->label('Status Pembayaran'),
            ])
            ->filters([
                // filter by status pembayaran
                SelectFilter::make('is_paid')
                    ->options([
                        true => 'Sudah Bayar',
                        false => 'Belum Bayar',
                    ])
                    ->label('Status Pembayaran')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (BookingTransaction $record) {
                        $record->is_paid = true;
                        $record->save();

                        // custom notifikasi
                        Notification::make()
                            ->title('Booking Approved')
                            ->success()
                            ->body('The booking has been successfully approved.')
                            ->send();

                        // mengirim notif melalui sms atau whatsapp dengan twilio
                        $sid = getenv("TWILIO_ACCOUNT_SID");
                        $token = getenv("TWILIO_AUTH_TOKEN");
                        $twilio = new Client($sid, $token);

                        $messageBody = "Hi {$record->name}, pemesanan Anda dengan kode booking {$record->booking_trx_id} sudah terbayar penuh.\n\n";
                        $messageBody .= "Silahkan datang ke lokasi kantor {$record->officeSpace->name} sesuai dengan jadwal untuk mulai menggunakan ruangan kerja tersebut.\n\n";
                        $messageBody .= "Jika Anda memiliki pertanayaan silahkan menghubungi CS kami di rentoffice.com/contact-us.";

                        // kirim dengan fitur sms
                        $message = $twilio->messages->create(
                            "+{$record->phone_number}",
                            [
                                "body" => $messageBody,
                                "from" => getenv("TWILIO_PHONE_NUMBER")
                            ]
                        );
                    })
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(BookingTransaction $record) => !$record->is_paid)
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookingTransactions::route('/'),
            'create' => Pages\CreateBookingTransaction::route('/create'),
            'edit' => Pages\EditBookingTransaction::route('/{record}/edit'),
        ];
    }
}
