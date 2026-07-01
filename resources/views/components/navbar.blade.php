<?php
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->to('/');
    }

    public function setLanguage(string $locale): void
    {
        if (in_array($locale, ['en', 'ur', 'ps'])) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
            $this->redirect(request()->header('Referer', '/'), navigate: true);
        }
    }
};
?>

<nav class="border-b border-slate-900 bg-slate-950/70 backdrop-blur-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-16 py-3 items-center justify-between">
            <!-- Brand and Logo -->
            <div class="flex items-center gap-3">
                <a href="/" class="flex items-center gap-2 group">
                    <div class="h-9 w-9 rounded-lg bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center text-white font-extrabold text-lg shadow-lg shadow-indigo-550/20 group-hover:scale-105 transition-transform font-sans">
                        {{ substr(\App\Models\Setting::get('logo_text', 'KP'), 0, 2) }}
                    </div>
                    <div>
                        <span class="font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-200 to-violet-400 tracking-wide text-base sm:text-lg block">
                            {{ \App\Models\Setting::get('site_name', 'KP Labor Matchmaker') }}
                        </span>
                        <span class="text-[9px] text-indigo-400 font-bold tracking-widest uppercase block -mt-0.5 font-sans">
                            {{ \App\Models\Setting::get('site_short_name', 'KP-LM') }} • {{ __('Official Government Initiative') }}
                        </span>
                    </div>
                </a>
            </div>

            <!-- Navigation Links & User Actions -->
            <div class="flex items-center gap-3 sm:gap-4">
                <!-- Navigation Items -->
                <a href="/register-worker" class="hidden md:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-300 hover:text-white transition-colors">
                    {{ __('Worker Registration') }}
                </a>
                <a href="/directory" class="hidden md:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-300 hover:text-white transition-colors">
                    {{ __('Search Directory') }}
                </a>
                <a href="/jobs" class="hidden md:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-300 hover:text-white transition-colors">
                    {{ __('Active Job Postings') }}
                </a>
                <a href="/guide" class="hidden md:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-300 hover:text-white transition-colors">
                    {{ __('User Guide') }}
                </a>
                <a href="/about" class="hidden lg:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-300 hover:text-white transition-colors">
                    {{ __('About Us') }}
                </a>
                <a href="/contact" class="hidden lg:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-300 hover:text-white transition-colors">
                    {{ __('Contact Us') }}
                </a>
                
                @auth
                    @if (Auth::user()->role === 'admin')
                        <a href="/admin" class="hidden md:inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                            {{ __('Admin Console') }}
                        </a>
                    @endif
                    <a href="/purchase" wire:navigate class="hidden md:inline-flex items-center gap-1.5 text-sm font-semibold text-slate-300 hover:text-white transition-colors {{ request()->is('purchase') ? 'text-indigo-455 font-bold' : '' }}">
                        {{ __('Buy Credits') }}
                    </a>
                @endauth

                <div class="h-4 w-[1px] bg-slate-800 hidden md:block"></div>

                <!-- Unified Settings Capsule (Language & Theme Switchers) -->
                <div class="flex items-center gap-2 bg-slate-900/50 border border-slate-800/60 rounded-2xl p-1 text-xs shadow-inner backdrop-blur-md">
                    <!-- Global Language Switcher Segment -->
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="setLanguage('en')" class="px-2 py-1.5 rounded-lg cursor-pointer transition-all duration-200 {{ app()->getLocale() === 'en' ? 'bg-gradient-to-r from-indigo-500 to-violet-600 text-white shadow-md scale-105 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/30' }}">EN</button>
                        <button type="button" wire:click="setLanguage('ur')" class="px-2.5 py-1.5 rounded-lg cursor-pointer transition-all duration-200 {{ app()->getLocale() === 'ur' ? 'bg-gradient-to-r from-indigo-500 to-violet-600 text-white shadow-md scale-105 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/30' }}">اردو</button>
                        <button type="button" wire:click="setLanguage('ps')" class="px-2.5 py-1.5 rounded-lg cursor-pointer transition-all duration-200 {{ app()->getLocale() === 'ps' ? 'bg-gradient-to-r from-indigo-500 to-violet-600 text-white shadow-md scale-105 font-black' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/30' }}">پښتو</button>
                    </div>

                    <div class="h-4 w-[1px] bg-slate-800/60"></div>

                    <!-- Theme Switcher Segment -->
                    <div class="flex items-center gap-0.5">
                        <!-- Light Mode Button -->
                        <button type="button" id="theme-btn-light" onclick="setTheme('light')" class="p-1.5 rounded-lg cursor-pointer transition-all duration-200 text-slate-400 hover:text-slate-200 hover:bg-slate-900/40" title="{{ __('Light Mode') }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707.707M12 7a5 5 0 100 10 5 5 0 000-10z"></path>
                            </svg>
                        </button>
                        <!-- Dark Mode Button -->
                        <button type="button" id="theme-btn-dark" onclick="setTheme('dark')" class="p-1.5 rounded-lg cursor-pointer transition-all duration-200 text-slate-400 hover:text-slate-200 hover:bg-slate-900/40" title="{{ __('Dark Mode') }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                        </button>
                        <!-- System Mode Button -->
                        <button type="button" id="theme-btn-system" onclick="setTheme('system')" class="p-1.5 rounded-lg cursor-pointer transition-all duration-200 text-slate-400 hover:text-slate-200 hover:bg-slate-900/40" title="{{ __('System Mode') }}">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                @auth
                    <!-- Logged in Employer / Admin -->
                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="flex flex-col items-end">
                            <span class="text-[10px] sm:text-xs font-semibold text-slate-200">{{ Auth::user()->name }}</span>
                            <a href="/credits" wire:navigate class="inline-flex items-center gap-1 text-[9px] font-bold text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded-full border border-indigo-500/20 hover:bg-indigo-500/20 hover:scale-[1.02] active:scale-[0.98] transition-all cursor-pointer">
                                <svg class="w-3 h-3 text-indigo-400 animate-pulse animate-duration-1000" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ Auth::user()->available_credits }} {{ __('Credits') }}
                            </a>
                        </div>
                        <button wire:click="logout" class="inline-flex items-center justify-center rounded-lg bg-slate-900 border border-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition-colors cursor-pointer">
                            ✕
                        </button>
                    </div>
                @else
                    <!-- Anonymous Guest -->
                    <div class="flex items-center gap-2">
                        <a href="/login" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-slate-900 border border-slate-800 px-3.5 py-1.5 text-xs font-bold text-slate-300 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer">
                            {{ __('Login') }}
                        </a>
                        <a href="/register" wire:navigate class="inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-indigo-500 to-violet-600 px-3.5 py-1.5 text-xs font-bold text-white hover:opacity-90 shadow-lg shadow-indigo-500/10 transition-all cursor-pointer">
                            {{ __('Register') }}
                        </a>
                    </div>
                @endauth
            </div>
        </div>
        
        <!-- Mobile Navigation Sub-Bar -->
        <div class="flex md:hidden gap-4 pb-3 overflow-x-auto text-xs font-bold border-t border-slate-900/40 pt-2 text-slate-400">
            <a href="/register-worker" class="whitespace-nowrap hover:text-white transition-colors {{ request()->is('register-worker') ? 'text-indigo-400' : '' }}">
                {{ __('Worker Registration') }}
            </a>
            <a href="/directory" class="whitespace-nowrap hover:text-white transition-colors {{ request()->is('directory') ? 'text-indigo-400' : '' }}">
                {{ __('Search Directory') }}
            </a>
            <a href="/jobs" class="whitespace-nowrap hover:text-white transition-colors {{ request()->is('jobs') ? 'text-indigo-400' : '' }}">
                {{ __('Active Job Postings') }}
            </a>
            <a href="/guide" class="whitespace-nowrap hover:text-white transition-colors {{ request()->is('guide') ? 'text-indigo-400' : '' }}">
                {{ __('User Guide') }}
            </a>
            <a href="/about" class="whitespace-nowrap hover:text-white transition-colors {{ request()->is('about') ? 'text-indigo-400' : '' }}">
                {{ __('About Us') }}
            </a>
            <a href="/contact" class="whitespace-nowrap hover:text-white transition-colors {{ request()->is('contact') ? 'text-indigo-400' : '' }}">
                {{ __('Contact Us') }}
            </a>
            @auth
                @if (Auth::user()->role === 'admin')
                    <a href="/admin" class="whitespace-nowrap text-indigo-400 hover:text-indigo-300 {{ request()->is('admin') ? 'text-indigo-400' : '' }}">
                        {{ __('Admin Console') }}
                    </a>
                @endif
                <a href="/credits" wire:navigate class="whitespace-nowrap hover:text-white transition-colors {{ request()->is('credits') ? 'text-indigo-400' : '' }}">
                    {{ __('Credits Manager') }}
                </a>
            @endauth
        </div>
    </div>
</nav>
