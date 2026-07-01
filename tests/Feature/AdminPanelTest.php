<?php

use App\Models\User;
use App\Models\Worker;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Models\CreditTransaction;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Reset permissions and seed roles
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    
    if (!Role::where('name', 'admin')->exists()) {
        Role::create(['name' => 'admin']);
    }
    if (!Role::where('name', 'employer')->exists()) {
        Role::create(['name' => 'employer']);
    }
    if (!Role::where('name', 'contractor')->exists()) {
        Role::create(['name' => 'contractor']);
    }

    // Default settings values
    Setting::updateOrCreate(['key' => 'allow_domestic_sector'], ['value' => '1', 'type' => 'boolean', 'group' => 'registration', 'description' => 'Allow domestic sector']);
    Setting::updateOrCreate(['key' => 'allow_worker_registration'], ['value' => '1', 'type' => 'boolean', 'group' => 'registration', 'description' => 'Worker registration']);
    Setting::updateOrCreate(['key' => 'require_employer_approval'], ['value' => '1', 'type' => 'boolean', 'group' => 'registration', 'description' => 'Require approval']);
    Setting::updateOrCreate(['key' => 'reveal_credit_cost'], ['value' => '1', 'type' => 'integer', 'group' => 'credits', 'description' => 'Cost']);
    Setting::updateOrCreate(['key' => 'default_welcome_credits'], ['value' => '5', 'type' => 'integer', 'group' => 'credits', 'description' => 'Welcome credits']);
    Setting::updateOrCreate(['key' => 'support_phone'], ['value' => '091-9210401', 'type' => 'string', 'group' => 'contact', 'description' => 'Phone']);
    Setting::updateOrCreate(['key' => 'support_email'], ['value' => 'support@example.com', 'type' => 'string', 'group' => 'contact', 'description' => 'Email']);
    Setting::updateOrCreate(['key' => 'support_address'], ['value' => 'Peshawar', 'type' => 'string', 'group' => 'contact', 'description' => 'Address']);
});

it('denies access to guests and non-admin users for administrative components', function () {
    // Unauthenticated guest
    Livewire::test('admin.users')
        ->call('checkAdmin')
        ->assertRedirect('/');

    // Authenticated employer
    $employer = User::create([
        'name' => 'Employer User',
        'email' => 'employer@example.com',
        'phone' => '03001111111',
        'password' => bcrypt('password123'),
        'role' => 'employer',
        'is_approved' => true
    ]);
    $employer->assignRole('employer');

    $this->actingAs($employer);

    Livewire::test('admin.users')
        ->call('checkAdmin')
        ->assertRedirect('/');
});

it('permits access to authenticated administrator users', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin_test@example.com',
        'phone' => '03002222222',
        'password' => bcrypt('password123'),
        'role' => 'admin',
        'is_approved' => true
    ]);
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Livewire::test('admin.users')
        ->call('checkAdmin')
        ->assertHasNoErrors();
});

it('allows administrator to toggle employer approval status and records activity log', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin_test2@example.com',
        'phone' => '03003333333',
        'password' => bcrypt('password123'),
        'role' => 'admin',
        'is_approved' => true
    ]);
    $admin->assignRole('admin');

    $employer = User::create([
        'name' => 'Pending Employer',
        'email' => 'pending@example.com',
        'phone' => '03004444444',
        'password' => bcrypt('password123'),
        'role' => 'employer',
        'is_approved' => false
    ]);
    $employer->assignRole('employer');

    $this->actingAs($admin);

    Livewire::test('admin.users')
        ->call('toggleApproval', $employer->id)
        ->assertHasNoErrors();

    $this->assertTrue((bool)$employer->fresh()->is_approved);

    // Verify activity log entry
    $this->assertDatabaseHas('activity_logs', [
        'admin_id' => $admin->id,
        'action' => 'user_approval_toggled'
    ]);
});

