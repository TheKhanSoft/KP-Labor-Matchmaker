<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Worker;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $sectorFilter = '';
    public string $availabilityFilter = '';

    // Create/Edit form fields
    public bool $showEditModal = false;
    public ?int $editingWorkerId = null;
    public string $name = '';
    public string $phone = '';
    public string $sector = 'Industrial';
    public string $skill_category = '';
    public string $custom_trade = '';
    public string $district = 'Peshawar';
    public int $experience_years = 0;
    public int $age = 25;
    public bool $is_available = true;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:workers,phone,' . ($this->editingWorkerId ?: 'NULL'),
            'sector' => 'required|string|in:Industrial,Domestic',
            'skill_category' => 'required|string',
            'custom_trade' => 'required_if:skill_category,Other|nullable|string|max:255',
            'district' => 'required|string',
            'experience_years' => 'required|integer|min:0|max:50',
            'age' => 'required|integer|min:15|max:100',
            'is_available' => 'required|boolean',
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSectorFilter()
    {
        $this->resetPage();
    }

    public function updatingAvailabilityFilter()
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->checkAdmin();
        $this->resetValidation();
        $this->editingWorkerId = null;
        $this->name = '';
        $this->phone = '';
        $this->sector = 'Industrial';
        $this->skill_category = '';
        $this->custom_trade = '';
        $this->district = 'Peshawar';
        $this->experience_years = 0;
        $this->age = 25;
        $this->is_available = true;

        $this->showEditModal = true;
    }

    public function editWorker(int $id): void
    {
        $this->checkAdmin();
        $this->resetValidation();
        $this->editingWorkerId = $id;

        $worker = Worker::findOrFail($id);
        $this->name = $worker->name;
        $this->phone = $worker->phone;
        $this->sector = $worker->sector;
        
        $standardTrades = [
            'Welder', 'Plumber', 'Electrician', 'Storekeeper', 'Store Incharge', 
            'Forklift Operator', 'Security Guard', 'Mason', 'Carpenter', 'Painter', 
            'HVAC Technician', 'Auto Mechanic', 'Steel Fixer', 'Scaffolder', 
            'Crane Operator', 'Pipe Fitter', 'Machinist', 'Boiler Operator', 
            'Quality Control Inspector', 'Office Assistant', 'Office Boy', 
            'Receptionist', 'Record Keeper', 'Dispatch Rider', 'Helper',
            'Cook', 'Maid', 'Data Entry Operator', 'Driver', 'Nanny', 'Gardener', 
            'Tailor', 'Laundry Man', 'Watchman', 'Delivery Rider', 'Sweeper'
        ];

        if (in_array($worker->skill_category, $standardTrades)) {
            $this->skill_category = $worker->skill_category;
            $this->custom_trade = '';
        } else {
            $this->skill_category = 'Other';
            $this->custom_trade = $worker->skill_category;
        }

        $this->district = $worker->district;
        $this->experience_years = $worker->experience_years;
        $this->age = $worker->age ?? 25;
        $this->is_available = (bool)$worker->is_available;

        $this->showEditModal = true;
    }

    public function saveWorker(): void
    {
        $this->checkAdmin();
        $validatedData = $this->validate();

        $resolvedSkill = $this->skill_category === 'Other' ? $this->custom_trade : $this->skill_category;

        if ($this->editingWorkerId) {
            $worker = Worker::findOrFail($this->editingWorkerId);
            $worker->name = $this->name;
            $worker->phone = $this->phone;
            $worker->sector = $this->sector;
            $worker->skill_category = $resolvedSkill;
            $worker->district = $this->district;
            $worker->experience_years = $this->experience_years;
            $worker->age = $this->age;
            $worker->is_available = $this->is_available;
            $worker->save();

            $this->logActivity('worker_updated', "Updated worker registry profile {$worker->name} (ID: {$worker->id})");
            session()->flash('success', "Worker profile {$worker->name} updated successfully.");
        } else {
            $worker = Worker::create([
                'name' => $this->name,
                'phone' => $this->phone,
                'sector' => $this->sector,
                'skill_category' => $resolvedSkill,
                'district' => $this->district,
                'experience_years' => $this->experience_years,
                'age' => $this->age,
                'is_available' => $this->is_available,
            ]);

            $this->logActivity('worker_created', "Created worker profile {$worker->name} (ID: {$worker->id})");
            session()->flash('success', "Worker profile {$worker->name} registered successfully.");
        }

        $this->showEditModal = false;
    }

    public function toggleAvailability(int $id): void
    {
        $this->checkAdmin();
        $worker = Worker::findOrFail($id);
        $worker->is_available = !$worker->is_available;
        $worker->save();
        $status = $worker->is_available ? 'Available' : 'Busy';

        $this->logActivity('worker_availability_toggled', "Toggled availability for worker {$worker->name} (ID: {$worker->id}) to {$status}");
        session()->flash('success', "Availability status for {$worker->name} updated to {$status}.");
    }

    public function deleteWorker(int $id): void
    {
        $this->checkAdmin();
        $worker = Worker::findOrFail($id);
        $name = $worker->name;
        $worker->delete();

        $this->logActivity('worker_deleted', "Deleted worker profile {$name} (ID: {$id})");
        session()->flash('success', "Worker profile for {$name} deleted successfully.");
    }
};
?>

