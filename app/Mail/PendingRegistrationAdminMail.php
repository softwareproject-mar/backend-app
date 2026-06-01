<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PendingRegistrationAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $pendingUser,
        public bool $isReminder = false
    ) {}

    public function envelope(): Envelope
    {
        $app = config('app.name');

        $subject = $this->isReminder
            ? "Pengingat: pendaftar masih menunggu persetujuan - {$app}"
            : "Pendaftar baru menunggu persetujuan - {$app}";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pending-registration-admin',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
