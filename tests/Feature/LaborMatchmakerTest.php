<?php

use App\Models\User;
use App\Models\Worker;
use Livewire\Livewire;

it('asserts workers registration performs an upsert instead of creating duplicates', function () {
    Worker::create([
        'phone' => '03330000000',
        'name' => 'Original Name',
        'sector' => 'Domestic',
        'skill_category' => 'Cook',
        'district' => 'Peshawar',
        'experience_years' => 4,
        'is_available' => true
    ]);

    Livewire::test('worker-intake')
        ->set('phone', '03330000000')
        ->set('name', 'Updated Match Name')
        ->set('sector', 'Domestic')
        ->set('skill_category', 'Cook')
        ->set('district', 'Peshawar')
        ->set('experience_years', 5)
        ->call('processIntake')
        ->assertHasNoErrors();

    // Affirm count remains exactly 1
    $this->assertEquals(1, Worker::where('phone', '03330000000')->count());
    $this->assertEquals('Updated Match Name', Worker::where('phone', '03330000000')->first()->name);
});

it('triggers payment gateway simulation modal when available employer credit equals zero', function () {
    $employer = User::create([
        'name' => 'Broke Employer',
        'phone' => '03001234567',
        'role' => 'employer',
        'available_credits' => 0,
        'is_approved' => true
    ]);
    
    $worker = Worker::create([
        'phone' => '03159999999',
        'name' => 'Labor Worker',
        'sector' => 'Industrial',
        'skill_category' => 'Welder',
        'district' => 'Mardan',
        'experience_years' => 3
    ]);

    $this->actingAs($employer);

    Livewire::test('search-directory')
        ->call('revealContact', $worker->id)
        ->assertSet('showPaymentModal', true);
});

it('supports custom trade registration via other option', function () {
    Livewire::test('worker-intake')
        ->set('phone', '03450009999')
        ->set('name', 'Custom Worker')
        ->set('sector', 'Industrial')
        ->set('skill_category', 'Other')
        ->set('custom_trade', 'CNC Operator')
        ->set('district', 'Peshawar')
        ->set('experience_years', 3)
        ->call('processIntake')
        ->assertHasNoErrors();

    $this->assertEquals(1, Worker::where('phone', '03450009999')->count());
    $this->assertEquals('CNC Operator', Worker::where('phone', '03450009999')->first()->skill_category);
});

it('allows registered employers to post job openings and displays them on the jobs board', function () {
    $employer = User::create([
        'name' => 'Registered Employer',
        'email' => 'employer_test@example.com',
        'phone' => '03001234567',
        'role' => 'employer',
        'available_credits' => 5
    ]);

    $this->actingAs($employer);

    Livewire::test('jobs-board')
        ->set('post_title', 'Need Electrician for Hospital Project')
        ->set('post_trade', 'Electrician')
        ->set('post_district', 'Peshawar')
        ->set('post_salary', 35000)
        ->set('post_duration', 'Monthly')
        ->set('post_phone', '03009876543')
        ->set('post_description', 'Looking for an experienced electrician to work on a commercial hospital building project in Peshawar.')
        ->call('submitJob')
        ->assertHasNoErrors();

    // Verify it exists in the database
    $this->assertEquals(1, \App\Models\JobPost::where('title', 'Need Electrician for Hospital Project')->count());
    
    // Verify it appears on the jobs board queries
    Livewire::test('jobs-board')
        ->assertSee('Need Electrician for Hospital Project');
});

