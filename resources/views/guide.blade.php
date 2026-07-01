<x-layouts.app>
    <div class="max-w-4xl mx-auto px-4 py-12 sm:py-20 relative" x-data="{ currentTab: 'workers' }">
        <!-- Back Button -->
        <a href="/" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-indigo-400 mb-8 transition-colors">
            ← {{ __('Back to Home') }}
        </a>

        <!-- Header -->
        <div class="space-y-4 mb-12">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 uppercase tracking-widest">
                {{ __('Official Government Initiative') }}
            </span>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white font-display">
                {{ __('User Guide') }}
            </h1>
            <p class="text-slate-400 text-sm sm:text-base leading-relaxed">
                {{ __('Step-by-step illustrated manual for workers registering profiles and employers seeking skilled matches.') }}
            </p>
        </div>

        <!-- Selector Tabs -->
        <div class="flex gap-2 border-b border-slate-900 pb-3 mb-8">
            <button type="button" @click="currentTab = 'workers'"
                    :class="currentTab === 'workers' ? 'bg-indigo-500/10 border-indigo-500/30 text-indigo-400' : 'bg-transparent border-transparent text-slate-400 hover:text-white'"
                    class="px-4 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer">
                👷 {{ __('For Workers') }}
            </button>
            <button type="button" @click="currentTab = 'employers'"
                    :class="currentTab === 'employers' ? 'bg-violet-500/10 border-violet-500/30 text-violet-400' : 'bg-transparent border-transparent text-slate-400 hover:text-white'"
                    class="px-4 py-2 rounded-xl text-xs font-bold border transition-all cursor-pointer">
                💼 {{ __('For Employers') }}
            </button>
        </div>

        <!-- Tab 1: For Workers -->
        <div x-show="currentTab === 'workers'" class="space-y-8 animate-fadeIn">
            <!-- Step 1 -->
            <div class="bg-slate-900/30 border border-slate-800 p-6 rounded-2xl flex gap-4 items-start backdrop-blur-md">
                <div class="h-8 w-8 rounded-lg bg-indigo-500/10 flex items-center justify-center font-black text-indigo-400">1</div>
                <div class="space-y-2">
                    <h3 class="font-bold text-white text-base">
                        {{ __('Open Intake Form') }}
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ __('Navigate to the worker registration page. Select your language at the top (English, Urdu, or Pashto) to update all form labels.') }}
                    </p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="bg-slate-900/30 border border-slate-800 p-6 rounded-2xl flex gap-4 items-start backdrop-blur-md">
                <div class="h-8 w-8 rounded-lg bg-indigo-500/10 flex items-center justify-center font-black text-indigo-400">2</div>
                <div class="space-y-2">
                    <h3 class="font-bold text-white text-base">
                        {{ __('Enter Phone & Name') }}
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ __('Provide an active mobile number in 03xxxxxxxxx format. The system performs an upsert: it updates existing profiles instead of creating duplicates.') }}
                    </p>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="bg-slate-900/30 border border-slate-800 p-6 rounded-2xl flex gap-4 items-start backdrop-blur-md">
                <div class="h-8 w-8 rounded-lg bg-indigo-500/10 flex items-center justify-center font-black text-indigo-400">3</div>
                <div class="space-y-2">
                    <h3 class="font-bold text-white text-base">
                        {{ __('Select Sector and Trade') }}
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ __('Select Industrial or Domestic sector. Click your specific trade card (e.g. Electrician, Cook). If your trade is not listed, click "Other" to type your custom trade.') }}
                    </p>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="bg-slate-900/30 border border-slate-800 p-6 rounded-2xl flex gap-4 items-start backdrop-blur-md">
                <div class="h-8 w-8 rounded-lg bg-indigo-500/10 flex items-center justify-center font-black text-indigo-400">4</div>
                <div class="space-y-2">
                    <h3 class="font-bold text-white text-base">
                        {{ __('Set Experience & Save') }}
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ __('Use the interactive "+" and "-" buttons to set your years of experience. Set your immediate work availability, and click "Save Profile" to publish your details.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Tab 2: For Employers -->
        <div x-show="currentTab === 'employers'" class="space-y-8 animate-fadeIn">
            <!-- Step 1 -->
            <div class="bg-slate-900/30 border border-slate-800 p-6 rounded-2xl flex gap-4 items-start backdrop-blur-md">
                <div class="h-8 w-8 rounded-lg bg-violet-500/10 flex items-center justify-center font-black text-violet-400">1</div>
                <div class="space-y-2">
                    <h3 class="font-bold text-white text-base">
                        {{ __('Register Organization & Login') }}
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ __('Access the employer portal. If you are a new employer, select the Register tab and fill in your contact and firm details (Company Name, Address, City, sector, etc.) and set a password. Existing employers can log in directly using their email or mobile number along with their password.') }}
                    </p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="bg-slate-900/30 border border-slate-800 p-6 rounded-2xl flex gap-4 items-start backdrop-blur-md">
                <div class="h-8 w-8 rounded-lg bg-violet-500/10 flex items-center justify-center font-black text-violet-400">2</div>
                <div class="space-y-2">
                    <h3 class="font-bold text-white text-base">
                        {{ __('Search and Filter Directory') }}
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ __('Use the dashboard search box and select sectors, trades, or KP districts. The list dynamically loads matching workers, showcasing their sector, experience levels, and availability.') }}
                    </p>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="bg-slate-900/30 border border-slate-800 p-6 rounded-2xl flex gap-4 items-start backdrop-blur-md">
                <div class="h-8 w-8 rounded-lg bg-violet-500/10 flex items-center justify-center font-black text-violet-400">3</div>
                <div class="space-y-2">
                    <h3 class="font-bold text-white text-base">
                        {{ __('Reveal Contact & Concurrency') }}
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ __('Click "Reveal Contact" on any card to deduct 1 credit. The lock is pessimistic and transactional to prevent concurrent requests double-spending. If your wallet is empty, recharge via mock Easypaisa/JazzCash panels.') }}
                    </p>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="bg-slate-900/30 border border-slate-800 p-6 rounded-2xl flex gap-4 items-start backdrop-blur-md">
                <div class="h-8 w-8 rounded-lg bg-violet-500/10 flex items-center justify-center font-black text-violet-400">4</div>
                <div class="space-y-2">
                    <h3 class="font-bold text-white text-base">
                        {{ __('Post Labor Opportunities') }}
                    </h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ __('Navigate to the Jobs Board (/jobs) and click "Post a Job Request". Fill in salary offerings and trade details. Job postings are immediately viewable by matching workers.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
