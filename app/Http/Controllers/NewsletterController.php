<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        Subscriber::firstOrCreate(['email' => $validated['email']]);

        UserActivityLog::record(auth()->id(), 'newsletter_subscribed', "Newsletter subscribed: {$validated['email']}");

        return response()->json(['message' => 'Subscribed successfully.']);
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        Subscriber::where('email', $validated['email'])->delete();

        UserActivityLog::record(auth()->id(), 'newsletter_unsubscribed', "Newsletter unsubscribed: {$validated['email']}");

        return response()->json(['message' => 'Unsubscribed successfully.']);
    }
}
