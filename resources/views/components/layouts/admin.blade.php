<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100 antialiased selection:bg-indigo-500/30 selection:text-indigo-400">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'KP Labor Matchmaker - Admin Panel' }}</title>

    <!-- Google Fonts: Outfit, Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles / Scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <!-- Dark/Light Theme Handler -->
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || 'system';
            const root = document.documentElement;
            if (theme === 'dark') {
                root.classList.add('dark');
            } else if (theme === 'light') {
                root.classList.remove('dark');
            } else {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    root.classList.add('dark');
                } else {
                    root.classList.remove('dark');
                }
            }
        })();

        function applyTheme(theme) {
            const root = document.documentElement;
            if (theme === 'dark') {
                root.classList.add('dark');
            } else if (theme === 'light') {
                root.classList.remove('dark');
            } else {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    root.classList.add('dark');
                } else {
                    root.classList.remove('dark');
                }
            }
            updateThemeUI(theme);
        }

        function setTheme(theme) {
            localStorage.setItem('theme', theme);
            applyTheme(theme);
        }

        function updateThemeUI(theme) {
            const activeClasses = ['bg-indigo-600', 'text-white', 'scale-105', 'shadow-md'];
            const inactiveClasses = ['text-slate-400', 'hover:text-slate-700', 'dark:hover:text-slate-200', 'hover:bg-slate-100', 'dark:hover:bg-slate-900/40'];

            ['light', 'dark', 'system'].forEach(t => {
                const btn = document.getElementById('theme-btn-' + t);
                if (btn) {
                    if (t === theme) {
                        inactiveClasses.forEach(c => btn.classList.remove(c));
                        activeClasses.forEach(c => btn.classList.add(c));
                    } else {
                        activeClasses.forEach(c => btn.classList.remove(c));
                        inactiveClasses.forEach(c => btn.classList.add(c));
                    }
                }
            });
        }

        document.addEventListener("DOMContentLoaded", () => {
            const theme = localStorage.getItem('theme') || 'system';
            updateThemeUI(theme);
        });
    </script>
