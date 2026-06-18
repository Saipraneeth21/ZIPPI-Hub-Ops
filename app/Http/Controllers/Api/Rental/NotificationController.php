<?php
namespace App\Http\Controllers\Api\Rental;

use App\Http\Controllers\Controller;
use App\Http\Resources\Rental\NotificationResource;
use App\Models\Rental\Notification;
use App\Support\Concerns\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $r)
    {
        $q = Notification::where('user_id', $r->user()->id)->latest();
        $items = $q->paginate($r->integer('per_page', 20));
        $unread = Notification::where('user_id', $r->user()->id)->whereNull('read_at')->count();
        return $this->ok(NotificationResource::collection($items), '', [
            'pagination' => ['current_page' => $items->currentPage(), 'total' => $items->total()],
            'unread_count' => $unread,
        ]);
    }

    public function unreadCount(Request $r)
    {
        return $this->ok(['unread_count' => Notification::where('user_id', $r->user()->id)->whereNull('read_at')->count()]);
    }

    public function markRead(Request $r, Notification $notification)
    {
        abort_unless($notification->user_id === $r->user()->id, 403);
        $notification->update(['read_at' => now()]);
        return $this->ok(null, 'Marked as read');
    }

    public function markAllRead(Request $r)
    {
        $n = Notification::where('user_id', $r->user()->id)->whereNull('read_at')->update(['read_at' => now()]);
        return $this->ok(['updated' => $n], 'All notifications marked read');
    }
}
