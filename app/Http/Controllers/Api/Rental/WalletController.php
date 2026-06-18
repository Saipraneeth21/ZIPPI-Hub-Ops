<?php
namespace App\Http\Controllers\Api\Rental;

use App\Http\Controllers\Controller;
use App\Http\Resources\Rental\WalletTransactionResource;
use App\Services\Rental\WalletService;
use App\Support\Concerns\ApiResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly WalletService $wallet) {}

    public function show(Request $r)
    {
        $w = $this->wallet->forUser($r->user()->id);
        return $this->ok(['balance' => (int) $w->balance, 'currency' => $w->currency]);
    }

    public function transactions(Request $r)
    {
        $w = $this->wallet->forUser($r->user()->id);
        $tx = $w->transactions()->latest()->paginate($r->integer('per_page', 20));
        return $this->ok(WalletTransactionResource::collection($tx), '', [
            'pagination' => ['current_page' => $tx->currentPage(), 'total' => $tx->total()],
        ]);
    }
}
