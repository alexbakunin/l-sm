<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DefaultCrm extends Mailable
{
    use Queueable, SerializesModels;

    private string $emailFrom;
    private string $emailFromName;
    private string $emailSubject;
    public string $emailContent;
    public string $emailPlain;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        string $emailFrom,
        string $emailFromName,
        string $emailSubject,
        string $emailContent,
        string $emailPlain
    ) {
        $this->emailFrom = $emailFrom;
        $this->emailFromName = $emailFromName;
        $this->emailSubject = $emailSubject;
        $this->emailContent = $emailContent;
        $this->emailPlain = $emailPlain;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->from($this->emailFrom, $this->emailFromName)
            ->subject($this->emailSubject)
            ->view('emails.default_crm')
            ->text('emails.default_crm_plain');
    }
}
