# ToolDock

A **modular monolith** personal productivity platform built with Laravel 12, React, and Inertia.js. ToolDock provides a unified workspace for managing finances, habits, secure credentials, and more — all through a plugin-style module system that allows features to be installed, enabled, or disabled independently.

---

## ✨ Features

### 🏦 Treasury — Personal Finance Manager

- Multi-wallet management with multi-currency support
- Income, expense, and transfer tracking with fee handling
- Budget planning with rollover support
- Savings goals with allocation tracking
- Exchange rate integration with anti-arbitrage currency conversion (BCMath precision)
- Financial health recommendations (Emergency Fund, Job Loss Fund, Insurance Fund)
- Reports and chart visualizations

### 📅 Routine — Habit Tracker

- Boolean (check/uncheck) and measurable (numeric) habit types
- Streak calculation with daily and weekly modes
- Pause/resume with streak preservation (freeze + bridge)
- 365-day heatmap visualization
- Completion rate tracking over 4-week windows
- Category support for habit organization

### 🔐 Vault — Secure Credential Manager

- Encrypted storage for passwords, cards, notes, and server credentials
- Encryption at rest via Laravel's `encrypted` cast
- TOTP/2FA code generation (server-side, secret never exposed to frontend)
- PIN-based vault locking with session-based access control
- Password generator
- Favorites and category organization

### 📋 Audit Log — Activity Tracking

- Automatic change tracking via the `LogsActivity` trait
- 16 event types across CRUD, auth, files, and relationships
- Formatted diff generation with 6 specialized formatters
- Asynchronous logging via queued jobs with emergency file fallback
- No audit trail is ever lost

### 🔔 Signal — Real-time Notifications

- 4 delivery modes: silent, flash, trigger, broadcast
- WebSocket integration via Laravel Reverb
- Per-user notification preferences
- Module-scoped notification categories

### 📁 Media — File Management

- Polymorphic file ownership (any model can have files)
- Temporary → permanent file lifecycle
- Automatic cleanup on model deletion
- Storage usage dashboard

### 🏷️ Categories, Groups, Settings

- Hierarchical category system shared across modules
- Group-based access control (GBAC) alongside RBAC
- Three-layer settings: definition → global value → user override

---

## 🏗️ Architecture

### Modular Monolith

