# 🛠️ ToolDock

**The Definitive Modular Productivity Workspace.**

ToolDock is a comprehensive personal productivity hub designed to centralize your digital life. From managing complex finances and habit tracking to secure credential storage and creative brainstorming - every tool is integrated into a single, high-performance environment.

Built as a **Modular Monolith** using **Laravel**, **React**, and **Inertia.js**, ToolDock offers total flexibility: every feature is a self-contained module that can be installed, enabled, or disabled at runtime without impacting the core system.

---

## 📦 Modules

ToolDock is powered by a sophisticated modular architecture. Manage your feature set dynamically through the built-in Module Manager.

### 🏗️ Foundation (Protected)

- 🛡️ **Core** - The platform's backbone. Handles identity management, Role-Based Access Control (RBAC), session security, and the runtime Module Manager.

- 🔔 **Signal** - Real-time notification engine. Supports multi-channel delivery via WebSockets with granular user preferences and module-scoped categories.

- ⚙️ **Settings** - A multi-layer configuration system allowing modules to define defaults, admins to set global values, and users to apply personal overrides.

- 📋 **Audit Log** - High-integrity activity tracking. Records system-wide changes with structured diffs via asynchronous processing to ensure zero data loss.

- 👥 **Groups** - Team-centric access control. Adds an organizational layer alongside RBAC for managing users, roles, and permissions at a group level.

- 📁 **Media** - Advanced file management. Features a polymorphic ownership model, automated lifecycle management, and a dedicated storage dashboard.

- 🏷️ **Categories** - A shared taxonomy service. Provides hierarchical categorization that modules can hook into via a global registrar.

### 🚀 Productivity Suite

- 🔐 **Vault** - Zero-compromise credential manager. Securely stores passwords, payment cards, and server keys with encryption-at-rest and TOTP support.

- 🏦 **Treasury** - Personal finance suite. Tracks income/expenses across multiple currencies and wallets. Features rollover budgets, savings goals, and financial health analytics.

- 📅 **Routine** - Habit tracking engine. Supports daily/weekly goals with boolean or measurable metrics. Includes streak tracking and visual consistency heatmaps.

- 🎨 **QuickDraw** - Infinite whiteboard. A freeform canvas for diagramming and brainstorming with persistent auto-saving and multi-canvas support.

- 📄 **Folio** - Resume & portfolio builder. Features a split-pane editor with live preview, drag-and-drop customization, and professional print-ready templates.

---

## 🛠️ Tech Stack

| Layer | Technologies |
|-------|-------------|
| **Backend** | PHP, Laravel, PostgreSQL, Redis |
| **Frontend** | React, Inertia.js, Vite, Radix UI + shadcn/ui |
| **Real-time** | Laravel Reverb (WebSockets), Laravel Horizon (Queues) |
| **Monitoring** | Laravel Pulse, Laravel Telescope |
| **Infrastructure** | Docker Compose, Nginx, PHP-FPM |
| **Code Quality** | ESLint, Prettier, Laravel Pint, Lefthook (pre-commit) |

---

## 🚀 Getting Started

### Prerequisites

- PHP & Composer
- Node.js & NPM
- PostgreSQL & Redis

### Quick Start

```bash
# Clone the repository
git clone https://github.com/fazaimron27/tooldock.git
cd tooldock

# Install dependencies
composer install && npm install

# Configure environment
cp .env.example .env
php artisan key:generate
# Configure your database and Redis credentials in .env

# Initialize database
php artisan migrate

# Start the unified development environment
composer dev
```

The `composer dev` command launches all services concurrently: Laravel server, Vite dev server, Horizon worker, log tail, and Reverb WebSocket server.

### Docker Support

```bash
docker compose up -d
```

This initializes three containers:

- **tooldock-app** - Laravel + PHP-FPM + Nginx (Port 8080)
- **tooldock-postgres** - PostgreSQL
- **tooldock-redis** - Redis

### Available Scripts

| Command | Description |
|---------|-------------|
| `composer dev` | Start all dev services (server, vite, horizon, reverb) |
| `npm run build` | Generate production assets |
| `npm run lint` | Run ESLint with auto-fix |
| `./vendor/bin/pint` | Standardize PHP code style |
| `php artisan test` | Execute the full test suite |

