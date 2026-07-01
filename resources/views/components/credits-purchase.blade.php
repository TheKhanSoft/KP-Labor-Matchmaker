<?php
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use App\Models\CreditTransaction;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithFileUploads;

    public string $paymentMethod = 'Bank';
    public string $paymentPhone = '';
    public int $purchaseCredits = 5;
    public int $selectedAccountIndex = 0;
    public int $purchaseStep = 1; // 1: Choose Credits, 2: Choose Method, 3: Confirm Purchase, 4: Submit Proof (Same time)
    public ?int $createdOrderId = null;
    public $screenshot;

    public function mount()
    {
        if (!Auth::check()) {
            session()->flash('error', __('Please login to access the purchase screen.'));
            return $this->redirect('/login', navigate: true);
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

    public function getCalculatedPrice(): int
    {
        return \App\Models\Setting::calculateCreditPrice(max(1, (int)$this->purchaseCredits));
    }

    public function getUnitPrice(): float
    {
        $credits = max(1, (int)$this->purchaseCredits);
        return $this->getCalculatedPrice() / $credits;
    }

    public function confirmPurchase()
    {
        if (!Auth::check()) return;

        $credits = max(1, (int)$this->purchaseCredits);
        $totalPrice = $this->getCalculatedPrice();

        // 1. Create pending transaction immediately without payment proof (payment_phone is empty)
        $tx = CreditTransaction::create([
            'user_id' => Auth::id(),
            'amount' => $credits,
            'price_pkr' => $totalPrice,
            'payment_method' => $this->paymentMethod,
            'payment_phone' => null, // empty proof for now
            'status' => 'pending',
        ]);

        $this->createdOrderId = $tx->id;
        $this->paymentPhone = '';
        $this->screenshot = null;
        $this->purchaseStep = 4; // Move to proof submission screen
        session()->flash('success', "Order #TX-{$tx->id} created successfully! You can verify now or later.");
    }

    public function submitProof()
    {
        $rules = [
            'paymentPhone' => 'required|string|min:3',
        ];
        if ($this->screenshot) {
            $rules['screenshot'] = 'image|max:5120';
        }

        $this->validate($rules, [
            'paymentPhone.required' => 'Payment Reference Number / TxID / Sender Account is required for verification.',
            'screenshot.image' => 'The payment proof must be an image.',
            'screenshot.max' => 'The screenshot size must not exceed 5MB.',
        ]);

        $proofPath = null;
        if ($this->screenshot) {
            $proofPath = $this->screenshot->store('proofs', 'public');
        }

        if ($this->createdOrderId) {
            $tx = CreditTransaction::find($this->createdOrderId);
            if ($tx) {
                $tx->update([
                    'payment_phone' => $this->paymentPhone,
                    'payment_proof' => $proofPath,
                ]);
            }
        }

        session()->flash('success', "Payment details and proof screenshot submitted successfully! Our team will verify this transaction shortly.");
        return $this->redirect('/orders', navigate: true);
    }
};
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">
    @php
        $currency = \App\Models\Setting::get('currency_code', 'PKR');
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

    <!-- Wizard Title -->
    <div class="mb-8 space-y-2">
        <h1 class="text-2xl font-black text-white tracking-tight">🪙 {{ __('Top Up Tokens') }}</h1>
        <p class="text-xs text-slate-400">Follow the steps below to buy credit tokens for candidate reveals.</p>
    </div>

    <!-- Wizard Panel -->
    <div class="bg-slate-900/30 border border-slate-800 p-6 sm:p-8 rounded-3xl backdrop-blur-md shadow-xl relative">
        <!-- Progress Steps -->
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-850">
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400">
                @if ($purchaseStep === 1) Step 1: Select Credits Quantity
                @elseif ($purchaseStep === 2) Step 2: Choose Payment Method
                @elseif ($purchaseStep === 3) Step 3: Confirm Purchase
                @elseif ($purchaseStep === 4) Step 4: Submit Payment Reference
                @endif
            </h3>
            <div class="flex gap-1.5 items-center">
                <span class="h-2.5 w-2.5 rounded-full {{ $purchaseStep >= 1 ? 'bg-indigo-500 shadow-md shadow-indigo-500/20' : 'bg-slate-700' }}"></span>
                <span class="h-2.5 w-2.5 rounded-full {{ $purchaseStep >= 2 ? 'bg-indigo-500 shadow-md shadow-indigo-500/20' : 'bg-slate-700' }}"></span>
                <span class="h-2.5 w-2.5 rounded-full {{ $purchaseStep >= 3 ? 'bg-indigo-500 shadow-md shadow-indigo-500/20' : 'bg-slate-700' }}"></span>
                <span class="h-2.5 w-2.5 rounded-full {{ $purchaseStep >= 4 ? 'bg-indigo-500 shadow-md shadow-indigo-500/20' : 'bg-slate-700' }}"></span>
            </div>
        </div>

        @if ($purchaseStep === 1)
            <!-- Step 1: Select Credits -->
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
                <div>
                    <label class="block text-[10px] uppercase font-bold tracking-wider text-slate-500 mb-2">
                        {{ __('Number of Credits to Purchase') }}
                    </label>
                    <input wire:model.live="purchaseCredits" type="number" min="1" required
                           class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-655 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-550 transition-all text-xs font-bold font-mono" />
                </div>

                @if (($pricingMode === 'tiered' || $pricingMode === 'cumulative') && !empty($tiers))
                    <div class="space-y-3">
                        <span class="block text-[10px] uppercase font-bold tracking-wider text-slate-500">
                            @if ($pricingMode === 'cumulative')
                                {{ __('Graduated Price brackets') }}
                            @else
                                {{ __('Bulk Discount packages') }}
                            @endif
                        </span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($tiers as $tier)
                                @php
                                    $isTierActive = ($activeTierMin === (int)$tier['min']);
                                    $discountPercent = $flatRate > 0 ? round((1 - ((int)$tier['price'] / $flatRate)) * 100) : 0;
                                @endphp
                                <div wire:click.prevent="$set('purchaseCredits', {{ $tier['min'] }})"
                                     class="relative p-3.5 rounded-2xl border transition-all cursor-pointer select-none flex flex-col justify-between {{ $isTierActive ? 'bg-indigo-500/10 border-indigo-500 shadow-md' : 'bg-slate-950/60 border-slate-800 hover:border-slate-700/80 hover:bg-slate-900/20' }}">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold text-white font-sans">
                                            {{ $tier['min'] }}+ {{ __('Credits') }}
                                        </span>
                                        @if($isTierActive)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-indigo-500 text-slate-950">
                                                {{ __('Applied') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider bg-slate-800 text-slate-450">
                                                {{ __('Select') }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-baseline gap-2 mt-2">
                                        <span class="text-sm font-black text-indigo-405 font-mono">{{ $currency }} {{ $tier['price'] }}<span class="text-[9px] font-bold text-slate-500">/cr</span></span>
                                        <span class="text-[10px] text-slate-550 line-through font-mono">{{ $currency }} {{ $flatRate }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($savings > 0)
                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl flex items-center gap-3">
                        <span class="text-xl">🎉</span>
                        <div class="text-xs leading-relaxed text-slate-350">
                            <span class="font-bold text-white block text-sm">Discount Applied!</span>
                            <span>You save <span class="font-extrabold font-mono text-emerald-400">{{ $currency }} {{ number_format($savings, 2) }}</span> compared to standard rates.</span>
                        </div>
                    </div>
                @endif

                <!-- Pricing Summary Card -->
                <div class="grid grid-cols-3 gap-4 p-4 bg-slate-950 border border-slate-800 rounded-2xl text-xs text-slate-400">
                    <div>
                        <span class="block text-[9px] uppercase font-bold text-slate-555 mb-1">Average Unit Rate</span>
                        <span class="text-white font-extrabold text-xs font-mono">{{ $currency }} {{ number_format($this->getUnitPrice(), 2) }}</span>
                    </div>
                    <div>
                        <span class="block text-[9px] uppercase font-bold text-slate-555 mb-1">Credits Amount</span>
                        <span class="text-indigo-400 font-extrabold text-sm font-mono">+{{ $purchaseCredits }} cr</span>
                    </div>
                    <div>
                        <span class="block text-[9px] uppercase font-bold text-slate-555 mb-1">Total Cost</span>
                        <span class="text-white font-extrabold text-sm font-mono font-mono">{{ $currency }} {{ number_format($this->getCalculatedPrice(), 2) }}</span>
                    </div>
                </div>

                <button type="button" wire:click="setPurchaseStep(2)"
                        class="w-full py-3.5 rounded-xl font-extrabold text-xs text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                    Next: Choose Payment Method &rarr;
                </button>
            </div>

        @elseif ($purchaseStep === 2)
            <!-- Step 2: Choose Payment Method -->
            <div class="space-y-5">
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

                <!-- Display Account Instructions -->
                @if(!empty($accounts) && isset($accounts[$selectedAccountIndex]))
                    @php $selAcc = $accounts[$selectedAccountIndex]; @endphp
                    <div class="p-4 bg-slate-950 border border-slate-800 rounded-2xl text-xs space-y-2">
                        <span class="block text-[9px] uppercase font-bold text-indigo-400 tracking-wider">Send Funds to:</span>
                        
                        @if(strtolower($paymentMethod) === 'bank')
                            <div class="grid grid-cols-2 gap-2 text-slate-400">
                                <div>Bank: <span class="text-white font-bold">{{ $selAcc['bank_name'] }}</span></div>
                                <div>IBAN: <span class="text-white font-bold font-mono text-[11px] select-all">{{ $selAcc['number'] }}</span></div>
                            </div>
                        @elseif(strtolower($paymentMethod) === 'crypto')
                            <div class="space-y-1 text-slate-400">
                                <div>Network: <span class="text-white font-bold">{{ $selAcc['network'] }}</span></div>
                                <div>Address: <span class="text-white font-bold font-mono text-[11px] select-all break-all">{{ $selAcc['address'] }}</span></div>
                            </div>
                        @else
                            <div class="grid grid-cols-2 gap-2 text-slate-400">
                                <div>Account Title: <span class="text-white font-bold">{{ $selAcc['title'] }}</span></div>
                                <div>Number: <span class="text-white font-bold font-mono text-[11px] select-all">{{ $selAcc['number'] }}</span></div>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="flex gap-4">
                    <button type="button" wire:click="setPurchaseStep(1)"
                            class="flex-1 py-3.5 rounded-xl border border-slate-800 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer text-center">
                        &larr; Back
                    </button>
                    <button type="button" wire:click="setPurchaseStep(3)" @if(empty($accounts)) disabled @endif
                            class="flex-grow py-3.5 rounded-xl font-extrabold text-xs uppercase tracking-wider text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer disabled:opacity-40">
                        Next: Confirm Purchase &rarr;
                    </button>
                </div>
            </div>

        @elseif ($purchaseStep === 3)
            <!-- Step 3: Confirmation Step -->
            <div class="space-y-5">
                <div class="p-6 bg-slate-950 border border-slate-800 rounded-3xl text-center space-y-4">
                    <div class="h-16 w-16 rounded-full bg-indigo-500/10 border border-indigo-500/25 flex items-center justify-center text-indigo-400 text-3xl mx-auto">
                        ❓
                    </div>
                    <div class="space-y-1">
                        <h4 class="text-sm font-black text-white uppercase tracking-wider">Confirm Your Order</h4>
                        <p class="text-xs text-slate-400 leading-relaxed max-w-sm mx-auto">
                            Please confirm that you want to purchase **{{ $purchaseCredits }} tokens** for a total of **{{ $currency }} {{ number_format($this->getCalculatedPrice(), 2) }}** using **{{ $paymentMethod }}**.
                        </p>
                    </div>
                    
                    <div class="p-4 bg-slate-900 border border-slate-800 rounded-2xl grid grid-cols-2 gap-4 text-xs text-left max-w-xs mx-auto">
                        <div>
                            <span class="block text-[8px] uppercase font-bold text-slate-555">Quantity</span>
                            <span class="text-white font-bold">{{ $purchaseCredits }} Credits</span>
                        </div>
                        <div>
                            <span class="block text-[8px] uppercase font-bold text-slate-555">Payment Gateway</span>
                            <span class="text-indigo-400 font-bold uppercase">{{ $paymentMethod }}</span>
                        </div>
                        <div class="col-span-2 border-t border-slate-800 pt-2 flex justify-between items-baseline">
                            <span class="text-[8px] uppercase font-bold text-slate-555">Total Cost</span>
                            <span class="text-white font-black text-sm">{{ $currency }} {{ number_format($this->getCalculatedPrice(), 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4">
                    <button type="button" wire:click="setPurchaseStep(2)"
                            class="flex-1 py-3.5 rounded-xl border border-slate-800 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer text-center">
                        &larr; Cancel
                    </button>
                    <button type="button" wire:click="confirmPurchase"
                            class="flex-grow py-3.5 rounded-xl font-extrabold text-xs uppercase tracking-wider text-white bg-indigo-650 hover:bg-indigo-705 shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                        Confirm Purchase &rarr;
                    </button>
                </div>
            </div>

        @elseif ($purchaseStep === 4)
            <!-- Step 4: Submit Payment Proof (Same time / optional) -->
            <div class="space-y-5">
                <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl flex items-start gap-3.5">
                    <span class="text-2xl leading-none">✅</span>
                    <div class="text-xs leading-normal">
                        <span class="font-bold text-white block text-sm">Order #TX-{{ $createdOrderId }} Saved!</span>
                        <p class="text-slate-350 mt-1">Your order is logged. You can enter payment details below now to submit for admin auditing, or skip and provide details later from your Order History panel.</p>
                    </div>
                </div>

                @if(!empty($accounts) && isset($accounts[$selectedAccountIndex]))
                    @php $selAcc = $accounts[$selectedAccountIndex]; @endphp
                    <div class="p-4 bg-slate-950/60 border border-slate-800 rounded-xl text-[11px] space-y-1.5 text-slate-400">
                        <div class="font-bold text-white">Please send {{ $currency }} {{ number_format($this->getCalculatedPrice(), 2) }} to:</div>
                        @if(strtolower($paymentMethod) === 'bank')
                            <div>Bank: <span class="text-white font-bold">{{ $selAcc['bank_name'] }}</span></div>
                            <div>IBAN / Number: <span class="text-white font-mono font-bold select-all">{{ $selAcc['number'] }}</span></div>
                        @elseif(strtolower($paymentMethod) === 'crypto')
                            <div>Address: <span class="text-white font-mono font-bold select-all break-all">{{ $selAcc['address'] }}</span></div>
                        @else
                            <div>Account: <span class="text-white font-bold">{{ $selAcc['title'] }}</span></div>
                            <div>Number: <span class="text-white font-mono font-bold select-all">{{ $selAcc['number'] }}</span></div>
                        @endif
                    </div>
                @endif

                <div>
                    <label class="block text-[10px] uppercase font-bold tracking-wider text-slate-500 mb-2">
                        Enter Transaction ID / Reference Phone
                    </label>
                    <input wire:model="paymentPhone" type="text" placeholder="e.g. TxID, Account Number, Reference Code" required
                           class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-550 transition-all text-xs font-bold font-mono" />
                    @error('paymentPhone') <span class="text-[10px] text-rose-500 mt-1 block font-semibold">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold tracking-wider text-slate-500 mb-2">
                        Upload Payment Screenshot / Proof (Optional)
                    </label>
                    <input type="file" wire:model="screenshot" accept="image/*"
                           class="w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-indigo-400 hover:file:bg-slate-750 cursor-pointer" />
                    @error('screenshot') <span class="text-[10px] text-rose-500 mt-1 block font-semibold">{{ $message }}</span> @enderror
                    
                    @if ($screenshot)
                        <div class="mt-3 p-2 bg-slate-950 border border-slate-850 rounded-xl max-w-xs relative flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <img src="{{ $screenshot->temporaryUrl() }}" class="h-10 w-10 object-cover rounded-lg border border-slate-800" />
                                <span class="text-[10px] text-slate-400 font-mono truncate max-w-[150px]">{{ $screenshot->getClientOriginalName() }}</span>
                            </div>
                            <button type="button" wire:click="$set('screenshot', null)" class="text-rose-500 hover:text-rose-455 text-xs font-bold font-sans p-1">✕</button>
                        </div>
                    @endif
                </div>

                <div class="flex gap-4">
                    <a href="/orders" wire:navigate
                            class="flex-1 py-3.5 rounded-xl border border-slate-800 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer text-center flex items-center justify-center">
                        Skip & Verify Later
                    </a>
                    <button type="button" wire:click="submitProof"
                            class="flex-grow py-3.5 rounded-xl font-extrabold text-xs uppercase tracking-wider text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                        Submit Payment Details
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
