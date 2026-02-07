<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * This is the main seeder that orchestrates all other seeders.
     * Run with: php artisan db:seed
     *
     * Seeding Order:
     * 1. Branches (3 locations)
     * 2. Services (3 services)
     * 3. Pricing (9 entries)
     * 4. Admin User (1 admin)
     * 5. Staff Users (3 staff, one per branch)
     * 6. Customers (5 sample customers)
     * 7. System Settings (18 settings)
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║   WASHBOX DATABASE SEEDER                    ║');
        $this->command->info('║   Laundry Management System                  ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
        $this->command->info('');

        // 1. Seed Branches
        $this->command->info('📍 Seeding Branches...');
        $this->call(BranchSeeder::class);

        // 2. Seed Services
        $this->command->info('🧺 Seeding Services...');
        $this->call(ServiceSeeder::class);

        // 4. Seed Admin User
        $this->command->info('👤 Seeding Admin User...');
        $this->call(AdminUserSeeder::class);

        // 5. Seed Staff Users
        $this->command->info('👥 Seeding Staff Users...');
        $this->call(StaffSeeder::class);

        // 6. Seed Customers
        $this->command->info('🙋 Seeding Customers...');
        $this->call(CustomerSeeder::class);

        // 7. Seed System Settings
        $this->command->info('⚙️  Seeding System Settings...');
        $this->call(SystemSettingsSeeder::class);

      $this->call(PickupRequestSeeder::class);
      $this->call(UnclaimedLaundrySeeder::class);
       $this->call(PromotionSeeder::class);
       $this->call(AddOnsTableSeeder::class);



        // Summary
        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║   ✅ DATABASE SEEDING COMPLETE!              ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('  ✅ 3 Branches created');
        $this->command->info('  ✅ 3 Services created');
        $this->command->info('  ✅ 9 Pricing entries created');
        $this->command->info('  ✅ 1 Admin user created');
        $this->command->info('  ✅ 3 Staff users created');
        $this->command->info('  ✅ 5 Customers created');
        $this->command->info('  ✅ 18 System settings initialized');
        $this->command->info('');
        $this->command->info('🔐 Credentials:');
        $this->command->info('');
        $this->command->info('  ADMIN:');
        $this->command->info('  📧 admin@washbox.com / Admin@123');
        $this->command->info('');
        $this->command->info('  STAFF:');
        $this->command->info('  📧 dgt.staff@washbox.com / Staff@123 (Dumaguete)');
        $this->command->info('  📧 sbl.staff@washbox.com / Staff@123 (Sibulan)');
        $this->command->info('  📧 bas.staff@washbox.com / Staff@123 (Bais)');
        $this->command->info('');
        $this->command->info('  CUSTOMERS:');
        $this->command->info('  📧 juan.delacruz@example.com / Customer@123');
        $this->command->info('  📧 maria.santos@example.com / Customer@123');
        $this->command->info('  ... and 3 more (all use Customer@123)');
        $this->command->info('');
        $this->command->info('⚠️  IMPORTANT: Change all passwords after first login!');
        $this->command->info('');
        $this->command->info('🚀 Next Steps:');
        $this->command->info('  1. Admin: http://localhost/admin/login');
        $this->command->info('  2. Staff: http://localhost/staff/login');
        $this->command->info('  3. API: http://localhost/api/login (mobile app)');
        $this->command->info('');
    }
}
