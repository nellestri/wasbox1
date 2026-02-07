<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    /**
     * Display the settings dashboard grouped by category
     */
    public function index()
    {
        $settings = SystemSetting::all()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update global business rules and branding
     */
    public function update(Request $request)
    {
        // 1. Image Upload for Branding (App Logo)
        if ($request->hasFile('app_logo')) {
            $path = $request->file('app_logo')->store('settings', 'public');
            SystemSetting::set('app_logo', $path, 'string', 'general');
        }

        // 2. Process all other text and toggle inputs
        $data = $request->except(['_token', '_method', 'app_logo']);

        foreach ($data as $key => $value) {
            $type = is_numeric($value) ? 'integer' : 'string';

            // Handle checkbox logic for notifications and reminders
            if (
                in_array($key, [
                    'enable_push_notifications',
                    'notify_order_received',
                    'notify_order_ready',
                    'notify_order_completed',
                    'notify_unclaimed',
                    'reminder_day_3',
                    'reminder_day_5',
                    'reminder_day_7'
                ])
            ) {
                $type = 'boolean';
                $value = $request->has($key) ? '1' : '0';
            }

            SystemSetting::set($key, $value, $type);
        }

        return redirect()->back()->with('success', 'WLMS Settings updated successfully.');
    }

    /**
     * Generate a new database SQL dump (Objective C.5)
     */
    public function backup()
    {
        try {
            $filename = "backup-" . now()->format('Y-m-d-H-i-s') . ".sql";
            $path = storage_path('app/backups/' . $filename);

            if (!File::exists(storage_path('app/backups'))) {
                File::makeDirectory(storage_path('app/backups'), 0755, true);
            }

            // Using mysqldump command
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                config('database.connections.mysql.host'),
                config('database.connections.mysql.database'),
                $path
            );

            exec($command);

            return response()->json(['success' => true, 'message' => 'Backup created: ' . $filename]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Download an existing backup file
     */
    public function downloadBackup($filename)
    {
        $path = storage_path('app/backups/' . $filename);
        if (File::exists($path)) {
            return response()->download($path);
        }
        return redirect()->back()->with('error', 'File not found.');
    }

    /**
     * Automatic Cleanup: Delete backups older than 30 days
     */
    public function cleanupBackups()
    {
        try {
            $path = storage_path('app/backups');

            if (!File::exists($path)) {
                return response()->json(['success' => true, 'message' => 'Backup folder does not exist.']);
            }

            $files = File::files($path);
            $deletedCount = 0;
            $now = Carbon::now();

            foreach ($files as $file) {
                $lastModified = Carbon::createFromTimestamp($file->getMTime());

                if ($lastModified->diffInDays($now) > 30) {
                    File::delete($file->getPathname());
                    $deletedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Cleanup complete. Removed $deletedCount old backup(s)."
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Show the user's personal profile
     */
    public function profile()
    {
        $user = Auth::user();
        return view('admin.profile.index', compact('user'));
    }

    /**
     * Update Admin/Staff personal details
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        User::find($user->id)->update($request->only('name', 'email', 'phone'));

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Securely update account password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::find($user->id)->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()->with('success', 'Password changed successfully.');
    }

    /**
     * Show the staff member's restricted profile
     */
    public function staffProfile()
    {
        $user = Auth::user();
        return view('staff.profile.index', compact('user'));
    }

    /**
     * Update staff profile (restricted to phone only)
     */
    public function staffUpdateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        // Only update phone; name and email are protected
        $user->update($request->only('phone'));

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }
    /**
     * Check the health status of system integrations
     */
    public function systemStatus()
    {
        $status = [
            'database' => false,
            'storage' => false,
            'fcm' => false,
            'last_backup' => 'Never'
        ];

        // 1. Check Database Connection
        try {
            DB::connection()->getPdo();
            $status['database'] = true;
        } catch (\Exception $e) {
        }

        // 2. Check Storage Permissions
        $status['storage'] = is_writable(storage_path('app/public')) && is_writable(storage_path('app/backups'));

        // 3. Check FCM Configuration
        $fcmKey = \App\Models\SystemSetting::get('fcm_server_key');
        $status['fcm'] = !empty($fcmKey);

        // 4. Get Last Backup Date
        $backupPath = storage_path('app/backups');
        if (File::exists($backupPath)) {
            $files = File::files($backupPath);
            if (count($files) > 0) {
                $latest = collect($files)->sortByDesc(fn($f) => $f->getMTime())->first();
                $status['last_backup'] = date('Y-m-d H:i', $latest->getMTime());
            }
        }

        return $status;
    }
    /**
     * Update FCM specific settings via AJAX
     */
    public function updateFCM(Request $request)
    {
        $request->validate([
            'fcm_server_key' => 'required|string',
            'fcm_sender_id' => 'required|string',
        ]);

        \App\Models\SystemSetting::set('fcm_server_key', $request->fcm_server_key, 'string', 'notifications');
        \App\Models\SystemSetting::set('fcm_sender_id', $request->fcm_sender_id, 'string', 'notifications');

        return response()->json(['success' => true, 'message' => 'FCM credentials updated successfully.']);
    }

    /**
     * Clean up backups older than 30 days (Objective C.5)
     */
    public function cleanup()
    {
        $path = storage_path('app/backups');
        if (!File::exists($path)) {
            return response()->json(['success' => true, 'message' => 'No backups to clean.']);
        }

        $files = File::files($path);
        $count = 0;
        foreach ($files as $file) {
            if (now()->timestamp - $file->getMTime() > (30 * 24 * 60 * 60)) {
                File::delete($file->getPathname());
                $count++;
            }
        }

        return response()->json(['success' => true, 'message' => "Removed $count old backup(s)."]);
    }
    /**
     * Update Notification & FCM Settings
     */
    public function updateNotifications(Request $request)
    {
        $request->validate([
            'fcm_server_key' => 'nullable|string',
            'fcm_sender_id' => 'nullable|string',
        ]);

        // Save API Keys
        \App\Models\SystemSetting::set('fcm_server_key', $request->fcm_server_key, 'string', 'notifications');
        \App\Models\SystemSetting::set('fcm_sender_id', $request->fcm_sender_id, 'string', 'notifications');

        // Save Toggles (Order status alerts)
        $toggles = [
            'notify_order_ready',
            'notify_order_completed',
            'notify_unclaimed_reminder'
        ];

        foreach ($toggles as $key) {
            \App\Models\SystemSetting::set($key, $request->has($key) ? '1' : '0', 'boolean', 'notifications');
        }

        return redirect()->back()->with('success', 'Notification settings updated successfully.');
    }
}
