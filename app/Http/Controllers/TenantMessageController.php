<?php

namespace App\Http\Controllers;

use App\Models\TenantMessage;
use Illuminate\Http\Request;

class TenantMessageController extends Controller
{
    public function index(Request $request)
    {
        $showResolved = $request->query('resolved', false);

        $query = TenantMessage::with(['tenant.user', 'room']);

        if ($showResolved) {
            $messages = $query->resolved()->orderByDesc('resolved_at')->paginate(20);
        } else {
            $messages = $query->unresolved()->orderByDesc('created_at')->paginate(20);
        }

        return view('tenant-messages.index', compact('messages', 'showResolved'));
    }

    public function room($roomId, Request $request)
    {
        $showResolved = $request->query('resolved', false);

        $query = TenantMessage::with(['tenant.user', 'room'])
            ->where('room_id', $roomId);

        if ($showResolved) {
            $messages = $query->resolved()->orderByDesc('resolved_at')->paginate(20);
        } else {
            $messages = $query->unresolved()->orderByDesc('created_at')->paginate(20);
        }

        return view('tenant-messages.index', compact('messages', 'showResolved'));
    }

    public function show(TenantMessage $message)
    {
        $message->load(['tenant.user', 'room', 'readBy', 'repliedBy', 'replies.user', 'resolvedBy']);

        // Mark as read if unread
        if ($message->status === 'unread') {
            $message->markAsRead(auth()->user());
        }
        
        // Mark unread replies from tenant as read
        $message->replies()
            ->whereNull('read_at')
            ->where('user_id', '!=', auth()->id())
            ->update(['read_at' => now()]);

        return view('tenant-messages.show', compact('message'));
    }

    public function reply(Request $request, TenantMessage $message)
    {
        // Check if message is resolved
        if ($message->isResolved()) {
            return back()->with('error', 'Cannot reply to a resolved conversation.');
        }

        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        // Add reply to thread
        $reply = $message->addReply(auth()->user(), $request->message);
        
        // Mark message as read if not already
        if ($message->status === 'unread') {
            $message->markAsRead(auth()->user());
        }

        // Send email notification to tenant
        try {
            $message->tenant->user->notify(new \App\Notifications\TenantMessageReplyNotification(
                $message,
                $reply,
                auth()->user()
            ));
        } catch (\Exception $e) {
            \Log::error('Failed to send message reply notification', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Reply sent successfully.');
    }

    public function resolve(TenantMessage $message)
    {
        if (!$message->isResolved()) {
            $message->markAsResolved(auth()->user());
        }

        return redirect()->route('tenant-messages.index')
            ->with('success', 'Concern marked as resolved.');
    }
}
