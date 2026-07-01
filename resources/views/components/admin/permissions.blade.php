<?php
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public array $permissions = [];

    public bool $showCreateModal = false;
    public string $name = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:permissions,name',
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
        $this->permissions = Permission::orderBy('name')->get()->toArray();
    }

    public function openCreateModal(): void
    {
        $this->checkAdmin();
        $this->resetValidation();
        $this->name = '';
        $this->showCreateModal = true;
    }

    public function savePermission(): void
    {
        $this->checkAdmin();
        $this->validate();

        $permission = Permission::create(['name' => $this->name]);

        $this->logActivity('permission_created', "Created Spatie permission key: {$this->name}");
        session()->flash('success', "Spatie Permission '{$this->name}' created successfully.");

        $this->loadData();
        $this->showCreateModal = false;
    }

    public function deletePermission(int $id): void
    {
        $this->checkAdmin();
        $permission = Permission::findOrFail($id);
        $name = $permission->name;
        $permission->delete();

        $this->logActivity('permission_deleted', "Deleted Spatie permission key: {$name} (ID: {$id})");
        session()->flash('success', "Spatie Permission '{$name}' deleted successfully.");
        $this->loadData();
    }
};
?>

<div class="space-y-6 font-sans">
    <!-- Header Summary -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl shadow-lg relative overflow-hidden animate-fadeIn">
        <div class="absolute -right-16 -top-16 w-36 h-36 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-100 tracking-tight">Spatie Permissions</h1>
            <p class="text-xs text-slate-400 font-medium">Manage granular system access levels for gate checks and authorization checks.</p>
        </div>
        <button type="button" wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 font-bold text-xs text-white shadow-lg shadow-indigo-600/20 active:scale-95 hover:scale-[1.02] transition-all cursor-pointer relative z-10">
            ➕ Create Permission
        </button>
    </div>

    <!-- Alert messages inside wrapper -->
    @if (session()->has('error'))
        <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-500 font-bold text-center text-xs">
            {{ session('error') }}
        </div>
    @endif
    @if (session()->has('success'))
        <div class="p-4 rounded-2xl bg-emerald-550/10 border border-emerald-550/20 text-emerald-555 text-center text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Permissions List Table -->
    <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 rounded-3xl overflow-hidden shadow-lg max-w-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-850/40 border-b border-slate-800/80 text-slate-400 uppercase font-extrabold tracking-wider">
                        <th class="px-6 py-4">Permission Key Name</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse ($permissions as $p)
                        <tr class="hover:bg-slate-850/30 dark:hover:bg-slate-900/20 transition-colors duration-150">
                            <td class="px-6 py-4 font-extrabold font-mono text-indigo-550 dark:text-indigo-400 select-all text-xs">{{ $p['name'] }}</td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" wire:click="deletePermission({{ $p['id'] }})"
                                        class="p-1.5 rounded-lg border border-slate-800 hover:border-rose-500/50 hover:bg-rose-500/10 text-slate-500 hover:text-rose-500 transition-colors cursor-pointer inline-flex items-center">
                                    ✕
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-6 py-8 text-center text-slate-500 font-bold">No Spatie permission keys exist yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Permission Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative w-full max-w-md bg-slate-900 border border-slate-850 p-6 rounded-3xl shadow-2xl z-10">
                <h3 class="text-sm font-extrabold text-white uppercase tracking-wider mb-6">Create Spatie Permission Key</h3>

                <form wire:submit="savePermission" class="space-y-6">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5 font-sans">Permission Key Name</label>
                        <input wire:model="name" type="text" placeholder="e.g. view_reports" required
                               class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold font-mono" />
                        @error('name') <span class="text-[10px] text-rose-500 mt-1 block font-sans">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-850">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-slate-800 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-bold text-white shadow-md shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                            Save Key
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
