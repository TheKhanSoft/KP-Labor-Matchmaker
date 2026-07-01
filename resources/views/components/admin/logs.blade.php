<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CreditLock;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public string $search = '';

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

    public function updatingSearch()
    {
        $this->resetPage();
    }
};
?>

<div class="space-y-6 font-sans">
    <!-- Header Summary -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-6 rounded-3xl shadow-lg relative overflow-hidden animate-fadeIn">
        <div class="absolute -right-16 -top-16 w-36 h-36 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10">
            <h1 class="text-2xl font-extrabold text-slate-100 tracking-tight">Access Reveals</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">Review reveal history logs indicating which employers/contractors unlocked which worker phone contacts.</p>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-4 rounded-3xl flex flex-col md:flex-row gap-4 items-center justify-between shadow-lg">
        <div class="w-full md:w-80 relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-xs">🔍</span>
            <input wire:model.live.debounce.250ms="search" type="text" placeholder="Search by employer or worker name..."
                   class="w-full pl-10 pr-4 py-2 bg-slate-950/60 border border-slate-800/80 rounded-2xl text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all font-medium" />
        </div>
    </div>

    <!-- Datatable -->
    @php
        $query = CreditLock::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('employer', function ($eq) {
                    $eq->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('worker', function ($wq) {
                    $wq->where('name', 'like', '%' . $this->search . '%');
                });
            });
        }

        $logsList = $query->orderBy('created_at', 'desc')->paginate(15);
    @endphp

    <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 rounded-3xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-850/40 border-b border-slate-800/80 text-slate-400 uppercase font-extrabold tracking-wider">
                        <th class="px-6 py-4">Reveal Log ID</th>
                        <th class="px-6 py-4">Employer / Org</th>
                        <th class="px-6 py-4">Worker Unlocked</th>
                        <th class="px-6 py-4">Credits Deducted</th>
                        <th class="px-6 py-4">Unlocked Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse ($logsList as $log)
                        <tr class="hover:bg-slate-850/30 dark:hover:bg-slate-900/20 transition-colors duration-150">
                            <td class="px-6 py-4 font-mono font-bold text-slate-400">#REV-{{ str_pad($log->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4 font-bold text-slate-100">
                                {{ $log->employer ? $log->employer->name : 'Deleted Employer' }}
                                @if ($log->employer && $log->employer->profile && $log->employer->profile->company_name)
                                    <span class="block text-[10px] text-slate-500 font-normal mt-0.5">{{ $log->employer->profile->company_name }} ({{ $log->employer->profile->city ?: 'No City' }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-bold text-indigo-500">
                                {{ $log->worker ? $log->worker->name : 'Deleted Worker' }}
                                @if ($log->worker)
                                    <span class="block text-[10px] text-slate-500 font-normal mt-0.5">{{ $log->worker->skill_category }} • {{ $log->worker->district }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono font-semibold text-rose-500 text-center sm:text-left">-1 Credit</td>
                            <td class="px-6 py-4 text-slate-400 font-mono">{{ $log->created_at->format('M d, Y h:i A') }} ({{ $log->created_at->diffForHumans() }})</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-bold">No access reveals logged in database yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-800/80 bg-slate-850/20">
            {{ $logsList->links() }}
        </div>
    </div>
</div>