</head>
<body class="min-h-full flex flex-col font-sans relative overflow-x-hidden bg-slate-950 text-slate-100" 
      x-data="{ 
          mobileSidebarOpen: false, 
          searchOpen: false, 
          searchQuery: '',
          searchResults: [],
          allItems: [
              { name: 'Dashboard Overview', url: '/admin', desc: 'Main console stats, quick toggles and active logs', tags: 'dashboard overview console statistics metrics home' },
              { name: 'Users Directory', url: '/admin/users', desc: 'Manage system administrators, firms, and contractors', tags: 'users directory accounts admin employer contractor wallet approve verify' },
              { name: 'Workers Registry', url: '/admin/workers', desc: 'Browse and manage registered skilled workers registry', tags: 'workers registry labor trades electrician plumber carpenter available status' },
              { name: 'Spatie Security Roles', url: '/admin/roles', desc: 'Configure role-based access control (RBAC)', tags: 'roles spatie security access groups admin custom' },
              { name: 'Permissions List', url: '/admin/permissions', desc: 'Manage granular spatie authorization check keys', tags: 'permissions spatie security access gates guard keys' },
              { name: 'Credit Purchases & Orders', url: '/admin/orders', desc: 'Simulated credit purchases and manual token credits', tags: 'orders credit purchases transactions payments invoice easypaisa jazzcash manual' },
              { name: 'Contact Reveal Logs', url: '/admin/logs', desc: 'History of unlocked worker contacts by employers', tags: 'logs reveals contact phone unlock details employer contractor' },
              { name: 'System Audit Logs', url: '/admin/audit', desc: 'Administrative actions trail, profile updates & settings edits', tags: 'audit logs administrative trail security history actions deletes' },
              { name: 'App Configuration Settings', url: '/admin/settings', desc: 'General system preferences and branding options', tags: 'settings configuration branding site name logo title description' },
              { name: 'Settings: Timezone & Analytics', url: '/admin/settings?tab=localization', desc: 'Configure system timezone, currency, and Google Analytics ID', tags: 'settings timezone localization currency code google analytics tracker' },
              { name: 'Settings: Security & Signup controls', url: '/admin/settings?tab=security', desc: 'Toggle domestic trades, worker registration, and maintenance mode', tags: 'settings security verify employer signup registration age limits' },
              { name: 'Settings: Pricing Engine Policy', url: '/admin/settings?tab=pricing', desc: 'Configure credit cost per reveal, free credits, and bulk rates', tags: 'settings pricing flat rate tiers bulk discounts cost tax' },
              { name: 'Settings: Dynamic Social Links', url: '/admin/settings?tab=social', desc: 'Manage official social channels (Facebook, Twitter/X, LinkedIn etc.)', tags: 'settings social links facebook twitter x linkedin youtube handles' },
              { name: 'Settings: Payment Gateways', url: '/admin/settings?tab=payments', desc: 'Configure Easypaisa, JazzCash, Bank accounts and PayPal details', tags: 'settings payments bank wire transfer easypaisa jazzcash crypto wallets' },
              { name: 'Settings: Helpline Support Info', url: '/admin/settings?tab=helpline', desc: 'Configure customer support phone, email, and head office address', tags: 'settings helpline support phone email office address contact' }
          ],
          init() {
              this.searchResults = this.allItems;
              this.$watch('searchQuery', value => {
                  if (!value) {
                      this.searchResults = this.allItems;
                      return;
                  }
                  const q = value.toLowerCase();
                  this.searchResults = this.allItems.filter(item => 
                      item.name.toLowerCase().includes(q) || 
                      item.desc.toLowerCase().includes(q) || 
                      item.tags.toLowerCase().includes(q)
                  );
              });
          }
      }"
      @keydown.window.prevent.ctrl.k="searchOpen = true; $nextTick(() => $refs.searchInput.focus())"
      @keydown.window.prevent.cmd.k="searchOpen = true; $nextTick(() => $refs.searchInput.focus())"
      @keydown.window.escape="searchOpen = false">
    @if (\App\Models\Setting::get('enable_maintenance_banner', false))
        <div class="bg-amber-600 text-amber-50 px-4 py-2 text-center text-xs font-bold font-sans flex items-center justify-center gap-2 border-b border-amber-500/30 z-50 relative">
            <svg class="w-4 h-4 text-amber-50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span>{{ \App\Models\Setting::get('maintenance_message', 'Scheduled system maintenance is currently underway.') }}</span>
        </div>
    @endif

    <!-- Ambient Radial Glows -->
    <div class="pointer-events-none absolute -top-40 left-1/4 h-[500px] w-[500px] rounded-full bg-indigo-600/5 dark:bg-indigo-650/10 blur-[120px]"></div>
    <div class="pointer-events-none absolute top-1/3 -right-40 h-[600px] w-[600px] rounded-full bg-violet-650/5 dark:bg-violet-650/10 blur-[130px]"></div>
    
    <div class="flex flex-col lg:flex-row h-full min-h-screen p-4 sm:p-6 lg:p-8 gap-6 w-full max-w-[1680px] mx-auto z-10 relative">
        <!-- Sidebar Menu Desktop (Floating Glass Card) -->
        <aside class="hidden lg:flex flex-col w-72 shrink-0 sticky top-8 h-[calc(100vh-4rem)] border border-slate-800 bg-slate-900/80 dark:bg-slate-950/40 backdrop-blur-xl rounded-3xl shadow-xl p-6 transition-all duration-300 overflow-hidden">
            <div class="pb-6 mb-6 border-b border-slate-800 shrink-0">
                <a href="/admin" class="flex items-center gap-3.5 group">
                    <div class="h-11 w-11 rounded-2xl bg-indigo-600 flex items-center justify-center text-white font-extrabold text-base shadow-lg shadow-indigo-500/20 group-hover:rotate-12 group-hover:scale-105 transition-all duration-300">
                        {{ substr(\App\Models\Setting::get('logo_text', 'KP'), 0, 2) }}
                    </div>
                    <div>
                        <span class="font-extrabold tracking-tight text-sm block text-slate-100 group-hover:text-indigo-400 transition-colors duration-300">
                            {{ \App\Models\Setting::get('site_short_name', 'KP-LM') }} Admin
                        </span>
                        <span class="text-[9px] text-indigo-500 font-bold tracking-widest uppercase block -mt-0.5">Control Center</span>
                    </div>
                </a>
            </div>

            <!-- Sidebar Navigation Links -->
            <nav class="flex-grow space-y-1.5 overflow-y-auto pr-1">
                @php
                    $navItems = [
                        ['url' => '/admin', 'label' => 'Dashboard Overview', 'icon' => '📊', 'exact' => true],
                        ['url' => '/admin/users', 'label' => 'User Management', 'icon' => '👥', 'exact' => false],
                        ['url' => '/admin/workers', 'label' => 'Worker Trades Registry', 'icon' => '👷', 'exact' => false],
                        ['url' => '/admin/roles', 'label' => 'Custom Security Roles', 'icon' => '🔑', 'exact' => false],
                        ['url' => '/admin/permissions', 'label' => 'Granular Permissions', 'icon' => '🔒', 'exact' => false],
                        ['url' => '/admin/orders', 'label' => 'Credit Purchases', 'icon' => '💳', 'exact' => false],
                        ['url' => '/admin/logs', 'label' => 'Contact Reveal Logs', 'icon' => '📜', 'exact' => false],
                        ['url' => '/admin/audit', 'label' => 'System Audit Logs', 'icon' => '📝', 'exact' => false],
                        ['url' => '/admin/settings', 'label' => 'App Configuration', 'icon' => '⚙️', 'exact' => false],
                    ];
                @endphp

                @foreach ($navItems as $item)
                    @php
                        $isActive = $item['exact'] ? request()->is(ltrim($item['url'], '/')) : request()->is(ltrim($item['url'], '/') . '*');
                    @endphp
                    <a href="{{ $item['url'] }}" 
                       class="group relative flex items-center gap-3.5 px-4 py-3 rounded-2xl text-xs font-bold transition-all duration-300 {{ $isActive ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-850/60' }}">
                        <span class="text-base leading-none bg-slate-850/60 dark:bg-slate-900/60 p-2 rounded-xl group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">{{ $item['icon'] }}</span>
                        <span class="tracking-wide">{{ $item['label'] }}</span>
                        @if ($isActive)
                            <span class="absolute right-4 h-1.5 w-1.5 rounded-full bg-white animate-pulse"></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <!-- Bottom Profile / Logout action -->
            @auth
                <div class="mt-auto p-4 border border-slate-800/80 bg-slate-850/60 rounded-2xl flex flex-col gap-3 shadow-inner">
                    <div class="flex items-center gap-3.5">
                        <div class="h-10 w-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-md">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="flex-grow min-w-0">
                            <span class="block text-xs font-black text-slate-100 truncate">{{ Auth::user()->name }}</span>
                            <span class="block text-[9px] font-bold text-slate-500 truncate mt-0.5">{{ Auth::user()->email }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5 w-full mt-1">
                        <a href="/" class="flex-grow flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-850 text-[10px] font-bold text-slate-350 hover:text-slate-100 transition-colors shadow-sm duration-200">
                            🏠 View Site
                        </a>
                        <a href="/logout" class="p-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 transition-colors flex items-center justify-center border border-rose-500/20 shadow-sm duration-200" title="Log Out">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </a>
                    </div>
                </div>
            @endauth
        </aside>

        <!-- Mobile Sidebar Slideover -->
        <div x-show="mobileSidebarOpen" class="relative z-50 lg:hidden" role="dialog" aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="mobileSidebarOpen = false"></div>
            <div class="fixed inset-y-0 left-0 flex w-full max-w-xs bg-slate-900 border-r border-slate-800 p-6 flex-col">
                <div class="p-4 mb-6 border-b border-slate-800 flex justify-between items-center">
                    <a href="/admin" class="flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-extrabold text-sm shadow-md">
                            {{ substr(\App\Models\Setting::get('logo_text', 'KP'), 0, 2) }}
                        </div>
                        <span class="font-extrabold text-sm text-slate-100 dark:text-white">{{ \App\Models\Setting::get('site_short_name', 'KP-LM') }} Admin</span>
                    </a>
                    <button type="button" @click="mobileSidebarOpen = false" class="text-slate-400 hover:text-slate-800 dark:hover:text-white font-bold p-1">✕</button>
                </div>

                <nav class="flex-grow space-y-1.5 overflow-y-auto">
                    @foreach ($navItems as $item)
                        @php
                            $isActive = $item['exact'] ? request()->is(ltrim($item['url'], '/')) : request()->is(ltrim($item['url'], '/') . '*');
                        @endphp
                        <a href="{{ $item['url'] }}" @click="mobileSidebarOpen = false"
                           class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all {{ $isActive ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-850' }}">
                            <span class="text-sm bg-slate-850 dark:bg-slate-900 p-1.5 rounded-lg">{{ $item['icon'] }}</span>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                @auth
                    <div class="p-4 border border-slate-800 bg-slate-850 m-4 rounded-2xl flex flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-indigo-500/15 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-extrabold text-xs">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div class="flex-grow min-w-0">
                                <span class="block text-xs font-bold text-slate-800 dark:text-white truncate">{{ Auth::user()->name }}</span>
                                <span class="block text-[9px] text-slate-500 dark:text-slate-500 truncate">{{ Auth::user()->email }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="/" class="flex-grow flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 hover:bg-slate-850 text-[10px] font-bold text-slate-500 dark:text-slate-400 transition-colors">
                                🏠 View Site
                            </a>
                        </div>
                    </div>
                @endauth
            </div>
        </div>

        <!-- Main Workspace Area -->
        <div class="flex-grow flex flex-col min-w-0 bg-slate-900 border border-slate-800 rounded-3xl shadow-xl overflow-hidden transition-all duration-300">
            <!-- Top Navigation Header -->
            <header class="border-b border-slate-800 bg-slate-900/60 dark:bg-slate-950/40 backdrop-blur-md sticky top-0 z-40 py-4 px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <!-- Mobile Hamburger Button -->
                        <button type="button" @click="mobileSidebarOpen = true" class="lg:hidden p-2 rounded-xl border border-slate-800 text-slate-400 hover:text-slate-100 hover:bg-slate-850">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>

                        <h2 class="text-xs sm:text-sm font-extrabold text-slate-400 capitalize tracking-tight flex items-center gap-2">
                            <span class="p-1.5 rounded bg-indigo-500/10 text-indigo-500 text-xs">🛠️</span>
                            <span>Console</span>
                            <span class="text-slate-650">/</span>
                            <span class="text-indigo-500 font-semibold font-mono tracking-wide">{{ Route::current() ? str_replace('admin.', '', Route::currentRouteName()) : 'overview' }}</span>
                        </h2>
                    </div>

                    <!-- Header Stats / Widgets -->
                    <div class="flex items-center gap-4">
                        <!-- Spotlight search shortcut indicator -->
                        <div @click="searchOpen = true; $nextTick(() => $refs.searchInput.focus())"
                             class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 bg-slate-850 border border-slate-800 rounded-xl text-[10px] font-bold text-slate-500 select-none cursor-pointer hover:bg-slate-800 transition-colors">
                            <span>🔍 Search</span>
                            <kbd class="bg-slate-900 px-1.5 py-0.5 rounded-lg border border-slate-800 font-mono text-[9px] text-slate-400 shadow-sm">Ctrl K</kbd>
                        </div>

                        <!-- Clock Widget -->
                        <div id="admin-clock" class="hidden md:flex items-center px-3 py-1.5 bg-slate-850 border border-slate-800 rounded-xl text-xs font-semibold select-none shadow-inner">
                            <!-- Populated dynamically via JS -->
                            <span class="text-slate-400 font-medium">Clock loading...</span>
                        </div>

                        <!-- System Operational Status Dot -->
                        @php
                            $maintenance = \App\Models\Setting::get('maintenance_mode', false);
                        @endphp
                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border {{ $maintenance ? 'bg-rose-500/10 border-rose-500/20 text-rose-500' : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500' }} text-[10px] font-bold uppercase tracking-wider select-none shadow-sm">
                            <span class="h-2 w-2 rounded-full {{ $maintenance ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500 animate-pulse' }}"></span>
                            <span class="hidden sm:inline">{{ $maintenance ? 'Maintenance Mode' : 'System Operational' }}</span>
                            <span class="sm:hidden">{{ $maintenance ? 'Maint' : 'Active' }}</span>
                        </div>

                        <!-- Theme Toggle Capsule -->
                        <div class="flex items-center gap-0.5 bg-slate-850 border border-slate-800 rounded-xl p-0.5 text-xs shadow-inner">
                            <button type="button" id="theme-btn-light" onclick="setTheme('light')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer transition-all" title="Light Mode">☀️</button>
                            <button type="button" id="theme-btn-dark" onclick="setTheme('dark')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer transition-all" title="Dark Mode">🌙</button>
                            <button type="button" id="theme-btn-system" onclick="setTheme('system')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer transition-all" title="System Theme">🖥️</button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content Slot -->
            <main class="flex-grow p-6 lg:p-8 overflow-y-auto bg-slate-900/50">
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- Clock Widget Logic -->
    <script>
        function updateClock() {
            const now = new Date();
            const timezone = '{{ \App\Models\Setting::get('timezone', 'Asia/Karachi') }}';
            const timeString = now.toLocaleTimeString('en-US', { timeZone: timezone, hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });
            const dateString = now.toLocaleDateString('en-US', { timeZone: timezone, weekday: 'short', month: 'short', day: 'numeric' });
            const clockEl = document.getElementById('admin-clock');
            if (clockEl) {
                clockEl.innerHTML = `<span class="text-slate-500 font-medium">${dateString}</span> <span class="text-slate-350 mx-2">•</span> <span class="text-indigo-500 font-mono font-extrabold tracking-wide">${timeString}</span>`;
            }
        }
        setInterval(updateClock, 1000);
        document.addEventListener("DOMContentLoaded", updateClock);
    </script>

    @livewireScripts

    <!-- Spotlight Search Modal -->
    <div x-show="searchOpen" class="fixed inset-0 z-50 flex items-start justify-center pt-[15vh] px-4" style="display: none;">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/80 backdrop-blur-sm" @click="searchOpen = false"></div>
        
        <!-- Search Dialog Panel -->
        <div x-show="searchOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden z-10 font-sans">
            
            <!-- Search Input Header -->
            <div class="flex items-center gap-3 border-b border-slate-800 px-4 py-3 bg-slate-950/20">
                <span class="text-slate-400 text-sm">🔍</span>
                <input x-ref="searchInput" 
                       x-model="searchQuery" 
                       type="text" 
                       placeholder="Search admin sections, controls or settings..." 
                       class="w-full bg-transparent border-0 outline-none text-xs text-slate-100 placeholder-slate-500 pl-1 focus:ring-0 focus:outline-none" />
                <span class="text-[9px] bg-slate-850 px-1.5 py-0.5 rounded-lg border border-slate-800 text-slate-400 font-mono select-none">ESC</span>
            </div>

            <!-- Search Results list -->
            <div class="max-h-[300px] overflow-y-auto p-2 space-y-1">
                <template x-for="item in searchResults" :key="item.name">
                    <a :href="item.url" 
                       class="flex flex-col gap-0.5 px-3.5 py-2.5 rounded-2xl hover:bg-indigo-600 group transition-all duration-150 cursor-pointer">
                        <span class="text-[11px] font-extrabold text-slate-200 group-hover:text-white" x-text="item.name"></span>
                        <span class="text-[9px] text-slate-400 group-hover:text-indigo-200" x-text="item.desc"></span>
                    </a>
                </template>
                
                <div x-show="searchResults.length === 0" class="py-12 text-center text-slate-500 text-xs font-bold">
                    No results found for "<span class="text-slate-400" x-text="searchQuery"></span>"
                </div>
            </div>
            
            <!-- Footer controls -->
            <div class="border-t border-slate-800 px-4 py-2.5 bg-slate-950/40 text-[9px] text-slate-500 font-bold flex items-center justify-between">
                <span>Use keyboard arrows or mouse to select</span>
                <span>Press <kbd class="bg-slate-900 border border-slate-800 px-1 rounded shadow-sm text-[8px] font-mono text-slate-400">Ctrl K</kbd> to open</span>
            </div>
        </div>
    </div>
</body>
</html>
