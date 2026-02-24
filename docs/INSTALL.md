# Guia de Instalacion — Planes de Ahorro Lastenia

## Requisitos previos

- **PHP 8.4+** con extensiones: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath
- **Composer** (gestor de dependencias PHP)
- **MariaDB 10.4+** o **MySQL 8+**
- **Node.js 18+** y **npm** (para compilar assets con Vite)
- **Git**

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

Editar `.env` con los datos de tu base de datos:

```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3050
DB_DATABASE=planes_central
DB_USERNAME=root
DB_PASSWORD=

DB_AUDIT_DATABASE=planes_audit
```

> **Nota:** Ajustar `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD` segun tu entorno.

## 4. Crear la instancia de MariaDB (si no existe)

Si necesitas una instancia aislada de MariaDB, segui estos pasos. Si ya tenes MariaDB/MySQL corriendo, pasa al paso 5.

### 4.1 Crear directorio de datos

```bash
mkdir -p C:/xampp/mysql_3050/data
mkdir -p C:/xampp/mysql_3050/logs
```

### 4.2 Inicializar la base de datos

```bash
"C:/xampp/mysql/bin/mysql_install_db.exe" --datadir="C:/xampp/mysql_3050/data" --port=3050 --default-user --allow-remote-root-access
```

### 4.3 Registrar como servicio de Windows (requiere terminal de administrador)

```powershell
& "C:/xampp/mysql/bin/mysqld.exe" --install MySQL_Planes --defaults-file="C:/xampp/mysql_3050/data/my.ini"
net start MySQL_Planes
```

### 4.4 Reparar performance_schema (una sola vez)

```bash
"C:/xampp/mysql/bin/mysql_upgrade" -u root -P 3050 -h 127.0.0.1 --force
```

## 5. Crear las bases de datos

Conectarse a MariaDB y ejecutar:

```sql
CREATE DATABASE IF NOT EXISTS planes_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS planes_audit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Con el cliente de linea de comandos:

```bash
mysql -u root -P 3050 -h 127.0.0.1 -e "CREATE DATABASE IF NOT EXISTS planes_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS planes_audit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

> Las bases de datos de los tenants (`tenant_1`, `tenant_2`, etc.) se crean automaticamente al registrar una concesionaria nueva.

## 6. Ejecutar migraciones

### Migraciones de la BD central (landlord)

```bash
php artisan migrate --path=database/migrations/landlord --database=landlord
```

Esto crea en `planes_central`: `tenants`, `brands`, `plans`, `tenant_brand`.

### Migraciones de la BD central (users, sessions, etc.)

```bash
php artisan migrate --path=database/migrations/tenant --database=mariadb
```

Esto crea las tablas de infraestructura (users, sessions, cache, jobs, roles, permissions) en la BD central para los super_admins.

### Seed de roles y permisos en la BD central

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

Esto crea los 8 roles y 20 permisos definidos en los Enums (`app/Enums/RoleEnum.php` y `app/Enums/PermissionEnum.php`).

> **Nota:** Cuando se crea un tenant nuevo, el `TenantObserver` ejecuta automaticamente las migraciones y el seeder de roles/permisos en la BD del tenant.

## 7. Compilar assets

```bash
npm run build
```

Para desarrollo con hot-reload:

```bash
npm run dev
```

## 8. Verificar la instalacion

```bash
# Verificar conexion a la BD
php artisan db:show

# Verificar que las tablas se crearon
php artisan db:show --database=landlord

# Verificar conexion a la BD de auditoria
php artisan db:show --database=audit
```

### Verificar roles y permisos

Conectarse a MariaDB y ejecutar:

```sql
-- En planes_central
SELECT r.name AS rol, GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') AS permisos
FROM roles r
LEFT JOIN role_has_permissions rp ON r.id = rp.role_id
LEFT JOIN permissions p ON rp.permission_id = p.id
GROUP BY r.id, r.name
ORDER BY r.id;
```

Deben aparecer 8 roles y 20 permisos distribuidos segun cada rol.

## 9. Crear un tenant de prueba (opcional)

```bash
php artisan tinker
```

```php
use App\Models\Central\Tenant;

$tenant = Tenant::create([
    'name' => 'Mi Concesionaria (Test)',
    'domain' => 'test.localhost',
    'database' => 'tenant_test',
]);
// El Observer crea la BD, corre las migraciones y seedea roles/permisos automaticamente
```

## Arquitectura de bases de datos

```
MariaDB (puerto 3050)
├── planes_central    → BD central (tenants, marcas, planes, super_admins)
├── planes_audit      → BD de auditoria (activity_log)
├── tenant_1          → BD de concesionaria 1 (usuarios, contratos, pagos...)
├── tenant_2          → BD de concesionaria 2
└── tenant_N...       → Una BD por cada concesionaria
```

### Conexiones en Laravel

| Conexion  | Base de datos      | Comportamiento |
|-----------|--------------------|----------------|
| `mariadb` | planes_central     | **Dinamica**: Spatie la switchea al tenant activo |
| `landlord`| planes_central     | **Fija**: siempre apunta a la central |
| `audit`   | planes_audit       | **Fija**: siempre apunta a auditoria |

## Comandos utiles

```bash
# Correr migraciones en TODOS los tenants existentes
php artisan tenants:artisan "migrate --path=database/migrations/tenant --force"

# Correr migraciones en un tenant especifico
php artisan tenants:artisan "migrate --path=database/migrations/tenant --force" --tenant=1

# Levantar el servidor de desarrollo
php artisan serve
```
