<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index()
    {
        return Inertia::render('Contact/Index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        ContactMessage::create($validated);

        UserActivityLog::record(auth()->id(), 'contact_message_sent', "Contact message from: {$validated['email']} - {$validated['name']}");

        return to_route('contact')->with('success', 'Your message has been sent. We\'ll get back to you soon.');
    }
}
