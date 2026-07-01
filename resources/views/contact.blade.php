<x-layouts.app>
    <div class="max-w-4xl mx-auto px-4 py-12 sm:py-20 relative">
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
                {{ __('Contact Us') }}
            </h1>
            <p class="text-slate-400 text-sm sm:text-base leading-relaxed">
                {{ __('Get in touch with the support cell or visit the official headquarters of the KP Department of Labor.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Contact Information Cards -->
            <div class="md:col-span-1 space-y-6">
                <!-- Card 1: Helpline -->
                <div class="bg-slate-900/40 border border-slate-800 p-5 rounded-2xl backdrop-blur-md">
                    <span class="text-[10px] uppercase font-bold text-indigo-400 tracking-wider block mb-1">
                        {{ __('Helpline Number') }}
                    </span>
                    <span class="text-lg font-black text-white block">{{ \App\Models\Setting::get('support_phone', '091-9210401') }}</span>
                    <span class="text-xs text-slate-500 mt-1 block">{{ __('Mon - Fri, 9:00 AM - 5:00 PM') }}</span>
                </div>

                <!-- Card 2: Email -->
                <div class="bg-slate-900/40 border border-slate-800 p-5 rounded-2xl backdrop-blur-md">
                    <span class="text-[10px] uppercase font-bold text-violet-400 tracking-wider block mb-1">
                        {{ __('Support Email') }}
                    </span>
                    <span class="text-sm font-black text-white block select-all">{{ \App\Models\Setting::get('support_email', 'support.labor@kp.gov.pk') }}</span>
                    <span class="text-xs text-slate-500 mt-1 block">{{ __('Response within 24 hours') }}</span>
                </div>

                <!-- Card 3: Address -->
                <div class="bg-slate-900/40 border border-slate-800 p-5 rounded-2xl backdrop-blur-md">
                    <span class="text-[10px] uppercase font-bold text-purple-400 tracking-wider block mb-1">
                        {{ __('Headquarters') }}
                    </span>
                    <p class="text-xs text-slate-300 leading-relaxed font-semibold">
                        {{ \App\Models\Setting::get('support_address', 'Directorate of Labor, Khyber Road, Peshawar, Khyber Pakhtunkhwa, Pakistan.') }}
                    </p>
                </div>
            </div>

            <!-- Interactive Message Form -->
            <div class="md:col-span-2 bg-slate-900/40 border border-slate-800 p-6 sm:p-8 rounded-3xl backdrop-blur-md">
                <h3 class="text-lg font-bold text-white mb-6">
                    {{ __('Submit an Inquiry') }}
                </h3>
                
                @if (session()->has('contact_success'))
                    <div class="mb-6 p-4 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 font-semibold text-center text-xs animate-bounce">
                        {{ session('contact_success') }}
                    </div>
                @endif

                <form method="POST" action="#" onsubmit="event.preventDefault(); alert('Inquiry Simulated Successfully!');" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-2">{{ __('Your Name') }}</label>
                            <input type="text" placeholder="Ahmad Khan" required
                                   class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-medium" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-2">{{ __('Mobile Number') }}</label>
                            <input type="text" placeholder="03001234567" required
                                   class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-medium" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2">{{ __('Subject') }}</label>
                        <input type="text" placeholder="e.g. Account Topup Issue" required
                               class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-medium" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-2">{{ __('Message') }}</label>
                        <textarea rows="4" placeholder="..." required
                                  class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-650 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-xs font-medium"></textarea>
                    </div>

                    <button type="submit" 
                            class="w-full py-3 rounded-xl font-extrabold text-xs text-white bg-gradient-to-r from-indigo-500 to-violet-600 hover:opacity-90 active:scale-98 transition-all cursor-pointer">
                        {{ __('Send Message') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