<div class="space-y-6 font-sans">
    <!-- Header Summary -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl shadow-lg relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-36 h-36 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-100 tracking-tight">Workers Registry</h1>
            <p class="text-xs text-slate-400 font-medium">Manage local skilled labor directory, availability, and experience levels.</p>
        </div>
        <button type="button" wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 font-bold text-xs text-white shadow-lg shadow-indigo-600/20 active:scale-95 hover:scale-[1.02] transition-all cursor-pointer relative z-10">
            ➕ Register Worker
        </button>
    </div>

    <!-- Filters Panel -->
    <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-4 rounded-3xl flex flex-col md:flex-row gap-4 items-center justify-between shadow-lg">
        <div class="w-full md:w-80 relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-xs">🔍</span>
            <input wire:model.live.debounce.250ms="search" type="text" placeholder="Search by name, phone, trade, district..."
                   class="w-full pl-9 pr-4 py-2.5 bg-slate-950/60 border border-slate-800/80 rounded-2xl text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 transition-all font-medium" />
        </div>
        
        <div class="flex flex-wrap gap-3 w-full md:w-auto">
            <!-- Filter Sector -->
            <select wire:model.live="sectorFilter" 
                    class="px-3.5 py-2.5 bg-slate-950/60 border border-slate-800/80 rounded-2xl text-xs text-slate-350 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 transition-all font-semibold">
                <option value="">All Sectors</option>
                <option value="Industrial">Industrial</option>
                <option value="Domestic">Domestic</option>
            </select>

            <!-- Filter Availability -->
            <select wire:model.live="availabilityFilter" 
                    class="px-3.5 py-2.5 bg-slate-950/60 border border-slate-800/80 rounded-2xl text-xs text-slate-350 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 transition-all font-semibold">
                <option value="">All Availabilities</option>
                <option value="available">Available</option>
                <option value="busy">Busy / Engaged</option>
            </select>
        </div>
    </div>

    <!-- Datatable -->
    @php
        $query = Worker::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhere('skill_category', 'like', '%' . $this->search . '%')
                  ->orWhere('district', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->sectorFilter) {
            $query->where('sector', $this->sectorFilter);
        }

        if ($this->availabilityFilter) {
            $isAvailableVal = $this->availabilityFilter === 'available' ? 1 : 0;
            $query->where('is_available', $isAvailableVal);
        }

        $workersList = $query->orderBy('name')->paginate(10);
    @endphp

    <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 rounded-3xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-850/40 border-b border-slate-800/80 text-slate-400 uppercase font-extrabold tracking-wider">
                        <th class="px-6 py-4">Worker Info</th>
                        <th class="px-6 py-4">Sector / Trade</th>
                        <th class="px-6 py-4">Age</th>
                        <th class="px-6 py-4">District Location</th>
                        <th class="px-6 py-4">Experience</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse ($workersList as $worker)
                        <tr class="hover:bg-slate-850/30 dark:hover:bg-slate-900/20 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <span class="font-extrabold text-slate-200 block text-xs">{{ $worker->name }}</span>
                                <span class="text-[10px] text-slate-500 font-mono mt-0.5 font-bold select-all block">{{ $worker->phone }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-[9px] text-indigo-500 dark:text-indigo-400 font-extrabold uppercase">{{ $worker->skill_category }}</span>
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-800/80 border border-slate-700/80 text-[9px] text-slate-400 font-extrabold uppercase">{{ $worker->sector }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-350 font-bold">{{ $worker->age ?? 'N/A' }} Yrs</td>
                            <td class="px-6 py-4 text-slate-350 font-bold">{{ $worker->district }}</td>
                            <td class="px-6 py-4 text-slate-400 font-bold">{{ $worker->experience_years }} Years</td>
                            <td class="px-6 py-4 text-center">
                                <button type="button" wire:click="toggleAvailability({{ $worker->id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase transition-all cursor-pointer {{ $worker->is_available ? 'bg-indigo-500/10 text-indigo-550 dark:text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500/20' : 'bg-rose-500/10 text-rose-500 border border-rose-500/20 hover:bg-rose-500/20' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $worker->is_available ? 'bg-indigo-500 animate-pulse' : 'bg-rose-500' }}"></span>
                                    {{ $worker->is_available ? 'Available' : 'Busy' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                <button type="button" wire:click="editWorker({{ $worker->id }})" title="Edit Details"
                                        class="p-1.5 rounded-lg bg-slate-850/80 border border-slate-800 text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition-colors cursor-pointer inline-flex items-center">
                                    ⚙️
                                </button>
                                <button type="button" wire:click="deleteWorker({{ $worker->id }})" title="Delete Profile"
                                        class="p-1.5 rounded-lg border border-slate-800 hover:border-rose-500/50 hover:bg-rose-500/10 text-slate-500 hover:text-rose-500 transition-colors cursor-pointer inline-flex items-center">
                                    ✕
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 font-bold">No registered workers found matching search criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-800/80 bg-slate-850/20">
            {{ $workersList->links() }}
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" wire:click="$set('showEditModal', false)"></div>
            <div class="relative w-full max-w-lg bg-slate-900 border border-slate-850 p-6 sm:p-8 rounded-3xl shadow-2xl overflow-y-auto max-h-[90vh] z-10">
                <h3 class="text-lg font-extrabold text-white mb-6">
                    {{ $editingWorkerId ? 'Edit Worker Profile' : 'Register New Local Worker' }}
                </h3>

                <form wire:submit="saveWorker" class="space-y-6">
                    <!-- Base Details -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Worker Name</label>
                            <input wire:model="name" type="text" placeholder="Jan Gul" required
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                            @error('name') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Mobile Number</label>
                            <input wire:model="phone" type="text" placeholder="03330000000" required
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                            @error('phone') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Choose Sector</label>
                            <select wire:model.live="sector" 
                                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold">
                                <option value="Industrial">Industrial Sector</option>
                                <option value="Domestic">Domestic Sector</option>
                            </select>
                            @error('sector') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Years of Experience</label>
                            <input wire:model="experience_years" type="number" required
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                            @error('experience_years') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Age (Years)</label>
                            <input wire:model="age" type="number" required
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                            @error('age') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Trade and District -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Select Primary Trade</label>
                            <select wire:model.live="skill_category" 
                                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold">
                                <option value="">-- Select Trade --</option>
                                @php
                                    $standardTrades = [
                                        'Welder', 'Plumber', 'Electrician', 'Storekeeper', 'Store Incharge', 
                                        'Forklift Operator', 'Security Guard', 'Mason', 'Carpenter', 'Painter', 
                                        'HVAC Technician', 'Auto Mechanic', 'Steel Fixer', 'Scaffolder', 
                                        'Crane Operator', 'Pipe Fitter', 'Machinist', 'Boiler Operator', 
                                        'Quality Control Inspector', 'Office Assistant', 'Office Boy', 
                                        'Receptionist', 'Record Keeper', 'Dispatch Rider', 'Helper',
                                        'Cook', 'Maid', 'Data Entry Operator', 'Driver', 'Nanny', 'Gardener', 
                                        'Tailor', 'Laundry Man', 'Watchman', 'Delivery Rider', 'Sweeper'
                                    ];
                                    sort($standardTrades);
                                @endphp
                                @foreach ($standardTrades as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                                <option value="Other">Other (Custom Trade...)</option>
                            </select>
                            @error('skill_category') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">KP Region District</label>
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

                    <!-- Custom Trade (Conditional) -->
                    @if ($skill_category === 'Other')
                        <div class="animate-fadeIn">
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Specify Custom Trade Name</label>
                            <input wire:model="custom_trade" type="text" placeholder="CNC Lathe Operator" required
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                            @error('custom_trade') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- Availability Switch -->
                    <div>
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" wire:model="is_available" class="sr-only peer" />
                            <div class="w-9 h-5 bg-slate-950 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-450 after:border-slate-450 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-650 peer-checked:after:bg-white peer-checked:after:border-white"></div>
                            <span class="ml-3 text-xs font-bold text-slate-350">Available immediately for job offers</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-850">
                        <button type="button" wire:click="$set('showEditModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-slate-800 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-bold text-white shadow-md shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                            {{ $editingWorkerId ? 'Save Changes' : 'Register Worker' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
