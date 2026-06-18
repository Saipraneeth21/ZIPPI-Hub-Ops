<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\KycStatus;
use App\Filament\Resources\CustomerDocuments\Pages\CreateCustomerDocument;
use App\Filament\Resources\CustomerDocuments\Pages\EditCustomerDocument;
use App\Filament\Resources\CustomerDocuments\Pages\ListCustomerDocuments;
use App\Models\Rental\AdminUser;
use App\Models\Rental\KycDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCustomerDocumentsTest extends TestCase
{
    use RefreshDatabase;

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

    private function rider(): User
    {
        return User::create([
            'name' => 'Rider One',
            'email' => 'rider1@zippi.in',
            'password' => 'password',
        ]);
    }

    private function document(User $rider, string $status = 'pending'): KycDocument
    {
        return KycDocument::create([
            'user_id' => $rider->id,
            'document_type' => 'driving_license',
            'document_number_masked' => 'DL-XXXX-1234',
            'file_path' => "kyc/{$rider->id}/dl.txt",
            'status' => $status,
        ]);
    }

    public function test_kyc_reviewer_can_list_customer_documents(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');
        $doc = $this->document($this->rider());

        Livewire::test(ListCustomerDocuments::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$doc]);
    }

    public function test_ops_cannot_access_customer_documents(): void
    {
        // Ops is not in the kyc.review matrix.
        $this->actingAs($this->admin(AdminRole::Ops), 'admin');

        Livewire::test(ListCustomerDocuments::class)->assertForbidden();
        Livewire::test(CreateCustomerDocument::class)->assertForbidden();
    }

    public function test_creating_an_approved_document_auto_stamps_verification_date(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');
        $rider = $this->rider();

        Livewire::test(CreateCustomerDocument::class)
            ->fillForm([
                'user_id' => $rider->id,
                'document_type' => 'government_id',
                'document_number_masked' => 'GI-XXXX-7788',
                'status' => KycStatus::Approved->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $doc = KycDocument::where('user_id', $rider->id)->first();
        $this->assertNotNull($doc);
        $this->assertSame(KycStatus::Approved, $doc->status);
        $this->assertNotNull($doc->verified_at, 'verified_at should be auto-stamped on approval');
    }

    public function test_returning_a_document_to_pending_clears_verification_date(): void
    {
        $this->actingAs($this->admin(AdminRole::KycReviewer), 'admin');
        $doc = $this->document($this->rider(), 'approved');
        $doc->update(['verified_at' => now()]);

        Livewire::test(EditCustomerDocument::class, ['record' => $doc->getKey()])
            ->fillForm(['status' => KycStatus::Pending->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($doc->refresh()->verified_at);
    }
}
