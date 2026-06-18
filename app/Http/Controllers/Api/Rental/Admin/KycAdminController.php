<?php
namespace App\Http\Controllers\Api\Rental\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Rental\KycService;
use App\Support\Concerns\ApiResponse;
use Illuminate\Http\Request;

class KycAdminController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly KycService $kyc) {}

    public function index(Request $r)
    {
        $q = User::query()->whereHas('profile', function ($p) use ($r) {
            if ($r->filled('status')) $p->where('kyc_status', $r->string('status'));
        })->with('profile');
        $users = $q->paginate($r->integer('per_page', 20));
        return $this->ok($users->map(fn ($u) => [
            'user_id' => $u->id, 'name' => $u->name, 'mobile' => $u->mobile,
            'status' => $u->profile?->kyc_status,
        ]), '', ['pagination' => ['total' => $users->total()]]);
    }

    public function show(int $userId)
    {
        $u = User::with('profile', 'kycDocuments')->findOrFail($userId);
        return $this->ok([
            'user' => ['id' => $u->id, 'name' => $u->name, 'mobile' => $u->mobile],
            'status' => $u->profile?->kyc_status,
            'documents' => $u->kycDocuments->map(fn ($d) => [
                'id' => $d->id, 'type' => $d->document_type,
                'number_masked' => $d->document_number_masked, 'status' => $d->status,
            ]),
        ]);
    }

    public function approve(Request $r, int $userId)
    {
        $u = User::findOrFail($userId);
        $this->kyc->approve($u, $r->user()?->id);
        return $this->ok(['status' => 'approved'], 'KYC approved');
    }

    public function reject(Request $r, int $userId)
    {
        $data = $r->validate([
            'reason' => ['required', 'string', 'max:255'],
            'document_ids' => ['nullable', 'array'],
        ]);
        $u = User::findOrFail($userId);
        $this->kyc->reject($u, $data['reason'], $r->user()?->id, $data['document_ids'] ?? []);
        return $this->ok(['status' => 'rejected', 'reason' => $data['reason']], 'KYC rejected');
    }
}
