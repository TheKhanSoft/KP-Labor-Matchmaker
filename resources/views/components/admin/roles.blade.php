<?php
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public array $roles = [];
    public array $permissions = [];

    public bool $showEditModal = false;
    public ?int $editingRoleId = null;
    public string $name = '';
    public array $selectedPermissions = [];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:roles,name,' . ($this->editingRoleId ?: 'NULL'),
            'selectedPermissions' => 'array',
        ];
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

    public function mount()
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->checkAdmin();
        $this->roles = Role::orderBy('name')->get()->toArray();
        $this->permissions = Permission::orderBy('name')->get()->toArray();
    }

    public function openCreateModal(): void
    {
        $this->checkAdmin();
        $this->resetValidation();
        $this->editingRoleId = null;
        $this->name = '';
        $this->selectedPermissions = [];

        $this->showEditModal = true;
    }

    public function editRole(int $id): void
    {
        $this->checkAdmin();
        $this->resetValidation();
        $this->editingRoleId = $id;

        $roleObj = Role::findOrFail($id);
        $this->name = $roleObj->name;
        $this->selectedPermissions = $roleObj->permissions->pluck('name')->toArray();

        $this->showEditModal = true;
    }

    public function saveRole(): void
    {
        $this->checkAdmin();
        $this->validate();

        if ($this->editingRoleId) {
            $roleObj = Role::findOrFail($this->editingRoleId);
            
            // Protect core system roles from name changes
            $coreRoles = ['admin', 'employer', 'contractor'];
            if (in_array($roleObj->name, $coreRoles) && $roleObj->name !== $this->name) {
                session()->flash('error', 'Core system role names cannot be modified.');
                return;
            }

            $roleObj->name = $this->name;
            $roleObj->save();

            // Sync permissions
            $roleObj->syncPermissions($this->selectedPermissions);

            $this->logActivity('role_updated', "Updated Spatie role {$roleObj->name} permissions");
            session()->flash('success', "Spatie Access Control Role {$roleObj->name} updated successfully.");
        } else {
            $roleObj = Role::create(['name' => $this->name]);
            $roleObj->syncPermissions($this->selectedPermissions);

            $this->logActivity('role_created', "Created Spatie role {$roleObj->name}");
            session()->flash('success', "Spatie Access Control Role {$roleObj->name} created successfully.");
        }

        $this->loadData();
        $this->showEditModal = false;
    }

    public function deleteRole(int $id): void
    {
        $this->checkAdmin();
        $roleObj = Role::findOrFail($id);
        
        $coreRoles = ['admin', 'employer', 'contractor'];
        if (in_array($roleObj->name, $coreRoles)) {
            session()->flash('error', "Core system role '{$roleObj->name}' is protected and cannot be deleted.");
            return;
        }

        $name = $roleObj->name;
        $roleObj->delete();

        $this->logActivity('role_deleted', "Deleted custom Spatie role {$name} (ID: {$id})");
        session()->flash('success', "Custom Access Control Role '{$name}' deleted successfully.");
        $this->loadData();
    }
};
?>

