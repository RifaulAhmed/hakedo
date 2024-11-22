<?php

namespace App\Helpers; 

use Illuminate\Support\Facades\Mail;

class sendCredentialsEmail
{
    public static function sendCredentialsEmail($user, $password)
    {
        // dd($user);
        $data = [
            'full_name' => $user->username,
            'email' => $user->email,
            'password' => $password,
        ];

        Mail::send('emails.credentials', $data, function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Your Account Credentials');
        });
    }
}
