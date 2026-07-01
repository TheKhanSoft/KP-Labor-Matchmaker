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
                {{ __('About Us') }}
            </h1>
            <p class="text-slate-400 text-sm sm:text-base leading-relaxed">
                {{ __('Learn about the vision behind the KP Labor Matchmaking platform and our commitment to economic empowerment.') }}
            </p>
        </div>

        <!-- Glass Content Card -->
        <div class="bg-slate-900/40 border border-slate-800 p-8 rounded-3xl backdrop-blur-md space-y-8 text-slate-300 leading-relaxed text-sm">
            <div class="space-y-4">
                <h3 class="text-xl font-bold text-white">{{ __('Our Mission') }}</h3>
                <p>
                    {{ __('The Khyber Pakhtunkhwa Labor Matchmaking Platform is a public service initiative designed to eliminate middlemen, reduce recruitment friction, and connect skilled local labor directly with employers. By leveraging simple, bilingual, mobile-friendly PWA registration, we enable low-literacy workers in all 34 districts of KP to register their trades easily.') }}
                </p>
            </div>

            <div class="border-t border-slate-850 pt-8 space-y-4">
                <h3 class="text-xl font-bold text-white">{{ __('Core Objectives') }}</h3>
                <ul class="list-disc pl-5 space-y-2 text-slate-400">
                    <li>{{ __('Direct Connection: Eliminate commission-driven agents and job brokers.') }}</li>
                    <li>{{ __('Empowering Workers: Give workers ownership of their profiles and availability.') }}</li>
                    <li>{{ __('Employer Accessibility: Provide localized search filters to allow businesses to recruit nearby talent.') }}</li>
                </ul>
            </div>

            <div class="border-t border-slate-850 pt-8 space-y-4">
                <h3 class="text-xl font-bold text-white">{{ __('Government Commitment') }}</h3>
                <p>
                    {{ __('Under the guidance of the Department of Labor, Khyber Pakhtunkhwa, this platform is maintained as a free public utility. We use secure databases, pessimistic transaction locking to prevent credits manipulation, and dynamic SEO tools to maximize visibility.') }}
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
