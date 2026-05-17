<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage; // Add this import
use Illuminate\Support\Facades\Log; // Added for debugging/logging admin email

class LowStockAlert extends Notification
{
    use Queueable;

    public function __construct(
        public Product $product
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail']; // Modified
    }

    public function toMail(object $notifiable): MailMessage // New method
    {
        $productName = $this->product->name;
        $currentStock = $this->product->stock;

        return (new MailMessage)
                    ->error() // Mark as error/warning
                    ->subject('Low Stock Alert: ' . $productName)
                    ->line('The product "' . $productName . '" is running low on stock.')
                    ->line('Current stock: ' . $currentStock . ' units.')
                    ->action('View Product', route('admin.products.edit', $this->product->id))
                    ->line('Please replenish stock as soon as possible.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'stock' => $this->product->stock,
            'message' => "Low stock: {$this->product->name} ({$this->product->stock} left)",
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'low_stock';
    }
}
