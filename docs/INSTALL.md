# Guia de Instalacion — Planes de Ahorro Lastenia

## Requisitos previos

| Requisito | Version minima |
|-----------|---------------|
| PHP | 8.2+ con extensiones: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath |
| Composer | 2.x |
| MariaDB / MySQL | MariaDB 10.4+ o MySQL 8+ |
| Node.js | 18+ |
| npm | 9+ |
| Git | 2.x |

## 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd test-laravel
```

## 2. Instalar dependencias

```bash
composer install
npm install
```

## 3. Configurar el archivo .env

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los datos de tu entorno. Los valores importantes son:

```env
APP_NAME="Planes de Ahorro"
APP_URL=http://localhost:8000

# --- Base de datos ---
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3050
DB_DATABASE=planes_central
DB_USERNAME=root
DB_PASSWORD=

DB_AUDIT_DATABASE=planes_audit

# --- Sesiones en BD (obligatorio para multi-tenant) ---
SESSION_DRIVER=database
```

> **Importante:** Ajustar `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD` segun tu entorno local. El puerto por defecto de MariaDB/MySQL es `3306`. En este proyecto usamos `3050` porque la instancia esta aislada.

## 4. Crear las bases de datos

Conectarse a MariaDB/MySQL y crear las dos bases de datos centrales:

```sql
CREATE DATABASE IF NOT EXISTS planes_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS planes_audit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Desde la terminal:

```bash
mysql -u root -P 3050 -h 127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS planes_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS planes_audit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

> Las bases de datos de los tenants (`tenant_*`) se crean automaticamente al registrar una concesionaria desde la UI.

## 5. Ejecutar migraciones

El proyecto tiene dos conjuntos de migraciones separados:

### 5.1 Migraciones de la BD central — tablas del landlord

```bash
php artisan migrate --path=database/migrations/landlord --database=landlord
```

Esto crea en `planes_central` las tablas de gestion central:
- `tenants` — registro de concesionarias
- `brands` — marcas de vehiculos
- `plans` — planes de ahorro
- `tenant_brand` — pivot concesionaria-marca

### 5.2 Migraciones de la BD central — tablas de infraestructura

```bash
php artisan migrate --path=database/migrations/tenant
```

Esto crea en `planes_central` las tablas que tambien usa la app central:
- `users`, `password_reset_tokens` — autenticacion
- `sessions` — sesiones en BD
- `cache`, `cache_locks` — cache en BD
- `jobs`, `job_batches`, `failed_jobs` — colas
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` — RBAC (Spatie Permission)

## 6. Seed inicial (roles, permisos y usuario admin)

```bash
php artisan db:seed
```

Esto ejecuta `DatabaseSeeder` que llama a:

1. **RolesAndPermissionsSeeder** — Crea los 8 roles y 20 permisos definidos en `app/Enums/RoleEnum.php` y `app/Enums/PermissionEnum.php`.
2. **AdminUserSeeder** — Crea el usuario super_admin inicial:

| Campo | Valor |
|-------|-------|
| Nombre | Administrador |
| Email | `admin@planes.test` |
| Password | `password` |
| Rol | `super_admin` |

> **Nota:** Cuando se crea un tenant nuevo desde la UI, el `TenantObserver` ejecuta automaticamente: creacion de BD, migraciones, seed de roles/permisos, y creacion de un usuario `tenant_admin` con los datos ingresados en el formulario.

## 7. Compilar assets

Para desarrollo (con hot-reload):

```bash
npm run dev
```

Para produccion:

```bash
npm run build
```

## 8. Levantar la aplicacion

### Opcion A: Solo el servidor PHP (desarrollo basico)

```bash
php artisan serve
```

La app estara disponible en `http://localhost:8000`.

### Opcion B: Servidor + Vite + Queue en paralelo

```bash
composer dev
```

Esto levanta simultaneamente: servidor PHP, worker de colas y Vite con hot-reload.

## 9. Verificar la instalacion

### Verificar conexiones a BD

```bash
php artisan db:show
php artisan db:show --database=landlord
php artisan db:show --database=audit
```

### Verificar roles y permisos

