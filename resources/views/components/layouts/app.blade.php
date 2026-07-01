<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      dir="{{ in_array(app()->getLocale(), ['ur', 'ps']) ? 'rtl' : 'ltr' }}"
      class="h-full bg-slate-950 text-slate-100 antialiased selection:bg-indigo-500/30 selection:text-indigo-400">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ \App\Models\Setting::get('site_description', 'Official Khyber Pakhtunkhwa Labor Matchmaking Platform. Connect directly with skilled workers across KP districts including Peshawar, Mardan, Swat, and Abbottabad. No middleman, zero fees.') }}">
    <meta name="keywords" content="{{ \App\Models\Setting::get('site_keywords', 'Khyber Pakhtunkhwa, KP, Labor Matchmaking, Hire Workers, Welder, Cook, Maid, Pakistan, Job Portal') }}">
    <title>{{ $title ?? \App\Models\Setting::get('site_name', 'KP Labor Matchmaker') }}</title>

    <!-- Google Fonts: Outfit, Inter, Noto Sans Arabic & Noto Nastaliq Urdu -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&family=Noto+Sans+Arabic:wght@300;400;500;600;700;800;900&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">

    <!-- Styles / Scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    @if($gaId = \App\Models\Setting::get('google_analytics_id'))
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $gaId }}');
        </script>
    @endif

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
            const activeClasses = ['bg-gradient-to-r', 'from-indigo-500', 'to-violet-600', 'text-white', 'shadow-md', 'scale-105'];
            const inactiveClasses = ['text-slate-400', 'hover:text-slate-200', 'hover:bg-slate-900/40'];

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

        // Apply UI highlights on DOM load and immediately
        document.addEventListener("DOMContentLoaded", () => {
            const theme = localStorage.getItem('theme') || 'system';
            updateThemeUI(theme);
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (localStorage.getItem('theme') === 'system') {
                if (e.matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            }
        });
    </script>
</head>
<body class="min-h-full flex flex-col {{ app()->getLocale() === 'ur' ? 'font-urdu' : (app()->getLocale() === 'ps' ? 'font-pashto' : 'font-sans') }} relative overflow-x-hidden">
    @php
        $isAdmin = auth()->check() && auth()->user()->role === 'admin';
        $isMaintenanceMode = \App\Models\Setting::get('maintenance_mode', false);
    @endphp

    @if ($isMaintenanceMode && !$isAdmin)
        <!-- Premium Full-Screen Maintenance Page -->
        <div class="min-h-screen w-full bg-slate-950 flex flex-col items-center justify-center p-6 text-center relative overflow-hidden font-sans">
            <!-- Glow spots -->
            <div class="pointer-events-none absolute -top-40 left-1/4 h-[500px] w-[500px] rounded-full bg-indigo-600/10 blur-[120px]"></div>
            <div class="pointer-events-none absolute -bottom-20 right-10 h-[450px] w-[450px] rounded-full bg-purple-600/10 blur-[110px]"></div>

            <div class="max-w-md w-full bg-slate-900/40 border border-slate-800 p-8 sm:p-10 rounded-3xl backdrop-blur-md shadow-2xl relative space-y-6">
                <!-- Logo -->
                <div class="flex justify-center">
                    <div class="h-14 w-14 rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-650 flex items-center justify-center text-white font-extrabold text-xl shadow-lg shadow-indigo-550/20">
                        {{ substr(\App\Models\Setting::get('logo_text', 'KP'), 0, 2) }}
                    </div>
                </div>

                <div class="space-y-2">
                    <h1 class="text-2xl font-black text-white tracking-tight">{{ \App\Models\Setting::get('site_name', 'KP Labor Matchmaker') }}</h1>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold bg-amber-500/10 border border-amber-500/20 text-amber-400 uppercase tracking-widest mx-auto">
                        ⚠️ Maintenance Underway
                    </span>
                </div>

                <p class="text-sm text-slate-300 leading-relaxed">
                    {{ \App\Models\Setting::get('maintenance_message', 'Scheduled system maintenance is currently underway. We apologize for the inconvenience and will be back shortly.') }}
                </p>

                <div class="border-t border-slate-800/80 pt-6 space-y-3.5 text-xs text-slate-400">
                    <p class="font-bold uppercase tracking-wider text-[10px] text-slate-500">Need Immediate Assistance?</p>
                    <div class="flex flex-col gap-2 items-center">
                        <a href="tel:{{ \App\Models\Setting::get('support_phone', '091-9210401') }}" class="flex items-center gap-2 hover:text-white transition-colors">
                            <span>📞</span> <span>{{ \App\Models\Setting::get('support_phone', '091-9210401') }}</span>
                        </a>
                        <a href="mailto:{{ \App\Models\Setting::get('support_email', 'support.labor@kp.gov.pk') }}" class="flex items-center gap-2 hover:text-white transition-colors">
                            <span>✉️</span> <span>{{ \App\Models\Setting::get('support_email', 'support.labor@kp.gov.pk') }}</span>
                        </a>
                    </div>
                </div>

                <div class="pt-4">
                    <a href="/login" class="text-[10px] font-bold text-indigo-400 hover:text-indigo-300 transition-colors uppercase tracking-widest">
                        Admin Login &rarr;
                    </a>
                </div>
            </div>
            <div class="text-[10px] text-slate-650 mt-8">
                {{ \App\Models\Setting::get('footer_copyright_text', 'Confidential - Government of KP') }}
            </div>
        </div>
    @else
        @if (\App\Models\Setting::get('enable_maintenance_banner', false))
            <div class="bg-amber-600 text-amber-50 px-4 py-2 text-center text-xs font-bold font-sans flex items-center justify-center gap-2 border-b border-amber-500/30 z-50 relative">
                <svg class="w-4 h-4 text-amber-50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span>{{ \App\Models\Setting::get('maintenance_message', 'Scheduled system maintenance is currently underway.') }}</span>
            </div>
        @endif

        <!-- Ambient Radial Glows -->
        <div class="pointer-events-none absolute -top-40 left-1/4 h-[500px] w-[500px] rounded-full bg-indigo-600/10 blur-[120px]"></div>
        <div class="pointer-events-none absolute top-1/3 -right-40 h-[600px] w-[600px] rounded-full bg-violet-600/10 blur-[130px]"></div>
        <div class="pointer-events-none absolute -bottom-20 left-10 h-[450px] w-[450px] rounded-full bg-purple-600/10 blur-[110px]"></div>

        <!-- Navigation Header -->
        <livewire:navbar />

        <!-- Main Content Area -->
        <main class="flex-grow flex flex-col">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-900/60 bg-slate-950/40 backdrop-blur-md py-8 text-xs text-slate-500 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p>© {{ date('Y') }} {{ \App\Models\Setting::get('footer_copyright_text', 'Khyber Pakhtunkhwa Labor Matchmaking Platform. Confidential - Government of KP') }}</p>
                <div class="flex items-center gap-4">
                    @php
                        $socialLinksJson = \App\Models\Setting::get('social_links', '[]');
                        $socialLinks = json_decode($socialLinksJson, true) ?: [];
                        if (!function_exists('getSocialUrl')) {
                            function getSocialUrl(string $platform, string $username): string
                            {
                                $username = ltrim(trim($username), '@');
                                switch (strtolower($platform)) {
                                    case 'facebook': return "https://facebook.com/{$username}";
                                    case 'twitter':
                                    case 'x': return "https://x.com/{$username}";
                                    case 'linkedin': return "https://linkedin.com/in/{$username}";
                                    case 'instagram': return "https://instagram.com/{$username}";
                                    case 'youtube': return "https://youtube.com/@{$username}";
                                    case 'tiktok': return "https://tiktok.com/@{$username}";
                                    case 'whatsapp': return "https://wa.me/{$username}";
                                    case 'github': return "https://github.com/{$username}";
                                    default: return "https://{$platform}.com/{$username}";
                                }
                            }
                        }
                    @endphp
                    @foreach($socialLinks as $link)
                        @if(!empty($link['platform']) && !empty($link['username']))
                            <a href="{{ getSocialUrl($link['platform'], $link['username']) }}" target="_blank" rel="noopener noreferrer" class="hover:text-indigo-400 transition-colors font-semibold">
                                {{ $link['platform'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </footer>
    @endif

    @livewireScripts
</body>
</html>
