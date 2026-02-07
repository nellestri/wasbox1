<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Notification;
use App\Models\AdminNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendUnclaimedReminders extends Command
{
    protected $signature = 'unclaimed:send-reminders';
    protected $description = 'Send reminder notifications for unclaimed laundry orders';

    public function handle()
    {
        $this->info('🔔 Starting unclaimed laundry reminder process...');

        $reminderConfig = [
            1 => 'first',
            3 => 'second',
            7 => 'urgent',
            14 => 'final',
        ];

        $totalReminders = 0;

        foreach ($reminderConfig as $days => $urgency) {
            $orders = Order::with(['customer', 'branch'])
                ->unclaimedExactDays($days)
                ->get();

            foreach ($orders as $order) {
                // Send customer notification with FCM
                Notification::unclaimedReminder($order, $days, $urgency);

                // Notify admin for 3+ days
                if ($days >= 3) {
                    $color = match($urgency) {
                        'final' => 'danger',
                        'urgent' => 'warning',
                        default => 'info',
                    };

                    AdminNotification::create([
                        'type' => 'unclaimed',
                        'title' => "Unclaimed: {$days} Days",
                        'message' => "Order #{$order->tracking_number} from {$order->customer->name} - ₱" . number_format($order->total_amount, 2),
                        'icon' => 'exclamation-triangle',
                        'color' => $color,
                        'link' => route('admin.orders.show', $order->id),
                        'branch_id' => $order->branch_id,
                    ]);
                }

                $order->recordReminderSent();
                $totalReminders++;

                $this->line("  📧 Day {$days} reminder: {$order->customer->name} (#{$order->tracking_number})");
            }
        }

        // Mark orders as unclaimed after 7 days
        Order::shouldMarkUnclaimed()->each(function ($order) {
            $order->markAsUnclaimed();
        });

        // Update storage fees
        Order::markedUnclaimed()->each(function ($order) {
            $order->updateStorageFee();
        });

        $this->info("✅ Completed! Sent {$totalReminders} reminder(s)");
        Log::info("Unclaimed reminders: {$totalReminders} sent");

        return Command::SUCCESS;
    }
}
