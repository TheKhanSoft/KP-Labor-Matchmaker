<?php
use Livewire\Component;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    // Tab state
    public string $activeTab = 'branding';

    // Bindable settings keys
    public bool $allow_domestic_sector = true;
    public bool $allow_worker_registration = true;
    public bool $require_employer_approval = true;
    public int $reveal_credit_cost = 1;
    public int $default_welcome_credits = 5;
    public string $support_phone = '';
    public string $support_email = '';
    public string $support_address = '';

    // Credit pricing policy configurations
    public string $credit_pricing_mode = 'flat';
    public int $credit_flat_rate = 20;
    public array $credit_pricing_tiers = [];

    // General branding settings
    public string $site_name = '';
    public bool $enable_maintenance_banner = false;
    public string $maintenance_message = '';
    public int $min_worker_age = 18;
    public int $max_worker_age = 60;
    public int $max_free_jobs = 5;
    public int $tax_rate = 0;
    public string $currency_code = 'PKR';

    // In-depth configuration options
    public string $site_short_name = '';
    public string $site_description = '';
    public string $site_keywords = '';
    public string $google_analytics_id = '';
    public string $footer_copyright_text = '';
    public string $logo_text = '';
    public int $items_per_page = 10;
    public bool $maintenance_mode = false;

    // Advanced dynamic settings
    public string $timezone = 'Asia/Karachi';
    
    // Social Links (dropdown based, store username/platform)
    public array $social_links = [];

    // Payment Methods variables
    public bool $pm_bank_enabled = true;
    public array $pm_bank_accounts = [];
    public bool $pm_easypaisa_enabled = true;
    public array $pm_easypaisa_accounts = [];
    public bool $pm_jazzcash_enabled = true;
    public array $pm_jazzcash_accounts = [];
    public bool $pm_crypto_enabled = true;
    public array $pm_crypto_accounts = [];
    public bool $pm_paypal_enabled = false;
    public array $pm_paypal_accounts = [];

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
        $this->checkAdmin();
        $this->loadSettings();

        $urlTab = request()->query('tab');
        if ($urlTab && in_array($urlTab, ['branding', 'localization', 'security', 'pricing', 'social', 'payments', 'helpline'])) {
            $this->activeTab = $urlTab;
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function loadSettings(): void
    {
        $this->allow_domestic_sector = Setting::get('allow_domestic_sector', true);
        $this->allow_worker_registration = Setting::get('allow_worker_registration', true);
        $this->require_employer_approval = Setting::get('require_employer_approval', true);
        $this->reveal_credit_cost = Setting::get('reveal_credit_cost', 1);
        $this->default_welcome_credits = Setting::get('default_welcome_credits', 5);
        $this->support_phone = Setting::get('support_phone', '091-9210401');
        $this->support_email = Setting::get('support_email', 'support.labor@kp.gov.pk');
        $this->support_address = Setting::get('support_address', 'Directorate of Labor, Peshawar');

        // Credit pricing
        $this->credit_pricing_mode = Setting::get('credit_pricing_mode', 'flat');
        $this->credit_flat_rate = (int)Setting::get('credit_flat_rate', 20);
        $tiersJson = Setting::get('credit_pricing_tiers', '[]');
        $this->credit_pricing_tiers = json_decode($tiersJson, true) ?: [];

        // Branding
        $this->site_name = Setting::get('site_name', 'KP Labor Matchmaker');
        $this->enable_maintenance_banner = Setting::get('enable_maintenance_banner', false);
        $this->maintenance_message = Setting::get('maintenance_message', 'Scheduled system maintenance is currently underway.');
        $this->min_worker_age = (int)Setting::get('min_worker_age', 18);
        $this->max_worker_age = (int)Setting::get('max_worker_age', 60);
        $this->max_free_jobs = (int)Setting::get('max_free_jobs', 5);
        $this->tax_rate = (int)Setting::get('tax_rate', 0);
        $this->currency_code = Setting::get('currency_code', 'PKR');

        // Configs
        $this->site_short_name = Setting::get('site_short_name', 'KP-LM');
        $this->site_description = Setting::get('site_description', 'Official Khyber Pakhtunkhwa Labor Matchmaking Platform. Connect directly with skilled workers across KP districts.');
        $this->site_keywords = Setting::get('site_keywords', 'Khyber Pakhtunkhwa, KP, Labor Matchmaking, Hire Workers, Job Portal');
        $this->google_analytics_id = Setting::get('google_analytics_id', 'G-XXXXXXXXXX');
        $this->footer_copyright_text = Setting::get('footer_copyright_text', 'Khyber Pakhtunkhwa Labor Matchmaking Platform. Confidential - Government of KP');
        $this->logo_text = Setting::get('logo_text', 'KP LABOR');
        $this->items_per_page = (int)Setting::get('items_per_page', 10);
        $this->maintenance_mode = Setting::get('maintenance_mode', false);

        // Advanced Settings
        $this->timezone = Setting::get('timezone', 'Asia/Karachi');
        $socialLinksJson = Setting::get('social_links', '[]');
        $this->social_links = json_decode($socialLinksJson, true) ?: [];

        // Load Payment Methods Nested Accounts
        $paymentMethodsJson = Setting::get('payment_methods', '[]');
        $pms = json_decode($paymentMethodsJson, true) ?: [];

        $this->pm_bank_enabled = false;
        $this->pm_bank_accounts = [];
        $this->pm_easypaisa_enabled = false;
        $this->pm_easypaisa_accounts = [];
        $this->pm_jazzcash_enabled = false;
        $this->pm_jazzcash_accounts = [];
        $this->pm_crypto_enabled = false;
        $this->pm_crypto_accounts = [];
        $this->pm_paypal_enabled = false;
        $this->pm_paypal_accounts = [];

        foreach ($pms as $pm) {
            $name = strtolower($pm['name']);
            $enabled = (bool)($pm['enabled'] ?? false);
            $accounts = $pm['accounts'] ?? [];

            if ($name === 'bank') {
                $this->pm_bank_enabled = $enabled;
                $this->pm_bank_accounts = $accounts;
            } elseif ($name === 'easypaisa') {
                $this->pm_easypaisa_enabled = $enabled;
                $this->pm_easypaisa_accounts = $accounts;
            } elseif ($name === 'jazzcash') {
                $this->pm_jazzcash_enabled = $enabled;
                $this->pm_jazzcash_accounts = $accounts;
            } elseif ($name === 'crypto') {
                $this->pm_crypto_enabled = $enabled;
                $this->pm_crypto_accounts = $accounts;
            } elseif ($name === 'paypal') {
                $this->pm_paypal_enabled = $enabled;
                $this->pm_paypal_accounts = $accounts;
            }
        }
    }

    // Pricing Tier
    public function addPricingTier(): void
    {
        $this->credit_pricing_tiers[] = ['min' => 10, 'price' => 18];
    }

    public function removePricingTier(int $index): void
    {
        unset($this->credit_pricing_tiers[$index]);
        $this->credit_pricing_tiers = array_values($this->credit_pricing_tiers);
    }

    // Social Links: Dropbox + Username
    public function addSocialLink(): void
    {
        $this->social_links[] = ['platform' => 'Facebook', 'username' => ''];
    }

    public function removeSocialLink(int $index): void
    {
        unset($this->social_links[$index]);
        $this->social_links = array_values($this->social_links);
    }

    // Payment Methods dynamically managed groups
    public function addBankAccount(): void
    {
        $this->pm_bank_accounts[] = ['bank_name' => '', 'title' => '', 'number' => '', 'branch' => ''];
    }
    public function removeBankAccount(int $index): void
    {
        unset($this->pm_bank_accounts[$index]);
        $this->pm_bank_accounts = array_values($this->pm_bank_accounts);
    }

    public function addEasyPaisaAccount(): void
    {
        $this->pm_easypaisa_accounts[] = ['title' => '', 'number' => ''];
    }
    public function removeEasyPaisaAccount(int $index): void
    {
        unset($this->pm_easypaisa_accounts[$index]);
        $this->pm_easypaisa_accounts = array_values($this->pm_easypaisa_accounts);
    }

    public function addJazzCashAccount(): void
    {
        $this->pm_jazzcash_accounts[] = ['title' => '', 'number' => ''];
    }
    public function removeJazzCashAccount(int $index): void
    {
        unset($this->pm_jazzcash_accounts[$index]);
        $this->pm_jazzcash_accounts = array_values($this->pm_jazzcash_accounts);
    }

    public function addCryptoAccount(): void
    {
        $this->pm_crypto_accounts[] = ['network' => 'USDT (TRC20)', 'address' => ''];
    }
    public function removeCryptoAccount(int $index): void
    {
        unset($this->pm_crypto_accounts[$index]);
        $this->pm_crypto_accounts = array_values($this->pm_crypto_accounts);
    }

    public function addPayPalAccount(): void
    {
        $this->pm_paypal_accounts[] = ['email' => ''];
    }
    public function removePayPalAccount(int $index): void
    {
        unset($this->pm_paypal_accounts[$index]);
        $this->pm_paypal_accounts = array_values($this->pm_paypal_accounts);
    }

    public function saveSettings(): void
    {
        $this->checkAdmin();

        $rules = [
            'reveal_credit_cost' => 'required|integer|min:0',
            'default_welcome_credits' => 'required|integer|min:0',
            'support_phone' => 'required|string',
            'support_email' => 'required|email',
            'support_address' => 'required|string',
            'credit_pricing_mode' => 'required|string|in:flat,tiered,cumulative',
            'credit_flat_rate' => 'required|integer|min:1',
            'credit_pricing_tiers' => 'array',
            'credit_pricing_tiers.*.min' => 'required|integer|min:1',
            'credit_pricing_tiers.*.price' => 'required|integer|min:1',
            
            'site_name' => 'required|string|max:100',
            'enable_maintenance_banner' => 'boolean',
            'maintenance_message' => 'required|string|max:255',
            'min_worker_age' => 'required|integer|min:15|max:100',
            'max_worker_age' => 'required|integer|min:18|max:120',
            'max_free_jobs' => 'required|integer|min:1',
            'tax_rate' => 'required|integer|min:0|max:100',
            'currency_code' => 'required|string|min:2|max:5',
            
            'site_short_name' => 'required|string|max:20',
            'site_description' => 'required|string|max:500',
            'site_keywords' => 'required|string|max:500',
            'google_analytics_id' => 'nullable|string|max:50',
            'footer_copyright_text' => 'required|string|max:255',
            'logo_text' => 'required|string|max:50',
            'items_per_page' => 'required|integer|min:5|max:100',
            'maintenance_mode' => 'boolean',
            'timezone' => 'required|string|max:100',
            
            'social_links' => 'array',
            'social_links.*.platform' => 'required|string|max:50',
            'social_links.*.username' => 'required|string|max:100',
            
            'pm_bank_enabled' => 'boolean',
            'pm_bank_accounts' => 'array',
            'pm_easypaisa_enabled' => 'boolean',
            'pm_easypaisa_accounts' => 'array',
            'pm_jazzcash_enabled' => 'boolean',
            'pm_jazzcash_accounts' => 'array',
            'pm_crypto_enabled' => 'boolean',
            'pm_crypto_accounts' => 'array',
            'pm_paypal_enabled' => 'boolean',
            'pm_paypal_accounts' => 'array'
        ];

        $this->validate($rules);

        Setting::set('allow_domestic_sector', $this->allow_domestic_sector);
        Setting::set('allow_worker_registration', $this->allow_worker_registration);
        Setting::set('require_employer_approval', $this->require_employer_approval);
        Setting::set('reveal_credit_cost', $this->reveal_credit_cost);
        Setting::set('default_welcome_credits', $this->default_welcome_credits);
        Setting::set('support_phone', $this->support_phone);
        Setting::set('support_email', $this->support_email);
        Setting::set('support_address', $this->support_address);

        // Credit pricing
        Setting::set('credit_pricing_mode', $this->credit_pricing_mode);
        Setting::set('credit_flat_rate', $this->credit_flat_rate);
        
        $tiers = $this->credit_pricing_tiers;
        usort($tiers, function ($a, $b) {
            return (int)$a['min'] <=> (int)$b['min'];
        });
        Setting::set('credit_pricing_tiers', json_encode($tiers));

        // Save settings
        Setting::set('site_name', $this->site_name);
        Setting::set('enable_maintenance_banner', $this->enable_maintenance_banner);
        Setting::set('maintenance_message', $this->maintenance_message);
        Setting::set('min_worker_age', $this->min_worker_age);
        Setting::set('max_worker_age', $this->max_worker_age);
        Setting::set('max_free_jobs', $this->max_free_jobs);
        Setting::set('tax_rate', $this->tax_rate);
        Setting::set('currency_code', $this->currency_code);

        // Save in-depth configurations
        Setting::set('site_short_name', $this->site_short_name);
        Setting::set('site_description', $this->site_description);
        Setting::set('site_keywords', $this->site_keywords);
        Setting::set('google_analytics_id', $this->google_analytics_id);
        Setting::set('footer_copyright_text', $this->footer_copyright_text);
        Setting::set('logo_text', $this->logo_text);
        Setting::set('items_per_page', $this->items_per_page);
        Setting::set('maintenance_mode', $this->maintenance_mode);

        // Advanced
        Setting::set('timezone', $this->timezone);
        Setting::set('social_links', json_encode(array_values($this->social_links)));

        // Combine Payment Gateways accounts array
        $methods = [
            [
                'name' => 'Bank',
                'title' => 'Bank Wire Transfer',
                'enabled' => $this->pm_bank_enabled,
                'accounts' => array_values($this->pm_bank_accounts)
            ],
            [
                'name' => 'EasyPaisa',
                'title' => 'EasyPaisa Mobile Wallet',
                'enabled' => $this->pm_easypaisa_enabled,
                'accounts' => array_values($this->pm_easypaisa_accounts)
            ],
            [
                'name' => 'JazzCash',
                'title' => 'JazzCash Mobile Wallet',
                'enabled' => $this->pm_jazzcash_enabled,
                'accounts' => array_values($this->pm_jazzcash_accounts)
            ],
            [
                'name' => 'Crypto',
                'title' => 'Crypto Wallet',
                'enabled' => $this->pm_crypto_enabled,
                'accounts' => array_values($this->pm_crypto_accounts)
            ],
            [
                'name' => 'PayPal',
                'title' => 'PayPal Account',
                'enabled' => $this->pm_paypal_enabled,
                'accounts' => array_values($this->pm_paypal_accounts)
            ]
        ];

        Setting::set('payment_methods', json_encode($methods));

        $this->logActivity('settings_updated', "Updated tabbed system configurations and active payment gateway list");
        session()->flash('success', "Settings saved successfully.");
    }
};
?>

