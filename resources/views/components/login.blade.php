<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    public string $activeTab = 'login'; // 'login' or 'register'

    // Login Fields
    public string $loginIdentifier = ''; // Email or Mobile Phone
    public string $loginPassword = '';

    // Registration Fields
    public string $regAccountType = 'employer'; // 'employer' (Firm/Company) or 'contractor' (Contractor)
    public string $regName = ''; // Contact Person Name
    public string $regEmail = '';
    public string $regPhone = ''; // Contact Mobile (03xxxxxxxxx)
    public string $regLandline = ''; // Alternate Landline/Phone
    public string $regFirmName = ''; // Company Name
    public string $regFirmCity = ''; // Company City
    public string $regFirmAddress = '';
    public string $regFirmSector = '';
    public string $regFirmDistrict = '';
    public string $regPassword = '';
    public string $regPassword_confirmation = '';

    public function mount(string $activeTab = 'login'): void
    {
        if (in_array($activeTab, ['login', 'register'])) {
            $this->activeTab = $activeTab;
        }
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['login', 'register'])) {
            $this->activeTab = $tab;
            $this->resetErrorBag();
            $this->redirect('/' . $tab, navigate: true);
        }
    }

    public function login(): void
    {
        $this->validate([
            'loginIdentifier' => 'required|string',
            'loginPassword' => 'required|string',
        ], [
            'loginIdentifier.required' => __('Email or mobile number is required.'),
            'loginPassword.required' => __('Password is required.'),
        ]);

        // Attempt to find user by email or phone
        $user = User::where('email', $this->loginIdentifier)
            ->orWhere('phone', $this->loginIdentifier)
            ->first();

        if ($user && Hash::check($this->loginPassword, $user->password)) {
            Auth::login($user);
            session()->flash('success', __('Logged in successfully!'));
            $this->redirect('/directory', navigate: true);
        } else {
            $this->addError('loginIdentifier', __('Invalid credentials or password.'));
        }
    }

    public function register(): void
    {
        $this->validate([
            'regAccountType' => 'required|in:employer,contractor',
            'regName' => 'required|string|min:3',
            'regEmail' => 'required|email|unique:users,email',
            'regPhone' => 'required|regex:/^(03)[0-9]{9}$/|unique:users,phone',
            'regLandline' => 'nullable|string|max:15',
            'regFirmName' => 'required|string|min:3',
            'regFirmCity' => 'required|string|min:2',
            'regFirmAddress' => 'required|string|min:10',
            'regFirmSector' => 'required|string',
            'regFirmDistrict' => 'required|string',
            'regPassword' => 'required|string|min:6|confirmed',
        ], [
            'regAccountType.required' => __('Please select your account type.'),
            'regAccountType.in' => __('Invalid account type selected.'),
            'regName.required' => __('Contact person name is required.'),
            'regEmail.required' => __('Email address is required.'),
            'regEmail.unique' => __('This email address is already registered.'),
            'regPhone.required' => __('Mobile number is required.'),
            'regPhone.regex' => __('Mobile number must be in format 03001234567.'),
            'regPhone.unique' => __('This mobile number is already registered.'),
            'regFirmName.required' => __('Organization/Firm name is required.'),
            'regFirmCity.required' => __('City is required.'),
            'regFirmAddress.required' => __('Firm address is required.'),
            'regFirmAddress.min' => __('Please enter complete firm address details (min 10 chars).'),
            'regFirmSector.required' => __('Business sector is required.'),
            'regFirmDistrict.required' => __('Please select the company district.'),
            'regPassword.required' => __('Password is required.'),
            'regPassword.min' => __('Password must be at least 6 characters.'),
            'regPassword.confirmed' => __('Password confirmation does not match.'),
        ]);

        $requireApproval = \App\Models\Setting::get('require_employer_approval', true);
        $welcomeCredits = (int)\App\Models\Setting::get('default_welcome_credits', 5);

        // Create User
        $user = User::create([
            'name' => $this->regName,
            'email' => $this->regEmail,
            'phone' => $this->regPhone,
            'password' => Hash::make($this->regPassword),
            'role' => $this->regAccountType,
            'is_approved' => !$requireApproval,
            'available_credits' => $welcomeCredits,
        ]);

        $user->assignRole($this->regAccountType);

        // Create User Profile
        $user->profile()->create([
            'company_name' => $this->regFirmName,
            'company_email' => $this->regEmail,
            'address' => $this->regFirmAddress,
            'sector' => $this->regFirmSector,
            'alternate_phone' => $this->regLandline,
            'district' => $this->regFirmDistrict,
            'city' => $this->regFirmCity,
            'province' => 'Khyber Pakhtunkhwa',
        ]);

        Auth::login($user);

        session()->flash('success', __('Organization registered and logged in successfully!'));
        $this->redirect('/directory', navigate: true);
    }
};
?>

