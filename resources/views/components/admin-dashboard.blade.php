<?php
use Livewire\Component;
use App\Models\Worker;
use App\Models\User;
use App\Models\CreditLock;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public string $activeTab = 'workers'; // 'workers' or 'employers'
    public string $workerSearch = '';
    public string $employerSearch = '';

    public function checkAdmin()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            session()->flash('error', 'Access Denied: Administrative privileges required.');
            return $this->redirect('/', navigate: true);
        }
    }

    public function mount()
    {
        $this->checkAdmin();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function toggleWorkerAvailability(int $id): void
    {
        $this->checkAdmin();
        $worker = Worker::find($id);
        if ($worker) {
            $worker->is_available = !$worker->is_available;
            $worker->save();
            session()->flash('success', "Availability for {$worker->name} updated successfully.");
        }
    }

    public function deleteWorker(int $id): void
    {
        $this->checkAdmin();
        $worker = Worker::find($id);
        if ($worker) {
            $name = $worker->name;
            $worker->delete();
            session()->flash('success', "Worker profile for {$name} was deleted.");
        }
    }

    public function addCredits(int $userId, int $amount): void
    {
        $this->checkAdmin();
        $user = User::find($userId);
        if ($user) {
            $user->available_credits += $amount;
            $user->save();
            session()->flash('success', "Added {$amount} credits to {$user->name}.");
        }
    }

    public function resetCredits(int $userId): void
    {
        $this->checkAdmin();
        $user = User::find($userId);
        if ($user) {
            $user->available_credits = 5;
            $user->save();
            session()->flash('success', "Credits for {$user->name} reset to default (5).");
        }
    }

    public function toggleEmployerApproval(int $userId): void
    {
        $this->checkAdmin();
        $user = User::find($userId);
        if ($user && ($user->role === 'employer' || $user->role === 'contractor')) {
            $user->is_approved = !$user->is_approved;
            $user->save();
            $status = $user->is_approved ? 'approved' : 'suspended/pending';
            session()->flash('success', "Account status for {$user->name} updated to {$status}.");
        }
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white font-display">
                Administrative Console
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                Monitor system activity, manage workers registry, and allocate employer credits.
            </p>
        </div>
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-bold bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 uppercase tracking-widest">
            Admin Mode
        </div>
    </div>

    <!-- Alert Banners -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 font-semibold text-center flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Key Metrics Cards -->
    @php
        $totalWorkers = Worker::count();
        $availableWorkers = Worker::where('is_available', true)->count();
        $totalEmployers = User::where('role', 'employer')->count();
        $totalUnlocks = CreditLock::count();
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Metric 1 -->
        <div class="bg-slate-900/40 border border-slate-800 p-5 rounded-2xl backdrop-blur-md">
            <span class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest">Total Workers</span>
            <span class="block text-2xl sm:text-3xl font-black text-white mt-1">{{ $totalWorkers }}</span>
            <span class="block text-[10px] text-slate-400 mt-1">{{ $availableWorkers }} Active / Available</span>
        </div>

        <!-- Metric 2 -->
        <div class="bg-slate-900/40 border border-slate-800 p-5 rounded-2xl backdrop-blur-md">
            <span class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest">Total Employers</span>
            <span class="block text-2xl sm:text-3xl font-black text-white mt-1">{{ $totalEmployers }}</span>
            <span class="block text-[10px] text-slate-400 mt-1">Registered Orgs</span>
        </div>

        <!-- Metric 3 -->
        <div class="bg-slate-900/40 border border-slate-800 p-5 rounded-2xl backdrop-blur-md">
            <span class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest">Total Reveals</span>
            <span class="block text-2xl sm:text-3xl font-black text-white mt-1">{{ $totalUnlocks }}</span>
            <span class="block text-[10px] text-slate-400 mt-1">Contact Connections</span>
        </div>

        <!-- Metric 4 -->
        <div class="bg-slate-900/40 border border-slate-800 p-5 rounded-2xl backdrop-blur-md">
            <span class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest">Database Health</span>
            <span class="block text-2xl sm:text-3xl font-black text-indigo-400 mt-1">ONLINE</span>
            <span class="block text-[10px] text-slate-400 mt-1">MySQL InnoDB</span>
        </div>
    </div>

    <!-- Management Section -->
    <div class="bg-slate-900/40 border border-slate-800 rounded-3xl backdrop-blur-md overflow-hidden shadow-2xl">
        <!-- Tabs Selector -->
        <div class="border-b border-slate-800/80 bg-slate-950/30 flex items-center justify-between px-6 py-4 flex-wrap gap-4">
            <div class="flex gap-2">
                <button type="button" wire:click="setTab('workers')" id="tab-workers"
                        class="px-4 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer {{ $activeTab === 'workers' ? 'bg-indigo-500/10 border-indigo-500/50 text-indigo-400' : 'bg-transparent border-transparent text-slate-400 hover:text-white' }}">
                    Manage Workers Registry
                </button>
                <button type="button" wire:click="setTab('employers')" id="tab-employers"
                        class="px-4 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer {{ $activeTab === 'employers' ? 'bg-indigo-500/10 border-indigo-500/50 text-indigo-400' : 'bg-transparent border-transparent text-slate-400 hover:text-white' }}">
                    Manage Employers / Credits
                </button>
            </div>
            
            <!-- Search inputs based on active tab -->
            <div>
                @if ($activeTab === 'workers')
                    <input wire:model.live.debounce.250ms="workerSearch" id="input-worker-search" type="text" placeholder="Search by name..." 
                           class="px-4 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 placeholder-slate-600 w-48 sm:w-60" />
                @else
                    <input wire:model.live.debounce.250ms="employerSearch" id="input-employer-search" type="text" placeholder="Search by name/phone..." 
                           class="px-4 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 placeholder-slate-600 w-48 sm:w-60" />
                @endif
            </div>
        </div>

        <!-- Tab 1: Workers Panel -->
        @if ($activeTab === 'workers')
            @php
                $wQuery = Worker::query();
                if ($workerSearch) {
                    $wQuery->where('name', 'like', '%' . $workerSearch . '%')
                           ->orWhere('phone', 'like', '%' . $workerSearch . '%')
                           ->orWhere('skill_category', 'like', '%' . $workerSearch . '%');
                }
                $workersList = $wQuery->orderBy('name')->paginate(15);
            @endphp
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950/20 border-b border-slate-800/60 text-slate-500 uppercase font-bold tracking-wider">
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4">Phone</th>
                            <th class="px-6 py-4">Sector / Trade</th>
                            <th class="px-6 py-4">KP District</th>
                            <th class="px-6 py-4">Experience</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @forelse ($workersList as $worker)
                            <tr class="hover:bg-slate-900/10 transition-colors">
                                <td class="px-6 py-4 font-bold text-white">{{ $worker->name }}</td>
                                <td class="px-6 py-4 text-slate-300 font-medium select-all">{{ $worker->phone }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded bg-slate-950 border border-slate-800 text-[10px] text-indigo-400 font-bold uppercase">{{ $worker->skill_category }}</span>
                                        <span class="px-2 py-0.5 rounded bg-slate-950 border border-slate-800 text-[10px] text-slate-400 font-bold uppercase">{{ $worker->sector }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-300">{{ $worker->district }}</td>
                                <td class="px-6 py-4 text-slate-300">{{ $worker->experience_years }} Years</td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button" wire:click="toggleWorkerAvailability({{ $worker->id }})" id="btn-toggle-availability-{{ $worker->id }}"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase transition-all cursor-pointer {{ $worker->is_available ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                                        {{ $worker->is_available ? 'Available' : 'Busy' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" wire:click="deleteWorker({{ $worker->id }})" id="btn-delete-worker-{{ $worker->id }}"
                                            class="inline-flex items-center justify-center p-1.5 rounded-lg border border-slate-800 hover:border-rose-500/50 hover:bg-rose-500/10 text-slate-400 hover:text-rose-400 transition-colors cursor-pointer">
                                        <!-- Trash Icon -->
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500 font-bold">No registered workers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <!-- Tab 2: Employers Panel -->
            @php
                $eQuery = User::whereIn('role', ['employer', 'contractor']);
                if ($employerSearch) {
                    $eQuery->where(function ($sub) {
                        $sub->where('name', 'like', '%' . $this->employerSearch . '%')
                            ->orWhere('phone', 'like', '%' . $this->employerSearch . '%')
                            ->orWhere('email', 'like', '%' . $this->employerSearch . '%');
                    });
                }
                $employersList = $eQuery->orderBy('name')->paginate(15);
            @endphp
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950/20 border-b border-slate-800/60 text-slate-500 uppercase font-bold tracking-wider">
                            <th class="px-6 py-4">Employer Name</th>
                            <th class="px-6 py-4">Account Type</th>
                            <th class="px-6 py-4">Phone / Email</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Available Credits</th>
                            <th class="px-6 py-4 text-right">Actions / Credit Allocation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        @forelse ($employersList as $emp)
                            <tr class="hover:bg-slate-900/10 transition-colors">
                                <td class="px-6 py-4 font-bold text-white">
                                    {{ $emp->name }}
                                    @if ($emp->profile && $emp->profile->company_name)
                                        <span class="block text-[10px] text-slate-500 font-normal mt-0.5">{{ $emp->profile->company_name }} ({{ $emp->profile->city ?: 'No City' }}, {{ $emp->profile->district }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-300 capitalize">{{ $emp->role === 'employer' ? 'Firm / Company' : 'Contractor' }}</td>
                                <td class="px-6 py-4 font-medium text-slate-400">
                                    <span class="block text-slate-300 font-mono">{{ $emp->phone }}</span>
                                    <span class="block text-[10px] text-slate-500">{{ $emp->email ?: 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button" wire:click="toggleEmployerApproval({{ $emp->id }})" id="btn-toggle-approval-{{ $emp->id }}"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase transition-all cursor-pointer {{ $emp->is_approved ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                                        {{ $emp->is_approved ? 'Approved' : 'Pending' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center font-extrabold text-indigo-400 select-all text-sm">{{ $emp->available_credits }}</td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button type="button" wire:click="addCredits({{ $emp->id }}, 5)" id="btn-add-5-{{ $emp->id }}"
                                            class="inline-flex items-center justify-center px-2.5 py-1.5 rounded-lg border border-slate-800 hover:border-indigo-500/50 hover:bg-indigo-500/10 text-slate-300 hover:text-indigo-400 font-bold transition-all cursor-pointer">
                                        +5 Credits
                                    </button>
                                    <button type="button" wire:click="addCredits({{ $emp->id }}, 10)" id="btn-add-10-{{ $emp->id }}"
                                            class="inline-flex items-center justify-center px-2.5 py-1.5 rounded-lg border border-slate-800 hover:border-indigo-500/50 hover:bg-indigo-500/10 text-slate-300 hover:text-indigo-400 font-bold transition-all cursor-pointer">
                                        +10 Credits
                                    </button>
                                    <button type="button" wire:click="resetCredits({{ $emp->id }})" id="btn-reset-credits-{{ $emp->id }}"
                                            class="inline-flex items-center justify-center px-2.5 py-1.5 rounded-lg border border-slate-800 hover:border-indigo-500/50 hover:bg-indigo-500/10 text-slate-400 hover:text-indigo-400 font-bold transition-all cursor-pointer">
                                        Reset to 5
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-bold">No registered employers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
