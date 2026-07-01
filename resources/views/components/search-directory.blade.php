<?php
use Livewire\Component;
use App\Models\Worker;
use App\Models\User;
use App\Models\CreditLock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $activeTab = 'directory'; // 'directory', 'unlocked'
    public string $search = '';
    public string $sector = '';
    public string $skill_category = '';
    public string $district = '';
    public string $sortBy = 'name_asc';
    public ?int $selectedWorkerId = null;

    public bool $showPaymentModal = false;

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['directory', 'unlocked'])) {
            $this->activeTab = $tab;
        }
    }

    public function selectWorker(?int $workerId): void
    {
        $this->selectedWorkerId = $workerId;
    }

    public function revealContact(int $workerId): void
    {
        if (!Auth::check()) {
            session()->flash('error', __('Please log in to unlock worker contacts.'));
            $this->redirect('/login', navigate: true);
            return;
        }

        if (!Auth::user()->is_approved) {
            session()->flash('error', __('Your account is pending administrator approval. You cannot unlock worker details yet.'));
            return;
        }

        $employerId = Auth::id();

        // Run the transactional deduction logic
        $success = $this->executeRevealTransaction($employerId, $workerId);

        if (!$success) {
            $this->showPaymentModal = true;
        }
    }

    private function executeRevealTransaction(int $employerId, int $workerId): bool
    {
        return DB::transaction(function () use ($employerId, $workerId) {
            $employer = User::where('id', $employerId)->lockForUpdate()->first();
            
            $alreadyUnlocked = CreditLock::where('employer_id', $employerId)
                ->where('worker_id', $workerId)
                ->exists();
            
            if ($alreadyUnlocked) return true;
            
            $cost = (int)\App\Models\Setting::get('reveal_credit_cost', 1);
            if ($employer->available_credits < $cost) return false;
            
            $employer->available_credits -= $cost;
            $employer->save();
            
            CreditLock::create([
                'employer_id' => $employerId,
                'worker_id' => $workerId,
            ]);
            
            return true;
        }, 5); // Retry count of 5 handles deadlocks gracefully
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative">
    @php
        $allowDomestic = \App\Models\Setting::get('allow_domestic_sector', true);

        $industrialTrades = [
            'Welder', 'Plumber', 'Electrician', 'Storekeeper', 'Store Incharge', 
            'Forklift Operator', 'Security Guard', 'Mason', 'Carpenter', 'Painter', 
            'HVAC Technician', 'Auto Mechanic', 'Steel Fixer', 'Scaffolder', 
            'Crane Operator', 'Pipe Fitter', 'Machinist', 'Boiler Operator', 
            'Quality Control Inspector', 'Office Assistant', 'Office Boy', 
            'Receptionist', 'Record Keeper', 'Dispatch Rider', 'Helper'
        ];
        $domesticTrades = [
            'Cook', 'Maid', 'Data Entry Operator', 'Driver', 'Nanny', 'Gardener', 
            'Tailor', 'Laundry Man', 'Watchman', 'Delivery Rider', 'Sweeper'
        ];

        $standardTrades = $allowDomestic 
            ? array_merge($industrialTrades, $domesticTrades)
            : $industrialTrades;

        $dbTradesQuery = \App\Models\Worker::distinct();
        if (!$allowDomestic) {
            $dbTradesQuery->where('sector', 'Industrial');
        }
        $dbTrades = $dbTradesQuery->pluck('skill_category')->toArray();
        $allTrades = array_unique(array_merge($standardTrades, $dbTrades));
        sort($allTrades);

        // Global Unlocked IDs lookup available in all tabs/scopes
        $unlockedIds = Auth::check() 
            ? \App\Models\CreditLock::where('employer_id', Auth::id())->pluck('worker_id')->toArray() 
            : [];

        // Dynamic stats calculation
        $totalRegistered = \App\Models\Worker::count();
        $availableCount = \App\Models\Worker::where('is_available', true)->count();
        $busyCount = $totalRegistered - $availableCount;
        $industrialCount = \App\Models\Worker::where('sector', 'Industrial')->count();
        $domesticCount = \App\Models\Worker::where('sector', 'Domestic')->count();
    @endphp
    
    <!-- Top Alert Banners -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 font-semibold text-center flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/2 transition-all">
            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 font-semibold text-center flex items-center justify-center gap-2 shadow-lg shadow-rose-500/2 transition-all">
            <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Dashboard Hero & Stats -->
    <div class="relative flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8 bg-gradient-to-r from-slate-900 via-slate-950 to-slate-900 border border-slate-800/80 p-8 rounded-3xl shadow-2xl backdrop-blur-md overflow-hidden">
        <!-- Ambient radial glow inside hero -->
        <div class="absolute -right-16 -top-16 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-48 h-48 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="space-y-2">
            <h1 class="text-2xl font-black text-white sm:text-3xl tracking-tight">
                {{ __('Employer Dashboard') }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 max-w-2xl leading-relaxed">
                {{ __('Recruit verified skilled professionals, view your unlocked candidates list, and top up credits.') }}
            </p>
        </div>
        
        <!-- Live Wallet Info -->
        @auth
            @php
                $unlockedCount = count($unlockedIds);
            @endphp
            <div class="flex items-center gap-3 bg-slate-950/80 p-3.5 border border-slate-800/80 rounded-2xl shadow-2xl">
                <div class="flex items-center gap-3 px-4 border-r border-slate-800/80">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <span class="block text-[9px] uppercase font-bold text-slate-500 tracking-wider leading-none">{{ __('Available Credits') }}</span>
                        <span class="block text-lg font-black text-indigo-400 mt-1 leading-none font-mono">{{ Auth::user()->available_credits }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-3 px-4">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <span class="block text-[9px] uppercase font-bold text-slate-500 tracking-wider leading-none">{{ __('Unlocked Profiles') }}</span>
                        <span class="block text-lg font-black text-indigo-400 mt-1 leading-none font-mono">{{ $unlockedCount }}</span>
                    </div>
                </div>
            </div>
        @else
            <div class="text-slate-400 text-xs font-semibold bg-slate-950/60 p-3.5 border border-slate-800/80 rounded-2xl">
                {{ __('Please log in to manage tokens and unlocks.') }}
            </div>
        @endauth
    </div>

    <!-- Segmented Tab Controls -->
    <div class="flex p-1 gap-1.5 bg-slate-900/60 border border-slate-800/80 rounded-2xl mb-8 max-w-sm shadow-lg">
        <button type="button" wire:click="setTab('directory')" id="tab-dir"
                class="flex-1 py-2.5 text-xs font-extrabold rounded-xl transition-all cursor-pointer text-center flex items-center justify-center gap-2 {{ $activeTab === 'directory' ? 'bg-indigo-500 text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <span>{{ __('Search Directory') }}</span>
        </button>
        <button type="button" wire:click="setTab('unlocked')" id="tab-unlocked"
                class="flex-1 py-2.5 text-xs font-extrabold rounded-xl transition-all cursor-pointer text-center flex items-center justify-center gap-2 {{ $activeTab === 'unlocked' ? 'bg-indigo-500 text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span>{{ __('My Unlocked Hires') }}</span>
        </button>
    </div>

    <!-- Tab 1: Directory Search Panel -->
    @if ($activeTab === 'directory')
        <!-- Unified Analytics Stats Grid -->
        <div class="grid grid-cols-2 {{ $allowDomestic ? 'md:grid-cols-5' : 'md:grid-cols-4' }} gap-4 mb-8">
            <div class="bg-slate-900/40 border-l-4 border-l-slate-500 border border-slate-800/80 hover:border-slate-700 transition-all rounded-2xl p-5 flex items-center justify-between shadow-md hover:-translate-y-0.5 duration-300">
                <div>
                    <span class="text-[10px] font-bold text-slate-550 uppercase tracking-wider block leading-none">{{ __('Total Registered') }}</span>
                    <span class="text-2xl font-black text-white font-mono mt-2 block leading-none">{{ $totalRegistered }}</span>
                </div>
                <div class="h-9 w-9 rounded-xl bg-slate-950/80 border border-slate-850 flex items-center justify-center text-slate-400 shadow-inner">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            
            <div class="bg-slate-900/40 border-l-4 border-l-indigo-500 border border-slate-800/80 hover:border-slate-700 transition-all rounded-2xl p-5 flex items-center justify-between shadow-md hover:-translate-y-0.5 duration-300">
                <div>
                    <span class="text-[10px] font-bold text-indigo-500/80 uppercase tracking-wider block leading-none">{{ __('Available Now') }}</span>
                    <div class="flex items-baseline gap-1.5 mt-2">
                        <span class="text-2xl font-black text-indigo-400 font-mono leading-none">{{ $availableCount }}</span>
                        <span class="text-[9px] font-bold text-indigo-500/80 font-mono">({{ $totalRegistered > 0 ? number_format(($availableCount / $totalRegistered) * 100, 0) : 0 }}%)</span>
                    </div>
                </div>
                <div class="h-9 w-9 rounded-xl bg-indigo-500/5 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shadow-inner">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-slate-900/40 border-l-4 border-l-rose-500 border border-slate-800/80 hover:border-slate-700 transition-all rounded-2xl p-5 flex items-center justify-between shadow-md hover:-translate-y-0.5 duration-300">
                <div>
                    <span class="text-[10px] font-bold text-rose-500/80 uppercase tracking-wider block leading-none">{{ __('Currently Busy') }}</span>
                    <span class="text-2xl font-black text-rose-450 font-mono mt-2 block leading-none">{{ $busyCount }}</span>
                </div>
                <div class="h-9 w-9 rounded-xl bg-rose-500/5 border border-rose-500/20 flex items-center justify-center text-rose-450 shadow-inner">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-slate-900/40 border-l-4 border-l-sky-500 border border-slate-800/80 hover:border-slate-700 transition-all rounded-2xl p-5 flex items-center justify-between shadow-md hover:-translate-y-0.5 duration-300">
                <div>
                    <span class="text-[10px] font-bold text-sky-500/80 uppercase tracking-wider block leading-none">{{ __('Industrial Sector') }}</span>
                    <span class="text-2xl font-black text-sky-400 font-mono mt-2 block leading-none">{{ $industrialCount }}</span>
                </div>
                <div class="h-9 w-9 rounded-xl bg-sky-500/5 border border-sky-500/20 flex items-center justify-center text-sky-400 shadow-inner">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>

            @if ($allowDomestic)
            <div class="bg-slate-900/40 border-l-4 border-l-purple-500 border border-slate-800/80 hover:border-slate-700 transition-all rounded-2xl p-5 flex items-center justify-between shadow-md hover:-translate-y-0.5 duration-300">
                <div>
                    <span class="text-[10px] font-bold text-purple-500/80 uppercase tracking-wider block leading-none">{{ __('Domestic Sector') }}</span>
                    <span class="text-2xl font-black text-purple-400 font-mono mt-2 block leading-none">{{ $domesticCount }}</span>
                </div>
                <div class="h-9 w-9 rounded-xl bg-purple-500/5 border border-purple-500/20 flex items-center justify-center text-purple-400 shadow-inner">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                </div>
            </div>
            @endif
        </div>

        <!-- Filters Control Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-2 {{ $allowDomestic ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-4 mb-8 bg-slate-900/30 p-6 rounded-3xl border border-slate-800 shadow-xl backdrop-blur-md">
            <!-- Text Search -->
            <div class="relative">
                <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">{{ __('Search Name') }}</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" id="search-name" type="text" placeholder="{{ __('Search worker name...') }}" 
                           class="w-full pl-9 pr-4 py-2.5 bg-slate-950 border border-slate-800/80 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-xs font-semibold" />
                </div>
            </div>

            @if ($allowDomestic)
            <!-- Sector Filter -->
            <div>
                <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">{{ __('Sector') }}</label>
                <select wire:model.live="sector" id="filter-sector" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800/80 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-xs font-semibold">
                    <option value="">{{ __('All Sectors') }}</option>
                    <option value="Industrial">{{ __('Industrial') }}</option>
                    <option value="Domestic">{{ __('Domestic') }}</option>
                </select>
            </div>
            @endif

            <!-- Skill Filter -->
            <div>
                <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">{{ __('Trade / Skill') }}</label>
                <select wire:model.live="skill_category" id="filter-trade" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800/80 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-xs font-semibold">
                    <option value="">{{ __('All Trades') }}</option>
                    @foreach ($allTrades as $trade)
                        <option value="{{ $trade }}">{{ __($trade) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- District Filter -->
            <div>
                <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">{{ __('District') }}</label>
                <select wire:model.live="district" id="filter-district" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800/80 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-xs font-semibold">
                    <option value="">{{ __('All Districts') }}</option>
                    <option value="Peshawar">{{ __('Peshawar') }}</option>
                    <option value="Nowshera">{{ __('Nowshera') }}</option>
                    <option value="Charsadda">{{ __('Charsadda') }}</option>
                    <option value="Mardan">{{ __('Mardan') }}</option>
                    <option value="Swabi">{{ __('Swabi') }}</option>
                    <option value="Swat">{{ __('Swat') }}</option>
                    <option value="Buner">{{ __('Buner') }}</option>
                    <option value="Haripur">{{ __('Haripur') }}</option>
                    <option value="Abbottabad">{{ __('Abbottabad') }}</option>
                </select>
            </div>

            <!-- Sort Filter -->
            <div>
                <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider mb-2">{{ __('Sort By') }}</label>
                <select wire:model.live="sortBy" id="filter-sort" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800/80 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-xs font-semibold">
                    <option value="name_asc">{{ __('Name: A-Z') }}</option>
                    <option value="name_desc">{{ __('Name: Z-A') }}</option>
                    <option value="experience_desc">{{ __('Experience: Highest First') }}</option>
                    <option value="experience_asc">{{ __('Experience: Lowest First') }}</option>
                </select>
            </div>
        </div>

        @php
            $query = \App\Models\Worker::query();

            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }
            if ($allowDomestic) {
                if ($sector) {
                    $query->where('sector', $sector);
                }
            } else {
                $query->where('sector', 'Industrial');
            }
            if ($skill_category) {
                $query->where('skill_category', $skill_category);
            }
            if ($district) {
                $query->where('district', $district);
            }

            if ($sortBy === 'experience_desc') {
                $query->orderBy('experience_years', 'desc');
            } elseif ($sortBy === 'experience_asc') {
                $query->orderBy('experience_years', 'asc');
            } elseif ($sortBy === 'name_desc') {
                $query->orderBy('name', 'desc');
            } else {
                $query->orderBy('name', 'asc');
            }

            $workers = $query->get();
        @endphp

        <!-- Workers Directory Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse ($workers as $worker)
                @php
                    $isUnlocked = in_array($worker->id, $unlockedIds);
                @endphp
                <div wire:click="selectWorker({{ $worker->id }})" 
                     class="group relative bg-slate-900/20 hover:bg-slate-900/45 border {{ $selectedWorkerId === $worker->id ? 'border-indigo-500 bg-slate-900/60 ring-2 ring-indigo-500/10 shadow-lg shadow-indigo-500/5' : 'border-slate-800 hover:border-slate-700/80' }} p-6 rounded-3xl flex flex-col justify-between backdrop-blur-md transition-all duration-300 cursor-pointer shadow-md hover:-translate-y-0.5">
                    
                    <!-- Card Top Header -->
                    <div class="flex justify-between items-start gap-4">
                        <!-- Left: Avatar / Initials & Details -->
                        <div class="flex items-center gap-3">
                            <div class="h-11 w-11 rounded-2xl bg-gradient-to-tr from-indigo-500/15 to-violet-500/5 border border-indigo-500/25 flex items-center justify-center text-indigo-400 font-extrabold text-sm group-hover:scale-105 transition-transform shadow-inner font-sans">
                                {{ substr($worker->name, 0, 2) }}
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white group-hover:text-indigo-400 transition-colors leading-tight font-sans">
                                    {{ $worker->name }}
                                </h3>
                                <div class="flex items-center gap-1.5 mt-1.5">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $worker->is_available ? 'bg-indigo-500 shadow-sm shadow-indigo-500/50 animate-pulse' : 'bg-rose-500 shadow-sm shadow-rose-500/50' }}"></span>
                                    <span class="text-[9px] font-bold uppercase tracking-wider {{ $worker->is_available ? 'text-indigo-400' : 'text-rose-400' }}">
                                        {{ $worker->is_available ? __('Available') : __('Busy') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Sector / Trade Badges -->
                        <div class="flex flex-col items-end gap-1.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[9px] font-extrabold uppercase bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 tracking-wider">
                                {{ __($worker->skill_category) }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[9px] font-extrabold uppercase bg-slate-950/60 border border-slate-800/80 text-slate-400 tracking-wider">
                                {{ __($worker->sector) }}
                            </span>
                        </div>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-2 gap-4 mt-5 p-4 bg-slate-950/40 border border-slate-800/60 rounded-2xl font-medium text-slate-300">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <div>
                                <span class="text-[9px] uppercase font-bold text-slate-500 block leading-none">{{ __('District') }}</span>
                                <span class="text-xs font-bold text-slate-200 mt-1.5 block leading-none">{{ __($worker->district) }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <div>
                                <span class="text-[9px] uppercase font-bold text-slate-500 block leading-none">{{ __('Experience') }}</span>
                                <span class="text-xs font-bold text-slate-200 mt-1.5 block leading-none">
                                    <span class="font-mono">{{ $worker->experience_years }}</span>
                                    <span>{{ __('Years') }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Gated Details -->
                    <div class="mt-5 pt-4 border-t border-slate-800/80 flex items-center justify-between gap-4">
                        <div class="flex flex-col">
                            <span class="text-[9px] uppercase font-bold text-slate-500 block leading-none">{{ __('Contact') }}</span>
                            @if ($isUnlocked)
                                <span class="text-xs sm:text-sm font-extrabold text-indigo-400 font-mono tracking-wider mt-1 block select-all">
                                    {{ $worker->phone }}
                                </span>
                            @else
                                <span class="text-xs sm:text-sm font-extrabold text-slate-650 font-mono tracking-widest mt-1 block select-none">
                                    {{ substr($worker->phone, 0, 4) }}-XXXXXXX
                                </span>
                            @endif
                        </div>

                        <div>
                            @if ($isUnlocked)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[9px] font-extrabold text-indigo-400 bg-indigo-500/10 rounded-xl border border-indigo-500/20 shadow-sm">
                                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ __('Unlocked') }}
                                </span>
                            @else
                                <button type="button" id="btn-reveal-{{ $worker->id }}" wire:click.stop="revealContact({{ $worker->id }})" 
                                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-slate-950 bg-gradient-to-r from-indigo-500 to-violet-500 rounded-xl hover:opacity-90 active:scale-95 shadow-md shadow-indigo-500/10 transition-all cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>{{ __('Reveal Contact') }}</span>
                                </button>
                            @endif
                        </div>
                    </div>

                </div>
            @empty
                <div class="col-span-2 text-center py-16 bg-slate-900/10 border border-slate-800 border-dashed rounded-3xl">
                    <svg class="w-10 h-10 text-slate-700 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <p class="text-sm font-bold text-slate-400">{{ __('No workers found matching the criteria.') }}</p>
                    <p class="text-xs text-slate-600 mt-1">{{ __('Try resetting the search or filter options.') }}</p>
                </div>
            @endforelse
        </div>

    <!-- Tab 2: My Hires (Unlocked Contacts Ledger) -->
    @elseif ($activeTab === 'unlocked')
        @php
            $myLocks = Auth::check() 
                ? \App\Models\CreditLock::where('employer_id', Auth::id())->with('worker')->get() 
                : collect();
        @endphp
        <div class="bg-slate-900/30 border border-slate-800/80 rounded-3xl overflow-hidden backdrop-blur-md shadow-xl">
            @if ($myLocks->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-950/60 border-b border-slate-800/80 text-slate-500 uppercase font-bold tracking-wider">
                                <th class="px-6 py-4">{{ __('Worker Name') }}</th>
                                <th class="px-6 py-4">{{ __('Trade / Sector') }}</th>
                                <th class="px-6 py-4">{{ __('District') }}</th>
                                <th class="px-6 py-4">{{ __('Full Mobile Number') }}</th>
                                <th class="px-6 py-4">{{ __('Unlocked Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach ($myLocks as $lock)
                                @if ($lock->worker)
                                    <tr class="hover:bg-slate-900/20 transition-colors">
                                        <td class="px-6 py-4 font-bold text-white font-sans">{{ $lock->worker->name }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-2 py-0.5 rounded bg-slate-950 border border-slate-800 text-[9px] text-indigo-400 font-bold uppercase tracking-wider">{{ __($lock->worker->skill_category) }}</span>
                                                <span class="px-2 py-0.5 rounded bg-slate-950/60 border border-slate-800/60 text-[9px] text-slate-500 font-bold uppercase tracking-wider">{{ __($lock->worker->sector) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-350">{{ __($lock->worker->district) }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-extrabold text-indigo-400 select-all tracking-wider font-mono">
                                                    {{ $lock->worker->phone }}
                                                </span>
                                                <button type="button" onclick="navigator.clipboard.writeText('{{ $lock->worker->phone }}'); alert('{{ __('Copied mobile number to clipboard!') }}');"
                                                        class="p-1 rounded bg-slate-950 hover:bg-slate-900 border border-slate-800 text-slate-500 hover:text-indigo-400 transition-colors cursor-pointer" title="Copy">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-500 font-medium font-mono">
                                            {{ $lock->created_at->format('M d, Y - h:i A') }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="w-12 h-12 text-slate-750 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-sm font-bold text-slate-400">{{ __("You haven't unlocked any worker profiles yet.") }}</p>
                    <p class="text-xs text-slate-600 mt-1">{{ __('Visit the Search Directory to reveal contact numbers.') }}</p>
                </div>
            @endif
        </div>

    @endif

    <!-- ---------------------------------------------------- -->
    <!-- Insufficient Balance Modal (Dynamic Gateway details) -->
    <!-- ---------------------------------------------------- -->
    @if ($showPaymentModal)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" wire:click="closePaymentModal"></div>

            <!-- Modal Content Card -->
            <div class="relative bg-slate-900 border border-slate-800 p-6 sm:p-8 rounded-3xl w-full max-w-md shadow-2xl overflow-hidden transition-all text-slate-100 font-sans">
                <!-- Header -->
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-800/80">
                    <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                        <span class="text-rose-500">⚠️</span>
                        {{ __('Insufficient Credits') }}
                    </h3>
                    <button type="button" id="btn-close-payment-modal" wire:click="closePaymentModal" class="h-8 w-8 rounded-full hover:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white transition-colors cursor-pointer font-bold font-sans">
                        ✕
                    </button>
                </div>
                
                <div class="text-center py-6 space-y-4">
                    <div class="h-16 w-16 bg-rose-500/10 border border-rose-500/20 rounded-full flex items-center justify-center text-rose-500 text-3xl mx-auto">
                        🪙
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-sm font-bold text-white">{{ __('Recharge Required') }}</h4>
                        <p class="text-xs text-slate-400 leading-relaxed max-w-xs mx-auto">
                            {{ __('You do not have enough credits to reveal this candidate\'s contact details. Unlocking a profile costs') }} <span class="font-extrabold text-indigo-400 font-mono">{{ \App\Models\Setting::get('reveal_credit_cost', 1) }} cr</span>.
                        </p>
                    </div>
                </div>

                <div class="flex gap-3 pt-4 border-t border-slate-800/80">
                    <button type="button" wire:click="closePaymentModal"
                            class="flex-1 py-3 rounded-xl border border-slate-800 text-xs font-bold text-slate-400 hover:text-white hover:bg-slate-800/40 transition-colors cursor-pointer text-center">
                        {{ __('Cancel') }}
                    </button>
                    <a href="/credits" wire:navigate
                       class="flex-1 py-3 rounded-xl font-bold text-xs uppercase tracking-wider text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 active:scale-95 text-center flex items-center justify-center">
                        {{ __('Buy Credits') }} &rarr;
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- ---------------------------------------------------- -->
    <!-- Detailed Worker Profile Slide-over Drawer            -->
    <!-- ---------------------------------------------------- -->
    @if ($selectedWorkerId && ($selectedWorker = \App\Models\Worker::find($selectedWorkerId)))
        <div class="fixed inset-0 z-40 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" 
                     wire:click="selectWorker(null)"></div>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 {{ in_array(app()->getLocale(), ['ur', 'ps']) ? 'left-0 right-auto pr-10 pl-0' : '' }}">
                    <div class="pointer-events-auto w-screen max-w-md transform transition-all duration-500 ease-in-out">
                        <div class="flex h-full flex-col bg-slate-900 border-l border-slate-800 {{ in_array(app()->getLocale(), ['ur', 'ps']) ? 'border-r border-l-0' : '' }} p-6 shadow-2xl overflow-y-auto text-slate-100 relative">
                            
                            <!-- Drawer Header -->
                            <div class="flex items-center justify-between pb-5 border-b border-slate-800">
                                <div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-[9px] font-extrabold text-indigo-400 bg-indigo-500/10 rounded-full border border-indigo-500/20 uppercase tracking-wider mb-1">
                                        {{ __('Verified Worker Profile') }}
                                    </span>
                                    <h2 class="text-xl font-extrabold text-white" id="slide-over-title">
                                        {{ $selectedWorker->name }}
                                    </h2>
                                </div>
                                <button type="button" wire:click="selectWorker(null)" class="h-8 w-8 rounded-full hover:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white transition-colors cursor-pointer font-bold">
                                    ✕
                                </button>
                            </div>

                            <!-- Drawer Body -->
                            <div class="mt-6 flex-1 space-y-6">
                                <!-- Big Profile Icon/Initial -->
                                <div class="flex items-center gap-4 bg-slate-950/40 p-4 border border-slate-800 rounded-2xl shadow-inner">
                                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-slate-950 font-black text-2xl shadow-lg shadow-indigo-500/15 font-sans">
                                        {{ substr($selectedWorker->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-extrabold text-white leading-tight font-sans">{{ $selectedWorker->name }}</h3>
                                        <p class="text-xs text-slate-400 mt-2 flex items-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full {{ $selectedWorker->is_available ? 'bg-indigo-500 shadow-sm shadow-indigo-500/50' : 'bg-rose-500 shadow-sm shadow-rose-500/50' }}"></span>
                                            <span class="font-bold text-[10px] uppercase tracking-wider {{ $selectedWorker->is_available ? 'text-indigo-400' : 'text-rose-400' }}">
                                                {{ $selectedWorker->is_available ? __('Available') : __('Busy') }}
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Key Stats/Information Grid -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-slate-950/30 p-4 border border-slate-800 rounded-xl">
                                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">{{ __('Sector') }}</span>
                                        <span class="text-xs sm:text-sm font-bold text-white mt-1.5 block">{{ __($selectedWorker->sector) }}</span>
                                    </div>
                                    <div class="bg-slate-950/30 p-4 border border-slate-800 rounded-xl">
                                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">{{ __('Trade / Skill') }}</span>
                                        <span class="text-xs sm:text-sm font-bold text-indigo-400 mt-1.5 block">{{ __($selectedWorker->skill_category) }}</span>
                                    </div>
                                    <div class="bg-slate-950/30 p-4 border border-slate-800 rounded-xl">
                                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">{{ __('District') }}</span>
                                        <span class="text-xs sm:text-sm font-bold text-white mt-1.5 block">{{ __($selectedWorker->district) }}</span>
                                    </div>
                                    <div class="bg-slate-950/30 p-4 border border-slate-800 rounded-xl">
                                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block">{{ __('Experience') }}</span>
                                        <span class="text-xs sm:text-sm font-bold text-slate-200 mt-1.5 block">
                                            <span class="font-mono">{{ $selectedWorker->experience_years }}</span>
                                            <span>{{ __('Years') }}</span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Detail Specifications -->
                                <div class="space-y-4">
                                    <!-- Experience Level -->
                                    <div class="p-4 bg-slate-950/20 border border-slate-800 rounded-xl">
                                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block mb-2">{{ __('Experience Tier') }}</span>
                                        @php
                                            $exp = $selectedWorker->experience_years;
                                            if ($exp >= 8) {
                                                $tierName = __('Expert Master');
                                                $tierDesc = __('Seasoned master craftsman with over 8 years of proven experience, capable of leading complex industrial/residential works independently.');
                                            } elseif ($exp >= 3) {
                                                $tierName = __('Skilled Journeyman');
                                                $tierDesc = __('Competent and skilled professional with 3 to 7 years of work experience, requiring zero supervision for day-to-day operations.');
                                            } else {
                                                $tierName = __('Apprentice');
                                                $tierDesc = __('Entry-level technician with up to 2 years of practical training, suitable for supportive roles and supervised trades.');
                                            }
                                        @endphp
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                                            <span class="text-xs sm:text-sm font-bold text-white">{{ $tierName }}</span>
                                        </div>
                                        <p class="text-[11px] text-slate-400 leading-relaxed">
                                            {{ $tierDesc }}
                                        </p>
                                    </div>

                                    <!-- Ratings & Reviews Simulator -->
                                    <div class="p-4 bg-slate-950/20 border border-slate-800 rounded-xl">
                                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block mb-2">{{ __('Rating') }}</span>
                                        <div class="flex items-center gap-3">
                                            <div class="flex text-amber-400 gap-0.5 text-xs">
                                                ★ ★ ★ ★ ★
                                            </div>
                                            <span class="text-xs sm:text-sm font-bold text-white font-mono">4.9 / 5.0</span>
                                            <span class="text-[9px] text-slate-500 font-semibold">({{ __('from previous matches') }})</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Details with Unlock Action -->
                                <div class="pt-6 border-t border-slate-800">
                                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-wider block mb-2.5">{{ __('Contact Info') }}</span>
                                    <div class="p-4 bg-slate-950 border border-slate-800 rounded-2xl flex items-center justify-between shadow-inner">
                                        <div>
                                            @if (in_array($selectedWorker->id, $unlockedIds))
                                                <span class="block text-base sm:text-lg font-black text-indigo-400 select-all tracking-wider font-mono">
                                                    {{ $selectedWorker->phone }}
                                                </span>
                                                <span class="text-[9px] text-indigo-500/70 font-bold block mt-0.5">✓ {{ __('Unlocked') }}</span>
                                            @else
                                                <span class="block text-base sm:text-lg font-extrabold text-slate-500 tracking-widest font-mono select-none">
                                                    {{ substr($selectedWorker->phone, 0, 4) }}-XXXXXXX
                                                </span>
                                                <span class="text-[9px] text-slate-650 font-semibold block mt-0.5">{{ __('🔒 Click reveal button to unlock contact details') }}</span>
                                            @endif
                                        </div>

                                        @if (!in_array($selectedWorker->id, $unlockedIds))
                                            <button type="button" wire:click.stop="revealContact({{ $selectedWorker->id }})"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold text-slate-950 bg-gradient-to-r from-indigo-500 to-violet-500 rounded-xl hover:opacity-90 active:scale-95 shadow-md shadow-indigo-500/10 transition-all cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                                </svg>
                                                <span>{{ __('Reveal Contact') }}</span>
                                            </button>
                                        @else
                                            <button type="button" onclick="navigator.clipboard.writeText('{{ $selectedWorker->phone }}'); alert('{{ __('Phone number copied to clipboard!') }}');"
                                                    class="p-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-400 hover:text-white transition-colors cursor-pointer" title="Copy Number">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Drawer Footer -->
                            <div class="pt-6 border-t border-slate-800 mt-6 flex justify-end">
                                <button type="button" wire:click="selectWorker(null)"
                                        class="px-5 py-2.5 rounded-xl border border-slate-800 hover:bg-slate-800 text-xs font-bold text-slate-400 hover:text-white transition-all cursor-pointer">
                                    {{ __('Close Details') }}
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
