# CLAUDE.md — Contexto del Proyecto

## Proyecto
Sistema de **planes de ahorro multimarcas** para concesionarias de vehículos en Argentina. Migración de sistema legacy PHP 5.6 a stack moderno. Empresa: **Lastenia Software**.

## Principios Fundamentales

Este proyecto sigue **estrictamente** las mejores prácticas de desarrollo de software. Todo código generado o sugerido DEBE cumplir con estos principios:

### Clean Code
- **Nombres descriptivos y autoexplicativos**: Variables, métodos y clases deben revelar su intención. `$overdueInstallments` en vez de `$data` o `$items`.
- **Funciones pequeñas y con responsabilidad única**: Un método hace UNA sola cosa. Si necesitás usar "y" para describir qué hace, hay que dividirlo.
- **Sin comentarios obvios**: El código debe ser autoexplicativo. Los comentarios se reservan para el "por qué", nunca para el "qué".
- **Sin código muerto**: No dejar código comentado, métodos sin usar, ni imports innecesarios.
- **Early returns**: Evitar anidamiento excesivo. Validar y retornar temprano.
- **Sin magic numbers ni strings**: Todo valor fijo va en constantes o enums.
- **Inmutabilidad cuando sea posible**: Usar `readonly` en propiedades de DTOs y servicios.

### Principios SOLID
- **S — Single Responsibility**: Cada clase tiene una única razón para cambiar. Un Service no valida, no formatea respuestas, no envía emails.
- **O — Open/Closed**: Las Strategies permiten agregar marcas nuevas sin modificar el motor de cálculo existente.
- **L — Liskov Substitution**: Todas las implementaciones de una interfaz deben ser intercambiables.
- **I — Interface Segregation**: Interfaces específicas y pequeñas. No forzar a implementar métodos que no se usan.
- **D — Dependency Inversion**: Los Services dependen de abstracciones (interfaces), no de implementaciones concretas. Inyección de dependencias vía constructor.

### Clean Architecture
- **Separación estricta de capas**: Livewire Components → Controllers → Services → Models. Las dependencias SIEMPRE apuntan hacia adentro (de infraestructura hacia dominio).
- **La lógica de negocio es agnóstica al framework**: Los Services no deben depender de `Request`, `Response`, ni de Livewire. Reciben DTOs y devuelven DTOs o modelos.
- **Testabilidad**: Si una clase es difícil de testear, está mal diseñada. Toda lógica de negocio debe ser testeable sin levantar la aplicación completa.
- **Fail fast**: Validar inputs al inicio del flujo. Lanzar excepciones de negocio (`BusinessRuleException`) con mensajes claros.

### Mejores Prácticas Laravel
- **Usar las herramientas del framework**: No reinventar lo que Laravel ya resuelve (Form Requests, Policies, Events, Notifications, Queues).
- **Eloquent idiomático**: Scopes, accessors, mutators, casts, relaciones tipadas. Aprovechar el ORM.
- **Configuración por entorno**: Todo en `.env`, accedido vía `config()`. NUNCA `env()` fuera de archivos config.
- **Queue everything**: Cualquier operación que tarde más de 200ms va a una cola (emails, PDFs, cálculos masivos, AFIP).
- **Transacciones de BD**: Usar `DB::transaction()` para operaciones que involucren múltiples writes.
- **Rate limiting**: En endpoints públicos y operaciones sensibles.
- **Logging estructurado**: Contexto relevante en cada log (`Log::info('Payment processed', ['contract_id' => $id, 'amount' => $amount])`).

## Stack Tecnológico

### Backend + Frontend (monolito)
- **Laravel 12** con **PHP 8.5**
- **Livewire 3** + **Blade** para el frontend (NO usamos Next.js, NO usamos React, NO usamos API separada)
- **MySQL 8** para todas las bases de datos
- **Redis** para sesiones, caché y colas

### Paquetes principales
- **spatie/laravel-multitenancy** — Multi-tenancy con base de datos por tenant
- **spatie/laravel-permission** — RBAC (roles y permisos) por tenant
- **spatie/laravel-activitylog** — Auditoría de acciones de usuario
- **spatie/laravel-data** — DTOs tipados
- **spatie/laravel-medialibrary** — Archivos adjuntos
- **spatie/laravel-query-builder** — Filtros y ordenamiento
- **spatie/laravel-backup** — Backups automáticos
- **laravel/pennant** — Feature flags para migración gradual
- **brick/money** — Aritmética monetaria precisa (NUNCA usar float para dinero)
- **barryvdh/laravel-dompdf** — Generación de PDFs
- **larastan/larastan** — Análisis estático
- **laravel/pint** — Formateo de código

### Herramientas de desarrollo
- **Pest PHP** como framework de testing (NO PHPUnit)
- **Laravel Pint** para code style
- **Larastan** nivel 5 para análisis estático

## Arquitectura Multi-Tenant

### Modelo: Base de datos por tenant (database-per-tenant)
Usamos **Spatie Laravel Multitenancy** (NO Stancl/Tenancy). 

