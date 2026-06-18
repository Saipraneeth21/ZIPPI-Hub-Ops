<?php
namespace App\Http\Controllers\Api\Rental;

use App\Http\Controllers\Controller;
use App\Models\Rental\Payment;
use App\Services\Rental\PaymentService;
use App\Support\Concerns\ApiResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PaymentService $payments) {}

    public function verify(Request $r)
    {
        $data = $r->validate([
            'payment_reference' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);
        try {
            $payment = $this->payments->verifyAndConfirm(
                $data['payment_reference'], $data['razorpay_order_id'],
                $data['razorpay_payment_id'], $data['razorpay_signature']
            );
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), null, 422);
        }
        $booking = $payment->booking;
        return $this->ok([
            'payment_status' => $payment->status,
            'booking_status' => $booking->status,
            'booking_code' => $booking->booking_code,
        ], 'Payment verified');
    }

    public function get(Request $r, string $reference)
    {
        $payment = Payment::where('payment_reference', $reference)
            ->where('user_id', $r->user()->id)->firstOrFail();
        return $this->ok([
            'payment_reference' => $payment->payment_reference,
            'amount' => (int) $payment->amount,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at,
        ]);
    }
}