```bash
php artisan tinker --execute="echo Spatie\Permission\Models\Role::count() . ' roles, ' . Spatie\Permission\Models\Permission::count() . ' permisos';"
```

Debe mostrar: `8 roles, 20 permisos`.

### Verificar login

1. Abrir `http://localhost:8000`
2. Ir a login
3. Ingresar con `admin@planes.test` / `password`
4. Debe redirigir al dashboard

## 10. Crear un tenant de prueba (opcional)

Desde la UI (recomendado):

1. Loguearse como `admin@planes.test` en `http://localhost:8000`
2. Ir a **Concesionarias** en el sidebar
3. Click en **Nueva Concesionaria**
4. Completar nombre, dominio, y datos del usuario admin
5. El sistema crea automaticamente: BD, tablas, roles/permisos y usuario admin

Desde tinker:

```php
use App\Models\Central\Tenant;

$tenant = new Tenant([
    'name' => 'Mi Concesionaria',
    'domain' => 'test.localhost',
    'database' => 'tenant_test',
]);
$tenant->admin_name = 'Admin Test';
$tenant->admin_email = 'admin@test.com';
$tenant->save();
// El Observer crea la BD, migraciones, roles/permisos y usuario admin automaticamente
```

### Acceso al tenant

Abrir `http://test.localhost:8000` y loguearse con el email/password del admin del tenant (`password` por defecto).

> **Nota sobre subdominios:** Los navegadores modernos resuelven `*.localhost` a `127.0.0.1` automaticamente. Si no funciona, agregar la entrada en el archivo `hosts`:
>
> - **Windows:** `C:\Windows\System32\drivers\etc\hosts` (editar como administrador)
> - **Linux/Mac:** `/etc/hosts`
>
> ```
> 127.0.0.1   test.localhost
> ```

## Arquitectura de bases de datos

```
MariaDB (puerto 3050)
├── planes_central    → BD central (tenants, marcas, planes, users super_admin)
├── planes_audit      → BD de auditoria (activity_log)
├── tenant_test       → BD de concesionaria "Test" (users, contratos, pagos...)
├── tenant_alianz     → BD de concesionaria "Alianz"
└── tenant_N...       → Una BD por cada concesionaria
```

### Conexiones en Laravel

| Conexion | Base de datos | Comportamiento |
|----------|---------------|----------------|
| `mariadb` | planes_central (default) | **Dinamica**: Spatie la switchea al tenant activo segun el subdominio |
| `landlord` | planes_central | **Fija**: siempre apunta a la BD central |
| `audit` | planes_audit | **Fija**: siempre apunta a la BD de auditoria |

## Comandos utiles

```bash
# Levantar servidor de desarrollo
php artisan serve

# Levantar todo en paralelo (servidor + queue + vite)
composer dev

# Correr migraciones en TODOS los tenants existentes
php artisan tenants:artisan "migrate --path=database/migrations/tenant --force"

# Correr migraciones en un tenant especifico
php artisan tenants:artisan "migrate --path=database/migrations/tenant --force" --tenant=1

# Seed de roles/permisos en todos los tenants (si se agregan roles nuevos)
php artisan tenants:artisan "db:seed --class=RolesAndPermissionsSeeder --force"

# Formateo de codigo
./vendor/bin/pint

# Analisis estatico
./vendor/bin/phpstan analyse

# Tests
php artisan test
```

## Resolucion de problemas

### "No database selected" despues de crear tenant
Si al crear un tenant desde la UI aparece este error, verificar que `TenantObserver` restaura la conexion correctamente. El observer usa `Tenant::forgetCurrent()` + restauracion manual del resolver de conexion.

### "SQLSTATE[HY000] [1049] Unknown database"
La BD del tenant no existe. Verificar que el `TenantObserver` la esta creando. Revisar logs en `storage/logs/laravel.log`.

### Subdominio no resuelve
Verificar que `*.localhost` resuelve a `127.0.0.1`. Si no, agregar la entrada manualmente al archivo `hosts` (ver paso 10).

### Error de permisos al crear tenant desde la UI
Solo el usuario con rol `super_admin` puede acceder a `/tenants`. Verificar que estas logueado con `admin@planes.test`.
