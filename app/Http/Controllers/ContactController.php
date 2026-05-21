<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use App\Models\UserActivityLog;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index()
    {
        return Inertia::render('Contact/Index');
    }

    public function store(ContactRequest $request)
    {
        $validated = $request->validated();

        ContactMessage::create($validated);

        UserActivityLog::record(auth()->id(), 'contact_message_sent', "Contact message from: {$validated['email']} - {$validated['name']}");

        return to_route('contact')->with('success', 'Your message has been sent. We\'ll get back to you soon.');
    }
}
