<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NotificationController extends Controller
{
    /**
     * Menampilkan halaman riwayat notifikasi dengan paginasi.
     */
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(15);
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Menandai satu notifikasi sebagai telah dibaca dan mengarahkan ke tujuannya.
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if (isset($notification->data['path']) && !empty($notification->data['path'])) {
            return redirect(Storage::url($notification->data['path']));
        }

        return redirect()->back();
    }

    /**
     * Menandai semua notifikasi yang belum dibaca sebagai telah dibaca.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai sebagai sudah dibaca.');
    }

    /**
     * Menghapus notifikasi yang dipilih oleh pengguna.
     */
    public function deleteSelected(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id',
        ]);

        Auth::user()->notifications()
            ->whereIn('id', $request->notification_ids)
            ->delete();

        return redirect()->route('notifications.index')->with('success', 'Notifikasi yang dipilih berhasil dihapus.');
    }

    /**
     * Menghapus semua notifikasi milik pengguna.
     */
    public function deleteAll()
    {
        Auth::user()->notifications()->delete();
        return redirect()->route('notifications.index')->with('success', 'Semua riwayat notifikasi telah dihapus.');
    }
}
