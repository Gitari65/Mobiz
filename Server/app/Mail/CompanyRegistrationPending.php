<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Company;
use App\Models\User;

class CompanyRegistrationPending extends Mailable
{
    use Queueable, SerializesModels;

    public $company;
    public $user;

    public function __construct(Company $company, User $user)
    {
        $this->company = $company;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('New Company Registration Pending Review - ' . $this->company->name)
                    ->view('emails.company_registration_pending')
                    ->with([
                        'companyName' => $this->company->name,
                        'ownerName' => $this->user->name,
                        'ownerEmail' => $this->user->email,
                        'category' => $this->company->category,
                        'phone' => $this->company->phone,
                        'address' => $this->company->address,
                        'city' => $this->company->city,
                        'county' => $this->company->county,
                        'kraPin' => $this->company->kra_pin ?? 'N/A',
                    ]);
    }
}
