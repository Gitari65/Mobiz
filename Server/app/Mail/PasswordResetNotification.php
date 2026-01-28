<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class PasswordResetNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $otp;

    public function __construct(User $user, string $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Password Reset - New Temporary Password')
                    ->view('emails.password_reset')
                    ->with([
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                        'otp' => $this->otp,
                        'company' => config('app.name', 'MOBIz')
                    ]);
    }
}
