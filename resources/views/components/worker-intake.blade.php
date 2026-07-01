<?php
use Livewire\Component;
use App\Models\Worker;
use Livewire\Attributes\Validate;

new class extends Component {
    #[Validate('required|regex:/^(03)[0-9]{9}$/', message: [
        'required' => 'Mobile number is required.',
        'regex' => 'Mobile number must be 11 digits starting with 03 (e.g. 03001234567).'
    ])]
    public string $phone = '';

    #[Validate('required|string|min:3', message: [
        'required' => 'Name is required.',
        'min' => 'Name must be at least 3 characters.'
    ])]
    public string $name = '';

    #[Validate('required', message: ['required' => 'Please select a sector.'])]
    public string $sector = '';

    #[Validate('required', message: ['required' => 'Please select a trade.'])]
    public string $skill_category = '';

    public string $custom_trade = '';

    #[Validate('required', message: ['required' => 'Please select your district.'])]
    public string $district = '';

    #[Validate('required|integer|min:0', message: ['integer' => 'Experience must be a number.'])]
    public int $experience_years = 0;

    public int $age = 18;

    public bool $is_available = true;

    // Language state: 'en' for English, 'ur' for Urdu, 'ps' for Pashto
    public string $lang = 'en';

    public function mount(): void
    {
        $this->lang = session('locale', app()->getLocale());
        $this->age = (int)\App\Models\Setting::get('min_worker_age', 18);
        if (!\App\Models\Setting::get('allow_domestic_sector', true)) {
            $this->sector = 'Industrial';
        }
    }

    public function toggleLanguage(): void
    {
        if ($this->lang === 'en') {
            $this->lang = 'ur';
        } elseif ($this->lang === 'ur') {
            $this->lang = 'ps';
        } else {
            $this->lang = 'en';
        }
        session(['locale' => $this->lang]);
        app()->setLocale($this->lang);
        $this->redirect('/register-worker', navigate: true);
    }

    public function setSector(string $val): void
    {
        $this->sector = $val;
        // Reset trade if it doesn't match sector
        if ($val === 'Industrial' && !in_array($this->skill_category, ['Welder', 'Plumber', 'Electrician', 'Storekeeper', 'Store Incharge', 'Forklift Operator', 'Security Guard', 'Mason', 'Carpenter', 'Painter', 'HVAC Technician', 'Auto Mechanic', 'Steel Fixer', 'Scaffolder', 'Crane Operator', 'Pipe Fitter', 'Machinist', 'Boiler Operator', 'Quality Control Inspector', 'Office Assistant', 'Office Boy', 'Receptionist', 'Record Keeper', 'Dispatch Rider', 'Helper', 'Other'])) {
            $this->skill_category = '';
        } elseif ($val === 'Domestic' && !in_array($this->skill_category, ['Cook', 'Maid', 'Data Entry Operator', 'Driver', 'Nanny', 'Gardener', 'Tailor', 'Laundry Man', 'Watchman', 'Delivery Rider', 'Sweeper', 'Office Boy', 'Other'])) {
            $this->skill_category = '';
        }
        $this->custom_trade = '';
    }

    public function setTrade(string $val): void
    {
        $this->skill_category = $val;
        if ($val !== 'Other') {
            $this->custom_trade = '';
        }
    }

    public function incrementExperience(): void
    {
        $this->experience_years++;
    }

    public function decrementExperience(): void
    {
        if ($this->experience_years > 0) {
            $this->experience_years--;
        }
    }

    public function toggleAvailability(): void
    {
        $this->is_available = !$this->is_available;
    }

    public function processIntake(): void
    {
        $this->validate();

        $minAge = (int)\App\Models\Setting::get('min_worker_age', 18);
        $maxAge = (int)\App\Models\Setting::get('max_worker_age', 60);

        $this->validate([
            'age' => "required|integer|min:{$minAge}|max:{$maxAge}",
        ], [
            'age.required' => __('Age is required.'),
            'age.integer' => __('Age must be a number.'),
            'age.min' => __("Minimum age requirement is :min years.", ['min' => $minAge]),
            'age.max' => __("Maximum age limit is :max years.", ['max' => $maxAge]),
        ]);

        if ($this->skill_category === 'Other') {
            $this->validate([
                'custom_trade' => 'required|string|min:3|max:50',
            ], [
                'custom_trade.required' => __('Please specify your trade.'),
                'custom_trade.min' => __('Trade must be at least 3 characters.'),
            ]);
            $finalTrade = trim($this->custom_trade);
        } else {
            $finalTrade = $this->skill_category;
        }

        Worker::updateOrCreate(
            ['phone' => $this->phone],
            [
                'name' => $this->name,
                'sector' => $this->sector,
                'skill_category' => $finalTrade,
                'district' => $this->district,
                'experience_years' => $this->experience_years,
                'age' => $this->age,
                'is_available' => $this->is_available,
            ]
        );

        session()->flash('success', __('Profile saved successfully!'));

        // Reset form but keep phone for reference
        $this->name = '';
        $this->sector = \App\Models\Setting::get('allow_domestic_sector', true) ? '' : 'Industrial';
        $this->skill_category = '';
        $this->custom_trade = '';
        $this->district = '';
        $this->experience_years = 0;
        $this->age = (int)\App\Models\Setting::get('min_worker_age', 18);
        $this->is_available = true;
    }
};
?>

