<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Create Roles
        $employerRole = Role::create(['name' => 'employer']);
        $contractorRole = Role::create(['name' => 'contractor']);
        $adminRole = Role::create(['name' => 'admin']);

        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'phone' => '03009999999',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'is_approved' => true,
            'available_credits' => 9999,
        ]);
        $admin->assignRole($adminRole);

        // 3. Create Seed Employers
        $employer1 = User::create([
            'name' => 'Test Employer',
            'email' => 'employer@example.com',
            'phone' => '03001111111',
            'password' => bcrypt('password123'),
            'role' => 'employer',
            'is_approved' => true,
            'available_credits' => 5,
        ]);
        $employer1->assignRole($employerRole);
        $employer1->profile()->create([
            'company_name' => 'KP Construction Ltd',
            'company_email' => 'info@kpconstruct.com',
            'address' => 'Phase 3, Hayatabad, Peshawar',
            'sector' => 'Construction',
            'alternate_phone' => '0915841234',
            'district' => 'Peshawar',
            'city' => 'Peshawar',
            'province' => 'Khyber Pakhtunkhwa',
        ]);

        $employer2 = User::create([
            'name' => 'Broke Employer',
            'email' => 'broke@example.com',
            'phone' => '03002222222',
            'password' => bcrypt('password123'),
            'role' => 'employer',
            'is_approved' => false,
            'available_credits' => 0,
        ]);
        $employer2->assignRole($employerRole);
        $employer2->profile()->create([
            'company_name' => 'Broke Retailers',
            'company_email' => 'sales@broke.com',
            'address' => 'Main Bazaar, Mardan',
            'sector' => 'Retail',
            'alternate_phone' => '0937841234',
            'district' => 'Mardan',
            'city' => 'Mardan',
            'province' => 'Khyber Pakhtunkhwa',
        ]);



        // 4. Create Seed Workers
        $workers = [
            [
                'phone' => '03330000000',
                'name' => 'Jan Muhammad',
                'sector' => 'Domestic',
                'skill_category' => 'Cook',
                'district' => 'Peshawar',
                'experience_years' => 4,
                'is_available' => true,
            ],
            [
                'phone' => '03159999999',
                'name' => 'Wali Khan',
                'sector' => 'Industrial',
                'skill_category' => 'Welder',
                'district' => 'Mardan',
                'experience_years' => 3,
                'is_available' => true,
            ],
            [
                'phone' => '03451234567',
                'name' => 'Sher Alam',
                'sector' => 'Industrial',
                'skill_category' => 'Welder',
                'district' => 'Peshawar',
                'experience_years' => 5,
                'is_available' => true,
            ],
            [
                'phone' => '03215551234',
                'name' => 'Bibi Amina',
                'sector' => 'Domestic',
                'skill_category' => 'Maid',
                'district' => 'Swat',
                'experience_years' => 2,
                'is_available' => true,
            ],
            [
                'phone' => '03129876543',
                'name' => 'Sajjad Ali',
                'sector' => 'Domestic',
                'skill_category' => 'Cook',
                'district' => 'Abbottabad',
                'experience_years' => 7,
                'is_available' => true,
            ],
            [
                'phone' => '03348765432',
                'name' => 'Niaz Gul',
                'sector' => 'Industrial',
                'skill_category' => 'Welder',
                'district' => 'Nowshera',
                'experience_years' => 6,
                'is_available' => false,
            ],
            [
                'phone' => '03014445555',
                'name' => 'Raza Khan',
                'sector' => 'Industrial',
                'skill_category' => 'Welder',
                'district' => 'Swabi',
                'experience_years' => 1,
                'is_available' => true,
            ],
            [
                'phone' => '03131112222',
                'name' => 'Gul Meena',
                'sector' => 'Domestic',
                'skill_category' => 'Maid',
                'district' => 'Haripur',
                'experience_years' => 10,
                'is_available' => true,
            ],
            [
                'phone' => '03104567890',
                'name' => 'Gohar Rehman',
                'sector' => 'Industrial',
                'skill_category' => 'Storekeeper',
                'district' => 'Mardan',
                'experience_years' => 4,
                'is_available' => true,
            ],
            [
                'phone' => '03407654321',
                'name' => 'Faridullah Jan',
                'sector' => 'Industrial',
                'skill_category' => 'Store Incharge',
                'district' => 'Peshawar',
                'experience_years' => 6,
                'is_available' => true,
            ],
            [
                'phone' => '03209876543',
                'name' => 'Sana Gul',
                'sector' => 'Domestic',
                'skill_category' => 'Data Entry Operator',
                'district' => 'Swat',
                'experience_years' => 2,
                'is_available' => true,
            ],
            [
                'phone' => '03310000001',
                'name' => 'Kashif Afridi',
                'sector' => 'Industrial',
                'skill_category' => 'Forklift Operator',
                'district' => 'Peshawar',
                'experience_years' => 5,
                'is_available' => true,
            ],
            [
                'phone' => '03310000002',
                'name' => 'Ziaul Haq',
                'sector' => 'Industrial',
                'skill_category' => 'Security Guard',
                'district' => 'Nowshera',
                'experience_years' => 8,
                'is_available' => true,
            ],
            [
                'phone' => '03310000003',
                'name' => 'Gohar Ali',
                'sector' => 'Industrial',
                'skill_category' => 'Mason',
                'district' => 'Charsadda',
                'experience_years' => 12,
                'is_available' => true,
            ],
            [
                'phone' => '03310000004',
                'name' => 'Bakht Zada',
                'sector' => 'Industrial',
                'skill_category' => 'Carpenter',
                'district' => 'Swat',
                'experience_years' => 6,
                'is_available' => true,
            ],
            [
                'phone' => '03310000005',
                'name' => 'Sohail Ahmad',
                'sector' => 'Industrial',
                'skill_category' => 'Painter',
                'district' => 'Mardan',
                'experience_years' => 3,
                'is_available' => true,
            ],
            [
                'phone' => '03310000006',
                'name' => 'Habib ur Rehman',
                'sector' => 'Industrial',
                'skill_category' => 'HVAC Technician',
                'district' => 'Peshawar',
                'experience_years' => 4,
                'is_available' => true,
            ],
            [
                'phone' => '03310000007',
                'name' => 'Imran Khan',
                'sector' => 'Industrial',
                'skill_category' => 'Auto Mechanic',
                'district' => 'Abbottabad',
                'experience_years' => 7,
                'is_available' => true,
            ],
            [
                'phone' => '03310000008',
                'name' => 'Muhammad Younas',
                'sector' => 'Industrial',
                'skill_category' => 'Steel Fixer',
                'district' => 'Haripur',
                'experience_years' => 5,
                'is_available' => true,
            ],
            [
                'phone' => '03310000009',
                'name' => 'Tariq Mehmood',
                'sector' => 'Industrial',
                'skill_category' => 'Scaffolder',
                'district' => 'Swat',
                'experience_years' => 3,
                'is_available' => true,
            ],
            [
                'phone' => '03310000010',
                'name' => 'Zafar Iqbal',
                'sector' => 'Industrial',
                'skill_category' => 'Crane Operator',
                'district' => 'Peshawar',
                'experience_years' => 9,
                'is_available' => true,
            ],
            [
                'phone' => '03310000011',
                'name' => 'Mushtaq Ahmad',
                'sector' => 'Industrial',
                'skill_category' => 'Pipe Fitter',
                'district' => 'Mardan',
                'experience_years' => 6,
                'is_available' => true,
            ],
            [
                'phone' => '03310000012',
                'name' => 'Shakir Ullah',
                'sector' => 'Industrial',
                'skill_category' => 'Machinist',
                'district' => 'Nowshera',
                'experience_years' => 5,
                'is_available' => true,
            ],
            [
                'phone' => '03310000013',
                'name' => 'Zahid Khan',
                'sector' => 'Industrial',
                'skill_category' => 'Boiler Operator',
                'district' => 'Haripur',
                'experience_years' => 7,
                'is_available' => true,
            ],
            [
                'phone' => '03310000014',
                'name' => 'Abid Ali',
                'sector' => 'Industrial',
                'skill_category' => 'Quality Control Inspector',
                'district' => 'Abbottabad',
                'experience_years' => 4,
                'is_available' => true,
            ],
            [
                'phone' => '03310000015',
                'name' => 'Faisal Shah',
                'sector' => 'Industrial',
                'skill_category' => 'Office Assistant',
                'district' => 'Peshawar',
                'experience_years' => 3,
                'is_available' => true,
            ],
            [
                'phone' => '03310000016',
                'name' => 'Sajid Mehmood',
                'sector' => 'Industrial',
                'skill_category' => 'Office Boy',
                'district' => 'Nowshera',
                'experience_years' => 2,
                'is_available' => true,
            ],
            [
                'phone' => '03310000017',
                'name' => 'Asma Bibi',
                'sector' => 'Industrial',
                'skill_category' => 'Receptionist',
                'district' => 'Peshawar',
                'experience_years' => 1,
                'is_available' => true,
            ],
            [
                'phone' => '03310000018',
                'name' => 'Kamran Khan',
                'sector' => 'Industrial',
                'skill_category' => 'Record Keeper',
                'district' => 'Mardan',
                'experience_years' => 5,
                'is_available' => true,
            ],
            [
                'phone' => '03310000019',
                'name' => 'Babar Zaman',
                'sector' => 'Industrial',
                'skill_category' => 'Dispatch Rider',
                'district' => 'Peshawar',
                'experience_years' => 4,
                'is_available' => true,
            ],
            [
                'phone' => '03310000020',
                'name' => 'Naveed Gul',
                'sector' => 'Industrial',
                'skill_category' => 'Helper',
                'district' => 'Charsadda',
                'experience_years' => 2,
                'is_available' => true,
            ],
            [
                'phone' => '03310000021',
                'name' => 'Adnan Sami',
                'sector' => 'Domestic',
                'skill_category' => 'Driver',
                'district' => 'Peshawar',
                'experience_years' => 8,
                'is_available' => true,
            ],
            [
                'phone' => '03310000022',
                'name' => 'Bibi Fatima',
                'sector' => 'Domestic',
                'skill_category' => 'Nanny',
                'district' => 'Swabi',
                'experience_years' => 5,
                'is_available' => true,
            ],
            [
                'phone' => '03310000023',
                'name' => 'Sardar Ali',
                'sector' => 'Domestic',
                'skill_category' => 'Gardener',
                'district' => 'Abbottabad',
                'experience_years' => 6,
                'is_available' => true,
            ],
            [
                'phone' => '03310000024',
                'name' => 'Shehzad Roy',
                'sector' => 'Domestic',
                'skill_category' => 'Tailor',
                'district' => 'Mardan',
                'experience_years' => 10,
                'is_available' => true,
            ],
            [
                'phone' => '03310000025',
                'name' => 'Umer Gul',
                'sector' => 'Domestic',
                'skill_category' => 'Laundry Man',
                'district' => 'Peshawar',
                'experience_years' => 3,
                'is_available' => true,
            ],
            [
                'phone' => '03310000026',
                'name' => 'Zahid Shah',
                'sector' => 'Domestic',
                'skill_category' => 'Watchman',
                'district' => 'Charsadda',
                'experience_years' => 7,
                'is_available' => true,
            ],
            [
                'phone' => '03310000027',
                'name' => 'Junaid Jamshed',
                'sector' => 'Domestic',
                'skill_category' => 'Delivery Rider',
                'district' => 'Peshawar',
                'experience_years' => 2,
                'is_available' => true,
            ],
            [
                'phone' => '03310000028',
                'name' => 'Rahman Gul',
                'sector' => 'Domestic',
                'skill_category' => 'Sweeper',
                'district' => 'Nowshera',
                'experience_years' => 4,
                'is_available' => true,
            ],
        ];

        $idx = 0;
        foreach ($workers as $workerData) {
            $workerData['age'] = 20 + (($idx * 7) % 35); // Generates ages between 20 and 54
            Worker::create($workerData);
            $idx++;
        }

        // 5. Create Seed Job Posts
        $jobPosts = [
            [
                'employer_id' => $employer1->id,
                'title' => 'Urgent Road Welder Required',
                'trade' => 'Welder',
                'district' => 'Peshawar',
                'salary' => 45000,
                'duration' => '3 Months',
                'phone' => '03001111111',
                'description' => 'Need an experienced industrial welder for pipeline and road construction project. Transportation and accommodation will be provided.',
            ],
            [
                'employer_id' => $employer1->id,
                'title' => 'Factory Forklift Operator',
                'trade' => 'Forklift Operator',
                'district' => 'Haripur',
                'salary' => 35000,
                'duration' => '1 Year',
                'phone' => '03001111111',
                'description' => 'Looking for a certified forklift operator for shift-based work in a manufacturing unit at Haripur Industrial Estate.',
            ],
            [
                'employer_id' => $employer2->id,
                'title' => 'Domestic Cook for Swat Villa',
                'trade' => 'Cook',
                'district' => 'Swat',
                'salary' => 25000,
                'duration' => 'Permanent',
                'phone' => '03002222222',
                'description' => 'Experienced domestic cook needed for a family guest house in Swat. Must know local traditional cuisines.',
            ],
            [
                'employer_id' => $employer1->id,
                'title' => 'Warehouse Storekeeper',
                'trade' => 'Storekeeper',
                'district' => 'Mardan',
                'salary' => 30000,
                'duration' => '6 Months',
                'phone' => '03001111111',
                'description' => 'Storekeeper required for inventory management at our regional distribution center in Mardan. Basic computer knowledge is a plus.',
            ],
            [
                'employer_id' => $employer2->id,
                'title' => 'Mason for Construction Site',
                'trade' => 'Mason',
                'district' => 'Charsadda',
                'salary' => 40000,
                'duration' => '2 Months',
                'phone' => '03002222222',
                'description' => 'Skilled brick mason needed for immediate house renovation project in Charsadda. Daily wages or contract basis.',
            ],
            [
                'employer_id' => $employer1->id,
                'title' => 'Office Assistant / Receptionist',
                'trade' => 'Office Assistant',
                'district' => 'Peshawar',
                'salary' => 28000,
                'duration' => '1 Year',
                'phone' => '03001111111',
                'description' => 'Male/Female Office Assistant to handle files, incoming calls and support general office admin tasks.',
            ],
        ];

        foreach ($jobPosts as $jobPostData) {
            \App\Models\JobPost::create($jobPostData);
        }

        // 6. Seed App Settings
        $settings = [
            [
                'key' => 'allow_domestic_sector',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'registration',
                'description' => 'Allow domestic sector registration for workers and show in search filters.'
            ],
            [
                'key' => 'allow_worker_registration',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'registration',
                'description' => 'Temporarily close or open new worker registration.'
            ],
            [
                'key' => 'require_employer_approval',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'registration',
                'description' => 'Require administrator approval before a newly registered employer can reveal contacts.'
            ],
            [
                'key' => 'reveal_credit_cost',
                'value' => '1',
                'type' => 'integer',
                'group' => 'credits',
                'description' => 'Amount of credits required to unlock a worker\'s mobile number.'
            ],
            [
                'key' => 'default_welcome_credits',
                'value' => '5',
                'type' => 'integer',
                'group' => 'credits',
                'description' => 'Credits given automatically to newly registered employers/contractors.'
            ],
            [
                'key' => 'support_phone',
                'value' => '091-9210401',
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Helpline support contact number shown on pages.'
            ],
            [
                'key' => 'support_email',
                'value' => 'support.labor@kp.gov.pk',
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Official support email address.'
            ],
            [
                'key' => 'support_address',
                'value' => 'Directorate of Labor, Khyber Road, Peshawar, Khyber Pakhtunkhwa, Pakistan.',
                'type' => 'string',
                'group' => 'contact',
                'description' => 'Physical location and office address.'
            ],
            [
                'key' => 'credit_pricing_mode',
                'value' => 'flat',
                'type' => 'string',
                'group' => 'credits',
                'description' => 'Credit pricing mode: flat rate or tiered bulk pricing.'
            ],
            [
                'key' => 'credit_flat_rate',
                'value' => '20',
                'type' => 'integer',
                'group' => 'credits',
                'description' => 'Flat price per credit in PKR.'
            ],
            [
                'key' => 'credit_pricing_tiers',
                'value' => '[{"min":1,"price":20},{"min":10,"price":18},{"min":50,"price":15}]',
                'type' => 'string',
                'group' => 'credits',
                'description' => 'JSON representation of pricing tiers: minimum credits and unit price in PKR.'
            ],
            [
                'key' => 'site_name',
                'value' => 'KP Labor Matchmaker',
                'type' => 'string',
                'group' => 'general',
                'description' => 'The branding title of the web portal.'
            ],
            [
                'key' => 'enable_maintenance_banner',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'Display a global warning banner to users about upcoming maintenance.'
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'Scheduled system maintenance will occur this Sunday at 2:00 AM.',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Message to display inside the global maintenance banner.'
            ],
            [
                'key' => 'min_worker_age',
                'value' => '18',
                'type' => 'integer',
                'group' => 'validation',
                'description' => 'Minimum age required to register as a worker.'
            ],
            [
                'key' => 'max_worker_age',
                'value' => '60',
                'type' => 'integer',
                'group' => 'validation',
                'description' => 'Maximum age allowed to register as a worker.'
            ],
            [
                'key' => 'max_free_jobs',
                'value' => '5',
                'type' => 'integer',
                'group' => 'validation',
                'description' => 'Maximum number of active job posts an employer can publish.'
            ],
            [
                'key' => 'tax_rate',
                'value' => '0',
                'type' => 'integer',
                'group' => 'credits',
                'description' => 'Percentage tax/service charge to apply on credit purchases.'
            ],
            [
                'key' => 'currency_code',
                'value' => 'PKR',
                'type' => 'string',
                'group' => 'credits',
                'description' => 'Currency code for display and transaction records.'
            ],
            [
                'key' => 'site_short_name',
                'value' => 'KP-LM',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Abbreviated name or short title of the portal.'
            ],
            [
                'key' => 'site_description',
                'value' => 'Official Khyber Pakhtunkhwa Labor Matchmaking Platform. Connect directly with skilled workers across KP districts. No middleman, zero fees.',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Dynamic meta description and introduction text.'
            ],
            [
                'key' => 'site_keywords',
                'value' => 'Khyber Pakhtunkhwa, KP, Labor Matchmaking, Hire Workers, Welder, Cook, Maid, Pakistan, Job Portal',
                'type' => 'string',
                'group' => 'general',
                'description' => 'SEO keywords separated by commas.'
            ],
            [
                'key' => 'google_analytics_id',
                'value' => 'G-XXXXXXXXXX',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Google Analytics Tracking measurement ID.'
            ],
            [
                'key' => 'footer_copyright_text',
                'value' => 'Khyber Pakhtunkhwa Labor Matchmaking Platform. Confidential - Government of KP',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Copyright wording shown at bottom footer.'
            ],
            [
                'key' => 'social_facebook',
                'value' => 'https://facebook.com/kplabor',
                'type' => 'string',
                'group' => 'social',
                'description' => 'Facebook page link.'
            ],
            [
                'key' => 'social_twitter',
                'value' => 'https://twitter.com/kplabor',
                'type' => 'string',
                'group' => 'social',
                'description' => 'Twitter/X page link.'
            ],
            [
                'key' => 'social_linkedin',
                'value' => 'https://linkedin.com/company/kplabor',
                'type' => 'string',
                'group' => 'social',
                'description' => 'LinkedIn organization page link.'
            ],
            [
                'key' => 'logo_text',
                'value' => 'KP LABOR',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Text logo for the header navigation.'
            ],
            [
                'key' => 'items_per_page',
                'value' => '10',
                'type' => 'integer',
                'group' => 'validation',
                'description' => 'Standard listing items count per pagination page.'
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'general',
                'description' => 'Completely lock public access and display maintenance splash screen.'
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Karachi',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default system timezone for display and operations.'
            ],
            [
                'key' => 'social_links',
                'value' => '[{"platform":"Facebook","url":"https://facebook.com/kplabor","icon":"facebook"},{"platform":"Twitter","url":"https://twitter.com/kplabor","icon":"twitter"},{"platform":"LinkedIn","url":"https://linkedin.com/company/kplabor","icon":"linkedin"}]',
                'type' => 'string',
                'group' => 'social',
                'description' => 'JSON array of active social media links.'
            ],
            [
                'key' => 'payment_methods',
                'value' => '[{"name":"Bank","title":"Bank Wire Transfer"},{"name":"Online","title":"Credit / Debit Card"},{"name":"Crypto","title":"USDT / Crypto Wallet"},{"name":"EasyPaisa","title":"EasyPaisa Mobile Wallet"}]',
                'type' => 'string',
                'group' => 'credits',
                'description' => 'JSON array of active payment options for credit recharge.'
            ],
        ];

        foreach ($settings as $settingData) {
            \App\Models\Setting::create($settingData);
        }
    }
}

