<?php

namespace App\Mail;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationToCustomer extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "თქვენი შეკვეთა #{$this->order->order_number} მიღებულია",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-confirmation-to-customer');
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdfs.invoice', ['order' => $this->order])->setPaper('a4')->output();

        return [
            Attachment::fromData(fn () => $pdf, "invoice-{$this->order->order_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
