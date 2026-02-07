<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\Branch;
use App\Models\Notification;
use App\Models\AdminNotification;
use App\Models\UnclaimedLaundry;
use App\Models\SystemSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnclaimedController extends Controller
{
    /**
     * Display all unclaimed laundry across all branches
     */
    public function index(Request $request)
    {
        $disposalThreshold = SystemSetting::get('disposal_threshold_days', 30);

        // Build query - can use either UnclaimedLaundry or Order model
        // Using Order model directly for real-time accuracy
        $query = Order::with(['customer', 'service', 'branch', 'staff'])
            ->where('status', 'ready')
            ->whereNotNull('ready_at')
            ->orderBy('ready_at', 'asc'); // Oldest first (most critical)

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by urgency level
        if ($request->filled('urgency')) {
            switch ($request->urgency) {
                case 'critical':
                    $query->where('ready_at', '<=', now()->subDays(14));
                    break;
                case 'urgent':
                    $query->where('ready_at', '<=', now()->subDays(7))
                          ->where('ready_at', '>', now()->subDays(14));
                    break;
                case 'warning':
                    $query->where('ready_at', '<=', now()->subDays(3))
                          ->where('ready_at', '>', now()->subDays(7));
                    break;
                case 'pending':
                    $query->where('ready_at', '<=', now()->subDays(1))
                          ->where('ready_at', '>', now()->subDays(3));
                    break;
            }
        }

        // Filter by minimum days
        if ($request->filled('min_days')) {
            $query->where('ready_at', '<=', now()->subDays((int) $request->min_days));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        // Also get UnclaimedLaundry records for backward compatibility
        $unclaimedOrders = UnclaimedLaundry::with(['customer', 'branch', 'order'])
            ->where('status', 'unclaimed')
            ->orderBy('days_unclaimed', 'desc')
            ->paginate(10);

        // Get all stats
        $stats = $this->getGlobalStats();

        // Get branch stats for comparison
        $branchStats = $this->getBranchComparison();

        // Get branches for filter
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.unclaimed.index', compact(
            'orders',
            'unclaimedOrders',
            'stats',
            'branchStats',
            'branches',
            'disposalThreshold'
        ));
    }

    /**
     * Show single unclaimed order details
     */
    public function show($id)
    {
        $order = Order::with([
            'customer',
            'service',
            'branch',
            'staff',
            'statusHistories.changedBy',
        ])->where('status', 'ready')->findOrFail($id);

        // Get reminder history
        $reminderHistory = Notification::where('order_id', $order->id)
            ->whereIn('type', ['unclaimed_reminder', 'unclaimed_day1', 'unclaimed_day3', 'unclaimed_day7', 'unclaimed_day14'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.unclaimed.show', compact('order', 'reminderHistory'));
    }

    /**
     * Send reminder to customer
     */
    public function sendReminder(Request $request, $id)
    {
        // Try to find Order first
        $order = Order::with(['customer', 'branch'])->find($id);

        // If not found, try UnclaimedLaundry
        if (!$order) {
            $unclaimed = UnclaimedLaundry::with(['order.customer', 'order.branch', 'customer'])->find($id);
            if ($unclaimed) {
                $order = $unclaimed->order;
            }
        }

        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        // Determine urgency based on days
        $days = $order->days_unclaimed;
        $urgency = match(true) {
            $days >= 14 => 'final',
            $days >= 7 => 'urgent',
            $days >= 3 => 'second',
            default => 'first',
        };

        // Create and send notification using helper method
        if (method_exists(Notification::class, 'createUnclaimedReminder')) {
            Notification::createUnclaimedReminder($order, $days, $urgency);
        } else {
            // Fallback: create notification directly
            Notification::create([
                'customer_id' => $order->customer_id,
                'type' => 'unclaimed_reminder',
                'title' => $this->getReminderTitle($urgency),
                'body' => $this->getReminderBody($order, $days, $urgency),
                'order_id' => $order->id,
            ]);
        }

        // Record reminder sent if method exists
        if (method_exists($order, 'recordReminderSent')) {
            $order->recordReminderSent();
        } else {
            $order->update([
                'last_reminder_at' => now(),
                'reminder_count' => ($order->reminder_count ?? 0) + 1,
            ]);
        }

        // Log activity
        $order->statusHistories()->create([
            'status' => 'ready',
            'changed_by' => Auth::id(),
            'notes' => "Unclaimed reminder sent by admin (Day {$days}, {$urgency})",
        ]);

        $customerName = $order->customer->name ?? 'Customer';
        return back()->with('success', "Reminder sent to {$customerName}!");
    }

    /**
     * Send reminders to all unclaimed orders
     */
    public function remindAll(Request $request)
    {
        $query = Order::with(['customer', 'branch'])
            ->where('status', 'ready')
            ->whereNotNull('ready_at')
            ->where('ready_at', '<=', now()->subDays(3)); // At least 3 days old

        // Optional: filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Only send to orders not reminded in last 24 hours
        $query->where(function ($q) {
            $q->whereNull('last_reminder_at')
              ->orWhere('last_reminder_at', '<=', now()->subHours(24));
        });

        $orders = $query->get();
        $count = 0;

        foreach ($orders as $order) {
            $days = $order->days_unclaimed;
            $urgency = match(true) {
                $days >= 14 => 'final',
                $days >= 7 => 'urgent',
                $days >= 3 => 'second',
                default => 'first',
            };

            // Create notification
            if (method_exists(Notification::class, 'createUnclaimedReminder')) {
                Notification::createUnclaimedReminder($order, $days, $urgency);
            } else {
                Notification::create([
                    'customer_id' => $order->customer_id,
                    'type' => 'unclaimed_reminder',
                    'title' => $this->getReminderTitle($urgency),
                    'body' => $this->getReminderBody($order, $days, $urgency),
                    'order_id' => $order->id,
                ]);
            }

            // Record reminder
            if (method_exists($order, 'recordReminderSent')) {
                $order->recordReminderSent();
            } else {
                $order->update([
                    'last_reminder_at' => now(),
                    'reminder_count' => ($order->reminder_count ?? 0) + 1,
                ]);
            }

            $count++;
        }

        if ($count > 0) {
            return back()->with('success', "Sent {$count} reminder(s) to customers with unclaimed laundry!");
        }

        return back()->with('warning', 'No orders needed reminders (all were reminded within 24 hours).');
    }

    /**
     * Mark order as claimed/paid
     */
    public function markClaimed(Request $request, $id)
    {
        // Try Order first
        $order = Order::with(['customer', 'branch'])->find($id);
        $unclaimedRecord = null;

        // Try UnclaimedLaundry if Order not found
        if (!$order) {
            $unclaimedRecord = UnclaimedLaundry::with(['order.customer', 'order.branch'])->find($id);
            if ($unclaimedRecord) {
                $order = $unclaimedRecord->order;
            }
        } else {
            // Check if there's an associated UnclaimedLaundry record
            $unclaimedRecord = UnclaimedLaundry::where('order_id', $order->id)->first();
        }

        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        $daysUnclaimed = $order->days_unclaimed;

        DB::transaction(function () use ($order, $unclaimedRecord, $daysUnclaimed) {
            // Update UnclaimedLaundry record if exists
            if ($unclaimedRecord) {
                $unclaimedRecord->update([
                    'status' => 'recovered',
                    'recovered_at' => now(),
                    'recovered_by' => Auth::id(),
                ]);
            }

            // Update order status
            if (method_exists($order, 'updateStatus')) {
                $order->updateStatus('paid', Auth::user(), 'Payment recorded - unclaimed order claimed after ' . $daysUnclaimed . ' days');
            } else {
                $order->update([
                    'status' => 'paid',
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            // Clear unclaimed flag if exists
            if (isset($order->is_unclaimed)) {
                $order->update(['is_unclaimed' => false]);
            }
        });

        // Create success notification for admin
        AdminNotification::create([
            'type' => 'unclaimed_recovered',
            'title' => '💰 Revenue Recovered!',
            'message' => "Order #{$order->tracking_number} claimed after {$daysUnclaimed} days - ₱" . number_format($order->total_amount, 2),
            'icon' => 'currency-dollar',
            'color' => 'success',
            'link' => route('admin.orders.show', $order->id),
            'branch_id' => $order->branch_id,
        ]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', "Order marked as claimed! Revenue of ₱" . number_format($order->total_amount, 2) . " recovered.");
    }

    /**
     * Mark order as disposed
     */
    public function markDisposed(Request $request, $id)
    {
        // Try Order first
        $order = Order::with(['customer', 'branch'])->find($id);
        $unclaimedRecord = null;

        if (!$order) {
            $unclaimedRecord = UnclaimedLaundry::with(['order.customer', 'order.branch'])->find($id);
            if ($unclaimedRecord) {
                $order = $unclaimedRecord->order;
            }
        } else {
            $unclaimedRecord = UnclaimedLaundry::where('order_id', $order->id)->first();
        }

        if (!$order) {
            return back()->with('error', 'Order not found.');
        }

        // Check if eligible for disposal
        $disposalThreshold = SystemSetting::get('disposal_threshold_days', 30);
        if ($order->days_unclaimed < $disposalThreshold) {
            return back()->with('error', "Order must be unclaimed for at least {$disposalThreshold} days before disposal.");
        }

        $daysUnclaimed = $order->days_unclaimed;

        DB::transaction(function () use ($order, $unclaimedRecord, $daysUnclaimed) {
            // Create or update UnclaimedLaundry record
            if (!$unclaimedRecord) {
                $unclaimedRecord = UnclaimedLaundry::create([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'branch_id' => $order->branch_id,
                    'days_unclaimed' => $daysUnclaimed,
                    'status' => 'disposed',
                    'disposed_at' => now(),
                    'disposed_by' => Auth::id(),
                    'notes' => "Disposed after {$daysUnclaimed} days - exceeded storage policy",
                ]);
            } else {
                $unclaimedRecord->update([
                    'status' => 'disposed',
                    'disposed_at' => now(),
                    'disposed_by' => Auth::id(),
                    'days_unclaimed' => $daysUnclaimed,
                    'notes' => ($unclaimedRecord->notes ?? '') . " | Disposed after {$daysUnclaimed} days",
                ]);
            }

            // Update order status
            if (method_exists($order, 'updateStatus')) {
                $order->updateStatus('cancelled', Auth::user(), 'Disposed - exceeded storage policy after ' . $daysUnclaimed . ' days');
            } else {
                $order->update([
                    'status' => 'cancelled',
                    'payment_status' => 'unpaid',
                    'cancelled_at' => now(),
                ]);
            }

            $order->update([
                'cancellation_reason' => "Disposed after {$daysUnclaimed} days unclaimed",
            ]);
        });

        // Notify customer
        Notification::create([
            'customer_id' => $order->customer_id,
            'type' => 'order_disposed',
            'title' => 'Order Disposed',
            'body' => "Your order #{$order->tracking_number} has been disposed per our {$daysUnclaimed}-day policy. Please contact us for questions.",
            'order_id' => $order->id,
        ]);

        // Admin notification
        AdminNotification::create([
            'type' => 'order_disposed',
            'title' => 'Order Disposed',
            'message' => "Order #{$order->tracking_number} disposed after {$daysUnclaimed} days - ₱" . number_format($order->total_amount, 2) . " lost",
            'icon' => 'trash',
            'color' => 'secondary',
            'link' => route('admin.unclaimed.history'),
            'branch_id' => $order->branch_id,
        ]);

        return back()->with('warning', "Order #{$order->tracking_number} has been marked as disposed. ₱" . number_format($order->total_amount, 2) . " recorded as loss.");
    }

    /**
     * View disposal history
     */
    public function disposalHistory(Request $request)
    {
        $query = UnclaimedLaundry::with(['order.customer', 'branch', 'disposedBy', 'customer'])
            ->where('status', 'disposed')
            ->orderBy('disposed_at', 'desc');

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('disposed_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('disposed_at', '<=', $request->to_date);
        }

        $history = $query->paginate(15)->withQueryString();

        // Calculate totals
        $allDisposed = UnclaimedLaundry::where('status', 'disposed')->with('order')->get();

        $totalLoss = $allDisposed->sum(function($item) {
            return $item->order->total_amount ?? 0;
        });

        $totalDisposed = $allDisposed->count();

        // Loss by branch
        $lossByBranch = $allDisposed->groupBy('branch_id')->map(function ($items) {
            return [
                'count' => $items->count(),
                'value' => $items->sum(fn($item) => $item->order->total_amount ?? 0),
            ];
        });

        // This month's loss
        $thisMonthLoss = UnclaimedLaundry::where('status', 'disposed')
            ->whereMonth('disposed_at', now()->month)
            ->whereYear('disposed_at', now()->year)
            ->with('order')
            ->get()
            ->sum(fn($item) => $item->order->total_amount ?? 0);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.unclaimed.history', compact(
            'history',
            'totalLoss',
            'totalDisposed',
            'lossByBranch',
            'thisMonthLoss',
            'branches'
        ));
    }

    /**
     * Send bulk reminders
     */
    public function sendBulkReminders(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
        ]);

        $count = 0;
        $orders = Order::with(['customer', 'branch'])
            ->where('status', 'ready')
            ->whereIn('id', $request->order_ids)
            ->get();

        foreach ($orders as $order) {
            $days = $order->days_unclaimed;
            $urgency = match(true) {
                $days >= 14 => 'final',
                $days >= 7 => 'urgent',
                $days >= 3 => 'second',
                default => 'first',
            };

            if (method_exists(Notification::class, 'createUnclaimedReminder')) {
                Notification::createUnclaimedReminder($order, $days, $urgency);
            } else {
                Notification::create([
                    'customer_id' => $order->customer_id,
                    'type' => 'unclaimed_reminder',
                    'title' => $this->getReminderTitle($urgency),
                    'body' => $this->getReminderBody($order, $days, $urgency),
                    'order_id' => $order->id,
                ]);
            }

            if (method_exists($order, 'recordReminderSent')) {
                $order->recordReminderSent();
            } else {
                $order->update([
                    'last_reminder_at' => now(),
                    'reminder_count' => ($order->reminder_count ?? 0) + 1,
                ]);
            }

            $count++;
        }

        return back()->with('success', "Sent {$count} reminder(s) successfully!");
    }

    /**
     * Export unclaimed list
     */
    public function export(Request $request)
    {
        $query = Order::with(['customer', 'service', 'branch'])
            ->where('status', 'ready')
            ->whereNotNull('ready_at')
            ->orderBy('ready_at', 'asc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $orders = $query->get();

        $filename = 'unclaimed_laundry_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Tracking #',
                'Branch',
                'Customer Name',
                'Phone',
                'Email',
                'Service',
                'Weight (kg)',
                'Total Amount',
                'Ready Date',
                'Days Unclaimed',
                'Urgency',
                'Storage Fee',
                'Total with Fees',
                'Reminders Sent',
                'Last Reminder',
            ]);

            foreach ($orders as $order) {
                $storageFee = $order->calculated_storage_fee ?? 0;
                $daysUnclaimed = $order->days_unclaimed ?? 0;

                fputcsv($file, [
                    $order->tracking_number,
                    $order->branch->name ?? 'N/A',
                    $order->customer->name ?? 'N/A',
                    $order->customer->phone ?? 'N/A',
                    $order->customer->email ?? 'N/A',
                    $order->service->name ?? 'N/A',
                    $order->weight ?? 0,
                    number_format($order->total_amount, 2),
                    $order->ready_at?->format('Y-m-d') ?? 'N/A',
                    $daysUnclaimed,
                    ucfirst($order->unclaimed_status ?? 'normal'),
                    number_format($storageFee, 2),
                    number_format($order->total_amount + $storageFee, 2),
                    $order->reminder_count ?? 0,
                    $order->last_reminder_at?->format('Y-m-d H:i') ?? 'Never',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get statistics (AJAX)
     */
    public function stats(Request $request)
    {
        if ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $baseQuery = Order::where('branch_id', $branchId)
                ->where('status', 'ready')
                ->whereNotNull('ready_at');

            return response()->json([
                'total' => (clone $baseQuery)->count(),
                'critical' => (clone $baseQuery)->where('ready_at', '<=', now()->subDays(14))->count(),
                'urgent' => (clone $baseQuery)->where('ready_at', '<=', now()->subDays(7))
                                              ->where('ready_at', '>', now()->subDays(14))->count(),
                'warning' => (clone $baseQuery)->where('ready_at', '<=', now()->subDays(3))
                                               ->where('ready_at', '>', now()->subDays(7))->count(),
                'total_value' => (clone $baseQuery)->sum('total_amount'),
            ]);
        }

        return response()->json($this->getGlobalStats());
    }

    /**
     * Get global statistics
     */
    private function getGlobalStats(): array
    {
        $baseQuery = Order::where('status', 'ready')->whereNotNull('ready_at');

        // Total unclaimed
        $total = (clone $baseQuery)->count();

        // By urgency
        $critical = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(14))->count();
        $urgent = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(7))
                                    ->where('ready_at', '>', now()->subDays(14))->count();
        $warning = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(3))
                                     ->where('ready_at', '>', now()->subDays(7))->count();
        $pending = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(1))
                                     ->where('ready_at', '>', now()->subDays(3))->count();

        // Values
        $totalValue = (clone $baseQuery)->sum('total_amount');
        $criticalValue = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(14))->sum('total_amount');
        $urgentValue = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(7))->sum('total_amount');

        // Storage fees calculation
        $storageFees = 0;
        $feePerDay = config('unclaimed.storage_fee_per_day', 10);
        $ordersWithFees = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(7))->get();
        foreach ($ordersWithFees as $order) {
            $daysUnclaimed = $order->days_unclaimed ?? now()->diffInDays($order->ready_at);
            $extraDays = max(0, $daysUnclaimed - 7);
            $storageFees += $extraDays * $feePerDay;
        }

        // Reminders sent today
        $remindersSentToday = Notification::whereIn('type', ['unclaimed_reminder', 'unclaimed_day1', 'unclaimed_day3', 'unclaimed_day7', 'unclaimed_day14'])
            ->whereDate('created_at', today())
            ->count();

        // Recovery this month (orders that were unclaimed but got paid)
        $recoveredThisMonth = UnclaimedLaundry::where('status', 'recovered')
            ->whereMonth('recovered_at', now()->month)
            ->whereYear('recovered_at', now()->year)
            ->with('order')
            ->get()
            ->sum(fn($item) => $item->order->total_amount ?? 0);

        // Disposed this month
        $disposedThisMonth = UnclaimedLaundry::where('status', 'disposed')
            ->whereMonth('disposed_at', now()->month)
            ->count();

        // Loss this month
        $lossThisMonth = UnclaimedLaundry::where('status', 'disposed')
            ->whereMonth('disposed_at', now()->month)
            ->with('order')
            ->get()
            ->sum(fn($item) => $item->order->total_amount ?? 0);

        return [
            'total' => $total,
            'critical' => $critical,
            'urgent' => $urgent,
            'warning' => $warning,
            'pending' => $pending,
            'total_value' => $totalValue,
            'critical_value' => $criticalValue,
            'urgent_value' => $urgentValue,
            'storage_fees' => $storageFees,
            'potential_total' => $totalValue + $storageFees,
            'reminders_today' => $remindersSentToday,
            'recovered_this_month' => $recoveredThisMonth,
            'disposed_this_month' => $disposedThisMonth,
            'loss_this_month' => $lossThisMonth,
        ];
    }

    /**
     * Get branch comparison stats
     */
    private function getBranchComparison(): array
    {
        $branches = Branch::where('is_active', true)->get();
        $branchStats = [];

        foreach ($branches as $branch) {
            $baseQuery = Order::where('branch_id', $branch->id)
                ->where('status', 'ready')
                ->whereNotNull('ready_at');

            $total = (clone $baseQuery)->count();
            $critical = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(14))->count();
            $value = (clone $baseQuery)->sum('total_amount');

            $branchStats[] = [
                'id' => $branch->id,
                'name' => $branch->name,
                'total' => $total,
                'critical' => $critical,
                'value' => $value,
            ];
        }

        // Sort by total descending
        usort($branchStats, fn($a, $b) => $b['total'] - $a['total']);

        return $branchStats;
    }

    /**
     * Helper: Get reminder title based on urgency
     */
    private function getReminderTitle(string $urgency): string
    {
        return match($urgency) {
            'first' => 'Friendly Reminder 🧺',
            'second' => 'Your Laundry is Waiting 👕',
            'urgent' => '⚠️ Urgent: Laundry Unclaimed',
            'final' => '🚨 Final Notice: Action Required',
            default => 'Unclaimed Laundry Reminder',
        };
    }

    /**
     * Helper: Get reminder body based on urgency
     */
    private function getReminderBody(Order $order, int $days, string $urgency): string
    {
        $branchName = $order->branch->name ?? 'our branch';
        $trackingNumber = $order->tracking_number;

        return match($urgency) {
            'first' => "Hi! Your laundry (#{$trackingNumber}) is ready at {$branchName}. Please pick it up at your convenience.",
            'second' => "Your laundry has been ready for {$days} days. Please pick up order #{$trackingNumber} at {$branchName}.",
            'urgent' => "URGENT: Order #{$trackingNumber} has been unclaimed for {$days} days. Storage fees may apply after 7 days.",
            'final' => "FINAL NOTICE: Order #{$trackingNumber} unclaimed for {$days} days. Per policy, items may be disposed after 30 days. Please contact us immediately.",
            default => "Your order #{$trackingNumber} is ready for pickup at {$branchName}.",
        };
    }
}