---

## 🏗️ Architecture & Internals

<details>
<summary><strong>Modular Monolith</strong></summary>

ToolDock leverages [nwidart/laravel-modules](https://github.com/nWidart/laravel-modules) to ensure code isolation. Each module maintains its own domain logic, models, services, and frontend assets.

```
app/                        ← Core application (registries, services, base classes)
Modules/
├── Core/                   ← User management, auth, module manager (protected)
├── Signal/                 ← Notifications (protected)
├── Settings/               ← Settings management (protected)
├── AuditLog/               ← Activity tracking (protected)
├── Groups/                 ← Group-based access (protected)
├── Media/                  ← File management (protected)
├── Categories/             ← Shared categories (protected)
├── Vault/                  ← Credential storage
├── Treasury/               ← Personal finance
├── Routine/                ← Habit tracking
├── QuickDraw/              ← Infinite whiteboard
└── Folio/                  ← Resume & portfolio builder
```

**(Protected)** modules provide essential services and cannot be disabled.

</details>

<details>
<summary><strong>Registry Pattern</strong></summary>

To prevent tight coupling, modules communicate via **Registries**. Contributions (menus, settings, etc.) are registered during the boot sequence, allowing the Core to discover features without direct cross-module imports.

| Registry | Purpose |
|----------|---------|
| `MenuRegistry` | Sidebar navigation items |
| `SettingsRegistry` | Module settings definitions |
| `PermissionRegistry` | RBAC permissions |
| `RoleRegistry` | Default roles |
| `CategoryRegistry` | Module-specific categories |
| `DashboardWidgetRegistry` | Dashboard widget definitions |
| `CommandRegistry` | Command Palette actions |
| `SignalHandlerRegistry` | Notification event handlers |
| `SignalCategoryRegistry` | Notification categories |
| `InertiaSharedDataRegistry` | Shared frontend data |
| `MiddlewareRegistry` | HTTP middleware |
| `GroupRegistry` | Access control groups |

</details>

<details>
<summary><strong>Module Lifecycle</strong></summary>

```
install()   → validate dependencies → DB record → migrations → seed registries
uninstall() → check protection → validate dependents → cleanup registries → rollback
enable()    → check installation → validate dependencies → activate → seed
disable()   → check protection → validate dependents → cleanup → deactivate
```

Module status is managed via the **database** for multi-server synchronization and state persistence.

</details>

<details>
<summary><strong>Caching Strategy</strong></summary>

Powered by a centralized `CacheService` using tag-based Redis caching with enterprise-grade resilience:

- **Tag-based invalidation** - Surgical flushing by module, user, or specific concern.
- **Circuit breaker** - Automatically falls back to direct database queries on Redis failure.
- **Resilience** - Includes exponential backoff retries and `CacheMetricsService` for hit/miss tracking.

</details>

<details>
<summary><strong>Project Structure</strong></summary>

```
tooldock/
├── app/
│   ├── Data/                  # DTOs
│   ├── Services/
│   │   ├── Cache/             # Resilience & Metrics
│   │   ├── Core/              # Global Config & Settings
│   │   └── Registry/          # Registry orchestration
├── Modules/
│   └── {Module}/
│       ├── app/               # Models, Controllers, Observers, Policies
│       ├── database/          # Migrations & Factories
│       ├── resources/assets/  # React components per module
│       └── module.json        # Metadata & dependencies
├── resources/js/
│   ├── Components/            # Shared UI (shadcn/ui)
│   ├── Hooks/                 # TanStack Query & Custom hooks
│   └── Stores/                # Zustand state management
└── vite.config.js
```

</details>

<details>
<summary><strong>Key Design Decisions</strong></summary>

| Decision | Rationale |
|----------|-----------|
| **Modular Monolith** | Combines service isolation with single-deployment simplicity. |
| **Database-backed Activation** | Ensures multi-node consistency and version tracking. |
| **Strict Registry Pattern** | Decouples modules; removes the "spaghetti code" risk. |
| **Inertia.js over REST** | Eliminates API boilerplate while providing a modern SPA feel. |

</details>

---

## 📄 License

This project is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
