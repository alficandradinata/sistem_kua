<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * [SISTEM KUA] Kotak notifikasi in-app untuk semua peran. Lihat PROGRESS.md.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString() === 'unread' ? 'unread' : 'all';

        $query = $request->user()->appNotifications()->latestFirst();

        if ($filter === 'unread') {
            $query->unread();
        }

        return view('notifications.index', [
            'filter' => $filter,
            'notifications' => $query->paginate(15)->withQueryString(),
            'unreadCount' => $request->user()->unreadNotificationCount(),
        ]);
    }

    public function markAsRead(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->markAsRead();

        return back()->with('status', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $jumlah = $request->user()->appNotifications()->unread()->update([
            'is_read' => true,
        ]);

        return back()->with('status', $jumlah > 0
            ? "{$jumlah} notifikasi ditandai sudah dibaca."
            : 'Tidak ada notifikasi baru.');
    }

    public function destroy(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->delete();

        return back()->with('status', 'Notifikasi dihapus.');
    }
}
