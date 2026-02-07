<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates initial system settings for the WashBox application.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            ['key' => 'app_name', 'value' => 'WashBox', 'type' => 'string', 'group' => 'general', 'description' => 'Application name'],
            ['key' => 'contact_phone', 'value' => '09123456789', 'type' => 'string', 'group' => 'general', 'description' => 'Contact phone number'],
            ['key' => 'contact_email', 'value' => 'info@washbox.com', 'type' => 'string', 'group' => 'general', 'description' => 'Contact email address'],
            ['key' => 'business_address', 'value' => 'Negros Oriental, Philippines', 'type' => 'string', 'group' => 'general', 'description' => 'Business address'],

            // Notification Settings
            ['key' => 'enable_push_notifications', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Enable FCM push notifications'],
            ['key' => 'notify_order_received', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Send notification when order received'],
            ['key' => 'notify_order_ready', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Send notification when order ready'],
            ['key' => 'notify_order_completed', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Send notification when order completed'],
            ['key' => 'notify_unclaimed', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'description' => 'Send unclaimed reminders'],

            // Pricing Settings
            ['key' => 'default_price_wash', 'value' => '40.00', 'type' => 'string', 'group' => 'pricing', 'description' => 'Default wash price per kg'],
            ['key' => 'default_price_dry', 'value' => '40.00', 'type' => 'string', 'group' => 'pricing', 'description' => 'Default dry price per kg'],
            ['key' => 'default_price_full', 'value' => '70.00', 'type' => 'string', 'group' => 'pricing', 'description' => 'Default full service price per kg'],
            ['key' => 'min_order_weight', 'value' => '3.0', 'type' => 'string', 'group' => 'pricing', 'description' => 'Minimum order weight in kg'],

            // Unclaimed Settings
            ['key' => 'reminder_day_3', 'value' => '1', 'type' => 'boolean', 'group' => 'unclaimed', 'description' => 'Send reminder on day 3'],
            ['key' => 'reminder_day_5', 'value' => '1', 'type' => 'boolean', 'group' => 'unclaimed', 'description' => 'Send reminder on day 5'],
            ['key' => 'reminder_day_7', 'value' => '1', 'type' => 'boolean', 'group' => 'unclaimed', 'description' => 'Send reminder on day 7'],
            ['key' => 'max_unclaimed_days', 'value' => '30', 'type' => 'integer', 'group' => 'unclaimed', 'description' => 'Maximum days before disposal'],

            // Backup Settings
            ['key' => 'backup_schedule', 'value' => 'disabled', 'type' => 'string', 'group' => 'backup', 'description' => 'Automatic backup schedule'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create($setting);
            $this->command->info("✅ Created setting: {$setting['key']}");
        }

        $this->command->info('');
        $this->command->info('==============================================');
        $this->command->info('  SYSTEM SETTINGS INITIALIZED');
        $this->command->info('==============================================');
        $this->command->info('  General: 4 settings');
        $this->command->info('  Notifications: 5 settings');
        $this->command->info('  Pricing: 4 settings');
        $this->command->info('  Unclaimed: 4 settings');
        $this->command->info('  Backup: 1 setting');
        $this->command->info('==============================================');
        $this->command->info('');
    }
}
