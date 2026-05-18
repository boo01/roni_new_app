<?php

namespace App\Livewire;

use App\Mail\NewOrderToOwner;
use App\Mail\OrderConfirmationToCustomer;
use App\Services\Cart;
use App\Services\OrderCreator;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CheckoutPage extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:50')]
    public string $phone = '';

    #[Validate('nullable|string|max:255')]
    public string $company_name = '';

    #[Validate('nullable|string|max:50')]
    public string $company_tax_id = '';

    #[Validate('required|string|max:255')]
    public string $address = '';

    #[Validate('nullable|string|max:2000')]
    public string $notes = '';

    public function mount(Cart $cart): void
    {
        if ($cart->isEmpty()) {
            $this->redirectRoute('cart.show', navigate: false);
            return;
        }

        if ($user = auth()->user()) {
            $this->name = (string) $user->name;
            $this->email = (string) $user->email;
            $this->phone = (string) ($user->phone ?? '');
            $this->company_name = (string) ($user->company_name ?? '');
            $this->company_tax_id = (string) ($user->company_tax_id ?? '');
            $this->address = (string) ($user->address ?? '');
        }
    }

    public function submit(OrderCreator $creator)
    {
        $data = $this->validate();

        $order = $creator->create(auth()->user(), $data);

        // Best-effort: failures here are logged (mail driver is "log" in dev).
        try {
            Mail::to(config('shop.owner_email'))->send(new NewOrderToOwner($order));
            if (! empty($order->customer_snapshot['email'])) {
                Mail::to($order->customer_snapshot['email'])->send(new OrderConfirmationToCustomer($order));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->redirectRoute('order.thank-you', ['order' => $order->order_number], navigate: false);
    }

    public function render(Cart $cart)
    {
        return view('livewire.checkout-page', [
            'summary' => $cart->summary(auth()->user()),
        ]);
    }
}
