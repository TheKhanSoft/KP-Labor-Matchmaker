<?php
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ActivityLog;
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
            <h1 class="text-2xl font-extrabold text-slate-100 tracking-tight">Audit Logs</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">Review internal administrative actions, deletions, profile updates, and settings changes.</p>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 p-4 rounded-3xl flex flex-col md:flex-row gap-4 items-center justify-between shadow-lg">
        <div class="w-full md:w-80 relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-xs">🔍</span>
            <input wire:model.live.debounce.250ms="search" type="text" placeholder="Search by action or audit description..."
                   class="w-full pl-9 pr-4 py-2.5 bg-slate-950/60 border border-slate-800/80 rounded-2xl text-xs text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-550 transition-all font-medium" />
        </div>
    </div>

    <!-- Datatable -->
    @php
        $query = ActivityLog::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('action', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('admin', function ($aq) {
                      $aq->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $auditsList = $query->orderBy('created_at', 'desc')->paginate(15);
    @endphp

    <div class="bg-slate-900 border border-slate-800/80 dark:bg-slate-950/40 dark:border-slate-800/80 rounded-3xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-850/40 border-b border-slate-800/80 text-slate-400 uppercase font-extrabold tracking-wider">
                        <th class="px-6 py-4">Audit ID</th>
                        <th class="px-6 py-4">Admin Account</th>
                        <th class="px-6 py-4">Action</th>
                        <th class="px-6 py-4">Detailed Description</th>
                        <th class="px-6 py-4 font-mono">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse ($auditsList as $audit)
                        <tr class="hover:bg-slate-850/30 dark:hover:bg-slate-900/20 transition-colors duration-150">
                            <td class="px-6 py-4 font-mono font-bold text-slate-400">#AUD-{{ str_pad($audit->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4 font-bold text-slate-100">{{ $audit->admin ? $audit->admin->name : 'Deleted Admin' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded bg-indigo-500/10 border border-indigo-500/10 text-[10px] text-indigo-500 font-bold uppercase tracking-wider font-mono">
                                    {{ $audit->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-300 font-medium max-w-md break-words">{{ $audit->description }}</td>
                            <td class="px-6 py-4 text-slate-500 font-mono">{{ $audit->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 font-bold">No administrative activities logged yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-800/80 bg-slate-850/20">
            {{ $auditsList->links() }}
        </div>
    </div>
</div>