it('restricts credits routes to authenticated users and handles purchase flows', function () {
    // 1. Unauthenticated guests are redirected to login
    $this->get('/credits')->assertRedirect('/login');
    $this->get('/orders')->assertRedirect('/login');
    $this->get('/purchase')->assertRedirect('/login');

    // 2. Create authenticated employer
    $employer = User::create([
        'name' => 'Credits Employer',
        'email' => 'credits_employer@example.com',
        'phone' => '03331112222',
        'role' => 'employer',
        'available_credits' => 10,
        'is_approved' => true
    ]);
    
    $this->actingAs($employer);
    $this->get('/credits')->assertStatus(200);
    $this->get('/orders')->assertStatus(200);
    $this->get('/purchase')->assertStatus(200);

    // 3. Test credits-purchase component (Step 3: Confirm, Step 4: Submit Proof at same time)
    $comp = Livewire::test('credits-purchase')
        ->set('purchaseCredits', 20)
        ->set('paymentMethod', 'EasyPaisa')
        ->call('confirmPurchase')
        ->assertHasNoErrors()
        ->assertSet('purchaseStep', 4);

    $txId = $comp->get('createdOrderId');
    $this->assertNotNull($txId);

    // Verify transaction exists with payment_phone = null (confirmed but no details yet)
    $this->assertDatabaseHas('credit_transactions', [
        'id' => $txId,
        'user_id' => $employer->id,
        'amount' => 20,
        'payment_method' => 'EasyPaisa',
        'payment_phone' => null,
        'status' => 'pending'
    ]);

    // Submit details at the same time with a fake screenshot proof
    \Illuminate\Support\Facades\Storage::fake('public');
    $file1 = \Illuminate\Http\UploadedFile::fake()->image('proof1.png');

    $comp->set('screenshot', $file1)
        ->set('paymentPhone', '03331112222')
        ->call('submitProof')
        ->assertHasNoErrors();

    // Verify database has updated reference details and proof filename/path
    $this->assertDatabaseHas('credit_transactions', [
        'id' => $txId,
        'payment_phone' => '03331112222'
    ]);
    $txUpdated = \App\Models\CreditTransaction::find($txId);
    $this->assertNotNull($txUpdated->payment_proof);
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($txUpdated->payment_proof);

    // 4. Test credits-orders component (Submitting proof later)
    $tx2 = \App\Models\CreditTransaction::create([
        'user_id' => $employer->id,
        'amount' => 15,
        'price_pkr' => 300,
        'payment_method' => 'Bank',
        'payment_phone' => null,
        'status' => 'pending'
    ]);

    $file2 = \Illuminate\Http\UploadedFile::fake()->image('proof2.png');

    Livewire::test('credits-orders')
        ->call('startProofSubmission', $tx2->id)
        ->set('screenshot', $file2)
        ->set('paymentPhone', 'TxRef999')
        ->call('submitProof')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('credit_transactions', [
        'id' => $tx2->id,
        'payment_phone' => 'TxRef999'
    ]);
    $tx2Updated = \App\Models\CreditTransaction::find($tx2->id);
    $this->assertNotNull($tx2Updated->payment_proof);
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($tx2Updated->payment_proof);

    // 5. Test credits-ledger component
    Livewire::test('credits-ledger')
        ->assertSet('historyTab', 'purchased')
        ->call('setHistoryTab', 'used')
        ->assertSet('historyTab', 'used');
});

it('verifies admin.users component actions work correctly', function () {
    $admin = User::create([
        'name' => 'System Admin',
        'email' => 'admin_test_1@example.com',
        'phone' => '03339998888',
        'role' => 'admin',
        'is_approved' => true
    ]);
    
    $employer = User::create([
        'name' => 'Test Employer',
        'email' => 'employer_test_1@example.com',
        'phone' => '03339997777',
        'role' => 'employer',
        'available_credits' => 5,
        'is_approved' => false
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.users')
        // Test toggling approval
        ->call('toggleApproval', $employer->id)
        ->assertHasNoErrors();
        
    $employer->refresh();
    $this->assertTrue((bool)$employer->is_approved);

    Livewire::test('admin.users')
        // Test adding credits
        ->call('addCredits', $employer->id, 15)
        ->assertHasNoErrors();
        
    $employer->refresh();
    $this->assertEquals(20, $employer->available_credits);

    Livewire::test('admin.users')
        // Test setViewingUser
        ->call('setViewingUser', $employer->id)
        ->assertSet('viewingUserId', $employer->id)
        ->call('setViewingUser', null)
        ->assertSet('viewingUserId', null);

    Livewire::test('admin.users')
        // Test editUser load
        ->call('editUser', $employer->id)
        ->assertSet('editingUserId', $employer->id)
        ->assertSet('name', $employer->name)
        ->assertSet('email', $employer->email)
        ->assertSet('phone', $employer->phone)
        ->assertSet('role', $employer->role)
        // Test saveUser validation and update (needs company details since role is employer)
        ->set('company_name', 'Employer Inc')
        ->set('sector', 'Construction')
        ->set('district', 'Peshawar')
        ->set('city', 'Peshawar')
        ->set('address', 'Phase 3 Hayatabad')
        ->call('saveUser')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    $employer->refresh();
    $this->assertEquals('Employer Inc', $employer->profile->company_name);

    Livewire::test('admin.users')
        // Test deleteUser
        ->call('deleteUser', $employer->id)
        ->assertHasNoErrors();

    $this->assertNull(User::find($employer->id));
});

it('verifies admin.settings component tabs switch activeTab correctly', function () {
    $admin = User::create([
        'name' => 'System Admin',
        'email' => 'admin_settings@example.com',
        'phone' => '03339996666',
        'role' => 'admin',
        'is_approved' => true
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.settings')
        ->assertSet('activeTab', 'branding')
        ->call('setTab', 'pricing')
        ->assertSet('activeTab', 'pricing')
        ->call('setTab', 'payments')
        ->assertSet('activeTab', 'payments');
});

