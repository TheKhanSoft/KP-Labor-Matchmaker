<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\CreditTransaction;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public string $approvalFilter = '';

    // Create/Edit form fields
    public bool $showEditModal = false;
    public ?int $editingUserId = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $role = 'employer';
    public string $password = '';
    public string $password_confirmation = '';
    public int $available_credits = 5;
    public bool $is_approved = false;

    // Profile details
    public string $company_name = '';
    public string $company_email = '';
    public string $address = '';
    public string $sector = '';
    public string $alternate_phone = '';
    public string $district = 'Peshawar';
    public string $city = '';

    // Details drawer
    public ?int $viewingUserId = null;

    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($this->editingUserId ?: 'NULL'),
            'phone' => 'required|string|unique:users,phone,' . ($this->editingUserId ?: 'NULL'),
            'role' => 'required|string|in:admin,employer,contractor',
            'available_credits' => 'required|integer|min:0',
            'is_approved' => 'required|boolean',
        ];

        if (!$this->editingUserId) {
            $rules['password'] = 'required|string|min:6|confirmed';
        } else {
            $rules['password'] = 'nullable|string|min:6|confirmed';
        }

        if (in_array($this->role, ['employer', 'contractor'])) {
            $rules['company_name'] = 'required|string|max:255';
            $rules['sector'] = 'required|string';
            $rules['district'] = 'required|string';
            $rules['city'] = 'required|string';
            $rules['address'] = 'required|string';
            $rules['company_email'] = 'nullable|email';
            $rules['alternate_phone'] = 'nullable|string';
        }

        return $rules;
    }

    public function checkAdmin()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            session()->flash('error', 'Access Denied: Administrative privileges required.');
            return $this->redirect('/', navigate: true);
        }
    }

    public function logActivity(string $action, string $description): void
    {
        ActivityLog::create([
            'admin_id' => Auth::id(),
            'action' => $action,
            'description' => $description
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingApprovalFilter()
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->checkAdmin();
        $this->resetValidation();
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->role = 'employer';
        $this->password = '';
        $this->password_confirmation = '';
        $this->available_credits = 5;
        $this->is_approved = false;
        
        $this->company_name = '';
        $this->company_email = '';
        $this->address = '';
        $this->sector = '';
        $this->alternate_phone = '';
        $this->district = 'Peshawar';
        $this->city = '';

        $this->showEditModal = true;
    }

    public function editUser(int $id): void
    {
        $this->checkAdmin();
        $this->resetValidation();
        $this->editingUserId = $id;

        $user = User::findOrFail($id);
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->role = $user->role;
        $this->password = '';
        $this->password_confirmation = '';
        $this->available_credits = $user->available_credits;
        $this->is_approved = (bool)$user->is_approved;

        if ($user->profile) {
            $this->company_name = $user->profile->company_name ?: '';
            $this->company_email = $user->profile->company_email ?: '';
            $this->address = $user->profile->address ?: '';
            $this->sector = $user->profile->sector ?: '';
            $this->alternate_phone = $user->profile->alternate_phone ?: '';
            $this->district = $user->profile->district ?: 'Peshawar';
            $this->city = $user->profile->city ?: '';
        } else {
            $this->company_name = '';
            $this->company_email = '';
            $this->address = '';
            $this->sector = '';
            $this->alternate_phone = '';
            $this->district = 'Peshawar';
            $this->city = '';
        }

        $this->showEditModal = true;
    }

    public function saveUser(): void
    {
        $this->checkAdmin();
        $validatedData = $this->validate();

        if ($this->editingUserId) {
            // Update User
            $user = User::findOrFail($this->editingUserId);
            $user->name = $this->name;
            $user->email = $this->email;
            $user->phone = $this->phone;
            
            // Check if role changed, handle Spatie roles
            if ($user->role !== $this->role) {
                $user->syncRoles([$this->role]);
            }
            $user->role = $this->role;

            if ($this->password) {
                $user->password = bcrypt($this->password);
            }

            // Detect credit change to log manual adjustments
            $oldCredits = $user->available_credits;
            $user->available_credits = $this->available_credits;
            $user->is_approved = $this->is_approved;
            $user->save();

            if ($oldCredits !== $this->available_credits) {
                $diff = $this->available_credits - $oldCredits;
                CreditTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $diff,
                    'payment_method' => 'admin_manual',
                    'status' => 'completed'
                ]);
            }

            // Update/Create profile
            if (in_array($this->role, ['employer', 'contractor'])) {
                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'company_name' => $this->company_name,
                        'company_email' => $this->company_email,
                        'address' => $this->address,
                        'sector' => $this->sector,
                        'alternate_phone' => $this->alternate_phone,
                        'district' => $this->district,
                        'city' => $this->city,
                        'province' => 'Khyber Pakhtunkhwa',
                    ]
                );
            } else {
                // Delete profile if role changed to admin
                $user->profile()->delete();
            }

            $this->logActivity('user_updated', "Updated details for user {$user->name} (ID: {$user->id})");
            session()->flash('success', "User account {$user->name} updated successfully.");
        } else {
            // Create User
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => bcrypt($this->password),
                'role' => $this->role,
                'available_credits' => $this->available_credits,
                'is_approved' => $this->is_approved,
            ]);

            // Assign Spatie Role
            $user->assignRole($this->role);

            // Log Transaction
            if ($this->available_credits > 0) {
                CreditTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $this->available_credits,
                    'payment_method' => 'admin_manual',
                    'status' => 'completed'
                ]);
            }

            // Profile creation
            if (in_array($this->role, ['employer', 'contractor'])) {
                $user->profile()->create([
                    'company_name' => $this->company_name,
                    'company_email' => $this->company_email,
                    'address' => $this->address,
                    'sector' => $this->sector,
                    'alternate_phone' => $this->alternate_phone,
                    'district' => $this->district,
                    'city' => $this->city,
                    'province' => 'Khyber Pakhtunkhwa',
                ]);
            }

            $this->logActivity('user_created', "Created new user {$user->name} (ID: {$user->id})");
            session()->flash('success', "User account {$user->name} created successfully.");
        }

        $this->showEditModal = false;
    }

    public function toggleApproval(int $id): void
    {
        $this->checkAdmin();
        $user = User::findOrFail($id);
        if ($user->role !== 'admin') {
            $user->is_approved = !$user->is_approved;
            $user->save();
            $status = $user->is_approved ? 'Approved' : 'Suspended';
            
            $this->logActivity('user_approval_toggled', "Toggled approval for user {$user->name} (ID: {$user->id}) to {$status}");
            session()->flash('success', "Account status for {$user->name} changed to {$status}.");
        }
    }

    public function addCredits(int $id, int $amount): void
    {
        $this->checkAdmin();
        $user = User::findOrFail($id);
        $user->available_credits += $amount;
        $user->save();

        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_method' => 'admin_manual',
            'status' => 'completed'
        ]);

        $this->logActivity('credits_allocated', "Allocated +{$amount} credits to user {$user->name} (ID: {$user->id})");
        session()->flash('success', "Added {$amount} credits to {$user->name}.");
    }

    public function deleteUser(int $id): void
    {
        $this->checkAdmin();
        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            session()->flash('error', "You cannot delete your own administrative account.");
            return;
        }

        $name = $user->name;
        $user->delete(); // cascading handles profiles

        $this->logActivity('user_deleted', "Deleted user account {$name} (ID: {$id})");
        session()->flash('success', "User account {$name} deleted successfully.");
    }

    public function setViewingUser(?int $id): void
    {
        $this->viewingUserId = $id;
    }
};
?>

