<x-layouts.app>
    <div class="flex-grow flex flex-col justify-center max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 relative">
        
        <!-- Main Hero Section (Split Layout) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center mb-20">
            
            <!-- Left Column: Action Panel & Messaging -->
            <div class="lg:col-span-7 space-y-8 text-left {{ in_array(app()->getLocale(), ['ur', 'ps']) ? 'lg:text-right' : '' }}">
                <div class="space-y-4">
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-[10px] font-bold bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 uppercase tracking-widest animate-pulse">
                        🛡️ {{ __('Official Government Initiative') }}
                    </span>
                    
                    <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-white leading-tight">
                        {{ __('Khyber Pakhtunkhwa') }} <br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-violet-300 to-purple-400">
                            {{ __('Labor Matchmaking') }}
                        </span>
                    </h1>
                    
                    @if(app()->getLocale() === 'en')
                        <h2 class="text-lg sm:text-xl font-bold text-slate-350 tracking-wide font-urdu">
                            خیبر پختونخوا لیبر میچ میکنگ پلیٹ فارم
                        </h2>
                    @endif

                    <p class="text-sm sm:text-base text-slate-400 leading-relaxed max-w-xl">
                        {{ __('Empowering skilled workers across all 34 districts of KP by establishing direct connections with employers. Simple, secure, and completely free of charge.') }}
                    </p>
                </div>

                <!-- Action Button Stack -->
                <div class="flex flex-col sm:flex-row gap-4 max-w-md">
                    <a href="/register-worker" class="group relative flex-1 inline-flex items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-indigo-500 to-violet-600 px-6 py-4 text-sm font-bold text-white hover:opacity-95 shadow-xl shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all cursor-pointer">
                        <svg class="w-5 h-5 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        <span>{{ __('Register as Worker') }}</span>
                    </a>
                    
                    <a href="/directory" class="group flex-1 inline-flex items-center justify-center gap-2.5 rounded-2xl bg-slate-900/40 border border-slate-800 hover:border-violet-500/40 hover:bg-slate-900/80 px-6 py-4 text-sm font-bold text-slate-200 hover:text-white transition-all cursor-pointer">
                        <svg class="w-5 h-5 text-slate-400 group-hover:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span>{{ __('Hire Skilled Workers') }}</span>
                    </a>
                </div>
            </div>

            <!-- Right Column: Live Platform Spotlight (Mock Dashboard) -->
            <div class="lg:col-span-5 relative">
                <!-- Glowing Aura Behind spotlight -->
                <div class="absolute -inset-2 bg-gradient-to-tr from-indigo-500 to-violet-600 rounded-3xl opacity-10 blur-xl pointer-events-none"></div>
                
                <div class="relative bg-slate-900/25 border border-slate-800/80 p-6 rounded-3xl backdrop-blur-md shadow-2xl">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800/60">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 absolute"></span>
                            <h3 class="text-xs font-black text-white uppercase tracking-wider pl-1.5">{{ __('Live Worker Spotlight') }}</h3>
                        </div>
                        <span class="text-[9px] font-bold text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded-full border border-indigo-500/20 uppercase">{{ __('Verified') }}</span>
                    </div>

                    <!-- Worker Spotlight Cards Feed -->
                    <div class="space-y-4">
                        <!-- Worker 1 -->
                        <div class="bg-slate-950/60 border border-slate-800/60 rounded-2xl p-4 flex gap-3.5 hover:border-slate-700 transition-colors">
                            <div class="h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-lg font-bold text-indigo-400">👷</div>
                            <div class="flex-grow space-y-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-extrabold text-white">Muhammad Irfan</h4>
                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-450">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ __('Available') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-slate-400">
                                    <span>{{ __('Welder') }} ({{ __('Industrial') }})</span>
                                    <span class="font-bold text-slate-350">Peshawar, KP</span>
                                </div>
                                <div class="flex items-center justify-between pt-1 text-[9px]">
                                    <span class="text-slate-500">{{ __('Experience') }}: <span class="font-bold text-slate-350">5 {{ __('Years') }}</span></span>
                                    <span class="text-amber-450 font-bold">★ 5.0</span>
                                </div>
                            </div>
                        </div>

                        <!-- Worker 2 -->
                        <div class="bg-slate-950/60 border border-slate-800/60 rounded-2xl p-4 flex gap-3.5 hover:border-slate-700 transition-colors">
                            <div class="h-10 w-10 rounded-xl bg-violet-500/10 flex items-center justify-center text-lg font-bold text-violet-400">⚡</div>
                            <div class="flex-grow space-y-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-extrabold text-white">Zia-ur-Rehman</h4>
                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-450">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ __('Available') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-slate-400">
                                    <span>{{ __('Electrician') }} ({{ __('Domestic') }})</span>
                                    <span class="font-bold text-slate-350">Mardan, KP</span>
                                </div>
                                <div class="flex items-center justify-between pt-1 text-[9px]">
                                    <span class="text-slate-500">{{ __('Experience') }}: <span class="font-bold text-slate-350">8 {{ __('Years') }}</span></span>
                                    <span class="text-amber-450 font-bold">★ 4.9</span>
                                </div>
                            </div>
                        </div>

                        <!-- Worker 3 -->
                        <div class="bg-slate-950/60 border border-slate-800/60 rounded-2xl p-4 flex gap-3.5 hover:border-slate-700 transition-colors">
                            <div class="h-10 w-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-lg font-bold text-purple-400">🔧</div>
                            <div class="flex-grow space-y-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-extrabold text-white">Sher Bahadur</h4>
                                    <span class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-450">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ __('Available') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-slate-400">
                                    <span>{{ __('Plumber') }} ({{ __('Industrial') }})</span>
                                    <span class="font-bold text-slate-350">Swat, KP</span>
                                </div>
                                <div class="flex items-center justify-between pt-1 text-[9px]">
                                    <span class="text-slate-500">{{ __('Experience') }}: <span class="font-bold text-slate-350">4 {{ __('Years') }}</span></span>
                                    <span class="text-amber-450 font-bold">★ 4.8</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supported Trades Catalog Showcase -->
        <div class="max-w-5xl mx-auto w-full mb-20 space-y-8">
            <div class="text-center space-y-2">
                <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    {{ __('Supported Skilled Trades') }}
                </h3>
                <p class="text-xs sm:text-sm text-slate-500 max-w-lg mx-auto">
                    {{ __('Connecting employers with verified professionals in these primary sectors.') }}
                </p>
            </div>
            
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <!-- Trade 1: Electrician -->
                <div class="group bg-slate-900/15 border border-slate-800/80 hover:border-indigo-500/40 p-4 sm:p-5 rounded-2xl flex items-center gap-4.5 backdrop-blur-xs transition-all hover:bg-slate-900/35 hover:-translate-y-0.5 duration-300">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/10 border border-indigo-500/10 flex items-center justify-center text-indigo-400 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-bold text-slate-300 group-hover:text-white transition-colors">{{ __('Electrician') }}</span>
                </div>

                <!-- Trade 2: Plumber -->
                <div class="group bg-slate-900/15 border border-slate-800/80 hover:border-indigo-500/40 p-4 sm:p-5 rounded-2xl flex items-center gap-4.5 backdrop-blur-xs transition-all hover:bg-slate-900/35 hover:-translate-y-0.5 duration-300">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/10 border border-indigo-500/10 flex items-center justify-center text-indigo-400 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-bold text-slate-300 group-hover:text-white transition-colors">{{ __('Plumber') }}</span>
                </div>

                <!-- Trade 3: Welder -->
                <div class="group bg-slate-900/15 border border-slate-800/80 hover:border-indigo-500/40 p-4 sm:p-5 rounded-2xl flex items-center gap-4.5 backdrop-blur-xs transition-all hover:bg-slate-900/35 hover:-translate-y-0.5 duration-300">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/10 border border-indigo-500/10 flex items-center justify-center text-indigo-400 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9l.879 2.121z"></path>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-bold text-slate-300 group-hover:text-white transition-colors">{{ __('Welder') }}</span>
                </div>

                <!-- Trade 4: Mason -->
                <div class="group bg-slate-900/15 border border-slate-800/80 hover:border-indigo-500/40 p-4 sm:p-5 rounded-2xl flex items-center gap-4.5 backdrop-blur-xs transition-all hover:bg-slate-900/35 hover:-translate-y-0.5 duration-300">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/10 border border-indigo-500/10 flex items-center justify-center text-indigo-400 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-bold text-slate-300 group-hover:text-white transition-colors">{{ __('Mason') }}</span>
                </div>

                <!-- Trade 5: Driver -->
                <div class="group bg-slate-900/15 border border-slate-800/80 hover:border-indigo-500/40 p-4 sm:p-5 rounded-2xl flex items-center gap-4.5 backdrop-blur-xs transition-all hover:bg-slate-900/35 hover:-translate-y-0.5 duration-300">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/10 border border-indigo-500/10 flex items-center justify-center text-indigo-400 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-bold text-slate-300 group-hover:text-white transition-colors">{{ __('Driver') }}</span>
                </div>

                <!-- Trade 6: Cook -->
                <div class="group bg-slate-900/15 border border-slate-800/80 hover:border-indigo-500/40 p-4 sm:p-5 rounded-2xl flex items-center gap-4.5 backdrop-blur-xs transition-all hover:bg-slate-900/35 hover:-translate-y-0.5 duration-300">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/10 border border-indigo-500/10 flex items-center justify-center text-indigo-400 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-bold text-slate-300 group-hover:text-white transition-colors">{{ __('Cook') }}</span>
                </div>

                <!-- Trade 7: Maid -->
                <div class="group bg-slate-900/15 border border-slate-800/80 hover:border-indigo-500/40 p-4 sm:p-5 rounded-2xl flex items-center gap-4.5 backdrop-blur-xs transition-all hover:bg-slate-900/35 hover:-translate-y-0.5 duration-300">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/10 border border-indigo-500/10 flex items-center justify-center text-indigo-400 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 00-2-2v4a2 2 0 002-2zm-3-3V3m-3 3V3M9 3v3M6 3v3"></path>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-bold text-slate-300 group-hover:text-white transition-colors">{{ __('Maid') }}</span>
                </div>

                <!-- Trade 8: Carpenter -->
                <div class="group bg-slate-900/15 border border-slate-800/80 hover:border-indigo-500/40 p-4 sm:p-5 rounded-2xl flex items-center gap-4.5 backdrop-blur-xs transition-all hover:bg-slate-900/35 hover:-translate-y-0.5 duration-300">
                    <div class="h-10 w-10 rounded-xl bg-indigo-500/10 border border-indigo-500/10 flex items-center justify-center text-indigo-400 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4H4v14a2 2 0 002 2h12a2 2 0 002-2v-5M9 9h1.5a1.5 1.5 0 100-3H9v3z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2 2-6 6H10v-2l6-6z"></path>
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-bold text-slate-300 group-hover:text-white transition-colors">{{ __('Carpenter') }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Platform Stats -->
        <div class="max-w-4xl mx-auto w-full border-t border-slate-900/60 pt-10 grid grid-cols-3 gap-6 text-center">
            <div class="bg-slate-900/10 border border-slate-800/50 p-4.5 rounded-2xl backdrop-blur-xs">
                <span class="block text-3xl sm:text-4xl font-extrabold text-white font-mono leading-none">34</span>
                <span class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mt-2.5">{{ __('KP Districts') }}</span>
            </div>
            <div class="bg-slate-900/10 border border-slate-800/50 p-4.5 rounded-2xl backdrop-blur-xs">
                <span class="block text-3xl sm:text-4xl font-extrabold text-white font-mono leading-none">100%</span>
                <span class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mt-2.5">{{ __('Direct Match') }}</span>
            </div>
            <div class="bg-slate-900/10 border border-slate-800/50 p-4.5 rounded-2xl backdrop-blur-xs">
                <span class="block text-3xl sm:text-4xl font-extrabold text-white font-mono leading-none">0</span>
                <span class="block text-[10px] uppercase font-bold text-slate-500 tracking-widest mt-2.5">{{ __('Hidden Fees') }}</span>
            </div>
        </div>

    </div>
</x-layouts.app>
