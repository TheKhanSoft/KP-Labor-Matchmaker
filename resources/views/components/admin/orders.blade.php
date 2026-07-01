<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\CreditTransaction;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $methodFilter = '';

    // Create Manual Credit allocation fields
    public bool $showCreateModal = false;
    public ?int $selectedUserId = null;
    public int $amount = 5;
    public string $payment_method = 'admin_manual';
    public string $payment_phone = '';
    public string $status = 'completed';

    // Edit Modal
    public bool $showEditModal = false;
    public ?int $editingTxId = null;
    public string $editStatus = 'completed';

    protected function rules(): array
    {
        return [
            'selectedUserId' => 'required|exists:users,id',
            'amount' => 'required|integer',
            'payment_method' => 'required|string',
            'payment_phone' => 'nullable|string',
            'status' => 'required|string|in:completed,failed,refunded',
        ];
    }

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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingMethodFilter()
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->checkAdmin();
        $this->resetValidation();
        $this->selectedUserId = null;
        $this->amount = 5;
        $this->payment_method = 'admin_manual';
        $this->payment_phone = '';
        $this->status = 'completed';

        $this->showCreateModal = true;
    }

    public function saveManualTransaction(): void
    {
        $this->checkAdmin();
        $this->validate();

        $user = User::findOrFail($this->selectedUserId);
        
        // Save Transaction
        $tx = CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'payment_phone' => $this->payment_phone,
            'status' => $this->status,
        ]);

        // Reflect inside user's wallet if transaction is completed
        if ($this->status === 'completed') {
            $user->available_credits += $this->amount;
            $user->save();
        }

        $this->logActivity('credits_allocated', "Manually created credit transaction ID {$tx->id} ({$this->amount} credits) for user {$user->name}");
        session()->flash('success', "Simulated transaction created successfully and credits adjusted.");

        $this->showCreateModal = false;
    }

    public function editStatus(int $id): void
    {
        $this->checkAdmin();
        $this->editingTxId = $id;
        $tx = CreditTransaction::findOrFail($id);
        $this->editStatus = $tx->status;
        $this->showEditModal = true;
    }

    public function saveStatus(): void
    {
        $this->checkAdmin();
        $tx = CreditTransaction::findOrFail($this->editingTxId);
        $oldStatus = $tx->status;
        
        if ($oldStatus !== $this->editStatus) {
            $tx->status = $this->editStatus;
            $tx->save();

            // Roll back user wallet credits if marked refunded
            if ($this->editStatus === 'refunded' && $oldStatus === 'completed') {
                $user = $tx->user;
                $user->available_credits = max(0, $user->available_credits - $tx->amount);
                $user->save();
            }
            // Add back user credits if marked completed from failed/refunded
            if ($this->editStatus === 'completed' && $oldStatus !== 'completed') {
                $user = $tx->user;
                $user->available_credits += $tx->amount;
                $user->save();
            }

            $this->logActivity('transaction_status_changed', "Changed transaction status for ID {$tx->id} from {$oldStatus} to {$this->editStatus}");
            session()->flash('success', "Transaction status updated successfully.");
        }

        $this->showEditModal = false;
    }

    public function deleteTransaction(int $id): void
    {
        $this->checkAdmin();
        $tx = CreditTransaction::findOrFail($id);
        $tx->delete();

        $this->logActivity('transaction_deleted', "Deleted transaction record ID: {$id}");
        session()->flash('success', "Transaction record deleted successfully.");
    }
};
?>

