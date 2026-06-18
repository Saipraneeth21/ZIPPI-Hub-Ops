<?php
namespace App\Http\Controllers\Api\Rental;

use App\Http\Controllers\Controller;
use App\Http\Resources\Rental\BikeResource;
use App\Models\Rental\Bike;
use App\Models\Rental\BikeCategory;
use App\Services\Rental\AvailabilityService;
use App\Support\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CatalogController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AvailabilityService $availability) {}

    public function categories()
    {
        $cats = BikeCategory::where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'slug' => $c->slug,
                'available_count' => $c->bikes()->where('status', 'available')->count(),
            ]);
        return $this->ok($cats);
    }

    public function bikes(Request $r)
    {
        $q = Bike::query()->with(['category', 'pricing', 'primaryImage', 'hub']);
        if ($r->filled('category_id')) $q->where('category_id', $r->integer('category_id'));
        if ($r->filled('hub_id')) $q->where('hub_id', $r->integer('hub_id'));
        if ($r->filled('status')) $q->where('status', $r->string('status'));
        $bikes = $q->paginate($r->integer('per_page', 20));
        return $this->ok(BikeResource::collection($bikes), '', [
            'pagination' => [
                'current_page' => $bikes->currentPage(),
                'per_page' => $bikes->perPage(),
                'total' => $bikes->total(),
            ],
        ]);
    }

    public function show(Bike $bike)
    {
        $bike->load(['category', 'pricing', 'images', 'hub']);
        return $this->ok(new BikeResource($bike));
    }

    public function availability(Request $r, Bike $bike)
    {
        $data = $r->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
        ]);
        $available = $this->availability->isAvailable(
            $bike->id, Carbon::parse($data['start_at']), Carbon::parse($data['end_at'])
        );
        return $this->ok(['bike_id' => $bike->id, 'available' => $available]);
    }
}
