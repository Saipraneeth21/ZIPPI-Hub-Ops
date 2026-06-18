<?php
namespace App\Http\Controllers\Api\Rental;

use App\Http\Controllers\Controller;
use App\Http\Resources\Rental\BookingResource;
use App\Models\Rental\Bike;
use App\Models\Rental\Booking;
use App\Services\Rental\BookingService;
use App\Services\Rental\PricingService;
use App\Support\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BookingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PricingService $pricing,
        private readonly BookingService $bookings,
    ) {}

    public function quote(Request $r)
    {
        $data = $r->validate([
            'bike_id' => ['required', 'exists:rental_bikes,id'],
            'duration_type' => ['required', 'in:hourly,daily,weekly,monthly'],
            'start_at' => ['required', 'date', 'after:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'coupon_code' => ['nullable', 'string'],
        ]);
        $bike = Bike::with('pricing')->findOrFail($data['bike_id']);
        try {
            $quote = $this->pricing->quote(
                $bike, $data['duration_type'], Carbon::parse($data['start_at']),
                Carbon::parse($data['end_at']), $data['coupon_code'] ?? null, $r->user()->id
            );
        } catch (\InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), null, 422);
        }
        return $this->ok(array_merge(['bike_id' => $bike->id], $quote->toArray()));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'bike_id' => ['required', 'exists:rental_bikes,id'],
            'duration_type' => ['required', 'in:hourly,daily,weekly,monthly'],
            'start_at' => ['required', 'date', 'after:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'pickup_hub_id' => ['nullable', 'exists:rental_hubs,id'],
            'return_hub_id' => ['nullable', 'exists:rental_hubs,id'],
            'coupon_code' => ['nullable', 'string'],
        ]);
        $key = $r->attributes->get('idempotency_key');
        try {
            $result = $this->bookings->create($r->user(), $data, $key);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), null, 409);
        }
        $b = $result['booking'];
        $p = $result['payment'];
        return $this->created([
            'booking' => new BookingResource($b),
            'payment' => [
                'gateway' => 'razorpay',
                'order_id' => $p->gateway_order_id,
                'amount' => (int) $p->amount,
                'currency' => 'INR',
                'payment_reference' => $p->payment_reference,
                'razorpay_key_id' => config('rental.razorpay.key_id'),
            ],
        ], 'Booking created. Complete payment to confirm.');
    }

    public function index(Request $r)
    {
        $map = [
            'upcoming' => ['confirmed', 'pending'],
            'active' => ['active'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled', 'expired'],
        ];
        $q = Booking::where('user_id', $r->user()->id)->with('bike')->latest();
        if ($r->filled('status') && isset($map[$r->string('status')->value()])) {
            $q->whereIn('status', $map[$r->string('status')->value()]);
        }
        $bookings = $q->paginate($r->integer('per_page', 20));
        return $this->ok(BookingResource::collection($bookings), '', [
            'pagination' => ['current_page' => $bookings->currentPage(), 'total' => $bookings->total()],
        ]);
    }

    public function show(Request $r, Booking $booking)
    {
        abort_unless($booking->user_id === $r->user()->id, 403);
        return $this->ok(new BookingResource($booking->load('bike')));
    }

    public function cancel(Request $r, Booking $booking)
    {
        abort_unless($booking->user_id === $r->user()->id, 403);
        $data = $r->validate(['reason' => ['required', 'string', 'max:255']]);
        try {
            $result = $this->bookings->cancel($booking, $data['reason']);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), null, 409);
        }
        return $this->ok($result, 'Booking cancelled');
    }

    public function unlock(Request $r, Booking $booking)
    {
        abort_unless($booking->user_id === $r->user()->id, 403);
        try {
            $booking = $this->bookings->unlock($booking);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), null, 409);
        }
        return $this->ok(['status' => $booking->status, 'actual_start_at' => $booking->actual_start_at], 'Bike unlocked. Ride started.');
    }

    public function returnBike(Request $r, Booking $booking)
    {
        abort_unless($booking->user_id === $r->user()->id, 403);
        $data = $r->validate(['return_hub_id' => ['nullable', 'exists:rental_hubs,id']]);
        try {
            $result = $this->bookings->returnBike($booking, $data['return_hub_id'] ?? null);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), null, 409);
        }
        return $this->ok($result, 'Ride completed. Deposit refund initiated.');
    }
}