ToolDock follows a **modular monolith** architecture powered by [nwidart/laravel-modules](https://github.com/nWidart/laravel-modules). Each module is a self-contained unit with its own models, services, controllers, migrations, routes, and frontend assets.

```
app/                        ← Core application (registries, services, base classes)
Modules/
├── Core/                   ← User management, roles, auth, module manager
├── Treasury/               ← Personal finance (protected)
├── Routine/                ← Habit tracking
├── Vault/                  ← Credential storage (protected)
├── AuditLog/               ← Activity tracking (protected)
├── Signal/                 ← Notifications (protected)
├── Media/                  ← File management (protected)
├── Categories/             ← Shared categories (protected)
├── Groups/                 ← Group-based access (protected)
└── Settings/               ← Settings management (protected)
```

### Registry Pattern

Modules integrate with the core via **12 registries** — no direct cross-module imports:

| Registry | Purpose |
|----------|---------|
| `MenuRegistry` | Sidebar navigation items |
| `SettingsRegistry` | Module settings definitions |
| `PermissionRegistry` | RBAC permissions |
| `RoleRegistry` | Default roles |
| `CategoryRegistry` | Module-specific categories |
| `DashboardWidgetRegistry` | Dashboard widget definitions |
| `CommandRegistry` | Artisan CLI commands |
| `SignalHandlerRegistry` | Notification event handlers |
| `SignalCategoryRegistry` | Notification categories |
| `InertiaSharedDataRegistry` | Shared frontend data |
| `MiddlewareRegistry` | HTTP middleware |
| `GroupRegistry` | Access control groups |

### Module Lifecycle

```
install()   → validate dependencies → DB record → migrations → seed registries
uninstall() → check protection → validate dependents → cleanup registries → rollback
enable()    → check installation → validate dependencies → activate → seed
disable()   → check protection → validate dependents → cleanup → deactivate
```

Module status is stored in a **database** (not JSON files), ensuring multi-server consistency.

---

## 🛠️ Tech Stack

### Backend

| Technology | Version | Purpose |
|-----------|---------|---------|
| **PHP** | ^8.2 | Runtime |
| **Laravel** | ^12.0 | Framework |
| **PostgreSQL** | 16 | Primary database |
| **Redis** | 7 | Cache, queue, sessions, broadcasting |
| **Laravel Horizon** | ^5.43 | Queue monitoring |
| **Laravel Reverb** | ^1.0 | WebSocket server |
| **Laravel Pulse** | ^1.5 | Application monitoring |
| **Laravel Telescope** | ^5.16 | Debug assistant |
| **Spatie Permission** | ^6.23 | RBAC |
| **nwidart/laravel-modules** | ^12.0 | Module system |
| **spomky-labs/otphp** | ^11.3 | TOTP generation |

### Frontend

| Technology | Purpose |
|-----------|---------|
| **React** | UI library |
| **Inertia.js v2** | SPA bridge (no separate API layer) |
| **Vite** | Build tool |
| **TanStack Table** | Data tables |
| **TanStack React Query** | Server state management |
| **Zustand** | Client state management |
| **Radix UI + shadcn/ui** | 31 accessible UI components |
| **Recharts** | Chart visualizations |
| **Lucide React** | Icon system |
| **Sonner** | Toast notifications |

### Infrastructure

| Technology | Purpose |
|-----------|---------|
| **Docker Compose** | Container orchestration |
| **FrankenPHP** | Application server |
| **Lefthook** | Git pre-commit hooks |
| **ESLint + Prettier** | JavaScript formatting |
| **Laravel Pint** | PHP formatting |

---

## 🚀 Getting Started

### Prerequisites

- **PHP** >= 8.2
- **Node.js** >= 18
- **Composer** >= 2
- **PostgreSQL** >= 16
- **Redis** >= 7

### Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/your-org/tooldock.git
   cd tooldock
   ```

2. **Install dependencies**

   ```bash
   composer install
   npm install
   ```

3. **Environment setup**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure your `.env`**

   ```dotenv
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=tooldock
   DB_USERNAME=your_user
   DB_PASSWORD=your_password

   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis

   BROADCAST_CONNECTION=reverb
   ```

5. **Run migrations**

   ```bash
   php artisan migrate
   ```

   Protected modules are automatically installed after migration.

6. **Build frontend assets**

   ```bash
   npm run dev    # Development with HMR
   npm run build  # Production build
   ```

7. **Start the queue worker**

   ```bash
   php artisan horizon
   ```

8. **Start the WebSocket server** (optional, for real-time notifications)

   ```bash
   php artisan reverb:start
   ```

### Docker

```bash
docker compose up -d
```

The Docker stack includes:

- **tooldock-app** — Laravel + FrankenPHP (port 8080, auto-migrate on startup)
- **tooldock-postgres** — PostgreSQL 16 Alpine
- **tooldock-redis** — Redis 7 Alpine (AOF persistence, 128MB maxmemory with LRU eviction)

---

## 📁 Project Structure

```
tooldock/
├── app/
│   ├── Data/                  # DTOs (DashboardWidget)
│   ├── Events/Modules/        # 8 module lifecycle events
│   ├── Helpers/               # Global helper functions
│   ├── Listeners/             # Module event listeners
│   ├── Providers/             # AppServiceProvider (22 singletons)
│   └── Services/
│       ├── Cache/             # CacheService, CacheMetricsService
│       ├── Core/              # Settings, Inertia, AppConfig, StorageLink
│       ├── Media/             # MediaConfigService
│       ├── Modules/           # Lifecycle, Discovery, Dependency Validation
│       └── Registry/          # 12 registry services
│
├── Modules/
│   └── {Module}/
│       ├── app/
│       │   ├── Http/          # Controllers, Middleware, Requests
│       │   ├── Models/        # Eloquent models
│       │   ├── Services/      # Business logic + Handlers/
│       │   ├── Providers/     # Service providers
│       │   ├── Observers/     # Model observers
│       │   └── Policies/      # Authorization policies
│       ├── config/            # Module configuration
│       ├── database/
│       │   ├── factories/     # Model factories
│       │   └── migrations/    # Database migrations
│       ├── resources/assets/  # Frontend pages and components
│       ├── routes/            # web.php + api.php
│       ├── tests/             # Module-level tests
│       └── module.json        # Module metadata and dependencies
│
├── resources/js/
│   ├── Components/
│   │   ├── Common/            # 15 reusable form/dialog components
│   │   ├── Dashboard/         # 14 dashboard components
│   │   ├── DataDisplay/       # DataTable, MetricCard, StatCard, StatGrid
│   │   ├── Form/              # DatePicker, FilePicker, DateTimePicker
│   │   ├── Layouts/           # DashboardLayout (persistent)
│   │   ├── Widgets/           # 5 widget renderers
│   │   └── ui/                # 31 shadcn/ui components
│   ├── Hooks/
│   │   ├── queries/           # 5 TanStack Query hooks
│   │   ├── use*.js            # 16 custom hooks
│   │   └── ...
│   └── Stores/                # Zustand store
│
├── docker-compose.yml
├── vite.config.js
└── lefthook.yml
```

---

## 🗄️ Database Schema

### Tables by Module

| Module | Tables |
|--------|--------|
| **Core** | `users`, `sessions`, `password_reset_tokens`, `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`, `core_menus`, `user_preferences` |
| **Settings** | `settings_config` |
| **Categories** | `categories` |
| **Groups** | `groups`, `groups_users`, `groups_permissions`, `groups_roles` |
| **AuditLog** | `auditlog_entries` |
| **Media** | `media_files` |
| **Signal** | `notifications` |
| **Treasury** | `wallets`, `treasury_goals`, `budgets`, `transactions`, `exchange_rates` |
| **Routine** | `habits`, `habit_logs` |
| **Vault** | `vaults`, `vault_locks` |

All primary keys are **UUIDs**. Module activation state is tracked in a separate `modules_statuses` table.

---

## ⚡ Caching Strategy

ToolDock uses a **tag-based Redis caching** architecture through a centralized `CacheService`:

| Feature | Implementation |
|---------|---------------|
| **Tag-based invalidation** | Surgical cache flushing by module, user, or concern |
| **Circuit breaker** | Falls back to direct queries on repeated cache failures |
| **Retry logic** | Exponential backoff on cache operations |
| **Metrics** | Hit/miss tracking via `CacheMetricsService` |
| **Graceful degradation** | Cache failures never break the application |

**Tag taxonomy:**

```
Global:    [settings], [menus], [permissions], [categories]
Module:    [module:treasury], [module:routine], [module:vault]
User:      [user-preferences], [user-{id}-preferences]
Combined:  [module:routine, user:{id}]
```

---

## 🔄 Queue Jobs

| Job | Module | Retries | Backoff | Purpose |
|-----|--------|---------|---------|---------|
| `CreateAuditLogJob` | AuditLog | 3 | 1s, 5s, 10s | Async audit logging with emergency file fallback |
| `SendNotificationJob` | Signal | 3 | 1s, 5s, 10s | Multi-mode notification dispatch |
| `RefreshExchangeRatesJob` | Treasury | 3 | 10s | Fetch latest exchange rates from API |

All jobs run on the **Redis** queue, monitored by **Laravel Horizon**.

---

## 🔧 Development

### Code Quality

Pre-commit hooks run automatically via **Lefthook**:

```bash
# JavaScript/JSX — Lint + Format
npx eslint --fix
npx prettier --write

# PHP — Format
./vendor/bin/pint
```

### Available Scripts

```bash
# Frontend
npm run dev              # Vite dev server with HMR
npm run build            # Production build
npm run lint             # ESLint fix
npm run format           # Prettier format
npm run format:check     # Prettier check

# Backend
php artisan horizon      # Queue monitoring dashboard
php artisan reverb:start # WebSocket server
php artisan telescope    # Debug dashboard
php artisan pulse        # Application monitoring

# Testing
php artisan test         # Run test suite
```

---

## 📐 Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| **Modular Monolith** over Microservices | Single deployment, shared database, no network overhead between modules |
| **Database-backed module activation** | Multi-server safe, queryable, version-trackable |
| **Registry Pattern** everywhere | Decoupled module integration without direct dependencies |
| **Redis for everything** (cache/queue/session) | Simplicity, tag support, performance |
| **Inertia.js over REST API** | No API duplication, server-driven routing, SPA experience |
| **BCMath for currency** | Arbitrary precision arithmetic prevents floating-point errors |
| **Truncation over rounding** | Anti-arbitrage: round-trip conversions can never create money |
| **Server-side TOTP** | Secrets never exposed to the frontend |
| **Async audit logging** | Non-blocking with emergency file fallback ensures no data loss |
| **Three-layer settings** | Clean separation: definition → global value → user override |

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
