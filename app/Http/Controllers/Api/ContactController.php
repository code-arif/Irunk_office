<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use App\Models\Contact;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Mail\ContactSupportMail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    use ApiResponse;

    /**
     * Store a new support message and send email to admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 422);
        }

        $user = auth('api')->user();
        $name = $user->name ?? $user->username;
        $email = $user->email;

        // Automatically generate subject if not provided
        $subject = "Support Request from " . $name;

        try {
            // 1. Save to database
            $contact = Contact::create([
                'name'    => $name,
                'email'   => $email,
                'subject' => $subject,
                'message' => $request->message,
                'status'  => 'active'
            ]);

            // 2. Prepare mail data
            $mailData = [
                'name'    => $name,
                'email'   => $email,
                'subject' => $subject,
                'message' => $request->message,
            ];

            // 3. Get admin email from settings
            $adminEmail = Setting::first()->email ?? config('mail.from.address');

            // 4. Send email
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new ContactSupportMail($mailData));
            }

            return $this->success($contact, 'Your message has been sent successfully. Support will get back to you soon.');

        } catch (\Exception $e) {
            return $this->error([], 'Something went wrong: ' . $e->getMessage(), 500);
        }
    }
}
