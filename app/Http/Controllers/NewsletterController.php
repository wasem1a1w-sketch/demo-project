<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsletterRequest;
use App\Models\Subscriber;
use App\Models\UserActivityLog;

class NewsletterController extends Controller
{
    public function subscribe(NewsletterRequest $request)
    {
        $validated = $request->validated();

        Subscriber::firstOrCreate(['email' => $validated['email']]);

        UserActivityLog::record(auth()->id(), 'newsletter_subscribed', "Newsletter subscribed: {$validated['email']}");

        return response()->json(['message' => 'Subscribed successfully.']);
    }

    public function unsubscribe(NewsletterRequest $request)
    {
        $validated = $request->validated();

        Subscriber::where('email', $validated['email'])->delete();

        UserActivityLog::record(auth()->id(), 'newsletter_unsubscribed', "Newsletter unsubscribed: {$validated['email']}");

        return response()->json(['message' => 'Unsubscribed successfully.']);
    }
}
