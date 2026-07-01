# 👷 Khyber Pakhtunkhwa Labor Matchmaker (KP-LM)

An official, premium digital matchmaking portal designed to directly connect skilled laborers (welders, plumbers, electricians, cooks, maids) across Khyber Pakhtunkhwa with hiring firms, organizations, and independent contractors. By eliminating middlemen and agency fees, KP-LM simplifies recruitment and drives transparent economic opportunities.

---

## 🌟 Core Architecture Features

- **Trilingual Localization Support**: Full translations for **English**, **Urdu**, and **Pashto** (utilizing custom fonts like *Noto Nastaliq Urdu* and *Bahij Muna Pashto* with responsive size scaling).
- **Floating Glass sidebars**: Constrained sidebar workspaces matching the high-fidelity aesthetics of modern SaaS portals.
- **Spotlight Search (`Ctrl + K` / `Cmd + K`)**: Alpine.js-powered instant site-wide navigation matching the current workspace context.
- **Dynamic Pricing Engine**: Fully configurable credit pricing supporting three distinct models:
  - *Fixed/Flat rate* per credit token.
  - *Monotonic Volume Bulk discount tiers* (with safety floor logic).
  - *Cumulative Graduated pricing brackets* (rate applies only to units inside each specific bracket).
- **Two-Stage Payments & Verification**: Checkout wizard that registers orders immediately as pending, allowing employers to upload payment screenshots (TxID references) either on the spot or later.

---

## 💻 Workspaces & Panels

### 👑 1. Admin Console (`/admin`)
The administrative control center allows system operators to manage the entire platform workflow:
- **Application Configuration Settings**: Tabbed dashboard managing Branding, Localization, Security & Signup rules, Credit Pricing Policies, Payment Gateways (Bank transfer, Easypaisa, JazzCash, Crypto), and Helpline contacts.
- **Credit Orders & Verification**: Audit interface to review transaction logs, inspect uploaded proof screenshots, and approve/refund/fail orders.
- **Users Directory**: Manage system administrators, firms, and contractors; approve/suspend accounts, adjust wallets, and allocate manual credits.
- **Workers Registry**: Browse all registered skilled laborers, update statuses, and register custom trade categories.
- **Activity & Security Audit Trails**: Live logs capturing administrative actions, configuration changes, and deletes.

### 🏢 2. Employer / Firm Workspace (`/directory`)
Dedicated workspace for hiring managers and company representatives:
- **Search Worker Directory**: Filter candidate pools by sector (Industrial, Domestic), specific trade (Electrician, Plumber, Cook, etc.), experience years, and district.
- **Candidate Unlock (Credit Deductions)**: Burn credit tokens to instantly reveal contact details of available workers.
- **Credits Ledger (`/credits`)**: View dynamic credit balances, approved purchase inflows, and detailed candidate reveal deduction logs.
- **Top-up checkout wizard (`/purchase`)**: Multi-step checkout flow (Choose Credits $\rightarrow$ Select Gateway $\rightarrow$ Confirm Checkout parameters $\rightarrow$ Submit reference & proof screenshot).
- **Order History (`/orders`)**: View pending and completed invoices, and submit or edit payment proof screenshots for pending orders at any time.

### 🔧 3. Independent Contractor Workspace
A tailored layout enabling local independent contractors to browse worker databases, unlock profiles, and post jobs:
- **Active Job Postings**: Post labor job openings (specifying trade, salary, duration, and district) displayed on the public Job Board.

### 🌐 4. Public Portals
- **Worker Intake Portal (`/register-worker`)**: Simple, accessible wizard allowing laborers to register their phone number, trade skills (including custom trades), district, and experience level.
- **Bilingual User Guide (`/guide`)**: Visual tutorials walking employers and workers through registration and recruitment.

---

## 🛠️ Technical Stack

- **Core Framework**: Laravel 13.x
- **Frontend Stack**: Livewire 3.x / 4.x, Tailwind CSS, Alpine.js, Vite
- **Localization**: Laravel JSON translation strings with RTL direction configurations
- **Security & RBAC**: Spatie Laravel-Permission
- **Testing**: Pest PHP Testing Framework (Unit, Component, and Feature Integration tests)

---

## 🚀 Installation & Setup

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/TheKhanSoft/KP-Labor-Matchmaker.git
   cd KP-Labor-Matchmaker
   ```

2. **Install Dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Configure Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate --seed
   ```

5. **Create Storage Symbolic Link**:
   ```bash
   php artisan storage:link
   ```

6. **Build Asset Bundle**:
   ```bash
   npm run build
   ```

7. **Start Servers**:
   ```bash
   php artisan serve
   # In another terminal:
   npm run dev
   ```

---

## 🧪 Running Automated Tests

Run the complete feature integration test suite with:
```bash
php artisan test
```
The test suite asserts wallet transactions, pricing engine policies, faked file uploads, and role restrictions.
