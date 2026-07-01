<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\CreditTransaction;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public string $activeTab = 'purchase'; // 'purchase', 'history'
    
    // Purchase fields
    public string $paymentMethod = 'Bank';
    public string $paymentPhone = '';
    public int $purchaseCredits = 5;
    public int $selectedAccountIndex = 0;
    public int $purchaseStep = 1;

    public function mount()
    {
        if (!Auth::check()) {
            session()->flash('error', __('Please login to access the credits dashboard.'));
            return $this->redirect('/login', navigate: true);
        }

        $urlTab = request()->query('tab');
        if ($urlTab && in_array($urlTab, ['purchase', 'history'])) {
            $this->activeTab = $urlTab;
        }
        
        $dbMethods = json_decode(\App\Models\Setting::get('payment_methods', '[]'), true) ?: [];
        $enabledMethods = array_filter($dbMethods, function ($pm) {
            return (bool)($pm['enabled'] ?? false);
        });
        if (!empty($enabledMethods)) {
            $first = reset($enabledMethods);
            $this->paymentMethod = $first['name'];
        }
        $this->selectedAccountIndex = 0;
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['purchase', 'history'])) {
            $this->activeTab = $tab;
        }
    }

    public function getCalculatedPrice(): int
    {
        return \App\Models\Setting::calculateCreditPrice(max(1, (int)$this->purchaseCredits));
    }

    public function getUnitPrice(): float
    {
        $credits = max(1, (int)$this->purchaseCredits);
        return $this->getCalculatedPrice() / $credits;
    }

    public function setPurchaseStep(int $step)
    {
        if ($step === 2) {
            $this->validate([
                'purchaseCredits' => 'required|integer|min:1',
            ]);
        }
        $this->purchaseStep = $step;
    }

    public function setPaymentMethod(string $method)
    {
        $this->paymentMethod = $method;
        $this->selectedAccountIndex = 0;
    }

    public function submitPurchaseRequest()
    {
        if (!Auth::check()) return;

        $this->validate([
            'purchaseCredits' => 'required|integer|min:1',
            'paymentPhone' => 'required|string|min:3',
        ], [
            'paymentPhone.required' => 'Payment Reference Number / TxID / Sender Account is required for verification.',
        ]);

        $credits = max(1, (int)$this->purchaseCredits);
        $totalPrice = $this->getCalculatedPrice();

        // Create pending payment order transaction log for admin verification
        CreditTransaction::create([
            'user_id' => Auth::id(),
            'amount' => $credits,
            'price_pkr' => $totalPrice,
            'payment_method' => $this->paymentMethod,
            'payment_phone' => $this->paymentPhone,
            'status' => 'pending',
        ]);

        $this->paymentPhone = '';
        $this->purchaseStep = 4; // Step 4 = Pending Verification Screen
        session()->flash('success', "Payment request for {$credits} credits submitted successfully. It will be added to your wallet once verified by the Admin.");
    }

    public function resetPurchaseFlow()
    {
        $this->purchaseStep = 1;
        $this->paymentPhone = '';
        $this->purchaseCredits = 5;
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative font-sans">
    @php
        $currency = \App\Models\Setting::get('currency_code', 'PKR');
        $userTransactions = Auth::check() 
            ? CreditTransaction::where('user_id', Auth::id())->orderBy('created_at', 'desc')->paginate(10)
            : collect();
        $pendingCreditsCount = Auth::check()
            ? CreditTransaction::where('user_id', Auth::id())->where('status', 'pending')->sum('amount')
            : 0;
    @endphp

    <!-- Dashboard Hero & Stats -->
    <div class="relative flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8 bg-gradient-to-r from-slate-900 via-slate-950 to-slate-900 border border-slate-800/80 p-8 rounded-3xl shadow-2xl backdrop-blur-md overflow-hidden">
        <div class="absolute -right-16 -top-16 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="space-y-2">
            <h1 class="text-2xl font-black text-white sm:text-3xl tracking-tight">
                {{ __('Credit Manager') }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 max-w-2xl leading-relaxed">
                {{ __('Top up your account balance to unlock worker contact details, and review your payment logs.') }}
            </p>
        </div>
        
        @auth
            <div class="flex items-center gap-3 bg-slate-950/80 p-3.5 border border-slate-800/80 rounded-2xl shadow-2xl">
                <div class="flex items-center gap-3 px-4 border-r border-slate-800/80">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                        🔑
                    </div>
                    <div class="text-left">
                        <span class="block text-[9px] uppercase font-bold text-slate-500 tracking-wider leading-none">{{ __('Available Balance') }}</span>
                        <span class="block text-lg font-black text-indigo-400 mt-1 leading-none font-mono">{{ Auth::user()->available_credits }} cr</span>
                    </div>
                </div>
                <div class="flex items-center gap-3 px-4">
                    <div class="h-10 w-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-550">
                        ⏳
                    </div>
                    <div class="text-left">
                        <span class="block text-[9px] uppercase font-bold text-slate-550 tracking-wider leading-none">{{ __('Pending Verification') }}</span>
                        <span class="block text-lg font-black text-amber-550 mt-1 leading-none font-mono">+{{ $pendingCreditsCount }} cr</span>
                    </div>
                </div>
            </div>
        @endauth
    </div>

    <!-- Segmented Tab Controls -->
    <div class="flex p-1 gap-1.5 bg-slate-900/60 border border-slate-800/80 rounded-2xl mb-8 max-w-md shadow-lg">
        <button type="button" wire:click="setTab('purchase')"
                class="flex-1 py-2.5 text-xs font-extrabold rounded-xl transition-all cursor-pointer text-center flex items-center justify-center gap-2 {{ $activeTab === 'purchase' ? 'bg-indigo-500 text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            🪙 Buy Credits
        </button>
        <button type="button" wire:click="setTab('history')"
                class="flex-1 py-2.5 text-xs font-extrabold rounded-xl transition-all cursor-pointer text-center flex items-center justify-center gap-2 {{ $activeTab === 'history' ? 'bg-indigo-500 text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            📋 Order History
        </button>
    </div>

    @if ($activeTab === 'purchase')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Info Card -->
            <div class="bg-slate-900/30 border border-slate-800 p-6 sm:p-8 rounded-3xl backdrop-blur-md flex flex-col justify-between shadow-xl relative overflow-hidden h-fit">
                <div class="absolute -left-12 -bottom-12 w-28 h-28 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
                <div class="space-y-6">
                    <h3 class="text-lg font-extrabold text-white mb-2">{{ __('Token Wallet') }}</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ __('Each worker contact reveal deducts 1 credit. Set up your request, choose a payment method, transfer the funds, and input details to verify.') }}
                    </p>
                    <div class="p-6 bg-slate-950/80 border border-slate-800/80 rounded-2xl text-center shadow-inner relative">
                        <span class="block text-[9px] uppercase font-bold text-slate-550 tracking-wider mb-2">{{ __('Your wallet') }}</span>
                        <span class="block text-4xl font-black text-indigo-455 font-mono">
                            {{ Auth::check() ? Auth::user()->available_credits : 0 }} <span class="text-xl">🪙</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Checkout Wizard Panel -->
            <div class="lg:col-span-2 bg-slate-900/30 border border-slate-800 p-6 sm:p-8 rounded-3xl backdrop-blur-md shadow-xl relative">
                <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-850">
                    <h3 class="text-lg font-extrabold text-white">{{ __('Token Balance Top Up') }}</h3>
                    <div class="flex gap-1.5 items-center">
                        <span class="h-2 w-2 rounded-full {{ $purchaseStep >= 1 ? 'bg-indigo-500' : 'bg-slate-700' }}"></span>
                        <span class="h-2 w-2 rounded-full {{ $purchaseStep >= 2 ? 'bg-indigo-500' : 'bg-slate-700' }}"></span>
                        <span class="h-2 w-2 rounded-full {{ $purchaseStep >= 3 ? 'bg-indigo-500' : 'bg-slate-700' }}"></span>
                        <span class="h-2 w-2 rounded-full {{ $purchaseStep >= 4 ? 'bg-indigo-500' : 'bg-slate-700' }}"></span>
                    </div>
                </div>
                
                @php
                    $pms = json_decode(\App\Models\Setting::get('payment_methods', '[]'), true) ?: [];
                    $dbMethods = array_filter($pms, function ($pm) {
                        return (bool)($pm['enabled'] ?? false);
                    });
                    $activeMethod = null;
                    foreach ($dbMethods as $method) {
                        if ($method['name'] === $paymentMethod) {
                            $activeMethod = $method;
                            break;
                        }
                    }
                    $accounts = $activeMethod['accounts'] ?? [];
                @endphp

                @if ($purchaseStep === 1)
                    <!-- Step 1: Select Credits Quantity -->
                    @php
                        $pricingMode = \App\Models\Setting::get('credit_pricing_mode', 'flat');
                        $flatRate = (int)\App\Models\Setting::get('credit_flat_rate', 20);
                        $tiersJson = \App\Models\Setting::get('credit_pricing_tiers', '[]');
                        $tiers = json_decode($tiersJson, true) ?: [];
                        
                        usort($tiers, function ($a, $b) {
                            return (int)$a['min'] <=> (int)$b['min'];
                        });

                        $activeTierMin = 0;
                        $activeTierPrice = $flatRate;
                        $descTiers = $tiers;
                        usort($descTiers, function ($a, $b) {
                            return (int)$b['min'] <=> (int)$a['min'];
                        });
                        foreach ($descTiers as $t) {
                            if ($purchaseCredits >= (int)$t['min']) {
                                $activeTierMin = (int)$t['min'];
                                $activeTierPrice = (int)$t['price'];
                                break;
                            }
                        }
                        
                        $standardTotal = $purchaseCredits * $flatRate;
                        $actualPrice = $this->getCalculatedPrice();
                        $savings = $standardTotal - $actualPrice;
                    @endphp
                    
                    <div class="space-y-5">
                        <div class="p-4 bg-indigo-500/5 border border-indigo-500/10 rounded-2xl">
                            <span class="text-xs font-bold text-indigo-400 block mb-1">
                                @if ($pricingMode === 'cumulative')
                                    Step 1: Choose Credit Quantity (Cumulative Brackets)
                                @else
                                    Step 1: Choose Credit Quantity
                                @endif
                            </span>
                            <p class="text-[10px] text-slate-400">
                                @if ($pricingMode === 'cumulative')
                                    Enter the number of token credits you want to purchase. The total price is calculated bracket-by-bracket (graduated cumulative pricing).
                                @else
                                    Enter the number of token credits you want to purchase. The total price will be calculated in real-time based on bulk discount tiers.
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] uppercase font-bold tracking-wider text-slate-500 mb-2">
                                {{ __('Credits to Purchase') }}
                            </label>
                            <input wire:model.live="purchaseCredits" type="number" min="1" required
                                   class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-655 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-550 transition-all text-xs font-bold font-mono" />
                        </div>

                        @if (($pricingMode === 'tiered' || $pricingMode === 'cumulative') && !empty($tiers))
                            <div class="space-y-3">
                                <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-500">
                                    @if ($pricingMode === 'cumulative')
                                        {{ __('Cumulative Price Brackets') }}
                                    @else
                                        {{ __('Available Bulk Discount Tiers') }}
                                    @endif
                                </span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach($tiers as $tier)
                                        @php
                                            $isTierActive = ($activeTierMin === (int)$tier['min']);
                                            $discountPercent = $flatRate > 0 ? round((1 - ((int)$tier['price'] / $flatRate)) * 100) : 0;
                                        @endphp
                                        <div wire:click.prevent="$set('purchaseCredits', {{ $tier['min'] }})"
                                             class="relative p-3.5 rounded-2xl border transition-all cursor-pointer select-none flex flex-col justify-between {{ $isTierActive ? 'bg-indigo-500/10 border-indigo-500 ring-1 ring-indigo-500/20 shadow-md shadow-indigo-500/5' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700/80 hover:bg-slate-900/20' }}">
                                            <div class="flex justify-between items-center">
                                                <span class="text-xs font-bold text-white font-sans">
                                                    @if ($pricingMode === 'cumulative')
                                                        {{ __('Bracket') }} {{ $tier['min'] }}+
                                                    @else
                                                        {{ $tier['min'] }}+ {{ __('Credits') }}
                                                    @endif
                                                </span>
                                                @if($isTierActive)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-indigo-500 text-slate-950">
                                                        @if ($pricingMode === 'cumulative')
                                                            {{ __('Active Bracket') }}
                                                        @else
                                                            {{ __('Applied') }}
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-slate-800 text-slate-450">
                                                        {{ __('Select') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex items-baseline gap-2 mt-2">
                                                <span class="text-sm font-black text-indigo-400 font-mono">{{ $currency }} {{ $tier['price'] }}<span class="text-[9px] font-bold text-slate-500">/cr</span></span>
                                                <span class="text-[10px] text-slate-550 line-through font-mono">{{ $currency }} {{ $flatRate }}</span>
                                            </div>
                                            @if($discountPercent > 0)
                                                <div class="mt-1.5 text-[9px] font-bold text-emerald-400 flex items-center gap-1">
                                                    <span>🔥</span>
                                                    <span>{{ __('Save') }} {{ $discountPercent }}% @if($pricingMode === 'cumulative') {{ __('on units here') }} @endif</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($savings > 0)
                            <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl flex items-center gap-3 shadow-md shadow-emerald-500/2 transition-all">
                                <span class="text-xl">🎉</span>
                                <div class="text-xs leading-relaxed text-slate-350">
                                    <span class="font-bold text-white block text-sm">
                                        @if ($pricingMode === 'cumulative')
                                            {{ __('Cumulative Discount Applied!') }}
                                        @else
                                            {{ __('Bulk Discount Applied!') }}
                                        @endif
                                    </span>
                                    <span>{{ __('You are saving') }} <span class="font-extrabold font-mono text-emerald-400">{{ $currency }} {{ number_format($savings, 2) }}</span> {{ __('compared to standard price.') }}</span>
                                </div>
                            </div>
                        @endif

                        <!-- Pricing Table Summary -->
                        <div class="grid grid-cols-3 gap-4 p-4 bg-slate-950 border border-slate-800 rounded-2xl text-xs text-slate-400">
                            <div>
                                <span class="block text-[9px] uppercase font-bold text-slate-555 mb-1">{{ __('Average Unit Price') }}</span>
                                <span class="text-white font-extrabold text-xs font-mono">
                                    @if ($savings > 0)
                                        <span class="text-slate-500 line-through text-[10px] mr-1">{{ $currency }} {{ number_format($flatRate, 2) }}</span>
                                    @endif
                                    {{ $currency }} {{ number_format($this->getUnitPrice(), 2) }}
                                </span>
                            </div>
                            <div>
                                <span class="block text-[9px] uppercase font-bold text-slate-555 mb-1">{{ __('Allocation') }}</span>
                                <span class="text-indigo-400 font-extrabold text-sm font-mono">+{{ $purchaseCredits }} Credits</span>
                            </div>
                            <div>
                                <span class="block text-[9px] uppercase font-bold text-slate-555 mb-1">{{ __('Total Price') }}</span>
                                <span class="text-white font-extrabold text-sm font-mono font-sans">
                                    @if ($savings > 0)
                                        <span class="text-slate-500 line-through text-[10px] block font-mono">{{ $currency }} {{ number_format($standardTotal, 2) }}</span>
                                    @endif
                                    <span class="font-mono">{{ $currency }} {{ number_format($this->getCalculatedPrice(), 2) }}</span>
                                </span>
                            </div>
                        </div>

                        <button type="button" wire:click="setPurchaseStep(2)"
                                class="w-full py-3.5 rounded-xl font-extrabold text-xs text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                            Next: Select Payment Method &rarr;
                        </button>
                    </div>

                @elseif ($purchaseStep === 2)
                    <!-- Step 2: Choose Payment Method -->
                    <div class="space-y-5">
                        <div class="p-4 bg-indigo-500/5 border border-indigo-500/10 rounded-2xl">
                            <span class="text-xs font-bold text-indigo-400 block mb-1">Step 2: Choose Payment Method</span>
                            <p class="text-[10px] text-slate-400 font-medium">Please choose one of the enabled official payment options. Receiving account details will be shown below.</p>
                        </div>
                        
                        <!-- Inner Tabs (Dynamic Payment Methods) -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach ($dbMethods as $method)
                                <button type="button" wire:click="setPaymentMethod('{{ $method['name'] }}')"
                                        class="p-3.5 rounded-xl border text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-1.5 select-none {{ $paymentMethod === $method['name'] ? 'bg-indigo-500/15 border-indigo-500 text-indigo-455 shadow-sm' : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:bg-slate-900/30' }}">
                                    <span class="text-lg">
                                        @if(strtolower($method['name']) === 'bank') 🏦 
                                        @elseif(strtolower($method['name']) === 'easypaisa') 📱 
                                        @elseif(strtolower($method['name']) === 'jazzcash') 💸 
                                        @elseif(strtolower($method['name']) === 'crypto') 🔑 
                                        @elseif(strtolower($method['name']) === 'paypal') 💳 
                                        @else 🪙 @endif
                                    </span>
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider block leading-none">{{ $method['name'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        <!-- Selector for accounts if multiple -->
                        @if(count($accounts) > 1)
                            <div>
                                <label class="block text-[10px] uppercase font-bold tracking-wider text-slate-500 mb-2">Select Receiving Account Details</label>
                                <select wire:model="selectedAccountIndex" class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 text-xs font-bold focus:outline-none font-sans">
                                    @foreach($accounts as $idx => $acc)
                                        @if(strtolower($paymentMethod) === 'bank')
                                            <option value="{{ $idx }}">{{ $acc['bank_name'] }} - {{ $acc['title'] }}</option>
                                        @elseif(strtolower($paymentMethod) === 'crypto')
                                            <option value="{{ $idx }}">{{ $acc['network'] }}</option>
                                        @else
                                            <option value="{{ $idx }}">{{ $acc['title'] }} ({{ $acc['number'] ?? ($acc['email'] ?? '') }})</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Display selected account transfer instructions -->
                        @if(!empty($accounts) && isset($accounts[$selectedAccountIndex]))
                            @php $selAcc = $accounts[$selectedAccountIndex]; @endphp
                            <div class="p-4 bg-slate-950 border border-slate-800 rounded-2xl text-xs space-y-2">
                                <span class="block text-[9px] uppercase font-bold text-indigo-400 tracking-wider">Transfer Funds to this account:</span>
                                
                                @if(strtolower($paymentMethod) === 'bank')
                                    <div class="grid grid-cols-2 gap-2 text-slate-400">
                                        <div>Bank Name: <span class="text-white font-bold">{{ $selAcc['bank_name'] }}</span></div>
                                        <div>Branch: <span class="text-white font-bold">{{ $selAcc['branch'] }}</span></div>
                                        <div class="col-span-2">Account Title: <span class="text-white font-bold">{{ $selAcc['title'] }}</span></div>
                                        <div class="col-span-2">IBAN / Account Number: <span class="text-white font-bold font-mono text-[11px] block mt-0.5 select-all">{{ $selAcc['number'] }}</span></div>
                                    </div>
                                @elseif(strtolower($paymentMethod) === 'crypto')
                                    <div class="space-y-1 text-slate-400">
                                        <div>Network/Asset: <span class="text-white font-bold">{{ $selAcc['network'] }}</span></div>
                                        <div>Address: <span class="text-white font-bold font-mono text-[11px] block mt-0.5 select-all break-all">{{ $selAcc['address'] }}</span></div>
                                    </div>
                                @elseif(strtolower($paymentMethod) === 'paypal')
                                    <div class="space-y-1 text-slate-400">
                                        <div>PayPal Email: <span class="text-white font-bold select-all">{{ $selAcc['email'] }}</span></div>
                                    </div>
                                @else
                                    <div class="grid grid-cols-2 gap-2 text-slate-400">
                                        <div>Wallet: <span class="text-white font-bold">{{ $paymentMethod }}</span></div>
                                        <div>Account Title: <span class="text-white font-bold">{{ $selAcc['title'] }}</span></div>
                                        <div class="col-span-2">Mobile Number: <span class="text-white font-bold font-mono text-[11px] block mt-0.5 select-all">{{ $selAcc['number'] }}</span></div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="p-4 text-center border border-dashed border-slate-800 rounded-2xl text-xs text-slate-500 font-medium">
                                No receiving accounts configured for this method by Administrator.
                            </div>
                        @endif

                        <div class="flex gap-4">
                            <button type="button" wire:click="setPurchaseStep(1)"
                                    class="flex-1 py-3.5 rounded-xl border border-slate-805 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer text-center">
                                &larr; Back
                            </button>
                            <button type="button" wire:click="setPurchaseStep(3)" @if(empty($accounts)) disabled @endif
                                    class="flex-grow py-3.5 rounded-xl font-extrabold text-xs uppercase tracking-wider text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                                Next: Verify Funds &rarr;
                            </button>
                        </div>
                    </div>

                @elseif ($purchaseStep === 3)
                    <!-- Step 3: Proof Submission -->
                    <div class="space-y-5">
                        <div class="p-4 bg-indigo-500/5 border border-indigo-500/10 rounded-2xl">
                            <span class="text-xs font-bold text-indigo-400 block mb-1">Step 3: Transfer Funds & Submit Details</span>
                            <p class="text-[10px] text-slate-400">Please send **{{ $currency }} {{ number_format($this->getCalculatedPrice(), 2) }}** to the account shown below, then input your reference phone or transaction ID.</p>
                        </div>

                        @if(!empty($accounts) && isset($accounts[$selectedAccountIndex]))
                            @php $selAcc = $accounts[$selectedAccountIndex]; @endphp
                            <div class="p-4 bg-slate-950/60 border border-slate-800 rounded-xl text-[11px] space-y-1.5 text-slate-400">
                                <div>Method: <span class="text-white font-bold">{{ $paymentMethod }}</span></div>
                                @if(strtolower($paymentMethod) === 'bank')
                                    <div>Bank: <span class="text-white font-bold">{{ $selAcc['bank_name'] }}</span></div>
                                    <div>IBAN / Number: <span class="text-white font-mono font-bold select-all">{{ $selAcc['number'] }}</span></div>
                                @elseif(strtolower($paymentMethod) === 'crypto')
                                    <div>Address: <span class="text-white font-mono font-bold select-all break-all">{{ $selAcc['address'] }}</span></div>
                                @elseif(strtolower($paymentMethod) === 'paypal')
                                    <div>Email: <span class="text-white font-bold select-all">{{ $selAcc['email'] }}</span></div>
                                @else
                                    <div>Account Title: <span class="text-white font-bold">{{ $selAcc['title'] }}</span></div>
                                    <div>Number: <span class="text-white font-mono font-bold select-all">{{ $selAcc['number'] }}</span></div>
                                @endif
                            </div>
                        @endif

                        <div>
                            <label class="block text-[10px] uppercase font-bold tracking-wider text-slate-500 mb-2">
                                {{ __('Account Reference / Phone / TxID for') }} {{ $paymentMethod }}
                            </label>
                            <input wire:model="paymentPhone" type="text" placeholder="e.g. Transaction ID, Phone, or Account Number" required
                                   class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-550 transition-all text-xs font-bold font-mono" />
                            @error('paymentPhone') <span class="text-[10px] text-rose-500 mt-1 block font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <!-- Real-time calculations block -->
                        <div class="grid grid-cols-2 gap-4 p-4 bg-slate-950 border border-slate-800 rounded-2xl text-xs text-slate-400">
                            <div>
                                <span class="block text-[9px] uppercase font-bold text-slate-555 mb-1">{{ __('Tokens Allocated') }}</span>
                                <span class="text-indigo-405 font-extrabold text-sm font-mono">+{{ $purchaseCredits }} Credits</span>
                            </div>
                            <div>
                                <span class="block text-[9px] uppercase font-bold text-slate-555 mb-1">{{ __('Total Price') }}</span>
                                <span class="text-white font-extrabold text-sm font-mono">{{ $currency }} {{ number_format($this->getCalculatedPrice(), 2) }}</span>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <button type="button" wire:click="setPurchaseStep(2)"
                                    class="flex-1 py-3.5 rounded-xl border border-slate-800 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer text-center">
                                &larr; Back
                            </button>
                            <button type="button" wire:click="submitPurchaseRequest"
                                    class="flex-grow py-3.5 rounded-xl font-extrabold text-xs uppercase tracking-wider text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                                Submit Verification Request
                            </button>
                        </div>
                    </div>

                @elseif ($purchaseStep === 4)
                    <!-- Step 4: Pending Verification Confirmation screen -->
                    <div class="text-center py-8 space-y-5">
                        <div class="h-16 w-16 bg-amber-500/10 border border-amber-500/25 rounded-full flex items-center justify-center text-amber-500 text-3xl mx-auto animate-pulse">
                            ⏳
                        </div>
                        <div class="space-y-2">
                            <h4 class="text-sm font-black text-white uppercase tracking-wider">Purchase Order Received!</h4>
                            <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
                                Your payment proof has been submitted. The requested **{{ $purchaseCredits }} credits** (totaling **{{ $currency }} {{ number_format($this->getCalculatedPrice(), 2) }}**) are now pending verification.
                            </p>
                            <p class="text-[10px] text-slate-550 max-w-xs mx-auto leading-normal">
                                Once approved, your wallet balance will automatically update. You can close this window now or buy more.
                            </p>
                        </div>
                        <button type="button" wire:click="resetPurchaseFlow"
                                class="inline-flex px-6 py-2.5 rounded-xl text-xs font-bold text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 hover:bg-indigo-500/20 transition-all cursor-pointer">
                            Buy More Credits
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @elseif ($activeTab === 'history')
        <!-- Datatable for Order History -->
        <div class="bg-slate-900/30 border border-slate-800/80 rounded-3xl overflow-hidden backdrop-blur-md shadow-xl">
            @if ($userTransactions->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-950/60 border-b border-slate-800/80 text-slate-500 uppercase font-bold tracking-wider">
                                <th class="px-6 py-4">Transaction ID</th>
                                <th class="px-6 py-4">Credits</th>
                                <th class="px-6 py-4">Price Charged</th>
                                <th class="px-6 py-4">Payment Method</th>
                                <th class="px-6 py-4">Payment Info</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach ($userTransactions as $tx)
                                <tr class="hover:bg-slate-900/20 transition-colors">
                                    <td class="px-6 py-4 font-mono font-bold text-slate-400">#TX-{{ str_pad($tx->id, 5, '0', STR_PAD_LEFT) }}</td>
                                    <td class="px-6 py-4 font-extrabold text-sm font-mono text-emerald-500">+{{ $tx->amount }}</td>
                                    <td class="px-6 py-4 font-bold text-white font-mono">{{ $currency }} {{ number_format($tx->price_pkr ?? ($tx->amount * \App\Models\Setting::get('credit_flat_rate', 20)), 2) }}</td>
                                    <td class="px-6 py-4 uppercase font-bold text-indigo-400">{{ $tx->payment_method }}</td>
                                    <td class="px-6 py-4 text-slate-350 font-mono text-[10px]">{{ $tx->payment_phone ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-slate-500 font-medium font-mono">{{ $tx->created_at->format('M d, Y - h:i A') }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-extrabold uppercase tracking-wider {{ $tx->status === 'completed' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-455' : ($tx->status === 'failed' ? 'bg-rose-500/10 border border-rose-500/20 text-rose-455' : ($tx->status === 'pending' ? 'bg-amber-500/10 border border-amber-500/25 text-amber-500' : 'bg-slate-800 border border-slate-700 text-slate-400')) }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $tx->status === 'completed' ? 'bg-emerald-500 animate-pulse' : ($tx->status === 'failed' ? 'bg-rose-500' : ($tx->status === 'pending' ? 'bg-amber-500 animate-pulse' : 'bg-slate-550')) }}"></span>
                                            {{ $tx->status === 'pending' ? 'Pending Approval' : $tx->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-slate-800/80 bg-slate-850/20">
                    {{ $userTransactions->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="w-12 h-12 text-slate-750 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-sm font-bold text-slate-400">{{ __("You haven't purchased any credits yet.") }}</p>
                    <p class="text-xs text-slate-600 mt-1">{{ __('Go to the "Buy Credits" tab to top up your account.') }}</p>
                </div>
            @endif
        </div>
    @endif
</div>
