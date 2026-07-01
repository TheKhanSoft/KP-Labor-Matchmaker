<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CreditTransaction;
use App\Models\CreditLock;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public string $historyTab = 'purchased'; // 'purchased', 'used'

    public function mount()
    {
        if (!Auth::check()) {
            session()->flash('error', __('Please login to access the credits ledger.'));
            return $this->redirect('/login', navigate: true);
        }
    }

    public function setHistoryTab(string $tab)
    {
        if (in_array($tab, ['purchased', 'used'])) {
            $this->historyTab = $tab;
        }
    }
};
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 font-sans">
    @php
        $currency = \App\Models\Setting::get('currency_code', 'PKR');
        $employerId = Auth::id();

        // 1. Get completed purchases
        $purchases = CreditTransaction::where('user_id', $employerId)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'purchasePage');

        // 2. Get unlocked candidate locks
        $usedLocks = CreditLock::where('employer_id', $employerId)
            ->with('worker')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'usedPage');

        // Stats calculations
        $totalUnlocks = CreditLock::where('employer_id', $employerId)->count();
        $totalPurchased = CreditTransaction::where('user_id', $employerId)
            ->where('status', 'completed')
            ->sum('amount');
    @endphp

    <!-- Stats Hero -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-indigo-950/40 via-indigo-900/10 to-slate-900 border border-indigo-500/20 p-6 rounded-3xl flex items-center justify-between shadow-lg">
            <div>
                <span class="text-[9px] font-bold text-indigo-400 uppercase tracking-widest block leading-none">Available Credits</span>
                <span class="text-3xl font-black text-white font-mono mt-2 block leading-none">{{ Auth::user()->available_credits }} cr</span>
            </div>
            <div class="h-12 w-12 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-400 font-extrabold text-lg">
                🪙
            </div>
        </div>

        <div class="bg-slate-900/40 border border-slate-800 p-6 rounded-3xl flex items-center justify-between shadow-lg">
            <div>
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest block leading-none">Total Purchased</span>
                <span class="text-3xl font-black text-white font-mono mt-2 block leading-none">{{ $totalPurchased }} cr</span>
            </div>
            <div class="h-12 w-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 font-extrabold text-lg">
                📥
            </div>
        </div>

        <div class="bg-slate-900/40 border border-slate-800 p-6 rounded-3xl flex items-center justify-between shadow-lg">
            <div>
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest block leading-none">Unlocked Candidates</span>
                <span class="text-3xl font-black text-white font-mono mt-2 block leading-none">{{ $totalUnlocks }} hires</span>
            </div>
            <div class="h-12 w-12 rounded-2xl bg-violet-500/10 flex items-center justify-center text-violet-405 font-extrabold text-lg">
                👷
            </div>
        </div>
    </div>

    <!-- Sub-tab Selector -->
    <div class="flex p-1 gap-1.5 bg-slate-900/60 border border-slate-800/80 rounded-2xl mb-8 max-w-md shadow-lg">
        <button type="button" wire:click="setHistoryTab('purchased')"
                class="flex-1 py-2.5 text-xs font-extrabold rounded-xl transition-all cursor-pointer text-center flex items-center justify-center gap-2 {{ $historyTab === 'purchased' ? 'bg-indigo-500 text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            📥 Credits Purchased (Approved Ledger)
        </button>
        <button type="button" wire:click="setHistoryTab('used')"
                class="flex-1 py-2.5 text-xs font-extrabold rounded-xl transition-all cursor-pointer text-center flex items-center justify-center gap-2 {{ $historyTab === 'used' ? 'bg-indigo-500 text-slate-950 shadow-md font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800/30' }}">
            📤 Credits Used (Candidate Reveals)
        </button>
    </div>

    @if ($historyTab === 'purchased')
        <!-- Tab: Credits Purchased -->
        <div class="bg-slate-900/30 border border-slate-800/80 rounded-3xl overflow-hidden backdrop-blur-md shadow-xl">
            @if ($purchases->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-950/60 border-b border-slate-800/80 text-slate-550 uppercase font-bold tracking-wider">
                                <th class="px-6 py-4">Transaction ID</th>
                                <th class="px-6 py-4">Credits Added</th>
                                <th class="px-6 py-4">Total Cost</th>
                                <th class="px-6 py-4">Payment Method</th>
                                <th class="px-6 py-4">Payment Reference</th>
                                <th class="px-6 py-4">Date Approved</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach ($purchases as $tx)
                                <tr class="hover:bg-slate-900/20 transition-colors">
                                    <td class="px-6 py-4 font-mono font-bold text-slate-400">#TX-{{ str_pad($tx->id, 5, '0', STR_PAD_LEFT) }}</td>
                                    <td class="px-6 py-4 font-extrabold text-sm font-mono text-emerald-500">+{{ $tx->amount }} cr</td>
                                    <td class="px-6 py-4 font-bold text-white font-mono">{{ $currency }} {{ number_format($tx->price_pkr ?? ($tx->amount * \App\Models\Setting::get('credit_flat_rate', 20)), 2) }}</td>
                                    <td class="px-6 py-4 uppercase font-bold text-indigo-400">{{ $tx->payment_method }}</td>
                                    <td class="px-6 py-4 font-mono text-[10px]">
                                        <span class="block text-slate-350">{{ $tx->payment_phone ?? 'N/A' }}</span>
                                        @if ($tx->payment_proof)
                                            <a href="{{ asset('storage/' . $tx->payment_proof) }}" target="_blank" class="inline-flex items-center gap-1 text-[8px] font-bold text-indigo-400 bg-indigo-500/10 px-1.5 py-0.5 rounded border border-indigo-500/20 hover:bg-indigo-500/20 transition-all mt-1">
                                                🖼️ View Screenshot
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 font-medium font-mono">{{ $tx->updated_at->format('M d, Y - h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-slate-800/80 bg-slate-850/20">
                    {{ $purchases->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="w-12 h-12 text-slate-750 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-sm font-bold text-slate-400">{{ __("No completed purchase records found.") }}</p>
                    <p class="text-xs text-slate-600 mt-1">Visit the top-up panel to buy new credits.</p>
                </div>
            @endif
        </div>
    @elseif ($historyTab === 'used')
        <!-- Tab: Credits Used -->
        <div class="bg-slate-900/30 border border-slate-800/80 rounded-3xl overflow-hidden backdrop-blur-md shadow-xl">
            @if ($usedLocks->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-950/60 border-b border-slate-800/80 text-slate-550 uppercase font-bold tracking-wider">
                                <th class="px-6 py-4">Unlocked Candidate</th>
                                <th class="px-6 py-4">Trade / Sector</th>
                                <th class="px-6 py-4">District</th>
                                <th class="px-6 py-4">Unlock Cost</th>
                                <th class="px-6 py-4">Unlock Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @foreach ($usedLocks as $lock)
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
                                        <td class="px-6 py-4 text-rose-500 font-mono font-extrabold">-1 cr</td>
                                        <td class="px-6 py-4 text-slate-500 font-medium font-mono">{{ $lock->created_at->format('M d, Y - h:i A') }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-slate-800/80 bg-slate-850/20">
                    {{ $usedLocks->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="w-12 h-12 text-slate-750 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <p class="text-sm font-bold text-slate-400">{{ __("No unlocked worker logs found.") }}</p>
                    <p class="text-xs text-slate-600 mt-1">Unlock contact numbers in the Worker Directory to view details here.</p>
                </div>
            @endif
        </div>
    @endif
</div>
