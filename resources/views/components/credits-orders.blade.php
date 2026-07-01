<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\CreditTransaction;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;
    use WithFileUploads;

    public string $paymentPhone = '';
    public ?int $editingOrderId = null;
    public $screenshot;

    public function mount()
    {
        if (!Auth::check()) {
            session()->flash('error', __('Please login to access the order history screen.'));
            return $this->redirect('/login', navigate: true);
        }
    }

    public function startProofSubmission(int $orderId)
    {
        $this->editingOrderId = $orderId;
        $this->paymentPhone = '';
        $this->screenshot = null;
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
            'paymentPhone.required' => 'Payment reference details are required.',
            'screenshot.image' => 'The screenshot must be an image.',
            'screenshot.max' => 'The screenshot size must not exceed 5MB.',
        ]);

        if ($this->editingOrderId) {
            $tx = CreditTransaction::where('user_id', Auth::id())
                ->where('id', $this->editingOrderId)
                ->first();
            
            if ($tx) {
                $proofPath = $tx->payment_proof;
                if ($this->screenshot) {
                    $proofPath = $this->screenshot->store('proofs', 'public');
                }

                $tx->update([
                    'payment_phone' => $this->paymentPhone,
                    'payment_proof' => $proofPath,
                ]);
                session()->flash('success', "Reference details and screenshot for Order #TX-{$tx->id} submitted successfully.");
            }
        }

        $this->editingOrderId = null;
        $this->paymentPhone = '';
        $this->screenshot = null;
    }

    public function cancelSubmission()
    {
        $this->editingOrderId = null;
        $this->paymentPhone = '';
        $this->screenshot = null;
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">
    @php
        $currency = \App\Models\Setting::get('currency_code', 'PKR');
        $userTransactions = Auth::check() 
            ? CreditTransaction::where('user_id', Auth::id())->orderBy('created_at', 'desc')->paginate(10)
            : collect();
    @endphp

    <!-- Header Section -->
    <div class="mb-8 space-y-2">
        <h1 class="text-2xl font-black text-white tracking-tight">📋 {{ __('My Order History') }}</h1>
        <p class="text-xs text-slate-400">Review all credit purchases, check verification status, or submit reference details for pending payments.</p>
    </div>

    <!-- Table Card -->
    <div class="bg-slate-900/30 border border-slate-800/80 rounded-3xl overflow-hidden backdrop-blur-md shadow-xl">
        @if ($userTransactions->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950/60 border-b border-slate-800/80 text-slate-550 uppercase font-bold tracking-wider">
                            <th class="px-6 py-4">Transaction ID</th>
                            <th class="px-6 py-4">Credits</th>
                            <th class="px-6 py-4">Price Charged</th>
                            <th class="px-6 py-4">Payment Method</th>
                            <th class="px-6 py-4">Payment Reference</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach ($userTransactions as $tx)
                            @php
                                $isEditingThis = ($editingOrderId === $tx->id);
                                $isPendingWithoutProof = ($tx->status === 'pending' && empty($tx->payment_phone));
                            @endphp
                            <tr class="hover:bg-slate-900/20 transition-colors {{ $isEditingThis ? 'bg-indigo-500/5' : '' }}">
                                <td class="px-6 py-4 font-mono font-bold text-slate-400">#TX-{{ str_pad($tx->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td class="px-6 py-4 font-extrabold text-sm font-mono text-emerald-500">+{{ $tx->amount }}</td>
                                <td class="px-6 py-4 font-bold text-white font-mono">{{ $currency }} {{ number_format($tx->price_pkr ?? ($tx->amount * \App\Models\Setting::get('credit_flat_rate', 20)), 2) }}</td>
                                <td class="px-6 py-4 uppercase font-bold text-indigo-400">{{ $tx->payment_method }}</td>
                                <td class="px-6 py-4 text-slate-350 font-mono text-[10px]">
                                    @if ($isEditingThis)
                                        <div class="space-y-2 p-3 bg-slate-950 border border-slate-850 rounded-2xl w-80 shadow-lg">
                                            <div>
                                                <label class="block text-[8px] uppercase tracking-wider text-slate-500 font-bold mb-1">TxID / Sender Reference</label>
                                                <input wire:model="paymentPhone" type="text" placeholder="Enter TxID / Ref"
                                                       class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-white placeholder-slate-650 focus:outline-none focus:border-indigo-500 text-[10px] font-mono font-bold" />
                                            </div>
                                            <div>
                                                <label class="block text-[8px] uppercase tracking-wider text-slate-500 font-bold mb-1">Screenshot Proof</label>
                                                <input type="file" wire:model="screenshot" accept="image/*"
                                                       class="w-full text-[9px] text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-[9px] file:font-bold file:bg-slate-800 file:text-indigo-400 hover:file:bg-slate-700 cursor-pointer" />
                                                @error('screenshot') <span class="text-[9px] text-rose-500 mt-0.5 block font-semibold">{{ $message }}</span> @enderror
                                            </div>
                                            @if ($screenshot)
                                                <div class="flex items-center gap-1.5 p-1 bg-slate-900 rounded-lg border border-slate-800">
                                                    <img src="{{ $screenshot->temporaryUrl() }}" class="h-6 w-6 object-cover rounded border border-slate-800" />
                                                    <span class="text-[8px] text-slate-500 truncate max-w-[120px] font-mono">{{ $screenshot->getClientOriginalName() }}</span>
                                                </div>
                                            @endif
                                            <div class="flex gap-2 justify-end pt-1 border-t border-slate-850">
                                                <button type="button" wire:click="cancelSubmission" class="px-2 py-1 rounded bg-slate-900 hover:bg-slate-850 text-slate-400 hover:text-white text-[9px] font-bold cursor-pointer">Cancel</button>
                                                <button type="button" wire:click="submitProof" class="px-3 py-1 rounded bg-indigo-650 hover:bg-indigo-700 text-white text-[9px] font-bold cursor-pointer">Save Proof</button>
                                            </div>
                                        </div>
                                    @else
                                        <div class="space-y-1">
                                            <span class="block font-bold text-slate-300">{{ $tx->payment_phone ?: 'No details submitted' }}</span>
                                            @if ($tx->payment_proof)
                                                <a href="{{ asset('storage/' . $tx->payment_proof) }}" target="_blank" class="inline-flex items-center gap-1 text-[8px] font-bold text-indigo-400 bg-indigo-500/10 px-1.5 py-0.5 rounded border border-indigo-500/20 hover:bg-indigo-500/20 transition-all">
                                                    🖼️ View Screenshot
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-medium font-mono">{{ $tx->created_at->format('M d, Y - h:i A') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-extrabold uppercase tracking-wider {{ $tx->status === 'completed' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-455' : ($tx->status === 'failed' ? 'bg-rose-500/10 border border-rose-500/20 text-rose-455' : ($tx->status === 'pending' ? 'bg-amber-500/10 border border-amber-500/25 text-amber-500' : 'bg-slate-800 border border-slate-700 text-slate-400')) }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $tx->status === 'completed' ? 'bg-emerald-500 animate-pulse' : ($tx->status === 'failed' ? 'bg-rose-500' : ($tx->status === 'pending' ? 'bg-amber-500 animate-pulse' : 'bg-slate-550')) }}"></span>
                                        {{ $tx->status === 'pending' ? 'Pending Approval' : $tx->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if ($isPendingWithoutProof && !$isEditingThis)
                                        <button wire:click="startProofSubmission({{ $tx->id }})"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/20 hover:bg-amber-500/20 transition-all font-bold text-[10px] text-amber-500 cursor-pointer">
                                            ✏️ Add Proof
                                        </button>
                                    @elseif (!$isPendingWithoutProof && $tx->status === 'pending' && !$isEditingThis)
                                        <button wire:click="startProofSubmission({{ $tx->id }})"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-800 border border-slate-750 hover:bg-slate-750 transition-all font-bold text-[10px] text-slate-300 cursor-pointer">
                                            ✏️ Edit Proof
                                        </button>
                                    @else
                                        <span class="text-slate-600 font-semibold font-mono">-</span>
                                    @endif
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
                <p class="text-sm font-bold text-slate-400">{{ __("You haven't ordered any credits yet.") }}</p>
                <p class="text-xs text-slate-600 mt-1">Visit the top-up page to purchase credit tokens.</p>
            </div>
        @endif
    </div>
</div>