<div class="space-y-6 font-sans">
    <!-- Header Summary -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl shadow-lg relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-36 h-36 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-100 tracking-tight">Spatie Access Roles</h1>
            <p class="text-xs text-slate-400 font-medium">Configure role-based access control and associate permissions.</p>
        </div>
        <button type="button" wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 font-bold text-xs text-white shadow-lg shadow-indigo-600/20 active:scale-95 hover:scale-[1.02] transition-all cursor-pointer relative z-10">
            ➕ Add Custom Role
        </button>
    </div>

    <!-- Alert messages inside wrapper -->
    @if (session()->has('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-500 font-bold text-center text-xs">
            {{ session('error') }}
        </div>
    @endif
    @if (session()->has('success'))
        <div class="p-4 rounded-2xl bg-emerald-550/10 border border-emerald-550/20 text-emerald-500 font-bold text-center text-xs">
            {{ session('success') }}
        </div>
    @endif

    <!-- Roles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($roles as $r)
            @php
                $roleModel = Role::findById($r['id']);
                $rolePerms = $roleModel->permissions->pluck('name')->toArray();
                $isCore = in_array($r['name'], ['admin', 'employer', 'contractor']);
            @endphp
            <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl flex flex-col justify-between shadow-lg hover:shadow-xl hover:-translate-y-1 hover:border-slate-700/80 transition-all duration-300 group relative overflow-hidden">
                <!-- Background Glow -->
                <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-500/5 rounded-full blur-xl group-hover:bg-indigo-500/10 transition-all"></div>
                
                <div class="space-y-4 relative z-10">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-100 capitalize flex items-center gap-2">
                                🔑 {{ $r['name'] }}
                            </h3>
                            <span class="text-[9px] font-extrabold text-slate-500 mt-1 block uppercase tracking-wider">
                                {{ $isCore ? 'Protected Core Role' : 'Custom Configured Role' }}
                            </span>
                        </div>
                        
                        @if (!$isCore)
                            <button type="button" wire:click="deleteRole({{ $r['id'] }})"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-rose-500 bg-slate-850/60 hover:bg-rose-500/10 border border-slate-800 transition-all cursor-pointer">
                                ✕
                             </button>
                        @endif
                    </div>

                    <!-- Associated Permissions -->
                    <div class="space-y-2">
                        <span class="text-[9px] uppercase font-extrabold text-slate-500 tracking-wider block">Assigned Permissions</span>
                        <div class="flex flex-wrap gap-1.5 pt-0.5">
                            @forelse ($rolePerms as $pName)
                                <span class="px-2 py-0.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-[9px] text-indigo-500 dark:text-indigo-400 font-extrabold uppercase">{{ $pName }}</span>
                            @empty
                                <span class="text-slate-500 text-[10px] font-bold italic">No permissions assigned yet.</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="pt-5 border-t border-slate-850/60 mt-5 flex justify-end relative z-10">
                    <button type="button" wire:click="editRole({{ $r['id'] }})"
                            class="px-3.5 py-2 rounded-xl bg-slate-850/85 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 text-[10px] font-bold text-slate-350 hover:text-slate-100 transition-all cursor-pointer">
                        Edit Permissions
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Edit Permissions Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" wire:click="$set('showEditModal', false)"></div>
            <div class="relative w-full max-w-xl bg-slate-900 border border-slate-850 p-6 sm:p-8 rounded-3xl shadow-2xl overflow-y-auto max-h-[90vh] z-10">
                <h3 class="text-lg font-extrabold text-white mb-6">
                    {{ $editingRoleId ? 'Configure Access Control Permissions' : 'Create Custom Security Role' }}
                </h3>

                <form wire:submit="saveRole" class="space-y-6">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Role Name</label>
                        <input wire:model="name" type="text" placeholder="e.g. support_staff" required
                               {{ in_array($name, ['admin', 'employer', 'contractor']) ? 'disabled' : '' }}
                               class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold disabled:opacity-50" />
                        @error('name') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Permissions Selection -->
                    <div class="space-y-3">
                        <label class="block text-[10px] uppercase font-bold text-slate-400">Associate Permissions</label>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-60 overflow-y-auto p-2 bg-slate-950/40 rounded-2xl border border-slate-850">
                            @forelse ($permissions as $p)
                                <label class="flex items-center gap-3 px-3 py-2.5 bg-slate-950/80 rounded-xl border border-slate-850 cursor-pointer select-none hover:border-slate-700 transition-colors">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $p['name'] }}"
                                           class="rounded border-slate-800 text-indigo-650 focus:ring-indigo-550 bg-slate-950 focus:ring-offset-slate-900" />
                                    <span class="text-xs font-bold text-slate-350 font-mono">{{ $p['name'] }}</span>
                                </label>
                            @empty
                                <div class="col-span-2 text-center py-6 text-xs text-slate-600 font-bold">
                                    No Spatie permission keys exist yet.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-850">
                        <button type="button" wire:click="$set('showEditModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-slate-800 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-bold text-white shadow-md shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                            Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