it('allows updating app settings and invalidates cache', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin_test3@example.com',
        'phone' => '03005555555',
        'password' => bcrypt('password123'),
        'role' => 'admin',
        'is_approved' => true
    ]);
    $admin->assignRole('admin');

    $this->actingAs($admin);

    // Verify initial settings state
    $this->assertTrue(Setting::get('allow_domestic_sector'));

    Livewire::test('admin.settings')
        ->set('allow_domestic_sector', false)
        ->set('allow_worker_registration', true)
        ->set('require_employer_approval', false)
        ->set('reveal_credit_cost', 2)
        ->set('default_welcome_credits', 10)
        ->set('support_phone', '091-1112223')
        ->set('support_email', 'newsupport@kp.gov.pk')
        ->set('support_address', 'Phase 3, Hayatabad, Peshawar')
        ->call('saveSettings')
        ->assertHasNoErrors();

    // Verify database and cache updates
    $this->assertFalse(Setting::get('allow_domestic_sector'));
    $this->assertEquals(2, Setting::get('reveal_credit_cost'));
    $this->assertEquals(10, Setting::get('default_welcome_credits'));
    $this->assertEquals('091-1112223', Setting::get('support_phone'));
    $this->assertEquals('newsupport@kp.gov.pk', Setting::get('support_email'));
    $this->assertEquals('Phase 3, Hayatabad, Peshawar', Setting::get('support_address'));

    // Verify activity log entry
    $this->assertDatabaseHas('activity_logs', [
        'admin_id' => $admin->id,
        'action' => 'settings_updated'
    ]);
});

it('respects require_employer_approval and default_welcome_credits settings upon user registration', function () {
    // Disable employer approval requirement and set welcome credits to 10
    Setting::set('require_employer_approval', false);
    Setting::set('default_welcome_credits', 10);

    Livewire::test('login')
        ->set('activeTab', 'register')
        ->set('regAccountType', 'employer')
        ->set('regName', 'Dynamic Register Name')
        ->set('regEmail', 'dynamic_reg@example.com')
        ->set('regPhone', '03450001111')
        ->set('regFirmName', 'Peshawar Tech Labs')
        ->set('regFirmCity', 'Peshawar')
        ->set('regFirmAddress', 'Industrial Zone Hayatabad, Peshawar')
        ->set('regFirmSector', 'IT Services')
        ->set('regFirmDistrict', 'Peshawar')
        ->set('regPassword', 'password123')
        ->set('regPassword_confirmation', 'password123')
        ->call('register')
        ->assertHasNoErrors();

    $registeredUser = User::where('email', 'dynamic_reg@example.com')->first();
    $this->assertNotNull($registeredUser);
    $this->assertTrue((bool)$registeredUser->is_approved); // Auto-approved
    $this->assertEquals(10, $registeredUser->available_credits); // Dynamic credits
});

it('respects allow_worker_registration setting status', function () {
    // 1. Enable registration
    Setting::set('allow_worker_registration', true);

    Livewire::test('worker-intake')
        ->assertSeeHtml('wire:submit="processIntake"')
        ->assertDontSee(__('Worker Registration Closed'));

    // 2. Disable registration
    Setting::set('allow_worker_registration', false);

    Livewire::test('worker-intake')
        ->assertDontSeeHtml('wire:submit="processIntake"')
        ->assertSee(__('Worker Registration Closed'));
});

it('allows manual credit adjustments by administrators and logs credit transactions', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin_test4@example.com',
        'phone' => '03006666666',
        'password' => bcrypt('password123'),
        'role' => 'admin',
        'is_approved' => true
    ]);
    $admin->assignRole('admin');

    $employer = User::create([
        'name' => 'Normal Employer',
        'email' => 'employer_wallet@example.com',
        'phone' => '03007777777',
        'password' => bcrypt('password123'),
        'role' => 'employer',
        'is_approved' => true,
        'available_credits' => 5
    ]);
    $employer->assignRole('employer');

    $this->actingAs($admin);

    Livewire::test('admin.users')
        ->call('addCredits', $employer->id, 15)
        ->assertHasNoErrors();

    $this->assertEquals(20, $employer->fresh()->available_credits);

    // Verify credit transaction entry
    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $employer->id,
        'amount' => 15,
        'payment_method' => 'admin_manual',
        'status' => 'completed'
    ]);
});

