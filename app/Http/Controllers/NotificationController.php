<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Menampilkan halaman riwayat notifikasi.
     */
    public function index()
    {
        $user = Auth::user();

        // Tandai semua notifikasi yang belum dibaca sebagai "telah dibaca"
        $user->unreadNotifications->markAsRead();

        // PENYEMPURNAAN: Paginasi yang lebih efisien dan idiomatik
        $notifications = $user->notifications()->latest()->paginate(15);

        return view('notifications.index', ['notifications' => $notifications]);
    }

    /**
     * Menandai satu notifikasi sebagai telah dibaca dan mengarahkan ke tujuannya.
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        // Cek apakah ada 'path' dalam data notifikasi
        if (isset($notification->data['path']) && !empty($notification->data['path'])) {
            $filePath = $notification->data['path'];

            // LOGIKA CERDAS: Cek di kedua disk, 'public' dan 'local' (private)
            if (Storage::disk('public')->exists($filePath)) {
                return Storage::disk('public')->response($filePath, $notification->data['filename'] ?? 'download.pdf');
            }

            if (Storage::disk('local')->exists($filePath)) {
                return Storage::disk('local')->response($filePath, $notification->data['filename'] ?? 'download.pdf');
            }

            Log::warning('File notifikasi tidak ditemukan di disk manapun.', ['path' => $filePath]);
        }

        return redirect()->route('notifications.index')->with('error', 'File tidak ditemukan atau telah dihapus.');
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
