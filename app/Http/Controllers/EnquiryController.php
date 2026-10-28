<?php

namespace App\Http\Controllers;

use App\Enums\EnquiryTopic;
use App\Models\Enquiry;
use App\Support\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (Intake::isBot($request, Enquiry::fakeReference(), 'Enquiry')) {
            return to_route('thanks');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'organization' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            'topic' => ['required', Rule::enum(EnquiryTopic::class)],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'phone.regex' => 'Enter a phone number with its area code, for example (876) 555-0123.',
            'message.min' => 'Please tell us a little more (at least 10 characters).',
        ]);

        $enquiry = Enquiry::create(['email' => strtolower($data['email'])] + $data + ['source_url' => Intake::source($request)]);
        Intake::complete($enquiry->refresh(), $request);

        return to_route('thanks');
    }
}