it('allows managing roles and permissions', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'admin_test5@example.com',
        'phone' => '03008888888',
        'password' => bcrypt('password123'),
        'role' => 'admin',
        'is_approved' => true
    ]);
    $admin->assignRole('admin');

    $this->actingAs($admin);

    if (!Permission::where('name', 'manage-jobs')->exists()) {
        Permission::create(['name' => 'manage-jobs']);
    }

    Livewire::test('admin.roles')
        ->set('name', 'moderator')
        ->call('saveRole')
        ->assertHasNoErrors();

    $this->assertTrue(Role::where('name', 'moderator')->exists());

    // Assign permission to role
    $role = Role::where('name', 'moderator')->first();
    Livewire::test('admin.roles')
        ->call('editRole', $role->id)
        ->set('selectedPermissions', ['manage-jobs'])
        ->call('saveRole')
        ->assertHasNoErrors();

    $this->assertTrue($role->hasPermissionTo('manage-jobs'));
});

it('calculates credit prices correctly for flat, monotonic bulk, and cumulative pricing modes', function () {
    $setSetting = function (string $key, $value) {
        Setting::updateOrCreate(['key' => $key], ['value' => (string)$value, 'type' => 'string', 'group' => 'pricing']);
        \Illuminate\Support\Facades\Cache::forget("setting.{$key}");
    };

    // 1. Fixed / Flat Mode
    $setSetting('credit_pricing_mode', 'flat');
    $setSetting('credit_flat_rate', '20');
    $this->assertEquals(200, Setting::calculateCreditPrice(10));
    $this->assertEquals(400, Setting::calculateCreditPrice(20));

    // Define tiers for Bulk / Cumulative tests
    // min 20 at price 18, min 50 at price 15
    $tiers = [
        ['min' => 20, 'price' => 18],
        ['min' => 50, 'price' => 15]
    ];
    $setSetting('credit_pricing_tiers', json_encode($tiers));

    // 2. Bulk Mode (Monotonic Floor)
    $setSetting('credit_pricing_mode', 'tiered');
    
    // 5 credits (no tier reached, flat rate applies) -> 5 * 20 = 100
    $this->assertEquals(100, Setting::calculateCreditPrice(5));
    
    // 19 credits -> 19 * 20 = 380
    $this->assertEquals(380, Setting::calculateCreditPrice(19));
    
    // 20 credits -> raw is 20 * 18 = 360, but floor from 19 is 380 -> should be 380!
    $this->assertEquals(380, Setting::calculateCreditPrice(20));
    
    // 21 credits -> raw is 21 * 18 = 378, but floor from 19 is 380 -> should be 380!
    $this->assertEquals(380, Setting::calculateCreditPrice(21));
    
    // 22 credits -> raw is 22 * 18 = 396, but floor from 19 is 380 -> should be 396!
    $this->assertEquals(396, Setting::calculateCreditPrice(22));

    // 3. Cumulative Mode (Graduated Brackets)
    $setSetting('credit_pricing_mode', 'cumulative');
    
    // 5 credits -> 5 * 20 = 100
    $this->assertEquals(100, Setting::calculateCreditPrice(5));
    
    // 22 credits -> first 19 cost 20 each (380), remaining 3 cost 18 each (54) -> 380 + 54 = 434
    $this->assertEquals(434, Setting::calculateCreditPrice(22));
    
    // 60 credits -> first 19 cost 20 each (380), next 30 cost 18 each (540), remaining 11 cost 15 each (165) -> 380 + 540 + 165 = 1085
    $this->assertEquals(1085, Setting::calculateCreditPrice(60));
});
