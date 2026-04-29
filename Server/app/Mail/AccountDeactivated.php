<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class AccountDeactivated extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $reason;

    public function __construct(User $user, string $reason = null)
    {
        $this->user = $user;
        $this->reason = $reason;
    }

    public function build()
    {
        return $this->subject('Your Account Has Been Deactivated')
                    ->view('emails.account_deactivated')
                    ->with([
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                        'company' => $this->user->company?->name ?? 'MOBIz',
                        'reason' => $this->reason,
                    ]);
    }
}
