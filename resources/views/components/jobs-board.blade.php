<?php
use Livewire\Component;
use App\Models\JobPost;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public string $search = '';
    public string $filter_district = '';
    public string $filter_trade = '';

    public bool $showPostForm = false;

    // Post Job Form State
    public string $post_title = '';
    public string $post_trade = '';
    public string $post_district = '';
    public int $post_salary = 0;
    public string $post_duration = 'Daily';
    public string $post_phone = '';
    public string $post_description = '';

    public function togglePostForm(): void
    {
        $this->showPostForm = !$this->showPostForm;
    }

    public function submitJob(): void
    {
        if (!Auth::check()) {
            session()->flash('error', __('You must be logged in to post a job request.'));
            return;
        }

        $this->validate([
            'post_title' => 'required|string|min:5|max:100',
            'post_trade' => 'required|string',
            'post_district' => 'required|string',
            'post_salary' => 'required|integer|min:1',
            'post_duration' => 'required|string',
            'post_phone' => 'required|regex:/^(03)[0-9]{9}$/',
            'post_description' => 'required|string|min:10|max:1000',
        ], [
            'post_phone.regex' => __('Mobile number must be 11 digits starting with 03 (e.g. 03001234567).')
        ]);

        JobPost::create([
            'employer_id' => Auth::id(),
            'title' => $this->post_title,
            'trade' => $this->post_trade,
            'district' => $this->post_district,
            'salary' => $this->post_salary,
            'duration' => $this->post_duration,
            'phone' => $this->post_phone,
            'description' => $this->post_description,
        ]);

        session()->flash('success', __('Job posting published successfully!'));

        // Reset
        $this->post_title = '';
        $this->post_trade = '';
        $this->post_district = '';
        $this->post_salary = 0;
        $this->post_duration = 'Daily';
        $this->post_phone = '';
        $this->post_description = '';
        $this->showPostForm = false;
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative">
    
    <!-- Top Alert Banners -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 font-semibold text-center flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 font-semibold text-center flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8 bg-slate-900/20 border border-slate-900 p-6 rounded-3xl backdrop-blur-md">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white font-display">
                {{ __('Active Job Postings') }}
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                {{ __('Browse contract and daily-wage opportunities posted by registered contractors and employers.') }}
            </p>
        </div>
        
        <div>
            @auth
                <button type="button" wire:click="togglePostForm" id="btn-toggle-post-job"
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 font-extrabold text-sm text-slate-950 hover:opacity-90 shadow-lg shadow-indigo-500/10 active:scale-95 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ __('Post a Job Request') }}
                </button>
            @else
                <div class="text-slate-500 text-xs font-semibold bg-slate-950 p-3 border border-slate-850 rounded-xl">
                    {{ __('Login as employer to post labor requests.') }}
                </div>
            @endauth
        </div>
    </div>

    <!-- Post Job Form (Collapsible Pane) -->
    @if ($showPostForm)
        <div class="mb-8 bg-slate-900/40 p-6 sm:p-8 rounded-3xl border border-slate-800 backdrop-blur-md transition-all">
            <h2 class="text-xl font-extrabold text-white mb-6">{{ __('Post a Job Request') }}</h2>
            
            <form wire:submit="submitJob" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-455 mb-2">{{ __('Job Title / Requirement') }}</label>
                        <input wire:model="post_title" type="text" placeholder="e.g. Mason needed for Swat Site" 
                               class="w-full px-4 py-3 bg-slate-950 border border-slate-850 rounded-xl text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm font-medium" />
                        @error('post_title') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Contact Phone -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-455 mb-2">{{ __('Contact Mobile') }}</label>
                        <input wire:model="post_phone" type="text" placeholder="e.g. 03001234567" 
                               class="w-full px-4 py-3 bg-slate-950 border border-slate-850 rounded-xl text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm font-medium" />
                        @error('post_phone') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Trade -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-455 mb-2">{{ __('Select Trade') }}</label>
                        <select wire:model="post_trade" class="w-full px-4 py-3 bg-slate-950 border border-slate-850 rounded-xl text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm font-medium">
                            <option value="">-- {{ __('Select Trade') }} --</option>
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
                                sort($standardTrades);
                            @endphp
                            @foreach ($standardTrades as $t)
                                <option value="{{ $t }}">{{ __($t) }}</option>
                            @endforeach
                            <option value="Other">{{ __('Other') }}</option>
                        </select>
                        @error('post_trade') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- District -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-455 mb-2">{{ __('Select District') }}</label>
                        <select wire:model="post_district" class="w-full px-4 py-3 bg-slate-950 border border-slate-850 rounded-xl text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm font-medium">
                            <option value="">-- {{ __('Select District') }} --</option>
                            <optgroup label="Peshawar Division">
                                <option value="Peshawar">Peshawar</option>
                                <option value="Nowshera">Nowshera</option>
                                <option value="Charsadda">Charsadda</option>
                            </optgroup>
                            <optgroup label="Mardan Division">
                                <option value="Mardan">Mardan</option>
                                <option value="Swabi">Swabi</option>
                            </optgroup>
                            <optgroup label="Malakand Division">
                                <option value="Swat">Swat</option>
                                <option value="Buner">Buner</option>
                            </optgroup>
                            <optgroup label="Hazara Division">
                                <option value="Abbottabad">Abbottabad</option>
                                <option value="Haripur">Haripur</option>
                            </optgroup>
                        </select>
                        @error('post_district') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Salary -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-455 mb-2">{{ __('Salary Offered (PKR)') }}</label>
                        <input wire:model="post_salary" type="number" placeholder="e.g. 1500" 
                               class="w-full px-4 py-3 bg-slate-950 border border-slate-850 rounded-xl text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm font-medium" />
                        @error('post_salary') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Duration -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-455 mb-2">{{ __('Work Duration') }}</label>
                        <select wire:model="post_duration" class="w-full px-4 py-3 bg-slate-950 border border-slate-850 rounded-xl text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm font-medium">
                            <option value="Daily">{{ __('Daily Wage') }}</option>
                            <option value="Weekly">{{ __('Weekly Contract') }}</option>
                            <option value="Monthly">{{ __('Monthly Salary') }}</option>
                            <option value="3 Days">{{ __('3 Days') }}</option>
                            <option value="1 Week">{{ __('1 Week') }}</option>
                            <option value="Project-based">{{ __('Project-based') }}</option>
                        </select>
                        @error('post_duration') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-455 mb-2">{{ __('Work Description / Details') }}</label>
                    <textarea wire:model="post_description" rows="4" placeholder="..."
                              class="w-full px-4 py-3 bg-slate-950 border border-slate-855 rounded-xl text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm font-medium"></textarea>
                    @error('post_description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-4">
                    <button type="submit" 
                            class="px-6 py-3 rounded-xl font-extrabold text-sm text-slate-950 bg-indigo-500 hover:opacity-90 active:scale-95 transition-all cursor-pointer">
                        {{ __('Post a Job Request') }}
                    </button>
                    <button type="button" wire:click="togglePostForm" 
                            class="px-6 py-3 rounded-xl font-bold text-sm text-slate-400 bg-slate-950 border border-slate-850 hover:text-white transition-all cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Filters Pane -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 bg-slate-900/40 p-4 rounded-2xl border border-slate-800/85 backdrop-blur-md">
        <!-- Search Keyword -->
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">{{ __('Search Keyword') }}</label>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search jobs...') }}" 
                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-850 rounded-xl text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm font-medium" />
        </div>

        <!-- Trade Filter -->
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">{{ __('Select Trade') }}</label>
            <select wire:model.live="filter_trade" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-850 rounded-xl text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm font-medium">
                <option value="">{{ __('All Trades') }}</option>
                @php
                    $filterTrades = JobPost::distinct()->pluck('trade')->sort()->values();
                @endphp
                @foreach ($filterTrades as $t)
                    <option value="{{ $t }}">{{ __($t) }}</option>
                @endforeach
            </select>
        </div>

        <!-- District Filter -->
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">{{ __('Select District') }}</label>
            <select wire:model.live="filter_district" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-850 rounded-xl text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm font-medium">
                <option value="">{{ __('All Districts') }}</option>
                @php
                    $filterDistricts = JobPost::distinct()->pluck('district')->sort()->values();
                @endphp
                @foreach ($filterDistricts as $d)
                    <option value="{{ $d }}">{{ __($d) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Active Jobs Listings Grid -->
    @php
        $query = JobPost::query();
        if ($search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }
        if ($filter_trade) {
            $query->where('trade', $filter_trade);
        }
        if ($filter_district) {
            $query->where('district', $filter_district);
        }
        $jobs = $query->orderBy('created_at', 'desc')->get();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse ($jobs as $job)
            <div class="bg-slate-900/30 border border-slate-800/80 p-6 rounded-2xl flex flex-col justify-between backdrop-blur-md relative overflow-hidden group hover:border-slate-700 transition-all">
                <div class="space-y-4">
                    <div class="flex justify-between items-start gap-4">
                        <div class="space-y-1">
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase bg-slate-950 border border-slate-800 text-indigo-400 tracking-wide">
                                {{ __($job->trade) }}
                            </span>
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase bg-slate-950 border border-slate-800 text-slate-400 tracking-wide">
                                {{ $job->district }}
                            </span>
                        </div>
                        
                        <div class="text-right">
                            <span class="block text-indigo-400 font-extrabold text-sm font-mono font-medium">PKR {{ number_format($job->salary) }}</span>
                            <span class="block text-[9px] text-slate-500 font-bold uppercase tracking-wider">{{ $job->duration }}</span>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-white group-hover:text-indigo-400 transition-colors">{{ $job->title }}</h3>
                        <p class="text-xs text-slate-450 line-clamp-3 mt-2 leading-relaxed">
                            {{ $job->description }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-800/40 flex items-center justify-between gap-4">
                    <div>
                        <span class="text-[9px] uppercase font-bold text-slate-600 block">{{ __('Posted By') }}</span>
                        <span class="text-xs font-semibold text-slate-400">{{ $job->employer ? $job->employer->name : __('Anonymous Employer') }}</span>
                    </div>

                    <div>
                        <span class="text-[9px] uppercase font-bold text-slate-600 block">{{ __('Helpline / Contact') }}</span>
                        <span class="text-sm font-black text-indigo-400 font-mono tracking-wide">{{ $job->phone }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-2 text-center py-16 bg-slate-900/10 border border-slate-900 border-dashed rounded-2xl">
                <svg class="w-10 h-10 text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <p class="text-sm font-bold text-slate-400">{{ __('No job openings found.') }}</p>
                <p class="text-xs text-slate-600 mt-1">{{ __('Check back later or change filter options.') }}</p>
            </div>
        @endforelse
    </div>

</div>