<div class="space-y-6 font-sans">
    <!-- Header Summary -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl shadow-lg relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-36 h-36 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-100 tracking-tight">Users Directory</h1>
            <p class="text-xs text-slate-400 font-medium">Manage system administrators, contractors, and firm organizations.</p>
        </div>
        <button type="button" wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 font-bold text-xs text-white shadow-lg shadow-indigo-600/20 active:scale-95 hover:scale-[1.02] transition-all cursor-pointer relative z-10">
            ➕ Add New User
        </button>
    </div>

    <!-- Filters Panel -->
    <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-4 rounded-3xl flex flex-col md:flex-row gap-4 items-center justify-between shadow-lg">
        <div class="w-full md:w-80 relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-xs">🔍</span>
            <input wire:model.live.debounce.250ms="search" type="text" placeholder="Search by name, phone, email, city..."
                   class="w-full pl-9 pr-4 py-2.5 bg-slate-950/60 border border-slate-800/80 rounded-2xl text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 transition-all font-medium" />
        </div>
        
        <div class="flex flex-wrap gap-3 w-full md:w-auto">
            <!-- Filter Role -->
            <select wire:model.live="roleFilter" 
                    class="px-3.5 py-2.5 bg-slate-950/60 border border-slate-800/80 rounded-2xl text-xs text-slate-350 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 transition-all font-semibold">
                <option value="">All Roles</option>
                <option value="admin">Administrators</option>
                <option value="employer">Firms / Companies</option>
                <option value="contractor">Contractors</option>
            </select>

            <!-- Filter Status -->
            <select wire:model.live="approvalFilter" 
                    class="px-3.5 py-2.5 bg-slate-950/60 border border-slate-800/80 rounded-2xl text-xs text-slate-350 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 transition-all font-semibold">
                <option value="">All Statuses</option>
                <option value="approved">Approved</option>
                <option value="pending">Pending Approval</option>
            </select>
        </div>
    </div>

    <!-- Datatable -->
    @php
        $query = User::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhereHas('profile', function ($pq) {
                      $pq->where('city', 'like', '%' . $this->search . '%')
                        ->orWhere('company_name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->roleFilter) {
            $query->where('role', $this->roleFilter);
        }

        if ($this->approvalFilter) {
            $isApprovedVal = $this->approvalFilter === 'approved' ? 1 : 0;
            $query->where('is_approved', $isApprovedVal);
        }

        $usersList = $query->orderBy('name')->paginate(10);
    @endphp

    <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 rounded-3xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-850/40 border-b border-slate-800/80 text-slate-400 uppercase font-extrabold tracking-wider">
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4">Contact Details</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Wallet Credits</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse ($usersList as $user)
                        <tr class="hover:bg-slate-850/30 dark:hover:bg-slate-900/20 transition-colors duration-150">
                            <!-- User Identity -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center font-extrabold text-indigo-600 dark:text-indigo-400 shadow-sm shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <button type="button" wire:click="setViewingUser({{ $user->id }})" class="font-extrabold text-slate-200 hover:text-indigo-400 hover:underline text-left cursor-pointer transition-colors text-xs">
                                            {{ $user->name }}
                                        </button>
                                        @if ($user->profile && $user->profile->company_name)
                                            <span class="block text-[10px] text-slate-500 font-semibold mt-0.5">{{ $user->profile->company_name }} ({{ $user->profile->city ?: 'No City' }})</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Role -->
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded-lg text-[9px] font-extrabold uppercase tracking-wider {{ $user->role === 'admin' ? 'bg-slate-800/80 border border-slate-700/80 text-slate-400' : ($user->role === 'employer' ? 'bg-indigo-500/10 border border-indigo-500/20 text-indigo-500 dark:text-indigo-400' : 'bg-violet-500/10 border border-violet-500/20 text-violet-500 dark:text-violet-400') }}">
                                    {{ $user->role === 'employer' ? 'Firm' : ($user->role === 'contractor' ? 'Contractor' : 'Admin') }}
                                </span>
                            </td>

                            <!-- Contact -->
                            <td class="px-6 py-4 font-bold text-slate-400">
                                <span class="block text-slate-200 font-mono select-all">{{ $user->phone }}</span>
                                <span class="block text-[10px] text-slate-500 mt-0.5 font-normal">{{ $user->email }}</span>
                            </td>

                            <!-- Approval Status -->
                            <td class="px-6 py-4 text-center">
                                @if ($user->role === 'admin')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">Approved</span>
                                @else
                                    <button type="button" wire:click="toggleApproval({{ $user->id }})"
                                             class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase transition-all cursor-pointer {{ $user->is_approved ? 'bg-indigo-500/10 text-indigo-550 dark:text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500/20' : 'bg-rose-500/10 text-rose-500 border border-rose-500/20 hover:bg-rose-500/20' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $user->is_approved ? 'bg-indigo-500 animate-pulse' : 'bg-rose-500' }}"></span>
                                        {{ $user->is_approved ? 'Approved' : 'Pending' }}
                                    </button>
                                @endif
                            </td>

                            <!-- Wallet Credits -->
                            <td class="px-6 py-4 text-center font-extrabold text-indigo-550 dark:text-indigo-400 text-sm font-mono select-all">
                                {{ $user->available_credits }}
                            </td>

                            <!-- Action Buttons -->
                            <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                @if ($user->role !== 'admin')
                                    <button type="button" wire:click="addCredits({{ $user->id }}, 10)" title="Add 10 Credits"
                                            class="px-2 py-1 rounded-lg bg-slate-850/80 border border-slate-800 text-[10px] font-bold text-slate-350 hover:text-indigo-400 hover:bg-slate-800 hover:border-slate-700 transition-all cursor-pointer">
                                        +10 Cr
                                    </button>
                                @endif
                                <button type="button" wire:click="editUser({{ $user->id }})" title="Edit Profile"
                                        class="p-1.5 rounded-lg bg-slate-850/80 border border-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition-all cursor-pointer inline-flex items-center">
                                    ⚙️
                                </button>
                                <button type="button" wire:click="deleteUser({{ $user->id }})" title="Delete Account"
                                        class="p-1.5 rounded-lg border border-slate-800 hover:border-rose-500/50 hover:bg-rose-500/10 text-slate-500 hover:text-rose-500 transition-all cursor-pointer inline-flex items-center">
                                    ✕
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-bold">No registered users matched the filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-800/80 bg-slate-850/20">
            {{ $usersList->links() }}
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" wire:click="$set('showEditModal', false)"></div>
            <div class="relative w-full max-w-2xl bg-slate-900 border border-slate-850 p-6 sm:p-8 rounded-3xl shadow-2xl overflow-y-auto max-h-[90vh] z-10">
                <h3 class="text-lg font-extrabold text-white mb-6">
                    {{ $editingUserId ? 'Edit User Attributes' : 'Create Administrative / Partner Account' }}
                </h3>

                <form wire:submit="saveUser" class="space-y-6">
                    <!-- Base Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">User Name</label>
                            <input wire:model="name" type="text" placeholder="Jan Ali" required
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                            @error('name') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Mobile Number</label>
                            <input wire:model="phone" type="text" placeholder="03001234567" required
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                            @error('phone') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Email Address</label>
                            <input wire:model="email" type="email" placeholder="example@kp.gov.pk" required
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                            @error('email') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Assign Role</label>
                            <select wire:model.live="role" 
                                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold">
                                <option value="admin">Administrator</option>
                                <option value="employer">Firm / Company</option>
                                <option value="contractor">Independent Contractor</option>
                            </select>
                            @error('role') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Passwords -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Password {{ $editingUserId ? '(Leave blank to keep current)' : '' }}</label>
                            <input wire:model="password" type="password" placeholder="••••••••" 
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                            @error('password') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Confirm Password</label>
                            <input wire:model="password_confirmation" type="password" placeholder="••••••••" 
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                        </div>
                    </div>

                    <!-- Wallet & Approval -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Wallet Credits</label>
                            <input wire:model="available_credits" type="number" required
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                            @error('available_credits') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex items-center pt-6">
                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" wire:model="is_approved" class="sr-only peer" />
                                <div class="w-9 h-5 bg-slate-950 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-450 after:border-slate-450 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-650 peer-checked:after:bg-white peer-checked:after:border-white"></div>
                                <span class="ml-3 text-xs font-bold text-slate-350">Approve User Status</span>
                            </label>
                        </div>
                    </div>

                    <!-- Extended Profile Details for Employer/Contractor -->
                    @if (in_array($this->role, ['employer', 'contractor']))
                        <div class="border-t border-slate-800/80 pt-6 space-y-4">
                            <h4 class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider">Company / Entity Profile</h4>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Company / Firm Name</label>
                                    <input wire:model="company_name" type="text" placeholder="KP Tech Solutions" 
                                           class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                                    @error('company_name') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Business Sector</label>
                                    <input wire:model="sector" type="text" placeholder="e.g. IT, Construction, Retail" 
                                           class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                                    @error('sector') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">City</label>
                                    <input wire:model="city" type="text" placeholder="Peshawar" 
                                           class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                                    @error('city') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Select KP District</label>
                                    <select wire:model="district" 
                                            class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold">
                                        <option value="Peshawar">Peshawar</option>
                                        <option value="Nowshera">Nowshera</option>
                                        <option value="Charsadda">Charsadda</option>
                                        <option value="Mardan">Mardan</option>
                                        <option value="Swabi">Swabi</option>
                                        <option value="Swat">Swat</option>
                                        <option value="Buner">Buner</option>
                                        <option value="Abbottabad">Abbottabad</option>
                                        <option value="Haripur">Haripur</option>
                                    </select>
                                    @error('district') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Company Email</label>
                                    <input wire:model="company_email" type="email" placeholder="hr@kptech.com" 
                                           class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                                    @error('company_email') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Alternate Landline / Phone</label>
                                    <input wire:model="alternate_phone" type="text" placeholder="0915840000" 
                                           class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                                    @error('alternate_phone') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Firm Address</label>
                                <textarea wire:model="address" rows="2" placeholder="Sector D, Phase 5 Hayatabad"
                                          class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold"></textarea>
                                @error('address') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-850">
                        <button type="button" wire:click="$set('showEditModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-slate-800 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-bold text-white shadow-md shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                            {{ $editingUserId ? 'Save Changes' : 'Create User' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Profile Details Slideover/Modal -->
    @if ($viewingUserId)
        @php
            $viewUser = User::find($viewingUserId);
        @endphp
        @if ($viewUser)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" wire:click="setViewingUser(null)"></div>
                <div class="relative w-full max-w-md bg-slate-900 border border-slate-850 p-6 rounded-3xl shadow-2xl z-10">
                    <div class="flex justify-between items-start mb-6">
                        <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider">User Details Info</h3>
                        <button type="button" wire:click="setViewingUser(null)" class="text-slate-400 hover:text-white font-bold cursor-pointer">✕</button>
                    </div>

                    <div class="space-y-4 text-xs font-medium">
                        <div class="flex items-center gap-3.5 pb-4 border-b border-slate-800/60">
                            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 text-lg font-bold shadow-inner">
                                👤
                            </div>
                            <div>
                                <h4 class="font-extrabold text-white text-sm leading-none">{{ $viewUser->name }}</h4>
                                <span class="text-[10px] text-slate-500 font-semibold capitalize mt-1 block">{{ $viewUser->role }} • ID: {{ $viewUser->id }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="block text-[9px] uppercase font-extrabold text-slate-500 tracking-wider">Contact Phone</span>
                                <span class="font-bold text-slate-200 mt-1 block font-mono select-all">{{ $viewUser->phone }}</span>
                            </div>
                            <div>
                                <span class="block text-[9px] uppercase font-extrabold text-slate-500 tracking-wider">Email Address</span>
                                <span class="font-bold text-slate-200 mt-1 block select-all">{{ $viewUser->email }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="block text-[9px] uppercase font-extrabold text-slate-500 tracking-wider">Approval Status</span>
                                <span class="inline-flex items-center gap-1.5 font-bold mt-1 text-[10px] {{ $viewUser->is_approved ? 'text-indigo-400' : 'text-rose-450' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $viewUser->is_approved ? 'bg-indigo-500' : 'bg-rose-500' }}"></span>
                                    {{ $viewUser->is_approved ? 'Approved' : 'Pending' }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-[9px] uppercase font-extrabold text-slate-500 tracking-wider">Wallet Balance</span>
                                <span class="font-extrabold text-indigo-450 dark:text-indigo-400 mt-1 block text-sm font-mono">{{ $viewUser->available_credits }} Credits</span>
                            </div>
                        </div>

                        @if ($viewUser->profile)
                            <div class="border-t border-slate-800/60 pt-4 space-y-3.5">
                                <h4 class="font-extrabold text-indigo-400 text-[10px] uppercase tracking-wider">Company Profile</h4>
                                
                                <div>
                                    <span class="block text-[9px] uppercase font-extrabold text-slate-500 tracking-wider">Company Name</span>
                                    <span class="font-extrabold text-slate-200 block mt-1">{{ $viewUser->profile->company_name }}</span>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-[9px] uppercase font-extrabold text-slate-500 tracking-wider">Business Sector</span>
                                        <span class="font-bold text-slate-200 block mt-1">{{ $viewUser->profile->sector }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-[9px] uppercase font-extrabold text-slate-500 tracking-wider">Location</span>
                                        <span class="font-bold text-slate-200 block mt-1">{{ $viewUser->profile->city ?: 'N/A' }}, {{ $viewUser->profile->district }}</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-[9px] uppercase font-extrabold text-slate-500 tracking-wider">Company Email</span>
                                        <span class="font-bold text-slate-200 block mt-1 select-all">{{ $viewUser->profile->company_email ?: 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-[9px] uppercase font-extrabold text-slate-500 tracking-wider">Alternate Landline</span>
                                        <span class="font-bold text-slate-200 block mt-1 select-all">{{ $viewUser->profile->alternate_phone ?: 'N/A' }}</span>
                                    </div>
                                </div>

                                <div>
                                    <span class="block text-[9px] uppercase font-extrabold text-slate-500 tracking-wider">Complete Address</span>
                                    <p class="text-slate-350 mt-1 leading-relaxed font-bold">{{ $viewUser->profile->address }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-850 flex justify-end">
                        <button type="button" wire:click="setViewingUser(null)"
                                class="px-4 py-2 bg-slate-950 border border-slate-800 text-[10px] font-bold text-slate-350 hover:text-white rounded-xl transition-all cursor-pointer">
                            Close Details
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
