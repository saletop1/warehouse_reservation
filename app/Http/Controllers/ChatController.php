<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // Halaman utama chat
    public function index()
    {
        $currentUser = Auth::user();

        // Ambil semua user kecuali diri sendiri
        $users = User::where('id', '!=', $currentUser->id)
            ->orderBy('name')
            ->get();

        return view('chat.index', compact('users'));
    }

    // Ambil pesan dengan user tertentu - PERBAIKAN PARAMETER
        public function getMessages($user)
    {
        try {
            $currentUserId = Auth::id();

            // Validasi user
            $userModel = User::find($user);
            if (!$userModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Tandai pesan sebagai sudah dibaca
            Chat::where('sender_id', $user)
                ->where('receiver_id', $currentUserId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            // Ambil pesan
            $messages = Chat::where(function($query) use ($currentUserId, $user) {
                    $query->where('sender_id', $currentUserId)
                        ->where('receiver_id', $user);
                })
                ->orWhere(function($query) use ($currentUserId, $user) {
                    $query->where('sender_id', $user)
                        ->where('receiver_id', $currentUserId);
                })
                ->with(['sender', 'receiver'])
                ->orderBy('created_at', 'asc')
                ->limit(100)
                ->get();

            return response()->json([
                'success' => true,
                'messages' => $messages,
                'current_user_id' => $currentUserId,
                'user' => [
                    'id' => $userModel->id,
                    'name' => $userModel->name,
                    'email' => $userModel->email
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Kirim pesan
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000'
        ]);

        $chat = Chat::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message' => $request->message
        ]);

        return response()->json([
            'success' => true,
            'message' => $chat
        ]);
    }

    // Hitung pesan belum dibaca
    public function unreadCount()
    {
        $count = Chat::where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    // Tandai pesan sebagai sudah dibaca
    public function markAsRead($messageId)
    {
        $message = Chat::find($messageId);

        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Message not found'], 404);
        }

        if ($message->receiver_id == Auth::id()) {
            $message->update(['is_read' => true, 'read_at' => now()]);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }
}