### Estructura de bases de datos
- **planes_central** → BD central (landlord). Contiene: tenants, domains, brands, plans, tenant_brand (pivot).
- **planes_audit** → BD de auditoría separada. Contiene: activity_log.
- **tenant_{id}** → Una BD por cada cliente/concesionaria. Contiene: users, subscribers, contracts, installments, payments, draws, draw_results, auctions, auction_bids, adjudications, roles, permissions, media.

### Configuración de Spatie Multitenancy
- Cada tenant se identifica por **dominio** (ej: oneautos.planesahorro.com).
- El `Tenant` model implementa el trait `UsesLandlordConnection` cuando se necesita acceder a la BD central.
- El switch de BD se hace mediante `SwitchTenantDatabaseTask` en el pipeline de tareas del tenant.
- Las migraciones de tenant van en `database/migrations/tenant/`.
- Las migraciones centrales van en `database/migrations/landlord/`.

### Relación concesionaria-marcas
Una concesionaria (tenant) puede manejar **múltiples marcas** de vehículos. Se resuelve con tabla pivot `tenant_brand` en la BD central. Los planes pertenecen a una marca (`plans.brand_id`), y el sistema filtra los planes disponibles según las marcas habilitadas para el tenant activo.

## Estructura de Carpetas (Convención)

```
app/
├── Data/              # DTOs con Spatie Laravel Data
├── Enums/             # RoleEnum, PermissionEnum, ContractStatusEnum, etc.
├── Exceptions/        # BusinessRuleException, etc.
├── Http/
│   ├── Controllers/   # Controllers web (NO API, usamos Livewire)
│   └── Middleware/     # EnsureTenantIsActive, etc.
├── Livewire/          # Componentes Livewire (frontend dinámico)
│   ├── Subscribers/   # Gestión de suscriptores
│   ├── Contracts/     # Contratos y cuotas
│   ├── Payments/      # Pagos
│   ├── Draws/         # Sorteos
│   └── Reports/       # Reportes IGJ/AFIP
├── Models/
│   ├── Central/       # Tenant, Brand, Plan (usan landlord connection)
│   └── ...            # User, Subscriber, Contract, etc. (BD tenant)
├── Notifications/
├── Observers/
├── Pipelines/         # Pipeline pattern para cálculo de cuotas
├── Policies/
├── Providers/
├── Services/          # Lógica de negocio (PaymentService, DrawService, etc.)
└── Strategies/        # Strategy pattern para cálculos por marca

database/
├── migrations/
│   ├── landlord/      # Migraciones BD central
│   └── tenant/        # Migraciones BD de cada tenant

resources/views/
├── components/        # Blade components reutilizables
├── layouts/           # Layout principal
├── livewire/          # Vistas de componentes Livewire
└── pages/             # Páginas estáticas
```

## Patrones de Diseño

### Obligatorios desde el día 1:
- **Service Layer**: TODA la lógica de negocio va en `app/Services/`. Los controllers y componentes Livewire NUNCA contienen lógica de negocio, solo delegan a servicios.
- **DTOs**: Usar Spatie Laravel Data para tipar entradas y salidas entre capas. NUNCA pasar arrays sueltos.
- **Enums**: PHP 8.1+ enums para roles, permisos, estados. NUNCA strings mágicos. Aprovechar features de PHP 8.5 (property hooks, asymmetric visibility) donde aporten claridad.

### Implementar a partir del mes 3-4:
- **Strategy Pattern**: Para cálculos de cuotas por marca (Toyota, VW, Fiat tienen fórmulas distintas).
- **Pipeline Pattern**: Para cálculo secuencial de cuotas (base → impuestos → seguro → admin → redondeo).


## Roles del Sistema

| Rol | Scope | Descripción |
|-----|-------|-------------|
| super_admin | Central | Gestión de tenants, configuración global |
| tenant_admin | Tenant | Acceso total dentro de su concesionaria |
| manager | Tenant | Supervisa operaciones, aprueba transacciones |
| agente_comercial | Tenant | Crea suscriptores, contratos, registra pagos |
| atencion_cliente | Tenant | Consulta info de suscriptores, gestiona reclamos |
| contador | Tenant | Pagos, reportes financieros, cobranzas, IGJ |
| auditor | Tenant | Solo lectura + acceso a logs de auditoría |
| cliente | Tenant | Portal: ve su perfil, planes, pagos, estado de cuenta |

## Convenciones de Código

### Naming
- **Modelos**: Singular, PascalCase → `Subscriber`, `DrawResult`
- **Tablas**: Plural, snake_case → `subscribers`, `draw_results`
- **Services**: `{Nombre}Service` → `PaymentService`
- **DTOs**: `{Acción}{Modelo}Data` → `CreateSubscriberData`
- **Enums**: `{Nombre}Enum` → `ContractStatusEnum`
- **Eventos**: Pasado → `PaymentReceived`, `ContractActivated`
- **Jobs**: Imperativo → `ProcessPaymentJob`, `GenerateIgjReportJob`
- **Componentes Livewire**: PascalCase → `SubscriberTable`, `PaymentForm`

