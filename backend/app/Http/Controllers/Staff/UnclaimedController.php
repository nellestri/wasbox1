<?php

namespace App\Http\Controllers\Staff;

use App\Models\Order;
use App\Models\Notification;
use App\Models\AdminNotification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnclaimedController extends Controller
{
    /**
     * Display unclaimed laundry for staff's branch
     */
    public function index(Request $request)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        $query = Order::with(['customer', 'service'])
            ->where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->whereNotNull('ready_at')
            ->orderBy('ready_at', 'asc'); // Oldest first

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

        // Filter by days range
        if ($request->filled('min_days')) {
            $query->where('ready_at', '<=', now()->subDays((int) $request->min_days));
        }

        if ($request->filled('max_days')) {
            $query->where('ready_at', '>=', now()->subDays((int) $request->max_days));
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

        $orders = $query->paginate(15)->withQueryString();

        // Calculate stats for this branch
        $stats = $this->getBranchStats($staff->branch_id);

        return view('staff.unclaimed.index', compact('orders', 'stats'));
    }

    /**
     * Show single unclaimed order details
     */
    public function show($id)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return redirect()->route('staff.dashboard')
                ->with('error', 'Your account is not assigned to a branch.');
        }

        $order = Order::with(['customer', 'service', 'branch', 'statusHistories.changedBy'])
            ->where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->findOrFail($id);

        // Get reminder history for this order
        $reminderHistory = Notification::where('order_id', $order->id)
            ->where('type', 'unclaimed_reminder')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('staff.unclaimed.show', compact('order', 'reminderHistory'));
    }

    /**
     * Send reminder to customer
     */
    public function sendReminder(Request $request, $id)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        $order = Order::with(['customer', 'branch'])
            ->where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->findOrFail($id);

        // Determine urgency based on days
        $days = $order->days_unclaimed;
        $urgency = match(true) {
            $days >= 14 => 'final',
            $days >= 7 => 'urgent',
            $days >= 3 => 'second',
            default => 'first',
        };

        // Create and send notification
        Notification::createUnclaimedReminder($order, $days, $urgency);

        // Record reminder sent
        $order->recordReminderSent();

        // Log activity
        $order->statusHistories()->create([
            'status' => 'ready',
            'changed_by' => $staff->id,
            'notes' => "Unclaimed reminder sent (Day {$days}, {$urgency})",
        ]);

        return back()->with('success', "Reminder sent to {$order->customer->name}!");
    }

    /**
     * Send bulk reminders
     */
    public function sendBulkReminders(Request $request)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
        ]);

        $count = 0;
        $orders = Order::with(['customer', 'branch'])
            ->where('branch_id', $staff->branch_id)
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

            Notification::createUnclaimedReminder($order, $days, $urgency);
            $order->recordReminderSent();
            $count++;
        }

        return back()->with('success', "Sent {$count} reminder(s) successfully!");
    }

    /**
     * Mark order as claimed/paid (quick action)
     */
    public function markClaimed(Request $request, $id)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        $order = Order::where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->findOrFail($id);

        // Update to paid status
        $order->updateStatus('paid', $staff, 'Payment recorded - unclaimed order claimed');

        // Notify admin
        AdminNotification::create([
            'type' => 'unclaimed_recovered',
            'title' => 'Unclaimed Order Recovered! 💰',
            'message' => "Order #{$order->tracking_number} claimed after {$order->days_unclaimed} days - ₱" . number_format($order->total_amount, 2),
            'icon' => 'currency-dollar',
            'color' => 'success',
            'link' => route('admin.orders.show', $order->id),
            'branch_id' => $order->branch_id,
        ]);

        return redirect()->route('staff.orders.show', $order)
            ->with('success', 'Order marked as claimed! Proceed to complete.');
    }

    /**
     * Call customer (log the attempt)
     */
    public function logCallAttempt(Request $request, $id)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        $order = Order::where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->findOrFail($id);

        $request->validate([
            'result' => 'required|in:answered,no_answer,busy,wrong_number,voicemail',
            'notes' => 'nullable|string|max:500',
        ]);

        // Log the call attempt
        $order->statusHistories()->create([
            'status' => 'ready',
            'changed_by' => $staff->id,
            'notes' => "Call attempt: {$request->result}" . ($request->notes ? " - {$request->notes}" : ""),
        ]);

        // Update last reminder if answered
        if ($request->result === 'answered') {
            $order->update(['last_reminder_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Call logged successfully',
        ]);
    }

    /**
     * Get branch statistics
     */
    private function getBranchStats(int $branchId): array
    {
        $baseQuery = Order::where('branch_id', $branchId)
            ->where('status', 'ready')
            ->whereNotNull('ready_at');

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

        // Total value at risk
        $totalValue = (clone $baseQuery)->sum('total_amount');

        // Critical value (14+ days)
        $criticalValue = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(14))->sum('total_amount');

        // Potential storage fees
        $storageFees = 0;
        $ordersWithFees = (clone $baseQuery)->where('ready_at', '<=', now()->subDays(7))->get();
        foreach ($ordersWithFees as $order) {
            $extraDays = $order->days_unclaimed - 7;
            $storageFees += max(0, $extraDays * config('unclaimed.storage_fee_per_day', 10));
        }

        // Reminders sent today
        $remindersSentToday = Notification::where('type', 'unclaimed_reminder')
            ->whereHas('order', fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', today())
            ->count();

        return [
            'total' => $total,
            'critical' => $critical,
            'urgent' => $urgent,
            'warning' => $warning,
            'pending' => $pending,
            'total_value' => $totalValue,
            'critical_value' => $criticalValue,
            'storage_fees' => $storageFees,
            'reminders_today' => $remindersSentToday,
        ];
    }

    /**
     * Get statistics (AJAX)
     */
    public function stats()
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        return response()->json($this->getBranchStats($staff->branch_id));
    }

    /**
     * Export unclaimed list
     */
    public function export(Request $request)
    {
        $staff = Auth::user();

        if (!$staff || !$staff->branch_id) {
            return back()->with('error', 'Your account is not assigned to a branch.');
        }

        $orders = Order::with(['customer', 'service'])
            ->where('branch_id', $staff->branch_id)
            ->where('status', 'ready')
            ->whereNotNull('ready_at')
            ->orderBy('ready_at', 'asc')
            ->get();

        $filename = 'unclaimed_laundry_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Tracking #',
                'Customer Name',
                'Phone',
                'Service',
                'Total Amount',
                'Ready Date',
                'Days Unclaimed',
                'Urgency',
                'Reminders Sent',
                'Last Reminder',
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->tracking_number,
                    $order->customer->name,
                    $order->customer->phone,
                    $order->service->name ?? 'N/A',
                    number_format($order->total_amount, 2),
                    $order->ready_at->format('Y-m-d'),
                    $order->days_unclaimed,
                    ucfirst($order->unclaimed_status),
                    $order->reminder_count ?? 0,
                    $order->last_reminder_at?->format('Y-m-d H:i') ?? 'Never',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
