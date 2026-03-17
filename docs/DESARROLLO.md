# Desarrollo — Planes de Ahorro Lastenia

## Usuarios de prueba

### Central (planes_central) — `http://localhost:8000`

| Email | Password | Rol | Permisos |
|-------|----------|-----|----------|
| admin@planes.test | password | super_admin | Todos (incluye gestion de concesionarias) |
| carlos@planes.test | password | manager | Operaciones, reportes, suscriptores |
| laura@planes.test | password | auditor | Solo lectura + auditoria |
| martin@planes.test | password | contador | Pagos, reportes financieros |

### Tenant "Test Auto 2" (tenant_test2) — `http://test2.localhost:8000`

| Email | Password | Rol | Permisos |
|-------|----------|-----|----------|
| admin@test2.com | password | tenant_admin | Todos dentro del tenant |
| roberto@test2.com | password | manager | Operaciones, reportes, suscriptores |
| ana@test2.com | password | agente_comercial | Suscriptores, contratos, pagos |
| diego@test2.com | password | atencion_cliente | Consulta suscriptores, contratos, pagos |
| sofia@test2.com | password | contador | Pagos, reportes financieros |
| pablo@test2.com | password | auditor | Solo lectura + auditoria |
| lucia@test2.com | password | cliente | Ver contratos y pagos propios |

> **Nota:** Para acceder a `test2.localhost` hay que agregar en `C:\Windows\System32\drivers\etc\hosts`:
> ```
> 127.0.0.1   test2.localhost
> ```

---

## Links de prueba

| URL | Descripcion | Requiere |
|-----|-------------|----------|
| http://localhost:8000/login | Login central (super_admin) | - |
| http://localhost:8000/dashboard | Dashboard central | auth |
| http://localhost:8000/tenants | Gestion de concesionarias | role:super_admin |
| http://localhost:8000/users | Lista de usuarios centrales | auth |
| http://localhost:8000/settings/profile | Perfil del usuario | auth |
| http://test2.localhost:8000/login | Login del tenant Test Auto 2 | hosts configurado |
| http://test2.localhost:8000/dashboard | Dashboard del tenant | auth |
| http://test2.localhost:8000/users | Lista de usuarios del tenant | auth |

---

## Progreso del desarrollo

### Modulo 0: Infraestructura base

- [x] Laravel 12 + PHP 8.4 configurado
- [x] MariaDB 10.4.32 en puerto 3050, servicio Windows `MySQL_Planes`
- [x] 3 conexiones de BD: `mariadb` (dinamica), `landlord` (fija central), `audit` (fija auditoria)
- [x] Spatie Laravel Multitenancy v4 — database-per-tenant
  - `DomainTenantFinder`: identifica tenant por subdominio
  - `SwitchTenantDatabaseTask`: cambia conexion `mariadb` al tenant activo
  - `PrefixCacheTask`: prefija cache por tenant
- [x] `TenantObserver`: auto-provisioning al crear tenant (BD + migraciones + seed de roles)
- [x] Migraciones separadas: `database/migrations/landlord/` y `database/migrations/tenant/`
- [x] Frontend: Livewire 3 + Blade + Flux UI + Tailwind

### Modulo 1: Usuarios, Roles y Permisos

- [x] Spatie Laravel Permission v7.2.0 instalado
- [x] `RoleEnum` — 8 roles: super_admin, tenant_admin, manager, agente_comercial, atencion_cliente, contador, auditor, cliente
- [x] `PermissionEnum` — 20 permisos agrupados: tenant, user, subscriber, contract, payment, draw, report, audit
- [x] Modelo `User` con traits `HasRoles`, `TwoFactorAuthenticatable`
- [x] `RolesAndPermissionsSeeder` — asigna permisos a cada rol
- [x] `AdminUserSeeder` — crea usuario super_admin en central
- [x] Migracion de permisos en `tenant/` (se replica en cada tenant)

### Modulo 1b: Autenticacion (Login)

