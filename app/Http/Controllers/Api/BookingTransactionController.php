<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingTransactionRequest;
use App\Http\Resources\Api\BookingTransactionResource;
use App\Http\Resources\Api\ViewBookingResource;
use App\Models\BookingTransaction;
use App\Models\OfficeSpace;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class BookingTransactionController extends Controller
{
    // mengecek data transaksi
    public function booking_details(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'booking_trx_id' => 'required|string',
        ]);

        $booking = BookingTransaction::where('phone_number', $request->phone_number)
            ->where('booking_trx_id', $request->booking_trx_id)
            ->with(['officeSpace', 'officeSpace.city'])
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'booking not found'], 404);
        }

        return new ViewBookingResource($booking);
    }

    // menyimpan data transaksi
    public function store(StoreBookingTransactionRequest $request)
    {
        $validatedData = $request->validated();

        $officeSpace = OfficeSpace::find($validatedData['office_space_id']);

        $validatedData['is_paid'] = false;
        $validatedData['booking_trx_id'] = BookingTransaction::generateUniqueTrxId();
        $validatedData['duration'] = $officeSpace->duration;

        $validatedData['ended_at'] = (new \DateTime($validatedData['started_at']))
            ->modify("+{$officeSpace->duration} days")->format('Y-m-d');

        $bookingTransaction = BookingTransaction::create($validatedData);

        // mengirim notif melalui sms atau whatsapp dengan twilio
        $sid = getenv("TWILIO_ACCOUNT_SID");
        $token = getenv("TWILIO_AUTH_TOKEN");
        $noWA = getenv("TWILIO_PHONE_NUMBER_WA");
        $twilio = new Client($sid, $token);

        $messageBody = "Hi {$bookingTransaction->name}, Terima kasih telah melakukan booking kantor di RentOffice.\n\n";
        $messageBody .= "Pesanan kantor {$bookingTransaction->officeSpace->name} Anda sedang kami proses dengan kode booking {$bookingTransaction->booking_trx_id}.\n\n";
        $messageBody .= "Kami akan mengembalikan status pesanan Anda secepat mungkin.";

        // kirim dengan fitur sms
        // $$twilio->messages->create(
        //     "+{$bookingTransaction->phone_number}",
        //     [
        //         "body" => $messageBody,
        //         "from" => getenv("TWILIO_PHONE_NUMBER")
        //     ]
        // );

        // kirim dengan fitur whatsapp
        $twilio->messages->create(
            "whatsapp:+{$bookingTransaction->phone_number}",
            [
                "body" => $messageBody,
                "from" => "whatsapp:{$noWA}"
            ]
        );

        // mengembalikan hasil response
        $bookingTransaction->load('officeSpace');
        return new BookingTransactionResource($bookingTransaction);
    }
}
