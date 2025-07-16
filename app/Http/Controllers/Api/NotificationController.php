<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Maka ny notif rehetra (novakiana sy tsy novakiana)
    public function index(Request $request)
    {
        return response()->json($request->user()->notifications);
    }

    // Maka ireo notif mbola tsy novakiana
    public function unread(Request $request)
    {
        return response()->json($request->user()->unreadNotifications);
    }

    // Maka ny isan'ny notif mbola tsy novakiana
    public function unreadCount(Request $request)
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications->count()
        ]);
    }

    // Marquer notif iray ho novakiana
    public function markAsRead(Request $request, $id)
    {
        $notif = $request->user()->notifications()->findOrFail($id);
        $notif->markAsRead();

        return response()->json(['message' => 'Notification marquée comme lue']);
    }

    // Marquer ny notif rehetra ho novakiana
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Toutes les notifications marquées comme lues']);
    }
}