<div class="max-w-3xl mx-auto px-4 py-8 sm:py-12" dir="{{ in_array($lang, ['ur', 'ps']) ? 'rtl' : 'ltr' }}">
    <!-- Title & Language Switcher -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">
                {{ __('Worker Registration') }}
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                {{ __('Register your profile to get matches with employers.') }}
            </p>
        </div>
        
        <button type="button" id="btn-toggle-language" wire:click="toggleLanguage" 
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm font-bold text-indigo-400 hover:bg-slate-800 hover:text-white transition-all cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 11.37 7.363 16.5 3 18.333"></path>
            </svg>
            @if ($lang === 'en')
                اردو / پښتو
            @elseif ($lang === 'ur')
                English / پښتو
            @else
                English / اردو
            @endif
        </button>
    </div>

    <!-- Main Form Card -->
    <div class="bg-slate-900/40 border border-slate-800 p-6 sm:p-8 rounded-2xl backdrop-blur-md shadow-xl relative overflow-hidden">
        
        @if (!\App\Models\Setting::get('allow_worker_registration', true))
            <div class="py-12 text-center">
                <div class="h-16 w-16 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">{{ __('Worker Registration Closed') }}</h3>
                <p class="text-sm text-slate-400 max-w-md mx-auto leading-relaxed">
                    {{ __('Worker Registration is temporarily closed by the administration. Please check back later.') }}
                </p>
            </div>
        @else
            @if (session()->has('success'))
                <div class="mb-6 p-4 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 font-semibold text-center flex items-center justify-center gap-2 animate-bounce">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <form wire:submit="processIntake" class="space-y-8">
            <!-- 1. Mobile Number and Name -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                        {{ __('Mobile Number') }}
                    </label>
                    <input wire:model.blur="phone" id="worker-phone" type="text" placeholder="e.g. 03001234567" 
                           class="w-full px-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm font-medium" />
                    @error('phone')
                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                        {{ __('Full Name') }}
                    </label>
                    <input wire:model="name" id="worker-name" type="text" placeholder="e.g. Ahmad Khan" 
                           class="w-full px-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm font-medium" />
                    @error('name')
                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- 2. Sector Selection (Visual Cards) -->
            @if (\App\Models\Setting::get('allow_domestic_sector', true))
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">
                    {{ __('Choose Sector') }}
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Industrial Sector -->
                    <button type="button" id="btn-sector-industrial" wire:click="setSector('Industrial')" 
                            class="flex items-center gap-4 p-4 rounded-xl border text-left cursor-pointer transition-all bg-slate-950/40 hover:bg-slate-900/60 {{ $this->sector === 'Industrial' ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-slate-850' }}">
                        <div class="h-12 w-12 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                            <!-- Smokestack Factory Icon -->
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-bold text-white text-base">{{ __('Industrial') }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ __('Factories, construction, trades') }}</div>
                        </div>
                    </button>

                    <!-- Domestic Sector -->
                    <button type="button" id="btn-sector-domestic" wire:click="setSector('Domestic')" 
                            class="flex items-center gap-4 p-4 rounded-xl border text-left cursor-pointer transition-all bg-slate-950/40 hover:bg-slate-900/60 {{ $this->sector === 'Domestic' ? 'border-indigo-500 ring-2 ring-indigo-500/20' : 'border-slate-850' }}">
                        <div class="h-12 w-12 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                            <!-- Home Outline Icon -->
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="font-bold text-white text-base">{{ __('Domestic') }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ __('Homes, offices, kitchen service') }}</div>
                        </div>
                    </button>
                </div>
                @error('sector')
                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>
            @endif

            <!-- 3. Skill / Trade Selection (Visual Options based on Sector) -->
            @if ($sector)
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">
                        {{ __('Select Trade') }}
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @if ($sector === 'Industrial')
                            <!-- Welder -->
                            <button type="button" id="btn-trade-welder" wire:click="setTrade('Welder')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Welder' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Welder' : 'ویلڈر' }}</span>
                            </button>
                            <!-- Plumber -->
                            <button type="button" id="btn-trade-plumber" wire:click="setTrade('Plumber')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Plumber' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Plumber' : 'پلمبر' }}</span>
                            </button>
                            <!-- Electrician -->
                            <button type="button" id="btn-trade-electrician" wire:click="setTrade('Electrician')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Electrician' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Electrician' : 'الیکٹریشن' }}</span>
                            </button>
                            <!-- Storekeeper -->
                            <button type="button" id="btn-trade-storekeeper" wire:click="setTrade('Storekeeper')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Storekeeper' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-14v14m0-14L4 7m8 4v14M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Storekeeper' : 'اسٹور کیپر' }}</span>
                            </button>
                            <!-- Store Incharge -->
                            <button type="button" id="btn-trade-store-incharge" wire:click="setTrade('Store Incharge')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Store Incharge' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Store Incharge' : 'اسٹور انچارج' }}</span>
                            </button>
                            <!-- Forklift Operator -->
                            <button type="button" id="btn-trade-forklift-operator" wire:click="setTrade('Forklift Operator')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Forklift Operator' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm0 0h5a2 2 0 002-2v-4a2 2 0 00-2-2h-5m-4-6l-1 1h7.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2m-9-3h9m-9-3h9m-9-3h9m-9-3h9"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Forklift Operator' : 'فورک لفٹ آپریٹر' }}</span>
                            </button>
                            <!-- Security Guard -->
                            <button type="button" id="btn-trade-security-guard" wire:click="setTrade('Security Guard')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Security Guard' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Security Guard' : 'سیکیورٹی گارڈ' }}</span>
                            </button>
                            <!-- Mason -->
                            <button type="button" id="btn-trade-mason" wire:click="setTrade('Mason')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Mason' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Mason' : 'راجگیر / مستری' }}</span>
                            </button>
                            <!-- Carpenter -->
                            <button type="button" id="btn-trade-carpenter" wire:click="setTrade('Carpenter')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Carpenter' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a2 2 0 01-2 2h-2m-2 0H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-4m-6 0v10"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Carpenter' : 'کارپینٹر / بڑھئی' }}</span>
                            </button>
                            <!-- Painter -->
                            <button type="button" id="btn-trade-painter" wire:click="setTrade('Painter')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Painter' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-9m-4-6h13"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Painter' : 'رنگ ساز / پینٹر' }}</span>
                            </button>
                            <!-- HVAC Technician -->
                            <button type="button" id="btn-trade-hvac-technician" wire:click="setTrade('HVAC Technician')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'HVAC Technician' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l-.707-.707"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'HVAC Technician' : 'اے سی ٹیکنیشن' }}</span>
                            </button>
                            <!-- Auto Mechanic -->
                            <button type="button" id="btn-trade-auto-mechanic" wire:click="setTrade('Auto Mechanic')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Auto Mechanic' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Auto Mechanic' : 'آٹو مکینک' }}</span>
                            </button>
                            <!-- Steel Fixer -->
                            <button type="button" id="btn-trade-steel-fixer" wire:click="setTrade('Steel Fixer')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Steel Fixer' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Steel Fixer' : 'سٹیل فکسر' }}</span>
                            </button>
                            <!-- Scaffolder -->
                            <button type="button" id="btn-trade-scaffolder" wire:click="setTrade('Scaffolder')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Scaffolder' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1v-2zM9 8v4m6-4v4"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Scaffolder' : 'سکیف ہولڈر' }}</span>
                            </button>
                            <!-- Crane Operator -->
                            <button type="button" id="btn-trade-crane-operator" wire:click="setTrade('Crane Operator')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Crane Operator' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Crane Operator' : 'کرین آپریٹر' }}</span>
                            </button>
                            <!-- Pipe Fitter -->
                            <button type="button" id="btn-trade-pipe-fitter" wire:click="setTrade('Pipe Fitter')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Pipe Fitter' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Pipe Fitter' : 'پائپ فٹر' }}</span>
                            </button>
                            <!-- Machinist -->
                            <button type="button" id="btn-trade-machinist" wire:click="setTrade('Machinist')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Machinist' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Machinist' : 'مشینسٹ' }}</span>
                            </button>
                            <!-- Boiler Operator -->
                            <button type="button" id="btn-trade-boiler-operator" wire:click="setTrade('Boiler Operator')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Boiler Operator' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Boiler Operator' : 'بوائلر آپریٹر' }}</span>
                            </button>
                            <!-- Quality Control Inspector -->
                            <button type="button" id="btn-trade-qc-inspector" wire:click="setTrade('Quality Control Inspector')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Quality Control Inspector' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'QC Inspector' : 'کوالٹی انسپکٹر' }}</span>
                            </button>
                            <!-- Office Assistant -->
                            <button type="button" id="btn-trade-office-assistant" wire:click="setTrade('Office Assistant')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Office Assistant' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Office Assistant' : 'آفس اسسٹنٹ' }}</span>
                            </button>
                            <!-- Office Boy -->
                            <button type="button" id="btn-trade-office-boy-ind" wire:click="setTrade('Office Boy')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Office Boy' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Office Boy' : 'آفس بوائے / چپڑاسی' }}</span>
                            </button>
                            <!-- Receptionist -->
                            <button type="button" id="btn-trade-receptionist" wire:click="setTrade('Receptionist')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Receptionist' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Receptionist' : 'ریسپشنسٹ' }}</span>
                            </button>
                            <!-- Record Keeper -->
                            <button type="button" id="btn-trade-record-keeper" wire:click="setTrade('Record Keeper')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Record Keeper' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Record Keeper' : 'ریکارد کیپر' }}</span>
                            </button>
                            <!-- Dispatch Rider -->
                            <button type="button" id="btn-trade-dispatch-rider" wire:click="setTrade('Dispatch Rider')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Dispatch Rider' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Dispatch Rider' : 'ڈسپیچ رائڈر' }}</span>
                            </button>
                            <!-- Helper -->
                            <button type="button" id="btn-trade-helper" wire:click="setTrade('Helper')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Helper' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Helper' : 'مددگار / ہیلپر' }}</span>
                            </button>
                            <!-- Other -->
                            <button type="button" id="btn-trade-other-ind" wire:click="setTrade('Other')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Other' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Other' : 'دیگر (ہنر لکھیں)' }}</span>
                            </button>
                        @elseif ($sector === 'Domestic')
                            <!-- Cook -->
                            <button type="button" id="btn-trade-cook" wire:click="setTrade('Cook')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Cook' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Cook' : 'باورچی' }}</span>
                            </button>
                            <!-- Maid -->
                            <button type="button" id="btn-trade-maid" wire:click="setTrade('Maid')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Maid' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Maid' : 'ملازمہ' }}</span>
                            </button>
                            <!-- Data Entry Operator -->
                            <button type="button" id="btn-trade-data-entry" wire:click="setTrade('Data Entry Operator')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Data Entry Operator' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Data Entry Operator' : 'ڈیٹا انٹری آپریٹر' }}</span>
                            </button>
                            <!-- Driver -->
                            <button type="button" id="btn-trade-driver" wire:click="setTrade('Driver')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Driver' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Driver' : 'ڈرائیور' }}</span>
                            </button>
                            <!-- Nanny -->
                            <button type="button" id="btn-trade-nanny" wire:click="setTrade('Nanny')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Nanny' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Nanny' : 'آیا / نینی' }}</span>
                            </button>
                            <!-- Gardener -->
                            <button type="button" id="btn-trade-gardener" wire:click="setTrade('Gardener')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Gardener' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Gardener' : 'مالی' }}</span>
                            </button>
                            <!-- Tailor -->
                            <button type="button" id="btn-trade-tailor" wire:click="setTrade('Tailor')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Tailor' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-4.879-4.879L12 12m2.121 2.121a3 3 0 11-4.242-4.242 3 3 0 014.242 4.242zM8 8L3 3m5 5L6 6m2 2l-2 2M3 21l1.9-5.7a3 3 0 015.68 0L12.5 21H3z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Tailor' : 'درزی' }}</span>
                            </button>
                            <!-- Laundry Man -->
                            <button type="button" id="btn-trade-laundry-man" wire:click="setTrade('Laundry Man')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Laundry Man' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-14v14m0-14L4 7m8 4v14M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Laundry Man' : 'دھوبی' }}</span>
                            </button>
                            <!-- Watchman -->
                            <button type="button" id="btn-trade-watchman" wire:click="setTrade('Watchman')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Watchman' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Watchman' : 'چوکیدار' }}</span>
                            </button>
                            <!-- Delivery Rider -->
                            <button type="button" id="btn-trade-delivery-rider" wire:click="setTrade('Delivery Rider')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Delivery Rider' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Delivery Rider' : 'ڈلیوری رائڈر' }}</span>
                            </button>
                            <!-- Sweeper -->
                            <button type="button" id="btn-trade-sweeper" wire:click="setTrade('Sweeper')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Sweeper' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 00-2 2v3m2 3h.01"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Sweeper' : 'صفائی والا / خاکروب' }}</span>
                            </button>
                            <!-- Office Boy -->
                            <button type="button" id="btn-trade-office-boy-dom" wire:click="setTrade('Office Boy')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Office Boy' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ $lang === 'en' ? 'Office Boy' : 'آفس بوائے / چپڑاسی' }}</span>
                            </button>
                            <!-- Other -->
                            <button type="button" id="btn-trade-other-dom" wire:click="setTrade('Other')" 
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border cursor-pointer transition-all bg-slate-950/30 hover:bg-slate-900/60 {{ $this->skill_category === 'Other' ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-500/5' : 'border-slate-850' }}">
                                <div class="h-10 w-10 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <span class="font-bold text-xs text-slate-200 text-center">{{ __('Other') }}</span>
                            </button>
                        @endif
                    </div>

                    @if ($skill_category === 'Other')
                        <div class="mt-4">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                                {{ __('Specify Your Trade') }}
                            </label>
                            <input wire:model="custom_trade" id="worker-custom-trade" type="text" placeholder="{{ __('e.g. CNC Operator / Record Clerk') }}" 
                                   class="w-full px-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm font-medium" />
                            @error('custom_trade')
                                <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                    @error('skill_category')
                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            @endif

            <!-- 4. District Selector (KP Divisions Matrix) -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                    {{ __('District (KP Region)') }}
                </label>
                <select wire:model="district" id="worker-district" 
                        class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm font-medium">
                    <option value="">-- {{ __('Select District') }} --</option>
                    
                    <optgroup label="{{ __('Peshawar Division') }}">
                        <option value="Peshawar">{{ __('Peshawar') }}</option>
                        <option value="Nowshera">{{ __('Nowshera') }}</option>
                        <option value="Charsadda">{{ __('Charsadda') }}</option>
                        <option value="Khyber">{{ __('Khyber') }}</option>
                        <option value="Mohmand">{{ __('Mohmand') }}</option>
                    </optgroup>
                    
                    <optgroup label="{{ __('Mardan Division') }}">
                        <option value="Mardan">{{ __('Mardan') }}</option>
                        <option value="Swabi">{{ __('Swabi') }}</option>
                    </optgroup>
                    
                    <optgroup label="{{ __('Malakand Division') }}">
                        <option value="Swat">{{ __('Swat') }}</option>
                        <option value="Buner">{{ __('Buner') }}</option>
                        <option value="Shangla">{{ __('Shangla') }}</option>
                        <option value="Malakand">{{ __('Malakand') }}</option>
                        <option value="Bajaur">{{ __('Bajaur') }}</option>
                        <option value="Lower Dir">{{ __('Lower Dir') }}</option>
                        <option value="Upper Dir">{{ __('Upper Dir') }}</option>
                    </optgroup>
                    
                    <optgroup label="{{ __('Hazara Division') }}">
                        <option value="Abbottabad">{{ __('Abbottabad') }}</option>
                        <option value="Haripur">{{ __('Haripur') }}</option>
                        <option value="Mansehra">{{ __('Mansehra') }}</option>
                        <option value="Battagram">{{ __('Battagram') }}</option>
                        <option value="Torghar">{{ __('Torghar') }}</option>
                        <option value="Kohistan">{{ __('Kohistan') }}</option>
                    </optgroup>
                </select>
                @error('district')
                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Age Selection -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                    {{ __('Age (Years)') }}
                </label>
                <input wire:model="age" id="worker-age" type="number" 
                       class="w-full px-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm font-medium" />
                @error('age')
                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- 5. Experience Years (Interactive Increment/Decrement Counter) -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">
                    {{ __('Years of Experience') }}
                </label>
                <div class="flex items-center gap-4">
                    <button type="button" id="btn-experience-decrement" wire:click="decrementExperience" 
                            class="h-12 w-12 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 hover:text-white font-extrabold text-lg flex items-center justify-center transition-all hover:bg-slate-900 active:scale-95 cursor-pointer">
                        -
                    </button>
                    
                    <div class="w-20 text-center font-extrabold text-2xl text-indigo-400 bg-slate-950/40 py-2 border border-slate-850 rounded-xl">
                        {{ $experience_years }}
                    </div>

                    <button type="button" id="btn-experience-increment" wire:click="incrementExperience" 
                            class="h-12 w-12 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 hover:text-white font-extrabold text-lg flex items-center justify-center transition-all hover:bg-slate-900 active:scale-95 cursor-pointer">
                        +
                    </button>
                    
                    <span class="text-xs text-slate-500 font-medium select-none">
                        {{ __('years') }}
                    </span>
                </div>
                @error('experience_years')
                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- 6. Toggle Availability -->
            <div class="flex items-center justify-between p-4 bg-slate-950/50 border border-slate-850 rounded-xl">
                <div>
                    <span class="font-bold text-sm text-slate-200 block">
                        {{ __('Available for immediate work?') }}
                    </span>
                    <span class="text-xs text-slate-500">
                        {{ __('Uncheck if you are currently busy.') }}
                    </span>
                </div>
                <button type="button" id="btn-availability-toggle" wire:click="toggleAvailability" 
                        class="h-7 w-12 rounded-full relative transition-colors duration-200 ease-in-out cursor-pointer {{ $is_available ? 'bg-indigo-500' : 'bg-slate-800' }}">
                    <span class="h-5 w-5 rounded-full bg-slate-950 absolute top-1 transition-all duration-200 ease-in-out {{ in_array($lang, ['ur', 'ps']) ? ($is_available ? 'right-1' : 'right-6') : ($is_available ? 'left-1' : 'left-6') }}"></span>
                </button>
            </div>

            <!-- 7. Save Button -->
            <div>
                <button type="submit" id="btn-worker-submit" 
                        class="w-full py-4 rounded-xl font-extrabold text-base text-slate-950 bg-gradient-to-r from-indigo-500 to-violet-500 hover:opacity-90 shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.99] transition-all cursor-pointer">
                    {{ $lang === 'en' ? 'Save Profile' : 'پروفائل محفوظ کریں' }}
                </button>
            </div>
        </form>
        @endif
    </div>
</div>
