<?php
use Livewire\Component;
use App\Models\User;
use App\Models\Worker;
use App\Models\CreditLock;
use App\Models\CreditTransaction;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

new class extends Component {
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

    public function toggleMaintenanceMode(): void
    {
        $this->checkAdmin();
        $current = (bool)Setting::get('maintenance_mode', false);
        Setting::set('maintenance_mode', !$current);
        $this->logActivity('settings_updated', 'Toggled maintenance mode via quick actions');
        session()->flash('success', 'Maintenance mode status updated.');
    }

    public function toggleDomesticSector(): void
    {
        $this->checkAdmin();
        $current = (bool)Setting::get('allow_domestic_sector', true);
        Setting::set('allow_domestic_sector', !$current);
        $this->logActivity('settings_updated', 'Toggled domestic sector registration availability');
        session()->flash('success', 'Domestic sector availability updated.');
    }

    public function toggleWorkerIntake(): void
    {
        $this->checkAdmin();
        $current = (bool)Setting::get('allow_worker_registration', true);
        Setting::set('allow_worker_registration', !$current);
        $this->logActivity('settings_updated', 'Toggled worker registration intake form availability');
        session()->flash('success', 'Worker intake status updated.');
    }

    public function approveEmployer($userId): void
    {
        $this->checkAdmin();
        $user = User::find($userId);
        if ($user) {
            $user->is_approved = true;
            $user->save();
            $this->logActivity('user_approved', "Approved registered employer: {$user->name} ({$user->email})");
            session()->flash('success', "Approved organization successfully.");
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
};
?>

<div class="space-y-8 font-sans">
    <!-- Welcome Header Card -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950/20 dark:via-indigo-950/40 to-slate-900 border border-slate-800/80 p-6 sm:p-8 rounded-3xl relative overflow-hidden shadow-xl">
        <!-- Blur overlays -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none animate-pulse" style="animation-duration: 6s;"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-violet-500/10 rounded-full blur-3xl pointer-events-none animate-pulse" style="animation-duration: 8s;"></div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 relative z-10">
            <div class="space-y-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold bg-indigo-500/10 border border-indigo-500/25 text-indigo-400 uppercase tracking-widest">
                    ⚡ Operations Control
                </span>
                <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-100 leading-tight">
                    KP Labor Matchmaker Console
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 max-w-2xl leading-relaxed font-medium">
                    Overview metrics, configure live registry parameters, approve organization signups, and monitor security activity logs.
                </p>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
                <a href="/admin/settings" class="px-4 py-2.5 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/20 hover:border-indigo-500/30 rounded-xl text-xs font-bold text-indigo-450 dark:text-indigo-400 transition-all shadow-sm hover:scale-[1.02] active:scale-95 duration-200">
                    ⚙️ System Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Livewire Alert Banner -->
    @if (session()->has('success'))
        <div class="p-4 rounded-2xl bg-emerald-550/10 border border-emerald-550/20 text-emerald-500 font-bold text-center text-xs flex items-center justify-center gap-2 shadow-sm animate-fade-in">
            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <!-- Data Analytics Metrics -->
    @php
        $totalUsers = User::count();
        $adminCount = User::where('role', 'admin')->count();
        $employerCount = User::where('role', 'employer')->count();
        $contractorCount = User::where('role', 'contractor')->count();

        $totalWorkers = Worker::count();
        $availableWorkers = Worker::where('is_available', true)->count();
        $busyWorkers = $totalWorkers - $availableWorkers;

        $totalOrders = CreditTransaction::where('status', 'completed')->count();
        $totalCreditsAllocated = CreditTransaction::where('status', 'completed')->sum('amount');
        
        $totalReveals = CreditLock::count();

        $recentReveals = CreditLock::with(['employer', 'worker'])->orderBy('created_at', 'desc')->take(5)->get();
        $recentTransactions = CreditTransaction::with('user')->orderBy('created_at', 'desc')->take(5)->get();
        $recentAudits = ActivityLog::orderBy('created_at', 'desc')->take(5)->get();
        
        // Pending approval employers
        $pendingEmployers = User::where('role', 'employer')
            ->where('is_approved', false)
            ->with('profile')
            ->take(5)
            ->get();
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Metric Card 1: Users -->
        <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl relative shadow-lg hover:shadow-xl hover:-translate-y-1 hover:border-slate-700/80 transition-all duration-300 group overflow-hidden">
            <!-- Background Glow -->
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/15 transition-all"></div>
            
            <div class="flex justify-between items-center">
                <span class="block text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">Total Users</span>
                <span class="text-lg bg-indigo-500/10 p-2 rounded-xl text-indigo-500 dark:text-indigo-400 group-hover:rotate-12 transition-transform duration-300 shadow-inner">👥</span>
            </div>
            
            <span class="block text-3xl font-extrabold text-slate-100 mt-4 font-mono tracking-tight leading-none">{{ $totalUsers }}</span>
            
            <div class="mt-5 flex items-center justify-between">
                <div class="flex flex-wrap items-center gap-1 text-[8px] font-bold">
                    <span class="px-1.5 py-0.5 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-500 dark:text-indigo-400">{{ $employerCount }} Orgs</span>
                    <span class="px-1.5 py-0.5 rounded-lg bg-violet-500/10 border border-violet-500/20 text-violet-500 dark:text-violet-400">{{ $contractorCount }} Cont</span>
                    <span class="px-1.5 py-0.5 rounded-lg bg-slate-850 border border-slate-800 text-slate-400">{{ $adminCount }} Adm</span>
                </div>
                <div class="h-6 w-16 shrink-0">
                    <svg class="w-full h-full text-indigo-550 dark:text-indigo-400" viewBox="0 0 100 30" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M0 20 Q 25 5, 50 15 T 100 8" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Metric Card 2: Workers -->
        <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl relative shadow-lg hover:shadow-xl hover:-translate-y-1 hover:border-slate-700/80 transition-all duration-300 group overflow-hidden">
            <!-- Background Glow -->
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/15 transition-all"></div>
            
            <div class="flex justify-between items-center">
                <span class="block text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">Registered Workers</span>
                <span class="text-lg bg-emerald-500/10 p-2 rounded-xl text-emerald-500 dark:text-emerald-400 group-hover:rotate-12 transition-transform duration-300 shadow-inner">👷</span>
            </div>
            
            <span class="block text-3xl font-extrabold text-slate-100 mt-4 font-mono tracking-tight leading-none">{{ $totalWorkers }}</span>
            
            <div class="mt-5 flex items-center justify-between">
                <div class="flex items-center gap-1.5 text-[8px] font-bold">
                    <span class="px-1.5 py-0.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-555 dark:text-emerald-400">{{ $availableWorkers }} Active</span>
                    <span class="px-1.5 py-0.5 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-555 dark:text-rose-455">{{ $busyWorkers }} Busy</span>
                </div>
                <div class="h-6 w-16 shrink-0">
                    <svg class="w-full h-full text-emerald-555 dark:text-emerald-450" viewBox="0 0 100 30" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M0 25 C 20 15, 40 28, 60 10 S 80 5, 100 2" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Metric Card 3: Orders -->
        <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl relative shadow-lg hover:shadow-xl hover:-translate-y-1 hover:border-slate-700/80 transition-all duration-300 group overflow-hidden">
            <!-- Background Glow -->
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-violet-500/10 rounded-full blur-2xl group-hover:bg-violet-500/15 transition-all"></div>
            
            <div class="flex justify-between items-center">
                <span class="block text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">Credit Transactions</span>
                <span class="text-lg bg-violet-500/10 p-2 rounded-xl text-violet-500 dark:text-violet-400 group-hover:rotate-12 transition-transform duration-300 shadow-inner">💳</span>
            </div>
            
            <span class="block text-3xl font-extrabold text-slate-100 mt-4 font-mono tracking-tight leading-none">{{ $totalOrders }}</span>
            
            <div class="mt-5 flex items-center justify-between">
                <div class="text-[9px] text-slate-400 font-bold">
                    <span class="text-indigo-400 dark:text-indigo-400 font-mono">{{ $totalCreditsAllocated }}</span> credits allocated
                </div>
                <div class="h-6 w-16 shrink-0">
                    <svg class="w-full h-full text-violet-550 dark:text-violet-450" viewBox="0 0 100 30" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M0 15 Q 30 25, 60 5 T 100 12" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Metric Card 4: Reveals -->
        <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl relative shadow-lg hover:shadow-xl hover:-translate-y-1 hover:border-slate-700/80 transition-all duration-300 group overflow-hidden">
            <!-- Background Glow -->
            <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/15 transition-all"></div>
            
            <div class="flex justify-between items-center">
                <span class="block text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">Revealed Contacts</span>
                <span class="text-lg bg-amber-500/10 p-2 rounded-xl text-amber-500 dark:text-amber-400 group-hover:rotate-12 transition-transform duration-300 shadow-inner">🔓</span>
            </div>
            
            <span class="block text-3xl font-extrabold text-slate-100 mt-4 font-mono tracking-tight leading-none">{{ $totalReveals }}</span>
            
            <div class="mt-5 flex items-center justify-between">
                <div class="text-[9px] text-slate-400 font-bold">
                    Unlocked worker details
                </div>
                <div class="h-6 w-16 shrink-0">
                    <svg class="w-full h-full text-amber-550 dark:text-amber-450" viewBox="0 0 100 30" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M0 28 Q 20 10, 40 22 T 80 5 T 100 18" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Center Interactive Actions & Approvals Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Quick Actions -->
        <div class="lg:col-span-1 bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl shadow-lg flex flex-col justify-between">
            <div>
                <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider mb-5 flex items-center gap-2 border-b border-slate-850 pb-3">
                    <span class="p-1 rounded bg-indigo-500/10 text-indigo-500 text-[10px]">⚡</span> Quick Registry Toggles
                </h3>
                
                <div class="space-y-4">
                    <!-- Toggle 1: Maintenance Mode -->
                    @php
                        $maintMode = Setting::get('maintenance_mode', false);
                    @endphp
                    <div class="flex items-center justify-between gap-4 p-3 bg-slate-850/40 border border-slate-800/50 rounded-2xl hover:border-slate-700/60 transition-colors">
                        <div class="space-y-0.5">
                            <span class="text-[11px] font-extrabold text-slate-200 block">Maintenance Mode</span>
                            <span class="text-[9px] text-slate-400 block leading-tight">Close frontend pages for audit</span>
                        </div>
                        <button type="button" wire:click="toggleMaintenanceMode" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $maintMode ? 'bg-rose-500 shadow-lg shadow-rose-500/20' : 'bg-slate-800' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out {{ $maintMode ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </div>

                    <!-- Toggle 2: Allow Domestic Sector -->
                    @php
                        $domesticSector = Setting::get('allow_domestic_sector', true);
                    @endphp
                    <div class="flex items-center justify-between gap-4 p-3 bg-slate-850/40 border border-slate-800/50 rounded-2xl hover:border-slate-700/60 transition-colors">
                        <div class="space-y-0.5">
                            <span class="text-[11px] font-extrabold text-slate-200 block">Domestic Sector</span>
                            <span class="text-[9px] text-slate-400 block leading-tight">Enable maid, cook and home tasks</span>
                        </div>
                        <button type="button" wire:click="toggleDomesticSector" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $domesticSector ? 'bg-indigo-650 dark:bg-indigo-500 shadow-lg shadow-indigo-600/20' : 'bg-slate-800' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out {{ $domesticSector ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </div>

                    <!-- Toggle 3: Worker Intake -->
                    @php
                        $workerIntake = Setting::get('allow_worker_registration', true);
                    @endphp
                    <div class="flex items-center justify-between gap-4 p-3 bg-slate-850/40 border border-slate-800/50 rounded-2xl hover:border-slate-700/60 transition-colors">
                        <div class="space-y-0.5">
                            <span class="text-[11px] font-extrabold text-slate-200 block">Worker Registration</span>
                            <span class="text-[9px] text-slate-400 block leading-tight">Allow new workers details intake</span>
                        </div>
                        <button type="button" wire:click="toggleWorkerIntake" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $workerIntake ? 'bg-indigo-650 dark:bg-indigo-500 shadow-lg shadow-indigo-600/20' : 'bg-slate-800' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out {{ $workerIntake ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-850 text-[10px] text-slate-500 leading-relaxed font-bold">
                Changes to these toggles take effect instantly and overwrite system configurations database cache parameters.
            </div>
        </div>

        <!-- Right: Pending Employer Approvals -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl shadow-lg">
            <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider mb-5 flex items-center justify-between border-b border-slate-850 pb-3">
                <span class="flex items-center gap-2"><span class="p-1 rounded bg-amber-500/10 text-amber-500 text-[10px]">🔑</span> Pending Organization Approvals</span>
                <span class="text-[9px] bg-amber-500/10 border border-amber-500/20 text-amber-500 px-2 py-0.5 rounded-full font-extrabold uppercase tracking-wide">Needs Verification</span>
            </h3>

            <div class="space-y-3.5 max-h-[260px] overflow-y-auto pr-1">
                @forelse ($pendingEmployers as $employer)
                    <div class="bg-slate-850/40 border border-slate-800/50 p-4 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 hover:border-slate-700/60 transition-all duration-200">
                        <div class="flex items-center gap-3.5">
                            <div class="h-10 w-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-extrabold text-xs shadow-md shrink-0">
                                {{ substr($employer->name, 0, 2) }}
                            </div>
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-200">{{ $employer->name }}</span>
                                    <span class="px-1.5 py-0.5 rounded-md bg-indigo-500/10 border border-indigo-500/20 text-[8px] font-extrabold uppercase text-indigo-500 dark:text-indigo-400 tracking-wider">
                                        {{ $employer->profile ? $employer->profile->sector : 'Employer' }}
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-400 font-bold leading-none">
                                    Company: <span class="text-slate-300">{{ $employer->profile ? $employer->profile->company_name : 'N/A' }}</span> • 
                                    Location: <span class="text-slate-300">{{ $employer->profile ? $employer->profile->district : 'N/A' }}</span>
                                </p>
                                <span class="block text-[9px] text-slate-500 font-mono font-bold">{{ $employer->email }} • {{ $employer->phone }}</span>
                            </div>
                        </div>
                        <button type="button" wire:click="approveEmployer({{ $employer->id }})" class="w-full sm:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-bold transition-all shadow-md cursor-pointer whitespace-nowrap">
                            Approve Account
                        </button>
                    </div>
                @empty
                    <div class="text-center py-10 bg-slate-850/20 border border-dashed border-slate-800 rounded-2xl">
                        <p class="text-slate-400 text-xs font-bold">No pending approvals found.</p>
                        <p class="text-[10px] text-slate-500 mt-1 font-semibold">All registered organizations are currently active.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Timeline Grid: Recent Unlocks, Orders & Log Audits -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Card: Unlocks -->
        <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl shadow-lg">
            <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider mb-5 flex items-center gap-2 border-b border-slate-850 pb-3">
                <span class="p-1 rounded bg-indigo-500/10 text-indigo-500 text-[10px]">🔓</span> Recent Reveals
            </h3>
            
            <div class="relative pl-6 border-l-2 border-dashed border-indigo-500/20 dark:border-indigo-500/10 space-y-6">
                @forelse ($recentReveals as $lock)
                    <div class="relative space-y-2">
                        <!-- Bullet indicator -->
                        <span class="absolute -left-[31px] top-1.5 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                        </span>
                        
                        <div class="bg-slate-850/40 border border-slate-800/60 p-3.5 rounded-2xl shadow-sm hover:border-slate-700/60 transition-colors">
                            <div class="flex justify-between items-center text-[9px] font-bold mb-1">
                                <span class="text-slate-200 truncate max-w-[130px]">{{ $lock->employer ? $lock->employer->name : 'Unknown Employer' }}</span>
                                <span class="text-slate-500 font-mono">{{ $lock->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-[10px] text-slate-400 leading-normal font-semibold">
                                Unlocked details of <span class="font-bold text-slate-100">{{ $lock->worker ? $lock->worker->name : 'Deleted Worker' }}</span>
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500 text-xs text-center py-6 font-semibold">No reveals recorded yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Card: Simulated Orders -->
        <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl shadow-lg">
            <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider mb-5 flex items-center gap-2 border-b border-slate-850 pb-3">
                <span class="p-1 rounded bg-emerald-500/10 text-emerald-500 text-[10px]">💳</span> Recent Purchases
            </h3>
            
            <div class="relative pl-6 border-l-2 border-dashed border-emerald-555/20 dark:border-emerald-555/10 space-y-6">
                @forelse ($recentTransactions as $tx)
                    <div class="relative space-y-2">
                        <!-- Bullet indicator -->
                        <span class="absolute -left-[31px] top-1.5 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-550"></span>
                        </span>
                        
                        <div class="bg-slate-850/40 border border-slate-800/60 p-3.5 rounded-2xl shadow-sm hover:border-slate-700/60 transition-colors">
                            <div class="flex justify-between items-start gap-2">
                                <div class="space-y-0.5">
                                    <span class="block text-[9px] font-bold text-slate-200 truncate max-w-[130px]">{{ $tx->user ? $tx->user->name : 'Deleted User' }}</span>
                                    <span class="block text-[8px] text-slate-500 font-mono font-bold">{{ $tx->payment_method }} • {{ $tx->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-extrabold text-emerald-550 dark:text-emerald-400 font-mono block">
                                        +{{ $tx->amount }} Cred
                                    </span>
                                    @if(isset($tx->price_pkr) && $tx->price_pkr > 0)
                                        <span class="block text-[8px] font-extrabold text-slate-500 font-mono">PKR {{ number_format($tx->price_pkr) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500 text-xs text-center py-6 font-semibold">No transactions recorded yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Card: Audits -->
        <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl shadow-lg">
            <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider mb-5 flex items-center gap-2 border-b border-slate-850 pb-3">
                <span class="p-1 rounded bg-violet-500/10 text-violet-500 text-[10px]">📝</span> Security Audit Trail
            </h3>
            
            <div class="relative pl-6 border-l-2 border-dashed border-violet-500/20 dark:border-violet-500/10 space-y-6">
                @forelse ($recentAudits as $log)
                    <div class="relative space-y-2">
                        <!-- Bullet indicator -->
                        <span class="absolute -left-[31px] top-1.5 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-violet-500"></span>
                        </span>
                        
                        <div class="bg-slate-850/40 border border-slate-800/60 p-3.5 rounded-2xl shadow-sm hover:border-slate-700/60 transition-colors">
                            <div class="flex justify-between items-center text-[9px] font-bold mb-1">
                                <span class="text-indigo-405 dark:text-indigo-400 font-mono capitalize">{{ str_replace('_', ' ', $log->action) }}</span>
                                <span class="text-slate-500 font-mono">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-[10px] text-slate-400 leading-normal font-semibold">
                                {{ $log->description }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-500 text-xs text-center py-6 font-semibold">No security audits recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