### Reglas estrictas
- SIEMPRE usar `brick/money` para montos. NUNCA float ni double.
- SIEMPRE usar `decimal(20,4)` en migraciones para campos monetarios.
- SIEMPRE usar Enums en vez de strings para estados y tipos.
- SIEMPRE usar Form Requests o Spatie Data para validación.
- SIEMPRE escribir tests Pest para servicios y lógica de negocio.
- SIEMPRE usar type hints en parámetros y return types en todos los métodos.
- SIEMPRE usar `readonly` en propiedades que no cambian después de la construcción.
- SIEMPRE usar early returns para reducir anidamiento.
- SIEMPRE usar inyección de dependencias por constructor, NUNCA instanciar con `new` dentro de servicios.
- NUNCA poner lógica de negocio en controllers o componentes Livewire.
- NUNCA hacer queries directos en componentes Livewire — delegar a Services.
- NUNCA usar `dd()` en commits — usar logging apropiado.
- NUNCA usar `env()` fuera de archivos config — siempre `config('key')`.
- NUNCA dejar código comentado o métodos sin usar.
- NUNCA crear clases de más de 200 líneas ni métodos de más de 20 líneas. Si crece, refactorizar.

### Eloquent
- Usar `preventLazyLoading()` en desarrollo para detectar N+1.
- Usar scopes para queries repetitivas (`scopeActive`, `scopeOverdue`).
- Relaciones tipadas con return types.
- Usar `$casts` para enums, dates, decimals.

## Contexto de Negocio (Argentina)

### Sistema de planes de ahorro
- Grupos de suscriptores que pagan cuotas mensuales para acceder a un vehículo.
- Adjudicación por **sorteo** mensual (azar) o **licitación** (mayor oferta).
- Las cuotas se recalculan mensualmente según el **valor móvil** del vehículo.
- Cada marca tiene fórmulas distintas de comisión, seguro y gastos administrativos.

### Regulaciones
- **IGJ** (Inspección General de Justicia): Supervisa planes de ahorro. Reportes mensuales obligatorios.
- **AFIP/ARCA**: Facturación electrónica. Cada tenant tiene su propio CUIT y certificados.
- **Ley 25.326**: Protección de datos personales.

### Módulos del sistema (orden de implementación)
1. Gestión de usuarios y roles
2. Catálogo de planes y marcas
3. Suscriptores y contratos
4. Motor de cálculo de cuotas
5. Procesamiento de pagos
6. Sorteos mensuales
7. Licitaciones
8. Adjudicaciones y entrega
9. Reporting IGJ
10. Integración AFIP (facturación electrónica)

## Base de Datos

### Puerto MariaDB local: 3050
### Conexiones en .env:
```
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3050
DB_DATABASE=planes_central
DB_USERNAME=root
DB_PASSWORD=

DB_AUDIT_DATABASE=planes_audit
```

### Reglas de migraciones
- Campos monetarios: `$table->decimal('amount', 20, 4)`
- Timestamps en todas las tablas: `$table->timestamps()`
- Soft deletes en entidades importantes: `$table->softDeletes()`
- Índices en foreign keys y campos de búsqueda frecuente.
- plan_id en tablas de tenant es referencia cruzada a BD central (NO foreign key constraint, es cross-database).

## Testing

- Framework: **Pest PHP** (NO PHPUnit)
- Tests de tenant usan trait `CreatesTenantContext` que inicializa un tenant de prueba.
- Priorizar Feature tests para flujos completos.
- Unit tests para Services y Strategies (lógica pura).
- SIEMPRE testear cálculos financieros con casos borde.

## Equipo

- 5-8 desarrolladores **sin experiencia previa en Laravel**.
- Vienen de PHP 5.6 legacy (el salto a PHP 8.5 es enorme: namespaces, Composer, tipos, enums, match, arrow functions, readonly, property hooks).
- Priorizar código simple, limpio y que siga las convenciones estándar de Laravel.
- NO sobrecomplicar con arquitecturas que el equipo no domina.
- Documentar decisiones técnicas con comentarios claros en el "por qué".

## Calidad de Código — Reglas de Enforcement

### Antes de cada commit:
```bash
./vendor/bin/pint                    # Formateo automático
./vendor/bin/phpstan analyse         # Análisis estático nivel 5
php artisan test                     # Todos los tests deben pasar
```

### Code review checklist:
- [ ] ¿La lógica de negocio está en un Service, no en un controller/Livewire?
- [ ] ¿Se usan DTOs para transferir datos entre capas?
- [ ] ¿Los montos usan brick/money y decimal(20,4)?
- [ ] ¿Las funciones tienen menos de 20 líneas?
- [ ] ¿Las clases tienen menos de 200 líneas?
- [ ] ¿Hay tests para la lógica nueva?
- [ ] ¿Se usan Enums en vez de strings para estados y tipos?
- [ ] ¿Se respeta el principio de single responsibility?
- [ ] ¿Los nombres son descriptivos y en inglés?
- [ ] ¿No hay código muerto, dd(), ni dumps?
