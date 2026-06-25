<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ContactRequest;
use App\Mail\AdminContactMail;
use App\Mail\UserContactMail;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(ContactRequest $request)
    {
        $data = $request->validated();

        Mail::to($data['email'])->send(new UserContactMail($data));

        Mail::to(config('mail.admin_email', config('mail.from.address')))->send(new AdminContactMail($data));

        return back()->with('toast-success', 'Tin nhắn của bạn đã được gửi thành công!');
    }
}