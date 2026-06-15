<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function thankYou(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->with('items')->firstOrFail();
        return view('pages.order-thank-you', compact('order'));
    }

    public function invoice(string $orderNumber): Response
    {
        $order = Order::where('order_number', $orderNumber)
            ->with('items.product.media')
            ->firstOrFail();

        $pdf = Pdf::setOptions([
            // Embed only the glyphs we use from the Georgian @font-face fonts.
            'isFontSubsettingEnabled' => true,
            'isRemoteEnabled' => false,
        ])->loadView('pdfs.invoice', ['order' => $order])->setPaper('a4');

        return $pdf->stream("invoice-{$order->order_number}.pdf");
    }
}