- [x] Laravel Fortify v1.30 — auth por sesion (NO Sanctum/API)
- [x] Vistas: login, registro, forgot-password, 2FA, verify-email, confirm-password
- [x] Rate limiting: 5 intentos/minuto por email+IP
- [x] 2FA habilitado con confirmacion de password
- [x] Logout con invalidacion de sesion
- [x] Redireccion post-login a `/dashboard`

### Modulo 1c: Gestion de Concesionarias (Tenants)

- [x] Pagina `/tenants` — lista de concesionarias con tabla Flux UI
- [x] Modal de creacion con nombre y dominio (auto-generado)
- [x] Al crear: TenantObserver provisiona BD + migraciones + roles automaticamente
- [x] Protegido con `role:super_admin` (ruta y sidebar)
- [x] Link al dominio de cada tenant en la tabla

### Paginas implementadas

- [x] `/dashboard` — Dashboard (placeholder, auth requerido)
- [x] `/tenants` — Gestion de concesionarias (solo super_admin)
- [x] `/users` — Lista de usuarios (muestra usuarios del contexto actual: central o tenant)
- [x] `/settings/profile` — Edicion de perfil
- [x] `/settings/password` — Cambio de password
- [x] `/settings/two-factor` — Configuracion 2FA
- [x] `/settings/appearance` — Tema (claro/oscuro)

---

## Arquitectura multi-tenant

```
Request a test2.localhost:8000/users
         |
         v
[Spatie ServiceProvider boot]
  DomainTenantFinder busca "test2.localhost" en planes_central.tenants
         |
         v
  Encuentra tenant (id=2, database=tenant_test2)
  -> SwitchTenantDatabaseTask cambia conexion "mariadb" a tenant_test2
  -> PrefixCacheTask prefija cache con tenant_2_
         |
         v
[Fortify auth middleware]
  Busca sesion en tenant_test2.sessions
  Busca usuario en tenant_test2.users
         |
         v
[Livewire UserList component]
  User::with('roles')->get()
  -> Consulta tenant_test2.users + tenant_test2.model_has_roles
         |
         v
  Muestra 7 usuarios del tenant
```

### Cuando NO hay tenant (localhost:8000)

```
Request a localhost:8000/users
         |
         v
[Spatie ServiceProvider boot]
  DomainTenantFinder busca "localhost" -> NO encuentra tenant
  -> Conexion "mariadb" queda apuntando a planes_central (default .env)
         |
         v
[Fortify auth middleware]
  Busca sesion en planes_central.sessions
  Busca usuario en planes_central.users
         |
         v
[Livewire UserList component]
  Muestra 4 usuarios de la central
```

---

## Archivos clave

| Archivo | Proposito |
|---------|-----------|
| `config/multitenancy.php` | Config de Spatie Multitenancy |
| `config/fortify.php` | Config de autenticacion |
| `config/permission.php` | Config de Spatie Permission |
| `config/database.php` | 3 conexiones (mariadb, landlord, audit) |
| `bootstrap/app.php` | Middleware aliases (role, permission, role_or_permission) |
| `app/Models/Central/Tenant.php` | Modelo Tenant (conexion landlord) |
| `app/Models/User.php` | Modelo User (HasRoles, TwoFactorAuthenticatable) |
| `app/Observers/TenantObserver.php` | Auto-provisioning de tenants |
| `app/Enums/RoleEnum.php` | 8 roles del sistema |
| `app/Enums/PermissionEnum.php` | 20 permisos del sistema |
| `app/Providers/FortifyServiceProvider.php` | Config de vistas y acciones de auth |
| `database/seeders/RolesAndPermissionsSeeder.php` | Seed de roles y permisos |
| `database/seeders/AdminUserSeeder.php` | Seed de usuario super_admin |
| `resources/views/pages/tenants/index.blade.php` | Pagina de gestion de concesionarias |
| `resources/views/pages/users/index.blade.php` | Pagina de lista de usuarios |
| `resources/views/layouts/app/sidebar.blade.php` | Sidebar con navegacion condicional |

---

## Proximo: Modulo 2 — Planes y Marcas

Modelos `Brand` y `Plan` en la BD central (landlord). Relacion many-to-many `Tenant <-> Brand` via tabla pivot `tenant_brand`. Las migraciones ya existen en `database/migrations/landlord/`.