<div class="space-y-6 font-sans">
    <!-- Header Summary -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl shadow-lg relative overflow-hidden animate-fadeIn">
        <div class="absolute -right-16 -top-16 w-36 h-36 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 space-y-1">
            <h1 class="text-2xl font-extrabold text-slate-100 tracking-tight">Credit Orders</h1>
            <p class="text-xs text-slate-400 font-medium">Review simulated payment transactions and manually allocate token credits.</p>
        </div>
        <button type="button" wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 font-bold text-xs text-white shadow-lg shadow-indigo-600/20 active:scale-95 hover:scale-[1.02] transition-all cursor-pointer relative z-10">
            ➕ Log Manual Allocation
        </button>
    </div>

    <!-- Filters Panel -->
    <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-4 rounded-3xl flex flex-col md:flex-row gap-4 items-center justify-between shadow-lg">
        <div class="w-full md:w-80 relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-xs">🔍</span>
            <input wire:model.live.debounce.250ms="search" type="text" placeholder="Search by employer name..."
                   class="w-full pl-9 pr-4 py-2.5 bg-slate-950/60 border border-slate-800/80 rounded-2xl text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 transition-all font-medium" />
        </div>
        
        <div>
            <select wire:model.live="methodFilter" 
                    class="px-3.5 py-2.5 bg-slate-950/60 border border-slate-800/80 rounded-2xl text-xs text-slate-350 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 transition-all font-semibold">
                <option value="">All Payment Methods</option>
                <option value="easypaisa">Easypaisa Mobile Wallet</option>
                <option value="jazzcash">JazzCash Mobile Wallet</option>
                <option value="admin_manual">Admin Manual Allocation</option>
            </select>
        </div>
    </div>

    <!-- Datatable -->
    @php
        $query = CreditTransaction::query();

        if ($this->search) {
            $query->whereHas('user', function ($uq) {
                $uq->where('name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->methodFilter) {
            $query->where('payment_method', $this->methodFilter);
        }

        $transactionsList = $query->orderBy('created_at', 'desc')->paginate(10);
    @endphp

    <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 rounded-3xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-850/40 border-b border-slate-800/80 text-slate-400 uppercase font-extrabold tracking-wider">
                        <th class="px-6 py-4">Transaction ID</th>
                        <th class="px-6 py-4">Employer</th>
                        <th class="px-6 py-4">Credits Amount</th>
                        <th class="px-6 py-4">Payment Info</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse ($transactionsList as $tx)
                        <tr class="hover:bg-slate-850/30 dark:hover:bg-slate-900/20 transition-colors duration-150">
                            <td class="px-6 py-4 font-mono font-bold text-slate-400">#TX-{{ str_pad($tx->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4 font-bold text-slate-100">{{ $tx->user ? $tx->user->name : 'Deleted User' }}</td>
                            <td class="px-6 py-4 font-extrabold text-sm font-mono {{ $tx->amount > 0 ? 'text-emerald-500' : 'text-rose-500' }}">
                                {{ $tx->amount > 0 ? '+' : '' }}{{ $tx->amount }}
                            </td>
                            <td class="px-6 py-4 text-slate-400 font-semibold">
                                <span class="capitalize font-extrabold block text-[10px] text-indigo-500">{{ $tx->payment_method }}</span>
                                @if ($tx->payment_phone)
                                    <span class="block text-[10px] text-slate-550 font-mono mt-0.5 font-bold">{{ $tx->payment_phone }}</span>
                                @endif
                                @if ($tx->payment_proof)
                                    <a href="{{ asset('storage/' . $tx->payment_proof) }}" target="_blank" class="inline-flex items-center gap-1 text-[8px] font-bold text-indigo-400 bg-indigo-500/10 px-1.5 py-0.5 rounded border border-indigo-500/20 hover:bg-indigo-500/20 transition-all mt-1">
                                        🖼️ View Proof
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-extrabold uppercase tracking-wider {{ $tx->status === 'completed' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-555' : ($tx->status === 'failed' ? 'bg-rose-500/10 border border-rose-500/20 text-rose-555' : ($tx->status === 'pending' ? 'bg-amber-500/10 border border-amber-500/25 text-amber-500' : 'bg-slate-800 border border-slate-700 text-slate-400')) }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $tx->status === 'completed' ? 'bg-emerald-500 animate-pulse' : ($tx->status === 'failed' ? 'bg-rose-500' : ($tx->status === 'pending' ? 'bg-amber-500 animate-pulse' : 'bg-slate-550')) }}"></span>
                                    {{ $tx->status === 'pending' ? 'Pending Verification' : $tx->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                                <button type="button" wire:click="editStatus({{ $tx->id }})" title="Update Order Status"
                                        class="px-2.5 py-1.5 rounded-lg bg-slate-850/80 border border-slate-800 text-[10px] font-bold text-slate-350 hover:text-white hover:bg-slate-800 hover:border-slate-700 transition-all cursor-pointer">
                                    Edit Status
                                </button>
                                <button type="button" wire:click="deleteTransaction({{ $tx->id }})" title="Delete Transaction Log"
                                        class="p-1.5 rounded-lg border border-slate-800 hover:border-rose-500/50 hover:bg-rose-500/10 text-slate-500 hover:text-rose-500 transition-all cursor-pointer inline-flex items-center">
                                    ✕
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-bold">No orders or transactions recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-800/80 bg-slate-850/20">
            {{ $transactionsList->links() }}
        </div>
    </div>

    <!-- Create Manual Allocation Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative w-full max-w-md bg-slate-900 border border-slate-855 p-6 rounded-3xl shadow-2xl z-10">
                <h3 class="text-sm font-extrabold text-white uppercase tracking-wider mb-6">Create Manual Credit Transaction</h3>

                <form wire:submit="saveManualTransaction" class="space-y-6">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Select Employer Account</label>
                        <select wire:model="selectedUserId" required
                                class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold">
                            <option value="">-- Choose Employer / Contractor --</option>
                            @foreach (User::whereIn('role', ['employer', 'contractor'])->orderBy('name')->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->phone }})</option>
                            @endforeach
                        </select>
                        @error('selectedUserId') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Amount (Credits)</label>
                            <input wire:model="amount" type="number" required
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                            @error('amount') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Order Status</label>
                            <select wire:model="status" required
                                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold">
                                <option value="pending">Pending Verification</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Allocation Method</label>
                            <select wire:model="payment_method" required
                                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold">
                                <option value="admin_manual">Admin Manual</option>
                                <option value="easypaisa">Simulated Easypaisa</option>
                                <option value="jazzcash">Simulated JazzCash</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5">Reference Mobile</label>
                            <input wire:model="payment_phone" type="text" placeholder="e.g. 03001234567" 
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-850">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-slate-800 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-bold text-white shadow-md shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                            Save Allocation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Edit Status Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" wire:click="$set('showEditModal', false)"></div>
            <div class="relative w-full max-w-md bg-slate-900 border border-slate-855 p-6 rounded-3xl shadow-2xl z-10">
                <h3 class="text-sm font-extrabold text-white uppercase tracking-wider mb-6">Update Transaction Status</h3>

                @php
                    $editingTx = \App\Models\CreditTransaction::find($editingTxId);
                @endphp
                @if ($editingTx && $editingTx->payment_proof)
                    <div class="mb-5">
                        <label class="block text-[10px] uppercase font-bold text-slate-405 mb-1.5 font-sans">Payment Proof Screenshot</label>
                        <a href="{{ asset('storage/' . $editingTx->payment_proof) }}" target="_blank" title="Click to view full image">
                            <img src="{{ asset('storage/' . $editingTx->payment_proof) }}" class="max-h-48 w-full object-cover rounded-xl border border-slate-800 hover:opacity-90 transition-opacity" />
                        </a>
                    </div>
                @endif

                <form wire:submit="saveStatus" class="space-y-6">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1.5 font-sans">Select Status</label>
                        <select wire:model="editStatus" required
                                class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 text-xs font-semibold">
                            <option value="pending">Pending Verification</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-850">
                        <button type="button" wire:click="$set('showEditModal', false)"
                                class="px-4 py-2.5 rounded-xl border border-slate-800 text-xs font-bold text-slate-400 hover:text-white transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-bold text-white shadow-md shadow-indigo-600/20 active:scale-95 transition-all cursor-pointer">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
