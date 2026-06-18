<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Wallets\Pages\ViewWallet;
use App\Models\Rental\AdminUser;
use App\Models\Rental\Booking;
use App\Models\Rental\Payment;
use App\Models\Rental\Wallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\CreatesRentalData;
use Tests\TestCase;

class AdminPaymentsModuleTest extends TestCase
{
    use RefreshDatabase, CreatesRentalData;

    private function admin(AdminRole $role): AdminUser
    {
        return AdminUser::create([
            'name' => 'T Admin',
            'email' => strtolower($role->value) . '@zippi.in',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function capturedPayment(int $amountPaise = 100000): Payment
    {
        $bike = $this->aBike();
        $rider = $this->approvedRider();
        $booking = Booking::create([
            'booking_code' => 'ZB' . strtoupper(Str::random(8)),
            'user_id' => $rider->id, 'bike_id' => $bike->id,
            'pickup_hub_id' => $bike->hub_id, 'return_hub_id' => $bike->hub_id, 'city_id' => $bike->city_id,
            'duration_type' => 'daily', 'start_at' => now()->addDay(), 'end_at' => now()->addDays(2),
            'total_amount' => $amountPaise, 'status' => 'completed',
        ]);

        return Payment::create([
            'payment_reference' => 'PAY' . strtoupper(Str::random(8)),
            'booking_id' => $booking->id, 'user_id' => $rider->id,
            'gateway' => 'razorpay', 'amount' => $amountPaise, 'currency' => 'INR',
            'method' => 'upi', 'status' => 'captured',
            'idempotency_key' => (string) Str::uuid(), 'paid_at' => now(),
        ]);
    }

    public function test_payments_list_renders_for_support(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');
        $payment = $this->capturedPayment();

        Livewire::test(ListPayments::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$payment]);
    }

    public function test_kyc_reviewer_cannot_view_payments(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');

        Livewire::test(ListPayments::class)->assertForbidden();
    }

    public function test_ops_can_initiate_a_small_refund(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $payment = $this->capturedPayment(100000); // ₹1,000

        Livewire::test(ViewPayment::class, ['record' => $payment->getKey()])
            ->callAction('initiateRefund', data: [
                'amount' => 500, 'reason' => 'Goodwill', 'destination' => 'wallet',
            ]);

        $this->assertDatabaseHas('rental_refunds', [
            'booking_id' => $payment->booking_id,
            'amount' => 50000, // ₹500 paise
        ]);
    }

    public function test_ops_cannot_refund_at_or_above_threshold(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $payment = $this->capturedPayment(700000); // ₹7,000

        // Threshold is ₹5,000 — ops attempting ₹6,000 must be blocked.
        Livewire::test(ViewPayment::class, ['record' => $payment->getKey()])
            ->callAction('initiateRefund', data: [
                'amount' => 6000, 'reason' => 'Big refund', 'destination' => 'wallet',
            ]);

        $this->assertDatabaseMissing('rental_refunds', ['booking_id' => $payment->booking_id]);
    }

    public function test_super_admin_can_refund_above_threshold(): void
    {
        $this->actingAs($this->admin(AdminRole::SuperAdmin), 'admin');
        $payment = $this->capturedPayment(700000);

        Livewire::test(ViewPayment::class, ['record' => $payment->getKey()])
            ->callAction('initiateRefund', data: [
                'amount' => 6000, 'reason' => 'Approved', 'destination' => 'wallet',
            ]);

        $this->assertDatabaseHas('rental_refunds', [
            'booking_id' => $payment->booking_id,
            'amount' => 600000,
        ]);
    }

    public function test_support_cannot_see_refund_action(): void
    {
        $this->actingAs($this->admin(AdminRole::Support), 'admin');
        $payment = $this->capturedPayment();

        Livewire::test(ViewPayment::class, ['record' => $payment->getKey()])
            ->assertActionHidden('initiateRefund');
    }

    public function test_super_admin_can_adjust_wallet_and_it_is_audited(): void
    {
        $this->actingAs($this->admin(AdminRole::SuperAdmin), 'admin');
        $rider = $this->approvedRider();
        $wallet = Wallet::updateOrCreate(['user_id' => $rider->id], ['balance' => 20000, 'currency' => 'INR']);

        Livewire::test(ViewWallet::class, ['record' => $wallet->getKey()])
            ->callAction('adjustWallet', data: [
                'direction' => 'credit', 'amount' => 150, 'reason' => 'Compensation',
            ]);

        $this->assertEquals(35000, $wallet->fresh()->balance); // 20000 + 15000
        $this->assertDatabaseHas('rental_audit_logs', [
            'action' => 'wallet.credit',
            'entity_type' => 'wallet',
            'entity_id' => $wallet->id,
        ]);
    }

    public function test_ops_cannot_adjust_wallet(): void
    {
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');
        $rider = $this->approvedRider();
        $wallet = Wallet::updateOrCreate(['user_id' => $rider->id], ['balance' => 0, 'currency' => 'INR']);

        Livewire::test(ViewWallet::class, ['record' => $wallet->getKey()])
            ->assertActionHidden('adjustWallet');
    }
}
