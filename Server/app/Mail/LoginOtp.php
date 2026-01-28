<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class LoginOtp extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $otp;

    public function __construct(User $user, string $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Your MOBIz login verification code')
                    ->view('emails.login_otp')
                    ->with([
                        'name' => $this->user->name,
                        'otp' => $this->otp,
                        'company' => $this->user->company?->name ?? null,
                    ]);
    }
}
