<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class NotificationController extends Controller
{
    /**
     * Menampilkan halaman riwayat notifikasi.
     */
    public function index()
    {
        // Tandai semua notifikasi yang belum dibaca sebagai "telah dibaca" saat halaman dibuka
        Auth::user()->unreadNotifications->markAsRead();

        // Ambil notifikasi milik pengguna dan tampilkan dengan paginasi
        $notifications = Auth::user()->notifications->sortByDesc('created_at');
        $notifications = $notifications->forPage(request('page', 1), 15);
        $paginatedNotifications = new \Illuminate\Pagination\LengthAwarePaginator(
            $notifications,
            Auth::user()->notifications->count(),
            15,
            request('page', 1),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('notifications.index', ['notifications' => $paginatedNotifications]);
    }

    /**
     * Menandai satu notifikasi sebagai telah dibaca dan mengarahkan ke tujuannya.
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications->find($id);

        if (!$notification) {
            abort(404);
        }
        $notification->markAsRead();

        if (isset($notification->data['path']) && !empty($notification->data['path'])) {
            if (Storage::disk('public')->exists($notification->data['path'])) {
                return redirect(Storage::url($notification->data['path']));
            }
        }

        return redirect()->route('notifications.index');
    }

    /**
     * Menghapus notifikasi yang dipilih.
     */
    public function deleteSelected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:notifications,id',
        ]);

        Auth::user()->notifications->whereIn('id', $request->ids)->each->delete();

        return back()->with('success', 'Notifikasi yang dipilih berhasil dihapus.');
    }

    /**
     * Menghapus semua notifikasi milik pengguna.
     */
    public function deleteAll()
    {
        Auth::user()->notifications->each->delete();

        return back()->with('success', 'Semua notifikasi berhasil dihapus.');
    }
}