<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-sm">
        <h1 class="text-xl font-black text-white">Application Configuration</h1>
        <p class="text-xs text-slate-500 mt-1">Configure global branding, security triggers, pricing algorithms, localization settings, and Helpline managers.</p>
    </div>

    <!-- Feedback Alerts -->
    @if (session()->has('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-455 font-semibold text-center text-xs flex items-center justify-center gap-2 shadow-sm">
            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-455 font-semibold text-xs shadow-sm">
            <div class="font-bold mb-1">Please fix the validation errors:</div>
            <ul class="list-disc list-inside text-[11px] text-rose-400 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="saveSettings" class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Settings Left Sub-navigation Tabs -->
        <div class="md:col-span-1 flex flex-col gap-1.5">
            @php
                $tabs = [
                    ['id' => 'branding', 'label' => 'Branding & SEO', 'icon' => '🎨'],
                    ['id' => 'localization', 'label' => 'Localization', 'icon' => '🌍'],
                    ['id' => 'security', 'label' => 'Security & Signup', 'icon' => '🔒'],
                    ['id' => 'pricing', 'label' => 'Pricing Engine', 'icon' => '💳'],
                    ['id' => 'social', 'label' => 'Social Channels', 'icon' => '📣'],
                    ['id' => 'payments', 'label' => 'Payment Gateways', 'icon' => '🏦'],
                    ['id' => 'helpline', 'label' => 'Helpline Contacts', 'icon' => '📞'],
                ];
            @endphp
            @foreach($tabs as $tab)
                <button type="button" wire:click="setTab('{{ $tab['id'] }}')"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl text-left text-xs font-bold transition-all duration-150 cursor-pointer border {{ $activeTab === $tab['id'] ? 'bg-indigo-500/10 border-indigo-500/35 text-indigo-400 shadow-sm' : 'bg-slate-900 border-slate-800 text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                    <span class="text-sm bg-slate-850 p-1 rounded-lg">{{ $tab['icon'] }}</span>
                    <span>{{ $tab['label'] }}</span>
                </button>
            @endforeach


        </div>

        <!-- Settings Config panel Column -->
        <div class="md:col-span-3">
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-sm min-h-[450px]">
                
                <!-- Tab 1: Branding & SEO -->
                @if ($activeTab === 'branding')
                <div class="space-y-6">
                    <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-slate-800 pb-2">Branding & Search Engine Optimization</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Portal Full Title</label>
                            <input wire:model="site_name" type="text" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Portal Short Title</label>
                            <input wire:model="site_short_name" type="text" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Logo Brand Text</label>
                            <input wire:model="logo_text" type="text" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Items Per Page Pagination</label>
                            <input wire:model="items_per_page" type="number" min="5" max="100" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">SEO Meta Keywords</label>
                        <input wire:model="site_keywords" type="text" placeholder="welder, cook, hiring, kp" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                    </div>

                    <div>
                        <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">SEO Meta Description</label>
                        <textarea wire:model="site_description" rows="3" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold"></textarea>
                    </div>

                    <div>
                        <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Footer Copyright Text</label>
                        <input wire:model="footer_copyright_text" type="text" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                    </div>
                </div>
                @endif

                <!-- Tab 2: Localization -->
                @if ($activeTab === 'localization')
                <div class="space-y-6">
                    <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-slate-800 pb-2">Localization & Timezone</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">System Timezone</label>
                            <select wire:model="timezone" class="w-full px-3 py-2.5 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-bold">
                                <option value="Asia/Karachi">Asia/Karachi (Pakistan Standard Time)</option>
                                <option value="Asia/Kabul">Asia/Kabul (Afghanistan Time)</option>
                                <option value="Asia/Dubai">Asia/Dubai (Gulf Standard Time)</option>
                                <option value="Asia/Riyadh">Asia/Riyadh (Arabia Standard Time)</option>
                                <option value="Asia/Qatar">Asia/Qatar (Qatar Time)</option>
                                <option value="Asia/Dhaka">Asia/Dhaka (Bangladesh Time)</option>
                                <option value="Asia/Kolkata">Asia/Kolkata (India Standard Time)</option>
                                <option value="Europe/London">Europe/London (Greenwich Mean Time / BST)</option>
                                <option value="America/New_York">America/New_York (Eastern Standard Time)</option>
                                <option value="UTC">UTC (Coordinated Universal Time)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Currency Code</label>
                            <input wire:model="currency_code" type="text" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-bold uppercase" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Google Analytics Tracking ID</label>
                        <input wire:model="google_analytics_id" type="text" placeholder="G-XXXXXXXXXX" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                    </div>
                </div>
                @endif

                <!-- Tab 3: Security & Signup -->
                @if ($activeTab === 'security')
                <div class="space-y-6">
                    <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-slate-800 pb-2">Access Rules & Signup Controls</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-850 border border-slate-800 rounded-xl flex items-center justify-between gap-4">
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-slate-200 block">Allow Domestic Trades</span>
                                <span class="text-[9px] text-slate-500 leading-none">Domestic cook/maid registrations.</span>
                            </div>
                            <button type="button" wire:click="$toggle('allow_domestic_sector')" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $allow_domestic_sector ? 'bg-indigo-600' : 'bg-slate-800' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $allow_domestic_sector ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </div>

                        <div class="p-4 bg-slate-850 border border-slate-800 rounded-xl flex items-center justify-between gap-4">
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-slate-200 block">Worker Registration Intake</span>
                                <span class="text-[9px] text-slate-500 leading-none">Allow new worker signup submissions.</span>
                            </div>
                            <button type="button" wire:click="$toggle('allow_worker_registration')" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $allow_worker_registration ? 'bg-indigo-600' : 'bg-slate-800' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $allow_worker_registration ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-850 border border-slate-800 rounded-xl flex items-center justify-between gap-4">
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-slate-200 block">Verify Employer Accounts</span>
                                <span class="text-[9px] text-slate-500 leading-none">Manually approve employers before unlocks.</span>
                            </div>
                            <button type="button" wire:click="$toggle('require_employer_approval')" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $require_employer_approval ? 'bg-indigo-600' : 'bg-slate-800' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $require_employer_approval ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </div>

                        <div class="p-4 bg-slate-850 border border-slate-800 rounded-xl flex items-center justify-between gap-4">
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-rose-455 block">Full Portal Maintenance Mode</span>
                                <span class="text-[9px] text-slate-500 leading-none">Locks public frontend visitors out.</span>
                            </div>
                            <button type="button" wire:click="$toggle('maintenance_mode')" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $maintenance_mode ? 'bg-rose-500' : 'bg-slate-800' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $maintenance_mode ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </div>
                    </div>

                    <div class="p-4 bg-slate-850 border border-slate-800 rounded-xl flex items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <span class="text-xs font-bold text-slate-200 block">Display Maintenance Warning banner</span>
                            <span class="text-[9px] text-slate-500 leading-none">Sticky alert ribbon shown to visitors.</span>
                        </div>
                        <button type="button" wire:click="$toggle('enable_maintenance_banner')" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $enable_maintenance_banner ? 'bg-indigo-600' : 'bg-slate-800' }}">
                            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $enable_maintenance_banner ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                    </div>

                    <div>
                        <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Maintenance Warning Text Message</label>
                        <input wire:model="maintenance_message" type="text" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Min Worker Age</label>
                            <input wire:model="min_worker_age" type="number" min="15" max="100" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Max Worker Age</label>
                            <input wire:model="max_worker_age" type="number" min="18" max="120" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Max Free Jobs Postings</label>
                            <input wire:model="max_free_jobs" type="number" min="1" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                    </div>
                </div>
                @endif

                <!-- Tab 4: Pricing Engine -->
                @if ($activeTab === 'pricing')
                <div class="space-y-6">
                    <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-slate-800 pb-2">Credits & Pricing Algorithms</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Credits Cost Per Reveal</label>
                            <input wire:model="reveal_credit_cost" type="number" min="0" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Welcome Free Credits</label>
                            <input wire:model="default_welcome_credits" type="number" min="0" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Recharge Tax Rate (%)</label>
                            <input wire:model="tax_rate" type="number" min="0" max="100" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Purchase Pricing Mode</label>
                        <select wire:model.live="credit_pricing_mode" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-bold">
                            <option value="flat">Fixed / Flat Pricing (Constant rate per credit)</option>
                            <option value="tiered">Bulk Pricing (Monotonic volume discount tiers)</option>
                            <option value="cumulative">Cumulative Pricing (Graduated bracketed rates)</option>
                        </select>
                    </div>

                    @if ($credit_pricing_mode === 'flat')
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Flat Rate Price per Credit ({{ $currency_code }})</label>
                            <input wire:model="credit_flat_rate" type="number" min="1" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                    @else
                        <div class="space-y-3.5">
                            <div class="flex justify-between items-center bg-slate-850/60 p-4 border border-slate-800/80 rounded-2xl">
                                <div class="space-y-0.5">
                                    <label class="block text-xs font-extrabold text-slate-200">
                                        @if ($credit_pricing_mode === 'cumulative')
                                            Cumulative Pricing Brackets
                                        @else
                                            Bulk Pricing Tiers
                                        @endif
                                    </label>
                                    <span class="text-[9px] text-slate-500 block leading-normal">
                                        @if ($credit_pricing_mode === 'cumulative')
                                            Define brackets where rates apply only to units inside that range (e.g. 1-20 at flat rate, 21-49 at Tier 1 rate). This ensures monotonic price progression.
                                        @else
                                            Define tiers where reaching a threshold discounts the unit rate of the entire purchase package. Monotonic floor logic prevents price drop drops for larger purchases.
                                        @endif
                                    </span>
                                </div>
                                <button type="button" wire:click="addPricingTier" class="px-2.5 py-1.5 rounded-lg bg-indigo-500/15 border border-indigo-500/25 text-indigo-400 text-[10px] font-bold hover:bg-indigo-500/25 cursor-pointer">
                                    + Add Tier
                                </button>
                            </div>
                            
                            <div class="space-y-3">
                                @foreach ($credit_pricing_tiers as $index => $tier)
                                    <div class="flex items-center gap-4 bg-slate-850 border border-slate-800 p-4 rounded-xl">
                                        <div class="flex-grow grid grid-cols-2 gap-4">
                                            <div>
                                                <span class="block text-[8px] uppercase font-bold text-slate-500 mb-1">
                                                    @if ($credit_pricing_mode === 'cumulative')
                                                        Bracket Minimum Credits
                                                    @else
                                                        Minimum Purchased Credits
                                                    @endif
                                                </span>
                                                <input wire:model="credit_pricing_tiers.{{ $index }}.min" type="number" required min="1" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-slate-200 focus:outline-none text-xs font-semibold" />
                                            </div>
                                            <div>
                                                <span class="block text-[8px] uppercase font-bold text-slate-500 mb-1">Rate Price ({{ $currency_code }} / Credit)</span>
                                                <input wire:model="credit_pricing_tiers.{{ $index }}.price" type="number" required min="1" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-slate-200 focus:outline-none text-xs font-semibold" />
                                            </div>
                                        </div>
                                        <button type="button" wire:click="removePricingTier({{ $index }})" class="p-2 rounded bg-rose-500/10 border border-rose-500/20 text-rose-455 hover:bg-rose-500/20 text-xs font-bold mt-4 cursor-pointer">
                                            ✕
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                @endif

                <!-- Tab 5: Social Channels -->
                @if ($activeTab === 'social')
                <div class="space-y-6">
                    <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-slate-800 pb-2">Dynamic Social Media Links</h3>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div class="space-y-0.5">
                                <span class="text-xs font-bold text-slate-200 block">Manage Footer Social links</span>
                                <span class="text-[9px] text-slate-500">Pick a famous platform, only type handle/username. We build prefix URLs automatically.</span>
                            </div>
                            <button type="button" wire:click="addSocialLink" class="px-2.5 py-1.5 rounded-lg bg-indigo-500/15 border border-indigo-500/25 text-indigo-400 text-[10px] font-bold hover:bg-indigo-500/25 cursor-pointer">
                                + Add Social Channel
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($social_links as $index => $sl)
                                <div class="flex items-center gap-3 bg-slate-850 border border-slate-800 p-4 rounded-xl relative">
                                    <div class="flex-grow grid grid-cols-3 gap-3">
                                        <div class="col-span-1">
                                            <span class="block text-[8px] uppercase font-bold text-slate-500 mb-1">Platform</span>
                                            <select wire:model="social_links.{{ $index }}.platform" required
                                                    class="w-full px-2 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-slate-200 focus:outline-none text-xs font-bold">
                                                <option value="Facebook">Facebook</option>
                                                <option value="Twitter">Twitter / X</option>
                                                <option value="LinkedIn">LinkedIn</option>
                                                <option value="Instagram">Instagram</option>
                                                <option value="YouTube">YouTube</option>
                                                <option value="TikTok">TikTok</option>
                                                <option value="WhatsApp">WhatsApp</option>
                                                <option value="GitHub">GitHub</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="block text-[8px] uppercase font-bold text-slate-500 mb-1">Username / Handle</span>
                                            <input wire:model="social_links.{{ $index }}.username" type="text" required placeholder="e.g. kplabor" 
                                                   class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-slate-200 focus:outline-none text-xs font-bold font-mono" />
                                        </div>
                                    </div>
                                    <button type="button" wire:click="removeSocialLink({{ $index }})" class="p-1.5 rounded bg-rose-500/10 border border-rose-500/20 text-rose-455 hover:bg-rose-500/20 text-xs font-bold mt-4 cursor-pointer">
                                        ✕
                                    </button>
                                </div>
                            @endforeach

                            @if (empty($social_links))
                                <div class="col-span-2 text-center py-8 bg-slate-850 border border-dashed border-slate-800 rounded-xl text-xs text-slate-500">
                                    No social channels configured. Links inside layout footer will remain hidden.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Tab 6: Payment Gateways -->
                @if ($activeTab === 'payments')
                <div class="space-y-6">
                    <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-slate-800 pb-2">Payment Gateways & Accounts</h3>
                    
                    <div class="space-y-6">
                        <!-- 1. BANK WIRE -->
                        <div class="p-4 bg-slate-850 border border-slate-800 rounded-xl space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="pm_bank_enabled" id="pm_bank_enabled" class="rounded border-slate-800 bg-slate-900 text-indigo-500 focus:ring-0" />
                                    <label for="pm_bank_enabled" class="text-xs font-black text-white cursor-pointer">🏦 Bank Wire Transfer accounts</label>
                                </div>
                                <button type="button" wire:click="addBankAccount" class="px-2 py-1 rounded bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-[9px] font-bold hover:bg-indigo-500/25">
                                    + Add Bank Account
                                </button>
                            </div>
                            
                            @if($pm_bank_enabled)
                                <div class="space-y-3.5">
                                    @foreach($pm_bank_accounts as $i => $acc)
                                        <div class="p-3 bg-slate-900 border border-slate-800 rounded-lg flex items-center gap-3">
                                            <div class="flex-grow grid grid-cols-4 gap-2">
                                                <div>
                                                    <span class="block text-[8px] uppercase text-slate-500 font-bold mb-0.5">Bank Name</span>
                                                    <input wire:model="pm_bank_accounts.{{ $i }}.bank_name" type="text" required placeholder="e.g. Bank of Khyber" class="w-full px-2 py-1.5 bg-slate-850 border border-slate-800 rounded text-xs font-semibold text-white focus:outline-none" />
                                                </div>
                                                <div>
                                                    <span class="block text-[8px] uppercase text-slate-500 font-bold mb-0.5">Account Title</span>
                                                    <input wire:model="pm_bank_accounts.{{ $i }}.title" type="text" required placeholder="e.g. KP Labor Dept" class="w-full px-2 py-1.5 bg-slate-850 border border-slate-800 rounded text-xs font-semibold text-white focus:outline-none" />
                                                </div>
                                                <div>
                                                    <span class="block text-[8px] uppercase text-slate-500 font-bold mb-0.5">Account / IBAN</span>
                                                    <input wire:model="pm_bank_accounts.{{ $i }}.number" type="text" required placeholder="e.g. PK80BOKH..." class="w-full px-2 py-1.5 bg-slate-850 border border-slate-800 rounded text-xs font-bold text-white focus:outline-none font-mono" />
                                                </div>
                                                <div>
                                                    <span class="block text-[8px] uppercase text-slate-500 font-bold mb-0.5">Branch Location</span>
                                                    <input wire:model="pm_bank_accounts.{{ $i }}.branch" type="text" required placeholder="e.g. Peshawar" class="w-full px-2 py-1.5 bg-slate-850 border border-slate-800 rounded text-xs font-semibold text-white focus:outline-none" />
                                                </div>
                                            </div>
                                            <button type="button" wire:click="removeBankAccount({{ $i }})" class="p-1 rounded bg-rose-500/10 text-rose-455 hover:bg-rose-500/20 text-xs font-bold">✕</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- 2. EASYPAISA -->
                        <div class="p-4 bg-slate-850 border border-slate-800 rounded-xl space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="pm_easypaisa_enabled" id="pm_easypaisa_enabled" class="rounded border-slate-800 bg-slate-900 text-indigo-500 focus:ring-0" />
                                    <label for="pm_easypaisa_enabled" class="text-xs font-black text-white cursor-pointer">📱 EasyPaisa Mobile Wallet accounts</label>
                                </div>
                                <button type="button" wire:click="addEasyPaisaAccount" class="px-2 py-1 rounded bg-indigo-500/10 border border-indigo-500/20 text-indigo-405 text-[9px] font-bold hover:bg-indigo-500/25">
                                    + Add EasyPaisa Account
                                </button>
                            </div>
                            
                            @if($pm_easypaisa_enabled)
                                <div class="space-y-3">
                                    @foreach($pm_easypaisa_accounts as $i => $acc)
                                        <div class="p-3 bg-slate-900 border border-slate-800 rounded-lg flex items-center gap-3">
                                            <div class="flex-grow grid grid-cols-2 gap-3">
                                                <div>
                                                    <span class="block text-[8px] uppercase text-slate-500 font-bold mb-0.5">Account Title</span>
                                                    <input wire:model="pm_easypaisa_accounts.{{ $i }}.title" type="text" required placeholder="e.g. Director Labor" class="w-full px-2 py-1.5 bg-slate-850 border border-slate-800 rounded text-xs font-semibold text-white focus:outline-none" />
                                                </div>
                                                <div>
                                                    <span class="block text-[8px] uppercase text-slate-500 font-bold mb-0.5">Mobile Number</span>
                                                    <input wire:model="pm_easypaisa_accounts.{{ $i }}.number" type="text" required placeholder="e.g. 03xxxxxxxxx" class="w-full px-2 py-1.5 bg-slate-850 border border-slate-800 rounded text-xs font-bold text-white focus:outline-none font-mono" />
                                                </div>
                                            </div>
                                            <button type="button" wire:click="removeEasyPaisaAccount({{ $i }})" class="p-1 rounded bg-rose-500/10 text-rose-455 hover:bg-rose-500/20 text-xs font-bold">✕</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- 3. JAZZCASH -->
                        <div class="p-4 bg-slate-850 border border-slate-800 rounded-xl space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="pm_jazzcash_enabled" id="pm_jazzcash_enabled" class="rounded border-slate-800 bg-slate-900 text-indigo-500 focus:ring-0" />
                                    <label for="pm_jazzcash_enabled" class="text-xs font-black text-white cursor-pointer">📱 JazzCash Mobile Wallet accounts</label>
                                </div>
                                <button type="button" wire:click="addJazzCashAccount" class="px-2 py-1 rounded bg-indigo-500/10 border border-indigo-500/20 text-indigo-405 text-[9px] font-bold hover:bg-indigo-500/25">
                                    + Add JazzCash Account
                                </button>
                            </div>
                            
                            @if($pm_jazzcash_enabled)
                                <div class="space-y-3">
                                    @foreach($pm_jazzcash_accounts as $i => $acc)
                                        <div class="p-3 bg-slate-900 border border-slate-800 rounded-lg flex items-center gap-3">
                                            <div class="flex-grow grid grid-cols-2 gap-3">
                                                <div>
                                                    <span class="block text-[8px] uppercase text-slate-500 font-bold mb-0.5">Account Title</span>
                                                    <input wire:model="pm_jazzcash_accounts.{{ $i }}.title" type="text" required placeholder="e.g. Director Labor" class="w-full px-2 py-1.5 bg-slate-850 border border-slate-800 rounded text-xs font-semibold text-white focus:outline-none" />
                                                </div>
                                                <div>
                                                    <span class="block text-[8px] uppercase text-slate-500 font-bold mb-0.5">Mobile Number</span>
                                                    <input wire:model="pm_jazzcash_accounts.{{ $i }}.number" type="text" required placeholder="e.g. 03xxxxxxxxx" class="w-full px-2 py-1.5 bg-slate-850 border border-slate-800 rounded text-xs font-bold text-white focus:outline-none font-mono" />
                                                </div>
                                            </div>
                                            <button type="button" wire:click="removeJazzCashAccount({{ $i }})" class="p-1 rounded bg-rose-500/10 text-rose-455 hover:bg-rose-500/20 text-xs font-bold">✕</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- 4. CRYPTO WALLET -->
                        <div class="p-4 bg-slate-850 border border-slate-800 rounded-xl space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="pm_crypto_enabled" id="pm_crypto_enabled" class="rounded border-slate-800 bg-slate-900 text-indigo-500 focus:ring-0" />
                                    <label for="pm_crypto_enabled" class="text-xs font-black text-white cursor-pointer">🪙 Cryptocurrency wallets (USDT etc.)</label>
                                </div>
                                <button type="button" wire:click="addCryptoAccount" class="px-2 py-1 rounded bg-indigo-500/10 border border-indigo-500/20 text-indigo-405 text-[9px] font-bold hover:bg-indigo-500/25">
                                    + Add Wallet address
                                </button>
                            </div>
                            
                            @if($pm_crypto_enabled)
                                <div class="space-y-3">
                                    @foreach($pm_crypto_accounts as $i => $acc)
                                        <div class="p-3 bg-slate-900 border border-slate-800 rounded-lg flex items-center gap-3">
                                            <div class="flex-grow grid grid-cols-2 gap-3">
                                                <div>
                                                    <span class="block text-[8px] uppercase text-slate-500 font-bold mb-0.5">Network / Asset</span>
                                                    <input wire:model="pm_crypto_accounts.{{ $i }}.network" type="text" required placeholder="e.g. USDT (TRC20)" class="w-full px-2 py-1.5 bg-slate-850 border border-slate-800 rounded text-xs font-semibold text-white focus:outline-none" />
                                                </div>
                                                <div>
                                                    <span class="block text-[8px] uppercase text-slate-500 font-bold mb-0.5">Wallet Address</span>
                                                    <input wire:model="pm_crypto_accounts.{{ $i }}.address" type="text" required placeholder="e.g. TY4h5..." class="w-full px-2 py-1.5 bg-slate-850 border border-slate-800 rounded text-xs font-bold text-white focus:outline-none font-mono" />
                                                </div>
                                            </div>
                                            <button type="button" wire:click="removeCryptoAccount({{ $i }})" class="p-1 rounded bg-rose-500/10 text-rose-455 hover:bg-rose-500/20 text-xs font-bold">✕</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- 5. PAYPAL -->
                        <div class="p-4 bg-slate-850 border border-slate-800 rounded-xl space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="pm_paypal_enabled" id="pm_paypal_enabled" class="rounded border-slate-800 bg-slate-900 text-indigo-500 focus:ring-0" />
                                    <label for="pm_paypal_enabled" class="text-xs font-black text-white cursor-pointer">💳 PayPal Accounts</label>
                                </div>
                                <button type="button" wire:click="addPayPalAccount" class="px-2 py-1 rounded bg-indigo-500/10 border border-indigo-500/20 text-indigo-405 text-[9px] font-bold hover:bg-indigo-500/25">
                                    + Add PayPal account
                                </button>
                            </div>
                            
                            @if($pm_paypal_enabled)
                                <div class="space-y-3">
                                    @foreach($pm_paypal_accounts as $i => $acc)
                                        <div class="p-3 bg-slate-900 border border-slate-800 rounded-lg flex items-center gap-3">
                                            <div class="flex-grow">
                                                <span class="block text-[8px] uppercase text-slate-500 font-bold mb-0.5">PayPal Email Address</span>
                                                <input wire:model="pm_paypal_accounts.{{ $i }}.email" type="email" required placeholder="paypal@domain.com" class="w-full px-2 py-1.5 bg-slate-850 border border-slate-800 rounded text-xs font-semibold text-white focus:outline-none" />
                                            </div>
                                            <button type="button" wire:click="removePayPalAccount({{ $i }})" class="p-1 rounded bg-rose-500/10 text-rose-455 hover:bg-rose-500/20 text-xs font-bold">✕</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- 6. CREDIT / DEBIT CARDS EXPLICITLY DISABLED -->
                        <div class="p-4 bg-slate-850/50 border border-dashed border-slate-800 rounded-xl text-center py-6">
                            <span class="block text-xs font-bold text-slate-500">💳 Credit / Debit Card Gateways</span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 mt-2 rounded bg-rose-500/10 border border-rose-500/25 text-rose-400 text-[9px] font-black uppercase tracking-wider">
                                Not Available
                            </span>
                            <p class="text-[10px] text-slate-500 mt-1 max-w-sm mx-auto">Direct online card checkout features are disabled on this instance. Please use Bank Transfer, PayPal, or Local Mobile Wallets.</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Tab 7: Helpline Contacts -->
                @if ($activeTab === 'helpline')
                <div class="space-y-6">
                    <h3 class="text-xs font-bold text-indigo-400 uppercase tracking-wider border-b border-slate-800 pb-2">Helpline Contacts</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Phone Support</label>
                            <input wire:model="support_phone" type="text" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                        <div>
                            <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Support Email</label>
                            <input wire:model="support_email" type="email" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[9px] uppercase font-bold text-slate-500 mb-1.5">Office Address</label>
                        <input wire:model="support_address" type="text" placeholder="welder, cook, hiring, kp" class="w-full px-3 py-2 bg-slate-850 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-semibold" />
                    </div>
                </div>
                @endif

            </div>

            <!-- Submit Buttons row -->
            <div class="flex justify-end pt-4">
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-extrabold text-white active:scale-95 transition-all shadow-md shadow-indigo-650/10 cursor-pointer">
                    Save Tab Settings
                </button>
            </div>
        </div>
    </form>
</div>