<div class="flex-grow flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8 relative min-h-[70vh]">
    <div class="w-full max-w-xl space-y-6 bg-slate-900/40 border border-slate-800/80 p-6 sm:p-10 rounded-3xl backdrop-blur-md shadow-2xl relative">
        <!-- Glow Aura -->
        <div class="absolute -top-10 -left-10 w-24 h-24 bg-indigo-500/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="text-center space-y-2">
            <h2 class="text-3xl font-black tracking-tight text-white font-display">
                {{ __('Employer Portal') }}
            </h2>
            <p class="text-xs sm:text-sm text-slate-400">
                {{ __('Sign in to your account or register your organization.') }}
            </p>
        </div>

        <!-- Custom segment switcher -->
        <div class="flex p-1 gap-1.5 bg-slate-900/50 border border-slate-800/60 rounded-2xl shadow-inner max-w-sm mx-auto">
            <button type="button" wire:click="setTab('login')" 
                    class="flex-1 py-2 rounded-xl text-xs font-bold transition-all duration-200 cursor-pointer text-center {{ $activeTab === 'login' ? 'bg-gradient-to-r from-indigo-500 to-violet-600 text-white shadow-md scale-102 font-black' : 'text-slate-400 hover:text-slate-200' }}">
                {{ __('Login') }}
            </button>
            <button type="button" wire:click="setTab('register')" 
                    class="flex-1 py-2 rounded-xl text-xs font-bold transition-all duration-200 cursor-pointer text-center {{ $activeTab === 'register' ? 'bg-gradient-to-r from-indigo-500 to-violet-600 text-white shadow-md scale-102 font-black' : 'text-slate-400 hover:text-slate-200' }}">
                {{ __('Register') }}
            </button>
        </div>

        <!-- Forms Container -->
        <div class="mt-4">
            <!-- 1. LOGIN FORM -->
            @if ($activeTab === 'login')
                <form wire:submit="login" class="space-y-5 animate-fadeIn">
                    <div class="space-y-4">
                        <div>
                            <label for="loginIdentifier" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">
                                {{ __('Email or Mobile Number') }}
                            </label>
                            <input wire:model="loginIdentifier" id="loginIdentifier" type="text" placeholder="e.g. info@firm.com or 03001234567" 
                                   class="appearance-none block w-full px-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm" />
                            @error('loginIdentifier')
                                <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="loginPassword" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">
                                {{ __('Password') }}
                            </label>
                            <input wire:model="loginPassword" id="loginPassword" type="password" placeholder="••••••••" 
                                   class="appearance-none block w-full px-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-sm" />
                            @error('loginPassword')
                                <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <button type="submit" id="btn-login-submit" class="group relative w-full flex justify-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-gradient-to-r from-indigo-500 to-violet-600 hover:opacity-90 shadow-lg shadow-indigo-500/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all cursor-pointer">
                            {{ __('Enter Platform') }}
                        </button>
                    </div>
                </form>
            @endif

            <!-- 2. REGISTER FORM -->
            @if ($activeTab === 'register')
                <form wire:submit="register" class="space-y-6 animate-fadeIn">
                    
                    <!-- Form Section: Contact Details -->
                    <div class="space-y-4">
                        <div class="border-b border-slate-800/80 pb-2">
                            <h3 class="text-xs font-black text-indigo-400 uppercase tracking-widest">{{ __('Contact Person Details') }}</h3>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="regName" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                    {{ __('Contact Person Name') }}
                                </label>
                                <input wire:model="regName" id="regName" type="text" placeholder="e.g. Ali Khan" 
                                       class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs" />
                                @error('regName')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="regEmail" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                    {{ __('Contact Email') }}
                                </label>
                                <input wire:model="regEmail" id="regEmail" type="email" placeholder="e.g. ali@firm.com" 
                                       class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs" />
                                @error('regEmail')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="regPhone" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                    {{ __('Contact Mobile') }}
                                </label>
                                <input wire:model="regPhone" id="regPhone" type="text" placeholder="e.g. 03001234567" 
                                       class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs" />
                                @error('regPhone')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="regLandline" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                    {{ __('Alternate Landline/Mobile') }}
                                </label>
                                <input wire:model="regLandline" id="regLandline" type="text" placeholder="e.g. 0915840000" 
                                       class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs" />
                                @error('regLandline')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Form Section: Organization Details -->
                    <div class="space-y-4">
                        <div class="border-b border-slate-800/80 pb-2">
                            <h3 class="text-xs font-black text-indigo-400 uppercase tracking-widest">{{ __('Organization Details') }}</h3>
                        </div>

                        <div>
                            <label for="regAccountType" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                {{ __('Account Type') }}
                            </label>
                            <select wire:model="regAccountType" id="regAccountType" 
                                    class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-550 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs font-semibold">
                                <option value="employer">{{ __('Firm / Company') }}</option>
                                <option value="contractor">{{ __('Independent Contractor') }}</option>
                            </select>
                            @error('regAccountType')
                                <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="regFirmName" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                    {{ __('Firm / Company Name') }}
                                </label>
                                <input wire:model="regFirmName" id="regFirmName" type="text" placeholder="e.g. KP Construction" 
                                       class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs" />
                                @error('regFirmName')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="regFirmSector" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                    {{ __('Business Sector') }}
                                </label>
                                <select wire:model="regFirmSector" id="regFirmSector" 
                                        class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-550 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs">
                                    <option value="">-- {{ __('Choose Sector') }} --</option>
                                    <option value="Construction">Construction</option>
                                    <option value="Manufacturing">Manufacturing</option>
                                    <option value="Services">Services / Retail</option>
                                    <option value="Agriculture">Agriculture</option>
                                    <option value="Logistics">Logistics / Transport</option>
                                    <option value="Domestic">Domestic Services</option>
                                    <option value="Other">Other Sector</option>
                                </select>
                                @error('regFirmSector')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label for="regFirmDistrict" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                    {{ __('District') }}
                                </label>
                                <select wire:model="regFirmDistrict" id="regFirmDistrict" 
                                        class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-550 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs">
                                    <option value="">-- {{ __('Select District') }} --</option>
                                    <option value="Peshawar">Peshawar</option>
                                    <option value="Mardan">Mardan</option>
                                    <option value="Swat">Swat</option>
                                    <option value="Abbottabad">Abbottabad</option>
                                    <option value="Nowshera">Nowshera</option>
                                    <option value="Charsadda">Charsadda</option>
                                    <option value="Swabi">Swabi</option>
                                    <option value="Haripur">Haripur</option>
                                    <option value="Kohat">Kohat</option>
                                    <option value="Bannu">Bannu</option>
                                    <option value="Dera Ismail Khan">Dera Ismail Khan</option>
                                    <option value="Mansehra">Mansehra</option>
                                    <option value="Buner">Buner</option>
                                </select>
                                @error('regFirmDistrict')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="regFirmCity" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                    {{ __('City') }}
                                </label>
                                <input wire:model="regFirmCity" id="regFirmCity" type="text" placeholder="e.g. Peshawar" 
                                       class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs" />
                                @error('regFirmCity')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="province" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                    {{ __('Province') }}
                                </label>
                                <input id="province" type="text" value="Khyber Pakhtunkhwa" disabled 
                                       class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/40 border border-slate-800/50 rounded-xl text-slate-400 text-xs select-none" />
                            </div>
                        </div>

                        <div>
                            <label for="regFirmAddress" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                {{ __('Firm Address') }}
                            </label>
                            <textarea wire:model="regFirmAddress" id="regFirmAddress" placeholder="e.g. Office #102, Peshawar Mall, Phase 3 Hayatabad" rows="2"
                                      class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs resize-none"></textarea>
                            @error('regFirmAddress')
                                <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Section: Account Password -->
                    <div class="space-y-4">
                        <div class="border-b border-slate-800/80 pb-2">
                            <h3 class="text-xs font-black text-indigo-400 uppercase tracking-widest">{{ __('Account Security') }}</h3>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="regPassword" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                    {{ __('Password') }}
                                </label>
                                <input wire:model="regPassword" id="regPassword" type="password" placeholder="••••••••" 
                                       class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs" />
                                @error('regPassword')
                                    <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="regPassword_confirmation" class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1.5">
                                    {{ __('Confirm Password') }}
                                </label>
                                <input wire:model="regPassword_confirmation" id="regPassword_confirmation" type="password" placeholder="••••••••" 
                                       class="appearance-none block w-full px-3.5 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all text-xs" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-xs font-bold rounded-xl text-white bg-gradient-to-r from-indigo-500 to-violet-600 hover:opacity-90 shadow-lg shadow-indigo-500/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all cursor-pointer">
                            {{ __('Register Organization') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
