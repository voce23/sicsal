# SICSAL — Sistema de Información Censal en Salud de Primer Nivel
## Plan de Desarrollo Completo v2.0 — Laravel 13 + Filament 5 + Livewire 4

---

## 1. CONTEXTO DEL PROYECTO

### Descripción
Sistema web para registro, gestión y análisis de información censal y de prestaciones de salud de centros de salud de primer nivel de atención en Bolivia. Permite comparar la población asignada por el INE con la población real registrada en el padrón, y genera informes para el CAI (Comité de Análisis de Información) y la Jefatura Municipal de Salud.

### Centro de salud piloto
- **Nombre**: C.S.A. HORNOMA
- **Código SNIS**: 300183
- **Departamento**: Cochabamba
- **Red de Salud**: Capinota
- **Municipio**: Capinota
- **Subsector**: Público
- **Población INE asignada**: 641 habitantes
- **Comunidades asignadas** (8):
  - Hornoma — 0 km (sede del centro de salud)
  - Huaychoma — 5 km
  - Villcabamba — 6 km
  - Cocoma — 24 km
  - Tocohalla — 48 km
  - Challavilque — 48 km
  - Calacaja — 72 km
  - Siquimirani — distancia a confirmar

### Problema principal
Existe alta migración de población en edad productiva. Esto genera meses o años sin controles prenatales ni partos. El sistema debe poder **justificar los ceros** en los indicadores de tres formas:
- **Idea 1**: Modal obligatorio al registrar cero en indicadores clave (motivo seleccionable)
- **Idea 5**: Campo de observaciones narrativas mensuales (texto libre, aparece en informe CAI)
- **Idea 6**: Bloque de contexto de migración calculado automáticamente desde el padrón (aparece en informe CAI y estadísticas)

### Roles del sistema
| Rol | Perfil real | Permisos |
|---|---|---|
| superadmin | Responsable del sistema | Ve todos los municipios y centros, crea admins |
| admin | Médico del centro de salud | Administra su centro, aprueba datos, define metas |
| registrador | Enfermera del centro de salud | Ingresa datos, consulta reportes |

Sin registro público. El admin crea los usuarios. El superadmin crea los admins.

### Períodos CAI (Comité de Análisis de Información)
| Corte | Mes | Período acumulado |
|---|---|---|
| CAI 1 | Mayo | Enero → Abril (4 meses) |
| CAI 2 | Septiembre | Enero → Agosto (8 meses) |
| CAI 3 / Cierre | Enero siguiente | Enero → Diciembre (año completo) |

---

## 2. ENTORNO DE DESARROLLO

### Herramientas locales
| Herramienta | Versión | Uso |
|---|---|---|
| Laragon | 6.x | Servidor local (Apache + MySQL + PHP) |
| PHP | 8.3.26 | Runtime del proyecto |
| MySQL | 8.x (incluido en Laragon) | Base de datos |
| phpMyAdmin | incluido en Laragon | Administración visual de BD |
| VS Code | última estable | Editor de código |
| Composer | 2.x | Gestor de dependencias PHP |
| Node.js | 18+ LTS | Compilación de assets (Vite) |

### Extensiones de VS Code recomendadas
```
Laravel Extension Pack (instala múltiples de una vez)
PHP Intelephense
Tailwind CSS IntelliSense
Blade Formatter
GitLens
```

### Stack tecnológico oficial
| Componente | Tecnología | Versión |
|---|---|---|
| Framework PHP | Laravel | 13.x |
| Panel admin | Filament | 5.x |
| Componentes reactivos | Livewire | 4.x (incluido en Filament 5) |
| CSS | Tailwind CSS | 4.1+ (requerido por Filament 5) |
| Base de datos | MySQL | 8.x |
| Roles y permisos | Spatie Laravel Permission | última compatible con L13 |
| Exportación Excel | Maatwebsite Laravel Excel | última compatible con L13 |
| Exportación PDF | Barryvdh Laravel DomPDF | última compatible con L13 |
| Gráficos | Chart.js | vía plugin Filament Charts |

### Producción (hosting)
- **Hosting**: Banahosting (cPanel)
- **PHP en producción**: 8.2 o 8.3 (ambos disponibles)
- **Despliegue**: subir vendor/ compilado + assets Vite compilados

---

## 3. INSTALACIÓN PASO A PASO

### 3.1 Crear el proyecto Laravel 13

Abrir terminal en VS Code, posicionarse en `C:\laragon\www\` y ejecutar:

```bash
composer create-project laravel/laravel sicsal

cd sicsal
```

### 3.2 Configurar el archivo .env

Editar `C:\laragon\www\sicsal\.env`:

```env
APP_NAME="SICSAL"
APP_ENV=local
APP_KEY=                          # se genera con artisan
APP_DEBUG=true
APP_URL=http://sicsal.test        # dominio local de Laragon

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sicsal
DB_USERNAME=root
DB_PASSWORD=                      # vacío en Laragon por defecto

FILESYSTEM_DISK=local
```

### 3.3 Crear la base de datos en phpMyAdmin

1. Abrir `http://localhost/phpmyadmin`
2. Clic en "Nueva base de datos"
3. Nombre: `sicsal`
4. Cotejamiento: `utf8mb4_unicode_ci`
5. Clic en "Crear"

### 3.4 Instalar Filament 5

```bash
composer require filament/filament:"^5.0"

php artisan filament:install --panels
```

Cuando pregunte por el ID del panel escribir: `admin`

### 3.5 Instalar Spatie Laravel Permission

```bash
composer require spatie/laravel-permission

php artisan vendor:publish --provider="Spatie\LaravelPermission\PermissionServiceProvider"

php artisan migrate
```

### 3.6 Instalar Laravel Excel (exportación)

```bash
composer require maatwebsite/excel
```

### 3.7 Instalar DomPDF (reportes PDF)

```bash
composer require barryvdh/laravel-dompdf
```

### 3.8 Generar clave de la aplicación

```bash
php artisan key:generate
```

### 3.9 Configurar dominio local en Laragon

1. Abrir Laragon → clic derecho en el ícono de la bandeja
2. Preferencias → Dominios virtuales
3. Verificar que el sufijo sea `.test`
4. El proyecto en `C:\laragon\www\sicsal` será accesible como `http://sicsal.test`

---

## 4. ESTRUCTURA DEL PROYECTO LARAVEL

```
sicsal/
├── app/
│   ├── Filament/
│   │   ├── Pages/              → Páginas personalizadas del panel
│   │   │   ├── Dashboard.php
│   │   │   ├── Piramide.php
│   │   │   ├── InformeCAI.php
│   │   │   └── CoberturaProgramas.php
│   │   ├── Resources/          → CRUD de Filament (uno por módulo)
│   │   │   ├── CentroSaludResource.php
│   │   │   ├── ComunidadResource.php
│   │   │   ├── UsuarioResource.php
│   │   │   ├── MetaIneResource.php
│   │   │   ├── PersonaResource.php
│   │   │   ├── DefuncionResource.php
│   │   │   ├── VacunaNinoResource.php
│   │   │   ├── CrecimientoInfantilResource.php
│   │   │   ├── MicronutrienteNinoResource.php
│   │   │   ├── EmbarazoResource.php
│   │   │   ├── ControlPrenatalResource.php
│   │   │   ├── PartoResource.php
│   │   │   └── AnticoncepcionResource.php
│   │   └── Widgets/            → Widgets del dashboard
│   │       ├── EstadisticasPoblacion.php
│   │       ├── AlertasWidget.php
│   │       ├── CoberturaMensual.php
│   │       └── MigracionContexto.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── PrestacionesController.php
│   │   │   ├── InformeCAIController.php
│   │   │   └── ExportController.php
│   │   └── Middleware/
│   │       └── FiltroCentroSalud.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Municipio.php
│   │   ├── CentroSalud.php
│   │   ├── Comunidad.php
│   │   ├── MetaIne.php
│   │   ├── Persona.php
│   │   ├── Defuncion.php
│   │   ├── VacunaNino.php
│   │   ├── CrecimientoInfantil.php
│   │   ├── MicronutrienteNino.php
│   │   ├── Embarazo.php
│   │   ├── ControlPrenatal.php
│   │   ├── Parto.php
│   │   ├── Puerperio.php
│   │   ├── Anticoncepcion.php
│   │   ├── PrestConsultaExterna.php
│   │   ├── PrestMicronutriente.php
│   │   ├── PrestVacuna.php
│   │   ├── PrestPrenatal.php
│   │   ├── PrestParto.php
│   │   ├── PrestPuerperio.php
│   │   ├── PrestCrecimiento.php
│   │   ├── PrestRecienNacido.php
│   │   ├── PrestAnticoncepcion.php
│   │   ├── PrestActividadComunidad.php
│   │   ├── JustificacionCero.php
│   │   ├── ObservacionMensual.php
│   │   └── MesCerrado.php
│   ├── Policies/
│   │   ├── PersonaPolicy.php
│   │   └── PrestacionPolicy.php
│   └── Exports/
│       ├── PadronExport.php
│       ├── ComunidadesExport.php
│       └── InformeCAIExport.php
├── database/
│   ├── migrations/             → Una migración por tabla
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── MunicipioSeeder.php
│       ├── CentroSaludSeeder.php
│       ├── ComunidadSeeder.php
│       ├── MetaIneSeeder.php
│       └── UserSeeder.php
├── resources/
│   ├── views/
│   │   ├── filament/           → Vistas personalizadas de Filament
│   │   ├── pdf/
│   │   │   └── informe-cai.blade.php
│   │   └── exports/
│   │       └── informe-cai.blade.php
│   └── css/
│       └── app.css
└── routes/
    ├── web.php
    └── api.php
```

---

## 5. MIGRACIONES — BASE DE DATOS COMPLETA

Crear cada migración con `php artisan make:migration create_TABLA_table`

### 5.1 Municipios
```php
// database/migrations/xxxx_create_municipios_table.php
Schema::create('municipios', function (Blueprint $table) {
    $table->id();
    $table->string('nombre', 100);
    $table->string('departamento', 100)->default('Cochabamba');
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

### 5.2 Centros de Salud
```php
Schema::create('centros_salud', function (Blueprint $table) {
    $table->id();
    $table->foreignId('municipio_id')->constrained('municipios');
    $table->string('nombre', 150);
    $table->string('codigo_snis', 20)->nullable();
    $table->enum('subsector', ['Público', 'Seguro Social', 'Privado', 'ONG'])->default('Público');
    $table->string('red_salud', 100)->nullable();
    $table->integer('poblacion_ine')->default(0);
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

### 5.3 Comunidades
```php
Schema::create('comunidades', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->string('nombre', 100);
    $table->decimal('distancia_km', 6, 2)->nullable();
    $table->decimal('latitud', 10, 7)->nullable();
    $table->decimal('longitud', 10, 7)->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

### 5.4 Users (modificar la migración existente de Laravel)
```php
// Añadir a la migración users existente:
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('centro_salud_id')->nullable()->constrained('centros_salud');
    $table->string('apellidos', 100)->after('name');
    // 'name' se usa para nombres
    // el rol se maneja con Spatie (tabla model_has_roles)
    $table->boolean('activo')->default(true);
    $table->timestamp('ultimo_acceso')->nullable();
});
```

### 5.5 Metas INE
```php
Schema::create('metas_ine', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->year('anio');
    $table->enum('grupo_etareo', [
        'menor_1', '1_anio', '2_anios', '3_anios', '4_anios',
        '1_4', '5_anios', '6_anios', 'menor_5', 'mayor_5',
        'menor_2', '2_3', '2_4', '5_9', '7_9', '10',
        '10_14', '15_19', '20_39', '40_49', '50_59', 'mayor_60',
        'mef_15_40', '7_49', 'adolescentes_10_19', 'mujeres_menor_20',
        'embarazos_esperados', 'partos_esperados', 'nacimientos_esperados', 'dt_7_49'
    ]);
    $table->enum('sexo', ['M', 'F', 'ambos']);
    $table->integer('cantidad')->default(0);
    $table->timestamps();
    $table->unique(['centro_salud_id', 'anio', 'grupo_etareo', 'sexo']);
});
```

### 5.6 Personas (Padrón)
```php
Schema::create('personas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->foreignId('comunidad_id')->constrained('comunidades');
    $table->string('nombres', 100);
    $table->string('apellidos', 100);
    $table->date('fecha_nacimiento');
    $table->enum('sexo', ['M', 'F']);
    $table->string('ci', 20)->nullable();
    $table->enum('tipo_seguro', ['SUS', 'privado', 'ninguno'])->default('ninguno');
    $table->enum('estado', ['residente', 'temporal', 'migrado'])->default('residente');
    $table->string('destino_migracion', 150)->nullable();
    $table->date('fecha_migracion')->nullable();
    $table->string('grupo_etareo', 30)->nullable(); // calculado al guardar
    $table->date('fecha_registro');
    $table->text('observaciones')->nullable();
    $table->boolean('activo')->default(true);
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->timestamps();
});
```

### 5.7 Defunciones
```php
Schema::create('defunciones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('persona_id')->nullable()->constrained('personas');
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->string('nombres', 100);
    $table->string('apellidos', 100);
    $table->date('fecha_nacimiento')->nullable();
    $table->enum('sexo', ['M', 'F']);
    $table->foreignId('comunidad_id')->nullable()->constrained('comunidades');
    $table->date('fecha_defuncion');
    $table->text('causa_defuncion')->nullable();
    $table->enum('lugar', ['establecimiento', 'domicilio', 'referido', 'en_transito']);
    $table->enum('grupo_etareo_defuncion', ['neonatal', 'infantil_menor_1', 'menor_5', '5_a_59', 'adulto_mayor']);
    $table->foreignId('registrado_por')->nullable()->constrained('users');
    $table->timestamps();
});
```

### 5.8 Vacunas Niños (individual)
```php
Schema::create('vacunas_ninos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('persona_id')->constrained('personas');
    $table->enum('tipo_vacuna', [
        'BCG', 'HepB_neonatal',
        'Pentavalente_1', 'Pentavalente_2', 'Pentavalente_3',
        'Pentavalente_4_refuerzo', 'Pentavalente_5_refuerzo',
        'IPV_1', 'bOPV_2', 'IPV_3', 'bOPV_4', 'bOPV_5',
        'Antirotavirica_1', 'Antirotavirica_2',
        'Antineumococica_1', 'Antineumococica_2', 'Antineumococica_3',
        'Influenza_1', 'Influenza_2', 'Influenza_unica',
        'SRP_1', 'SRP_2', 'Antiamarilica',
        'VPH_1', 'VPH_2',
        'dT_1', 'dT_2', 'dT_3', 'dT_4', 'dT_5', 'SR'
    ]);
    $table->date('fecha_aplicacion');
    $table->enum('dentro_fuera', ['dentro', 'fuera'])->default('dentro');
    $table->string('lote', 50)->nullable();
    $table->foreignId('aplicado_por')->nullable()->constrained('users');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->timestamps();
});
```

### 5.9 Crecimiento Infantil (individual)
```php
Schema::create('crecimiento_infantil', function (Blueprint $table) {
    $table->id();
    $table->foreignId('persona_id')->constrained('personas');
    $table->date('fecha');
    $table->decimal('peso_kg', 5, 2)->nullable();
    $table->decimal('talla_cm', 5, 2)->nullable();
    $table->decimal('perimetro_cefalico_cm', 5, 2)->nullable();
    $table->enum('clasificacion', [
        'normal', 'desnutricion_aguda', 'desnutricion_cronica',
        'desnutricion_global', 'sobrepeso', 'obesidad'
    ]);
    $table->enum('tipo_control', ['nuevo', 'repetido'])->default('nuevo');
    $table->enum('dentro_fuera', ['dentro', 'fuera'])->default('dentro');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->foreignId('registrado_por')->nullable()->constrained('users');
    $table->timestamps();
});
```

### 5.10 Micronutrientes Niños (individual)
```php
Schema::create('micronutrientes_ninos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('persona_id')->constrained('personas');
    $table->enum('tipo', [
        'hierro_menor_6m', 'hierro_6m_1anio', 'hierro_1anio', 'hierro_2_5anios',
        'vitamina_a_menor_1', 'vitamina_a_1anio_1ra', 'vitamina_a_1anio_2da',
        'vitamina_a_2_5_1ra', 'vitamina_a_2_5_2da',
        'zinc_menor_1', 'zinc_1anio',
        'chispitas_6_23m', 'nutribebe_menor_1', 'nutribebe_1anio'
    ]);
    $table->date('fecha');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->foreignId('registrado_por')->nullable()->constrained('users');
    $table->timestamps();
});
```

### 5.11 Embarazos
```php
Schema::create('embarazos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('persona_id')->constrained('personas');
    $table->date('fecha_inicio')->nullable();
    $table->date('fecha_probable_parto')->nullable();
    $table->tinyInteger('semanas_gestacion_ingreso')->nullable();
    $table->enum('estado', [
        'activa', 'migrada_temporal', 'atendida_otro_centro', 'culminada', 'perdida'
    ])->default('activa');
    $table->text('observaciones')->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

### 5.12 Controles Prenatales
```php
Schema::create('controles_prenatales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('embarazo_id')->constrained('embarazos');
    $table->tinyInteger('numero_control'); // 1, 2, 3, 4
    $table->date('fecha');
    $table->tinyInteger('semanas_gestacion')->nullable();
    $table->enum('dentro_fuera', ['dentro', 'fuera'])->default('dentro');
    $table->string('grupo_etareo', 20)->nullable();
    $table->foreignId('registrado_por')->nullable()->constrained('users');
    $table->timestamps();
});
```

### 5.13 Partos
```php
Schema::create('partos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('embarazo_id')->constrained('embarazos');
    $table->date('fecha_parto');
    $table->enum('tipo', ['vaginal', 'cesarea']);
    $table->enum('lugar', ['servicio', 'domicilio']);
    $table->enum('atendido_por', [
        'personal_calificado', 'partera_empirica',
        'partera_capacitada', 'articulacion', 'otros'
    ]);
    $table->string('grupo_etareo', 20)->nullable();
    $table->enum('resultado', ['nacido_vivo', 'nacido_muerto'])->default('nacido_vivo');
    $table->decimal('peso_rn_kg', 4, 3)->nullable();
    $table->foreignId('registrado_por')->nullable()->constrained('users');
    $table->timestamps();
});
```

### 5.14 Puerperio
```php
Schema::create('puerperios', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parto_id')->constrained('partos');
    $table->date('control_48h')->nullable();
    $table->date('control_7d')->nullable();
    $table->date('control_28d')->nullable();
    $table->date('control_42d')->nullable();
    $table->timestamps();
});
```

### 5.15 Anticoncepción
```php
Schema::create('anticoncepcion', function (Blueprint $table) {
    $table->id();
    $table->foreignId('persona_id')->constrained('personas');
    $table->enum('metodo', [
        'DIU', 'inyectable_mensual', 'inyectable_trimestral', 'pildora',
        'condon_masculino', 'condon_femenino', 'implante_subdermic',
        'metodos_naturales', 'AQV_femenino', 'AQV_masculino', 'pildora_emergencia'
    ]);
    $table->enum('tipo_usuaria', ['nueva', 'continua']);
    $table->date('fecha');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->foreignId('registrado_por')->nullable()->constrained('users');
    $table->timestamps();
});
```

### 5.16 Prestaciones Mensuales — Consulta Externa
```php
// IMPORTANTE: Estas tablas guardan TOTALES mensuales, no registros individuales.
// La enfermera ingresa los conteos al final de cada mes.

Schema::create('prest_consulta_externa', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->enum('grupo_etareo', [
        'menor_6m', '6m_menor_1', '1_4', '5_9', '10_14',
        '15_19', '20_39', '40_49', '50_59', 'mayor_60'
    ]);
    $table->integer('primera_m')->default(0);
    $table->integer('primera_f')->default(0);
    $table->integer('nueva_m')->default(0);
    $table->integer('nueva_f')->default(0);
    $table->integer('repetida_m')->default(0);
    $table->integer('repetida_f')->default(0);
    $table->timestamps();
    $table->unique(['centro_salud_id', 'mes', 'anio', 'grupo_etareo']);
});
```

### 5.17 Prestaciones — Micronutrientes
```php
Schema::create('prest_micronutrientes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->enum('tipo', [
        'hierro_embarazadas_completo', 'hierro_puerperas_completo',
        'hierro_menor_6m', 'hierro_menor_1', 'hierro_1anio', 'hierro_2_5',
        'vitA_puerpera_unica', 'vitA_menor_1_unica',
        'vitA_1anio_1ra', 'vitA_1anio_2da',
        'vitA_2_5_1ra', 'vitA_2_5_2da',
        'zinc_menor_1', 'zinc_1anio',
        'nutribebe_menor_1', 'nutribebe_1anio',
        'nutrimama_embarazada', 'nutrimama_lactancia',
        'carmelo_mayor_60', 'chispitas_6_23m'
    ]);
    $table->integer('cantidad')->default(0);
    $table->timestamps();
    $table->unique(['centro_salud_id', 'mes', 'anio', 'tipo']);
});
```

### 5.18 Prestaciones — Vacunas (conteos mensuales)
```php
Schema::create('prest_vacunas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->string('tipo_vacuna', 60);
    $table->string('grupo_etareo', 30);
    $table->integer('dentro_m')->default(0);
    $table->integer('dentro_f')->default(0);
    $table->integer('fuera_m')->default(0);
    $table->integer('fuera_f')->default(0);
    $table->timestamps();
    $table->unique(['centro_salud_id', 'mes', 'anio', 'tipo_vacuna', 'grupo_etareo']);
});
```

### 5.19 Prestaciones — Prenatales
```php
Schema::create('prest_prenatales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->enum('tipo_control', [
        'nueva_1er_trim', 'nueva_2do_trim', 'nueva_3er_trim', 'repetida', 'con_4to_control'
    ]);
    $table->enum('grupo_etareo', ['menor_10', '10_14', '15_19', '20_34', '35_49', '50_mas']);
    $table->integer('dentro')->default(0);
    $table->integer('fuera')->default(0);
    $table->timestamps();
    $table->unique(['centro_salud_id', 'mes', 'anio', 'tipo_control', 'grupo_etareo']);
});
```

### 5.20 Prestaciones — Partos
```php
Schema::create('prest_partos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->enum('tipo', ['vaginal', 'cesarea']);
    $table->enum('lugar', ['servicio', 'domicilio']);
    $table->enum('atendido_por', [
        'personal_calificado', 'partera_empirica', 'partera_capacitada', 'articulacion', 'otros'
    ]);
    $table->enum('grupo_etareo', ['menor_10', '10_14', '15_19', '20_34', '35_49', '50_mas']);
    $table->integer('cantidad')->default(0);
    $table->timestamps();
});
```

### 5.21 Prestaciones — Puerperio
```php
Schema::create('prest_puerperio', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->enum('tipo_control', ['48h', '7dias', '28dias', '42dias']);
    $table->integer('cantidad')->default(0);
    $table->timestamps();
    $table->unique(['centro_salud_id', 'mes', 'anio', 'tipo_control']);
});
```

### 5.22 Prestaciones — Crecimiento Infantil
```php
Schema::create('prest_crecimiento', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->enum('grupo_etareo', [
        'menor_1_dentro', 'menor_1_fuera',
        '1_menor_2_dentro', '1_menor_2_fuera',
        '2_menor_5_dentro', '2_menor_5_fuera'
    ]);
    $table->integer('nuevos_m')->default(0);
    $table->integer('nuevos_f')->default(0);
    $table->integer('repetidos_m')->default(0);
    $table->integer('repetidos_f')->default(0);
    $table->timestamps();
    $table->unique(['centro_salud_id', 'mes', 'anio', 'grupo_etareo']);
});
```

### 5.23 Prestaciones — Recién Nacidos
```php
Schema::create('prest_recien_nacidos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->enum('indicador', [
        'nacidos_vivos_servicio', 'nacidos_vivos_domicilio',
        'nacidos_vivos_4cpn', 'nacidos_vivos_peso_menor_2500',
        'nacidos_muertos', 'rn_lactancia_inmediata',
        'rn_alojamiento_conjunto', 'rn_corte_tardio_cordon',
        'rn_malformacion_congenita', 'rn_control_48h'
    ]);
    $table->integer('cantidad')->default(0);
    $table->timestamps();
    $table->unique(['centro_salud_id', 'mes', 'anio', 'indicador']);
});
```

### 5.24 Prestaciones — Anticoncepción
```php
Schema::create('prest_anticoncepcion', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->string('metodo', 60);
    $table->enum('tipo_usuaria', ['nueva', 'continua']);
    $table->enum('grupo_etareo', ['menor_10', '10_14', '15_19', '20_34', '35_49', '50_mas']);
    $table->integer('cantidad')->default(0);
    $table->timestamps();
});
```

### 5.25 Prestaciones — Actividades Comunidad
```php
Schema::create('prest_actividades_comunidad', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->enum('tipo_actividad', [
        'actividades_con_comunidad', 'cai_establecimiento',
        'comunidades_en_cai', 'familias_nuevas_carpetizadas',
        'familias_seguimiento', 'visitas_primeras',
        'visitas_segundas', 'visitas_terceras',
        'reuniones_autoridades', 'reuniones_comites_salud',
        'actividades_educativas_salud',
        'pcd_atendidas_establecimiento', 'pcd_atendidas_comunidad'
    ]);
    $table->integer('cantidad')->default(0);
    $table->timestamps();
    $table->unique(['centro_salud_id', 'mes', 'anio', 'tipo_actividad']);
});
```

### 5.26 Justificaciones de Cero (Idea 1)
```php
Schema::create('justificaciones_cero', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->enum('indicador', [
        'control_prenatal', 'partos', 'puerperio',
        'vacunacion_menor_5', 'control_crecimiento', 'micronutrientes_menor_5'
    ]);
    $table->enum('motivo', [
        'no_hay_poblacion_activa_padron',
        'poblacion_migrada_temporal',
        'atendida_otro_centro',
        'no_se_presento_razon_desconocida',
        'otro'
    ]);
    $table->string('detalle', 300)->nullable();
    $table->foreignId('registrado_por')->constrained('users');
    $table->timestamps();
    $table->unique(['centro_salud_id', 'mes', 'anio', 'indicador']);
});
```

### 5.27 Observaciones Mensuales (Idea 5)
```php
Schema::create('observaciones_mensuales', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->text('texto');
    $table->foreignId('registrado_por')->constrained('users');
    $table->timestamps();
    $table->unique(['centro_salud_id', 'mes', 'anio']);
});
```

### 5.28 Meses Cerrados
```php
Schema::create('meses_cerrados', function (Blueprint $table) {
    $table->id();
    $table->foreignId('centro_salud_id')->constrained('centros_salud');
    $table->tinyInteger('mes');
    $table->year('anio');
    $table->foreignId('cerrado_por')->constrained('users');
    $table->timestamp('fecha_cierre')->useCurrent();
    $table->foreignId('reabierto_por')->nullable()->constrained('users');
    $table->timestamp('fecha_reapertura')->nullable();
    $table->timestamps();
    $table->unique(['centro_salud_id', 'mes', 'anio']);
});
```

---

## 6. MODELOS ELOQUENT — RELACIONES PRINCIPALES

### User.php
```php
class User extends Authenticatable implements FilamentUser
{
    use HasRoles; // Spatie

    public function centroSalud(): BelongsTo
    {
        return $this->belongsTo(CentroSalud::class);
    }

    // Requerido por Filament: controla acceso al panel
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->activo && in_array($this->getRoleNames()->first(), ['superadmin', 'admin', 'registrador']);
    }
}
```

### CentroSalud.php
```php
class CentroSalud extends Model
{
    public function municipio(): BelongsTo { return $this->belongsTo(Municipio::class); }
    public function comunidades(): HasMany { return $this->hasMany(Comunidad::class); }
    public function personas(): HasMany { return $this->hasMany(Persona::class); }
    public function metasIne(): HasMany { return $this->hasMany(MetaIne::class); }
    public function usuarios(): HasMany { return $this->hasMany(User::class); }
}
```

### Persona.php
```php
class Persona extends Model
{
    public function centroSalud(): BelongsTo { return $this->belongsTo(CentroSalud::class); }
    public function comunidad(): BelongsTo { return $this->belongsTo(Comunidad::class); }
    public function embarazos(): HasMany { return $this->hasMany(Embarazo::class); }
    public function vacunas(): HasMany { return $this->hasMany(VacunaNino::class); }
    public function crecimiento(): HasMany { return $this->hasMany(CrecimientoInfantil::class); }
    public function micronutrientes(): HasMany { return $this->hasMany(MicronutrienteNino::class); }
    public function defuncion(): HasOne { return $this->hasOne(Defuncion::class); }

    // Accesors calculados
    public function getEdadAttribute(): int
    {
        return Carbon::parse($this->fecha_nacimiento)->age;
    }

    public function getEdadMesesAttribute(): int
    {
        return Carbon::parse($this->fecha_nacimiento)->diffInMonths(now());
    }

    public function getGrupoEtareoCalculadoAttribute(): string
    {
        $meses = $this->edad_meses;
        $anios = $this->edad;
        if ($meses < 6) return 'menor_6m';
        if ($meses < 12) return '6m_menor_1';
        if ($anios < 5) return '1_4';
        if ($anios < 10) return '5_9';
        if ($anios < 15) return '10_14';
        if ($anios < 20) return '15_19';
        if ($anios < 40) return '20_39';
        if ($anios < 50) return '40_49';
        if ($anios < 60) return '50_59';
        return 'mayor_60';
    }

    // Scopes útiles
    public function scopeActivos($query) { return $query->where('activo', true); }
    public function scopeResidentes($query) { return $query->where('estado', 'residente'); }
    public function scopeMigrados($query) { return $query->where('estado', 'migrado'); }
    public function scopeMenoresDe5($query) {
        return $query->where('fecha_nacimiento', '>=', now()->subYears(5));
    }
    public function scopeMujeresMef($query) {
        // Mujeres en edad fértil: 15 a 49 años
        return $query->where('sexo', 'F')
            ->where('fecha_nacimiento', '<=', now()->subYears(15))
            ->where('fecha_nacimiento', '>=', now()->subYears(49));
    }
}
```

---

## 7. CONFIGURACIÓN DE FILAMENT 5

### 7.1 Panel provider (app/Providers/Filament/AdminPanelProvider.php)

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()                              // Login incluido
        ->colors(['primary' => Color::Blue])
        ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
        ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
        ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
        ->widgets([
            EstadisticasPoblacion::class,
            AlertasWidget::class,
            MigracionContexto::class,
        ])
        ->navigationGroups([
            'Configuración',
            'Padrón Poblacional',
            'Salud Infantil',
            'Salud Materna',
            'Prestaciones Mensuales',
            'Estadísticas',
            'Informes',
        ]);
}
```

### 7.2 Roles Spatie — creación en seeder

```php
// database/seeders/RolesSeeder.php
use Spatie\LaravelPermission\Models\Role;

Role::create(['name' => 'superadmin']);
Role::create(['name' => 'admin']);
Role::create(['name' => 'registrador']);
```

### 7.3 Filtro global por centro de salud

Todos los Resources que muestran datos de un centro deben aplicar este scope para que admin y registrador solo vean su centro:

```php
// En cada Resource, método table():
->modifyQueryUsing(function (Builder $query) {
    $user = auth()->user();
    if (!$user->hasRole('superadmin')) {
        $query->where('centro_salud_id', $user->centro_salud_id);
    }
})
```

---

## 8. MÓDULOS — ESPECIFICACIÓN DETALLADA

---

### MÓDULO 0 — AUTENTICACIÓN

Filament 5 incluye el login por defecto al añadir `->login()` en el panel provider.

**Personalizar**:
- Cambiar el logo en `resources/views/vendor/filament/components/logo.blade.php`
- **No** activar registro público: no añadir `->registration()`
- **No** activar recuperación de contraseña pública: el admin resetea manualmente

**Reset de contraseña por admin**:
En el Resource de usuarios, el admin puede hacer clic en "Resetear contraseña" que genera una nueva contraseña temporal y la muestra en un modal una sola vez.

---

### MÓDULO 1 — CONFIGURACIÓN

**Navegación**: grupo "Configuración" en el sidebar de Filament

#### 1.1 Resource: CentroSaludResource
Solo visible para **superadmin**

Columnas en tabla: Nombre | Código SNIS | Municipio | Red de Salud | Población INE | Estado

Formulario:
- nombre (TextInput, requerido)
- codigo_snis (TextInput)
- municipio_id (Select con búsqueda)
- subsector (Select: Público/Seguro Social/Privado/ONG)
- red_salud (TextInput)
- poblacion_ine (TextInput numérico)
- activo (Toggle)

#### 1.2 Resource: ComunidadResource
Visible para **superadmin** y **admin**. Admin solo ve su centro.

Columnas: Nombre | Distancia km | Centro de Salud | Estado

Formulario:
- nombre (TextInput, requerido)
- centro_salud_id (Select, superadmin puede elegir, admin fijo en el suyo)
- distancia_km (TextInput numérico)
- latitud / longitud (TextInput, opcionales)
- activo (Toggle)

#### 1.3 Resource: MetaIneResource
Visible para **admin**. Solo puede ver y editar metas de su centro.

Presentación: tabla agrupada por año con todos los grupos etáreos y sus cantidades.

Permite importar desde Excel con plantilla descargable.

Valores iniciales ya incluidos en el seeder para C.S.A. HORNOMA 2026 (ver sección 10).

#### 1.4 Resource: UsuarioResource
- **Superadmin**: ve todos, crea admins y los asigna a centros
- **Admin**: ve y crea registradores de su centro

Formulario:
- name (nombres, TextInput requerido)
- apellidos (TextInput requerido)
- usuario (TextInput único requerido)
- contraseña temporal (TextInput, se muestra solo al crear)
- rol (Select: admin/registrador — superadmin puede asignar cualquier rol)
- centro_salud_id (Select, obligatorio para admin y registrador)
- activo (Toggle)

---

### MÓDULO 2 — PADRÓN POBLACIONAL

**Navegación**: grupo "Padrón Poblacional"

#### 2.1 Resource: PersonaResource

**Tabla con columnas**:
- Nombre completo (nombres + apellidos)
- Comunidad
- Edad (calculada en tiempo real)
- Sexo (badge M=azul, F=rosa)
- Tipo de seguro (badge)
- Estado (badge: residente=verde, temporal=amarillo, migrado=rojo)
- Acciones: ver, editar

**Filtros disponibles**:
- Por comunidad (Select)
- Por sexo (M/F)
- Por grupo etáreo (calculado)
- Por tipo de seguro
- Por estado migratorio
- Por rango de fechas de registro

**Formulario de alta/edición**:

Sección "Datos personales":
- nombres (TextInput, requerido)
- apellidos (TextInput, requerido)
- fecha_nacimiento (DatePicker, requerido)
- sexo (Radio: Masculino / Femenino, requerido)
- ci (TextInput, opcional — validar duplicado con advertencia, no bloqueo)

Sección "Seguro y ubicación":
- tipo_seguro (Select: SUS / Privado / Ninguno)
- comunidad_id (Select filtrado por centro del usuario)
- fecha_registro (DatePicker, default hoy)

Sección "Estado migratorio":
- estado (Select: Residente / Temporal / Migrado)
- Si estado = migrado: mostrar campo destino_migracion y fecha_migracion (usando Filament `->hidden(fn($get) => $get('estado') !== 'migrado')`)

Sección "Observaciones":
- observaciones (Textarea)

**Acción especial "Registrar defunción"**: botón en la fila de la tabla que abre un modal con el formulario de defunción prellenado con los datos de la persona.

**Exportación**: botón "Exportar a Excel" en la cabecera de la tabla, usa clase `PadronExport`.

#### 2.2 Resource: DefuncionResource

Acceso: registrador y admin.

**Flujo de registro**:
1. Campo de búsqueda: buscar en el padrón por nombre (Select con búsqueda en vivo)
2. Si se selecciona una persona del padrón: los datos se rellenan automáticamente
3. Si no está en el padrón: se pueden ingresar datos manuales
4. Al guardar, si la persona estaba en el padrón: marcarla como `activo = false`

**Campos**:
- persona_id (Select con búsqueda — opcional)
- nombres, apellidos, fecha_nacimiento, sexo (auto desde padrón o manual)
- comunidad_id (Select)
- fecha_defuncion (DatePicker, requerido)
- causa_defuncion (Textarea)
- lugar (Select: En el establecimiento / Domicilio / Referido / En tránsito)
- grupo_etareo_defuncion: calculado automáticamente al guardar según la edad al momento del fallecimiento

**Lógica de grupo etáreo de defunción**:
- < 28 días: neonatal
- 28 días a < 1 año: infantil_menor_1
- 1 a < 5 años: menor_5
- 5 a 59 años: 5_a_59
- 60 años y más: adulto_mayor

---

### MÓDULO 3 — SALUD INFANTIL

**Navegación**: grupo "Salud Infantil"
**Aplica a**: personas con edad < 5 años (y VPH para niñas 10-13 años, dT para 7-49 años)

#### 3.1 Resource: VacunaNinoResource

**Tabla**: muestra el carnet de cada niño/niña
- Columnas: Nombre | Edad | Comunidad | Esquema PAI | Última vacuna | Estado

**Estado del esquema PAI** (semáforo calculado):
- COMPLETO (verde): tiene todas las vacunas correspondientes a su edad
- INCOMPLETO (amarillo): falta alguna vacuna que ya debería tener
- CON ATRASO (rojo): tiene vacuna pendiente con más de 30 días de retraso según fecha de nacimiento

**Esquema PAI Bolivia completo**:
| Vacuna | Edad de aplicación |
|---|---|
| BCG | Al nacer |
| Hepatitis B neonatal | Al nacer |
| Pentavalente 1ra | 2 meses |
| Antirotavírica 1ra | 2 meses |
| Antineumocócica 1ra | 2 meses |
| IPV 1ra | 2 meses |
| Pentavalente 2da | 4 meses |
| bOPV 2da | 4 meses |
| Antirotavírica 2da | 4 meses |
| Antineumocócica 2da | 4 meses |
| Pentavalente 3ra | 6 meses |
| IPV 3ra | 6 meses |
| Influenza 1ra dosis | 6 meses |
| Influenza 2da dosis | 7 meses |
| Antineumocócica 3ra | 12 meses |
| SRP 1ra | 12 meses |
| Antiamarílica | 12 meses |
| Influenza única | 12 a 23 meses |
| Pentavalente 4to refuerzo | 18 meses |
| bOPV 4ta | 18 meses |
| SRP 2da | 18 meses |
| Pentavalente 5to refuerzo | 4 años |
| bOPV 5ta | 4 años |
| VPH 1ra (niñas) | 10 años |
| VPH 2da (niñas) | 11 años |
| dT (ambos sexos) | 7, 8, 9, 10, 11 años |

**Formulario de aplicación de vacuna**:
- persona_id (Select con búsqueda por nombre — filtrado a menores de 15 años)
- tipo_vacuna (Select — muestra solo las vacunas pendientes según edad del niño)
- fecha_aplicacion (DatePicker)
- dentro_fuera (Radio)
- lote (TextInput, opcional)

#### 3.2 Resource: CrecimientoInfantilResource

**Tabla**: lista de niños con su último control de crecimiento
- Columnas: Nombre | Edad | Comunidad | Último control | Peso | Talla | Clasificación

**Clasificación nutricional** (calculada automáticamente al guardar según tablas OMS):
- Normal
- Desnutrición aguda (bajo peso para la talla)
- Desnutrición crónica (baja talla para la edad)
- Desnutrición global (bajo peso para la edad)

La lógica de clasificación usa los valores Z de las tablas OMS 2006. Se recomienda incluir las tablas como arrays en un Helper o Service.

**Formulario**:
- persona_id (Select con búsqueda — filtrado a menores de 10 años)
- fecha (DatePicker)
- peso_kg (TextInput decimal)
- talla_cm (TextInput decimal)
- perimetro_cefalico_cm (TextInput decimal, opcional)
- tipo_control (Radio: nuevo / repetido)
- dentro_fuera (Radio)

Al guardar: calcular y guardar clasificacion automáticamente.

**Vista individual de niño**: gráfica de evolución peso/talla usando Livewire + Chart.js

#### 3.3 Resource: MicronutrienteNinoResource

**Tabla**: historial de micronutrientes por niño
- Columnas: Nombre del niño | Tipo | Fecha | Registrado por

**Formulario**:
- persona_id (Select con búsqueda)
- tipo (Select — ver enum en migración 5.10)
- fecha (DatePicker)

---

### MÓDULO 4 — SALUD MATERNA

**Navegación**: grupo "Salud Materna"

#### 4.1 Resource: EmbarazoResource

**Tabla de embarazadas activas**:
- Columnas: Nombre | Edad | Comunidad | Semanas | FPP | Controles | Estado

**Alertas en la tabla**:
- Badge rojo si tiene estado "activa" y no tiene ningún control registrado en los últimos 30 días
- Badge amarillo si tiene estado "migrada_temporal"

**Estado del embarazo** (clave para justificación de ceros):
- activa: debe recibir controles aquí
- migrada_temporal: salió del área, puede volver
- atendida_otro_centro: referida o fue por cuenta propia
- culminada: parto registrado (se cierra automáticamente)
- perdida: aborto o pérdida fetal

**Formulario**:
- persona_id (Select con búsqueda — filtrado a mujeres activas del padrón)
- fecha_inicio (DatePicker)
- fecha_probable_parto (DatePicker)
- semanas_gestacion_ingreso (TextInput numérico)
- estado (Select)
- observaciones (Textarea)

#### 4.2 Resource: ControlPrenatalResource

**Formulario**:
- embarazo_id (Select con búsqueda por nombre de la madre — filtrado a embarazos activos)
- numero_control (Select: 1 / 2 / 3 / 4)
- fecha (DatePicker)
- semanas_gestacion (TextInput)
- dentro_fuera (Radio)
- grupo_etareo (calculado automáticamente desde la persona)

Al registrar el 4to control: marcar en el embarazo que tiene CPN completo.

#### 4.3 Resource: PartoResource

**Formulario**:
- embarazo_id (Select con búsqueda — filtrado a embarazos activos y migrados)
- fecha_parto (DatePicker)
- tipo (Radio: Vaginal / Cesárea)
- lugar (Radio: En el servicio / Domicilio)
- atendido_por (Select)
- resultado (Radio: Nacido vivo / Nacido muerto)
- peso_rn_kg (TextInput decimal, solo si nacido vivo)

Al guardar: el embarazo pasa automáticamente a estado "culminada" y se crea el registro de puerperio vinculado.

#### 4.4 Resource: PuerperioResource (solo vista y edición de fechas de control)

**Tabla**:
- Columnas: Madre | Fecha parto | Control 48h | Control 7d | Control 28d | Control 42d
- Semáforo por columna: verde=realizado, rojo=vencido sin realizarse, gris=pendiente

#### 4.5 Resource: AnticoncepcionResource

**Formulario**:
- persona_id (Select con búsqueda — mujeres en edad fértil 15-49 del padrón)
- metodo (Select)
- tipo_usuaria (Radio: nueva / continua)
- fecha (DatePicker)

---

### MÓDULO 5 — PRESTACIONES MENSUALES

**Navegación**: grupo "Prestaciones Mensuales"

**REGLA CLAVE**: Las prestaciones son TOTALES mensuales que ingresa la enfermera al final de cada mes. No son registros individuales. Ejemplo: "BCG en menores de 1 año: 2 niños, 1 niña".

#### 5.1 Página: Selector de mes (PrestacionesIndex)

Página Filament personalizada que muestra una grilla del año con los 12 meses.

Estado visual de cada mes:
- Gris: sin datos ingresados
- Amarillo: datos parciales (alguna sección guardada)
- Verde con candado: mes cerrado

Al hacer clic en un mes → va al formulario de ese mes.

#### 5.2 Formulario de prestaciones mensual

Página Filament de pestañas (Tabs), una pestaña por sección:

**Pestaña 1: Consulta Externa**
Tabla editable (grid): filas = grupos etáreos, columnas = Primera M/F | Nueva M/F | Repetida M/F
Grupos: <6m | 6m-<1a | 1-4a | 5-9a | 10-14a | 15-19a | 20-39a | 40-49a | 50-59a | ≥60a

**Pestaña 2: Micronutrientes y Lactancia**
Lista de ítems con campo cantidad:
- Hierro embarazadas (dosis completa)
- Hierro puérperas (dosis completa)
- Hierro <6m (desde 4 meses)
- Hierro <1 año
- Hierro 1 año
- Hierro 2 a <5 años
- Vitamina A puérpera (única)
- Vitamina A <1 año (única)
- Vitamina A 1 año (1ra dosis)
- Vitamina A 1 año (2da dosis)
- Vitamina A 2 a <5 (1ra dosis)
- Vitamina A 2 a <5 (2da dosis)
- Zinc <1 año (talla baja)
- Zinc 1 año (talla baja)
- Nutribebé <1 año (alimento complementario)
- Nutribebé 1 año (alimento complementario)
- Nutri Mamá embarazadas
- Nutri Mamá en lactancia
- Carmelo (adultos ≥60 años)
- Chispitas Nutritivas 6-23 meses

**Pestaña 3: Vacunas Menores de 5 años**
Tabla: filas = tipo de vacuna, columnas = grupos etáreos × dentro/fuera × M/F
(estructura igual al formulario SNIS 301a)

**Pestaña 4: Consultas Prenatales**
Tabla: filas = tipo de control, columnas = grupos etáreos × dentro/fuera

**Pestaña 5: Partos y Puerperio**
- Partos: tipo × lugar × atendido_por × grupo etáreo
- Puerperio: 48h, 7d, 28d, 42d
- Recién nacidos: lista de indicadores

**Pestaña 6: Control de Crecimiento Infantil**
Tabla: grupos etáreos × dentro/fuera × nuevos M/F | repetidos M/F

**Pestaña 7: Anticoncepción**
Tabla: métodos × tipo usuaria × grupos etáreos

**Pestaña 8: Actividades con la Comunidad**
Lista de ítems con campo cantidad

**Pestaña 9: Observaciones (Idea 5)**
Textarea grande con label: "Observaciones del mes — Estas notas aparecerán en el informe CAI".
Máximo 1000 caracteres. Botón "Guardar observaciones".

#### 5.3 Sistema de cero justificado (Idea 1)

Implementado como un **Action de Filament** que se dispara automáticamente al guardar una pestaña cuando el total del indicador clave es 0.

**Indicadores clave que activan el modal**:
- Pestaña Prenatales: total de controles = 0
- Pestaña Partos: total de partos = 0
- Pestaña Puerperio: total de controles = 0
- Pestaña Vacunas: total vacunas menores de 5 = 0
- Pestaña Crecimiento: total de controles = 0
- Pestaña Micronutrientes: total de micronutrientes en menores de 5 = 0

**Modal de justificación**:
```
Título: "Registrando cero en [nombre del indicador]"
Subtítulo: "¿Cuál es el motivo de no tener actividad este mes?"

Opciones (radio obligatorio):
○ No hay población activa registrada en el padrón para este indicador
○ La población registrada migró temporalmente
○ Fue atendida en otro centro de salud
○ No se presentó a consulta (razón desconocida)
○ Otro motivo:  [TextInput condicional]

Botones: [Confirmar y guardar] — no tiene opción de cancelar
```

Si el usuario intenta cerrar el modal sin seleccionar, el modal vuelve a aparecer.

El registro se guarda en tabla `justificaciones_cero`. En el informe CAI el cero aparece con el texto "Justificado: [motivo]".

#### 5.4 Cierre de mes

Botón "Cerrar mes" disponible para **admin** en la página del formulario mensual.

Al cerrar:
1. Se guarda en `meses_cerrados`
2. La página muestra el mes en modo solo lectura para el registrador
3. El admin puede "Reabrir mes" (registro de quién y cuándo)

---

### MÓDULO 6 — ESTADÍSTICAS Y REPORTES

**Navegación**: grupo "Estadísticas"

#### 6.1 Dashboard (página principal de Filament)

**Widgets** (componentes Livewire de Filament):

Widget 1 — Estadísticas de población:
```
[Población padrón activa]  [Migrantes]  [Defunciones año]  [Meta INE]
      287                      42            3                641
```

Widget 2 — Alertas activas:
- Lista de niños con esquema de vacunación incompleto (con link a cada uno)
- Embarazadas activas sin control en últimos 30 días
- Puérperas con controles vencidos
- Meses del año sin cerrar

Widget 3 — Contexto de migración (Idea 6):
```
CONTEXTO DE MIGRACIÓN — [Mes actual] [Año]
Mujeres 15-49 activas en padrón:     [N]
Mujeres 15-49 migradas registradas:  [N] ([%])
Total migrantes registrados:         [N] ([%] del padrón)
```

Widget 4 — Cobertura rápida del mes:
Barras horizontales mini con porcentaje de cada programa vs. meta.

#### 6.2 Página: Pirámide Poblacional

Componente Livewire con dos pirámides lado a lado usando Chart.js (tipo "bar" horizontal con datasets).

**Pirámide izquierda**: Población INE por grupo etáreo (datos de `metas_ine`)
**Pirámide derecha**: Población real del padrón (solo activos + residentes + temporales)

Grupos etáreos de la pirámide:
- <1 año | 1-4 | 5-9 | 10-14 | 15-19 | 20-39 | 40-49 | 50-59 | 60+
- Barras azules = Masculino, rosadas = Femenino

Tabla de valores debajo de la pirámide con columnas: Grupo | INE M | INE F | Real M | Real F | Diferencia M | Diferencia F

Botones de exportación:
- "Exportar Excel": descarga tabla con PHPSpreadsheet
- "Exportar imagen": captura del canvas Chart.js con JavaScript

#### 6.3 Página: Comunidades y Población

Tabla Filament con los datos por comunidad (usando `PersonaResource` con agrupación).

Columnas: Comunidad | Km | Total | Hombres | Mujeres | <5 años | 5-19 | 20-59 | ≥60 | Migrantes
Fila final: TOTAL REAL | vs. META INE | Diferencia | %

Botón "Exportar Excel" → clase `ComunidadesExport`.

#### 6.4 Página: INE vs. Población Real (Idea 6 completa)

**Bloque de contexto de migración** (calculado desde el padrón):
```
CONTEXTO DE MIGRACIÓN — Período [selector]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Mujeres 15-49 años activas (MEF):          [N]
Mujeres 15-49 años migradas registradas:   [N] ([%] del total MEF)
Hombres 15-49 años migrados:               [N]
Total personas migradas registradas:       [N] ([%] del padrón total)

Este indicador explica la baja cobertura en:
• Control prenatal  • Partos  • Planificación familiar  • Vacunación
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

**Tabla de cobertura dual**:
| Programa | Meta INE | Pob. Real Activa | Atendidos | Cob. INE % | Cob. Real % |
|---|---|---|---|---|---|
| Control Prenatal 1er control | 15 | 8 | 3 | 20% | 37.5% |
| Partos atendidos calificados | 12 | 6 | 2 | 16.7% | 33.3% |
| BCG | 12 | 7 | 6 | 50% | 85.7% |
| ... | | | | | |

Selector de período: Mes específico | CAI 1 (ene-abr) | CAI 2 (ene-ago) | Gestión (ene-dic)

#### 6.5 Página: Cobertura por Programa

Gráfico de barras agrupadas (Chart.js) con tres series por programa:
- Meta INE (naranja)
- Población real activa (azul)
- Atendidos en el período (verde)

Selector de período: mensual / CAI 1 / CAI 2 / Gestión

Programas mostrados:
- Vacunas (BCG, Pentavalente, Antineumocócica, Antirotavírica, SRP, Fiebre Amarilla)
- Control prenatal (1er control, 4to control)
- Partos por personal calificado
- Control de crecimiento infantil
- Micronutrientes prioritarios
- Adulto mayor con carmelo

---

### MÓDULO 7 — INFORME CAI

**Navegación**: grupo "Informes"

#### 7.1 Página: Generador de Informe CAI

Formulario de selección:
- Centro de salud (superadmin puede elegir cualquiera, admin ve solo el suyo)
- Año (Select)
- Período (Radio: CAI 1 ene-abr / CAI 2 ene-ago / Cierre de gestión ene-dic)

Botones: "Generar Excel" | "Generar PDF"

#### 7.2 Estructura del informe generado

**Encabezado**:
- Logo SEDES Cochabamba
- Nombre: INFORME CAI [número] — [período]
- Establecimiento: [nombre] — Código SNIS: [código]
- Red de Salud: [red] | Municipio: [municipio]
- Responsable: [nombre del admin]
- Fecha de generación: [fecha]

**Sección 1 — Contexto de Migración** (Idea 6):
- Tabla de migración calculada desde el padrón para el período
- Texto explicativo sobre el impacto en la cobertura

**Sección 2 — Censo Poblacional**:
- Pirámide INE vs. real (imagen o tabla)
- Tabla de población por comunidades

**Sección 3 — Prestaciones Acumuladas del Período**:
- Consulta externa (tabla por grupo etáreo)
- Micronutrientes (tabla)
- Vacunas (tabla)
- Control prenatal y partos (tabla)
- Control de crecimiento (tabla)
- Anticoncepción (tabla)

**Sección 4 — Cobertura de Programas**:
- Tabla con Meta INE / Pob. real / Atendidos / % Cobertura INE / % Cobertura Real
- Para cada programa del período

**Sección 5 — Ceros Justificados del Período** (Idea 1):
Tabla: Mes | Indicador | Motivo | Detalle

**Sección 6 — Observaciones Narrativas** (Idea 5):
Un párrafo por cada mes del período que tenga observaciones.
Formato: **Mes de [Nombre]:** [texto de la enfermera]

**Sección 7 — Firma**:
Nombre y firma del responsable del establecimiento. Lugar y fecha.

#### 7.3 Exportación Excel
Usar `Maatwebsite\Excel` con múltiples hojas (una por sección).
Clase: `InformeCAIExport` con `WithMultipleSheets`.

#### 7.4 Exportación PDF
Usar `Barryvdh\DomPDF` con vista Blade en `resources/views/pdf/informe-cai.blade.php`.
Formato A4, orientación vertical, márgenes 1.5cm.

---

## 9. SEEDERS — DATOS INICIALES

```php
// database/seeders/DatabaseSeeder.php
public function run(): void
{
    $this->call([
        RolesSeeder::class,
        MunicipioSeeder::class,
        CentroSaludSeeder::class,
        ComunidadSeeder::class,
        MetaIneSeeder::class,
        UserSeeder::class,
    ]);
}
```

### MunicipioSeeder
```php
Municipio::create(['nombre' => 'Capinota', 'departamento' => 'Cochabamba']);
```

### CentroSaludSeeder
```php
CentroSalud::create([
    'municipio_id' => 1,
    'nombre' => 'C.S.A. HORNOMA',
    'codigo_snis' => '300183',
    'subsector' => 'Público',
    'red_salud' => 'Capinota',
    'poblacion_ine' => 641,
]);
```

### ComunidadSeeder
```php
$comunidades = [
    ['nombre' => 'Hornoma',      'distancia_km' => 0],
    ['nombre' => 'Huaychoma',    'distancia_km' => 5],
    ['nombre' => 'Villcabamba',  'distancia_km' => 6],
    ['nombre' => 'Cocoma',       'distancia_km' => 24],
    ['nombre' => 'Tocohalla',    'distancia_km' => 48],
    ['nombre' => 'Challavilque', 'distancia_km' => 48],
    ['nombre' => 'Calacaja',     'distancia_km' => 72],
    ['nombre' => 'Siquimirani',  'distancia_km' => null],
];
foreach ($comunidades as $c) {
    Comunidad::create(array_merge($c, ['centro_salud_id' => 1]));
}
```

### MetaIneSeeder (metas INE 2026 para C.S.A. HORNOMA)
```php
$metas = [
    ['grupo_etareo' => 'menor_1',              'sexo' => 'ambos', 'cantidad' => 12],
    ['grupo_etareo' => '1_anio',               'sexo' => 'ambos', 'cantidad' => 12],
    ['grupo_etareo' => '2_anios',              'sexo' => 'ambos', 'cantidad' => 13],
    ['grupo_etareo' => '3_anios',              'sexo' => 'ambos', 'cantidad' => 13],
    ['grupo_etareo' => '4_anios',              'sexo' => 'ambos', 'cantidad' => 13],
    ['grupo_etareo' => '1_4',                  'sexo' => 'ambos', 'cantidad' => 50],
    ['grupo_etareo' => 'menor_5',              'sexo' => 'ambos', 'cantidad' => 62],
    ['grupo_etareo' => 'mayor_5',              'sexo' => 'ambos', 'cantidad' => 578],
    ['grupo_etareo' => '5_9',                  'sexo' => 'ambos', 'cantidad' => 67],
    ['grupo_etareo' => '10_14',                'sexo' => 'ambos', 'cantidad' => 66],
    ['grupo_etareo' => '15_19',                'sexo' => 'ambos', 'cantidad' => 66],
    ['grupo_etareo' => '20_39',                'sexo' => 'ambos', 'cantidad' => 179],
    ['grupo_etareo' => '40_49',                'sexo' => 'ambos', 'cantidad' => 63],
    ['grupo_etareo' => '50_59',                'sexo' => 'ambos', 'cantidad' => 49],
    ['grupo_etareo' => 'mayor_60',             'sexo' => 'ambos', 'cantidad' => 88],
    ['grupo_etareo' => 'embarazos_esperados',  'sexo' => 'F',     'cantidad' => 15],
    ['grupo_etareo' => 'partos_esperados',     'sexo' => 'F',     'cantidad' => 12],
    ['grupo_etareo' => 'nacimientos_esperados','sexo' => 'ambos', 'cantidad' => 12],
    ['grupo_etareo' => 'adolescentes_10_19',   'sexo' => 'ambos', 'cantidad' => 132],
    ['grupo_etareo' => 'mujeres_menor_20',     'sexo' => 'F',     'cantidad' => 128],
    ['grupo_etareo' => '7_49',                 'sexo' => 'M',     'cantidad' => 209],
    ['grupo_etareo' => '7_49',                 'sexo' => 'F',     'cantidad' => 206],
    ['grupo_etareo' => 'mef_15_40',            'sexo' => 'F',     'cantidad' => 153],
    ['grupo_etareo' => 'dt_7_49',              'sexo' => 'M',     'cantidad' => 10],
    ['grupo_etareo' => 'dt_7_49',              'sexo' => 'F',     'cantidad' => 10],
];
foreach ($metas as $m) {
    MetaIne::create(array_merge($m, ['centro_salud_id' => 1, 'anio' => 2026]));
}
```

### UserSeeder
```php
// Crear roles Spatie
Role::firstOrCreate(['name' => 'superadmin']);
Role::firstOrCreate(['name' => 'admin']);
Role::firstOrCreate(['name' => 'registrador']);

// Usuario superadmin inicial
$superadmin = User::create([
    'name' => 'Administrador',
    'apellidos' => 'Sistema',
    'email' => 'admin@sicsal.bo',
    'usuario' => 'superadmin',
    'password' => Hash::make('Sicsal2026!'),
    'centro_salud_id' => null,
    'activo' => true,
]);
$superadmin->assignRole('superadmin');

// Admin del C.S.A. HORNOMA (médico)
$admin = User::create([
    'name' => 'Eusebio',
    'apellidos' => 'Panozo Franco',
    'email' => 'medico@hornoma.bo',
    'usuario' => 'medico.hornoma',
    'password' => Hash::make('Hornoma2026!'),
    'centro_salud_id' => 1,
    'activo' => true,
]);
$admin->assignRole('admin');
```

---

## 10. REGLAS DE NEGOCIO

1. Un **registrador** solo ve y edita datos de su centro de salud (filtro en todos los queries)
2. Un **admin** solo ve y edita datos de su centro de salud (mismo filtro)
3. El **superadmin** ve todos los centros pero no puede editar datos clínicos directamente
4. Un mes cerrado no puede ser editado por el registrador; solo el admin puede reabrirlo
5. No se borran registros físicamente: todo usa borrado lógico (`activo = false`)
6. Las personas migradas permanecen en el padrón marcadas con `estado = 'migrado'`; se excluyen del denominador de cobertura real pero se cuentan en el indicador de migración
7. Las defunciones marcan a la persona como `activo = false`
8. El cero en indicadores clave dispara el modal de justificación obligatorio antes de poder guardar
9. Las metas INE se cargan una vez por año y pueden editarse hasta que el admin las bloquee
10. Los acumulados CAI se calculan dinámicamente (suma de meses del período), no se guardan como totales
11. **Cobertura INE** = (atendidos ÷ meta INE del período) × 100
12. **Cobertura real** = (atendidos ÷ personas activas en el padrón para ese grupo etáreo) × 100
13. Meta del período CAI 1 (4 meses) = meta anual × (4/12)
14. Meta del período CAI 2 (8 meses) = meta anual × (8/12)
15. Los registros individuales de vacunas (carnet) y los conteos mensuales de prestaciones son tablas independientes
16. El campo `grupo_etareo` en tabla `personas` se recalcula en cada actualización del registro usando el accessor de Eloquent y se guarda para rendimiento en consultas

---

## 11. SEGURIDAD

- Contraseñas con `Hash::make()` de Laravel (bcrypt por defecto)
- Autenticación manejada por Filament (basada en Laravel Auth)
- Autorización por roles: Spatie Laravel Permission
- Policies de Laravel para controlar acceso a acciones sobre modelos
- Todos los queries usan Eloquent con bindings (sin SQL crudo sin sanitizar)
- Protección CSRF automática de Laravel en todos los formularios
- Prevención XSS: Blade escapa variables por defecto con `{{ }}`
- Variables sensibles solo en `.env` (nunca en código)
- `.env` en `.gitignore` (nunca subir al repositorio)

---

## 12. FASES DE DESARROLLO — ORDEN Y COMANDOS

### FASE 1 — Base del sistema

**Entregable**: login funcional, configuración y padrón poblacional completo.

```bash
# Crear todas las migraciones
php artisan make:migration create_municipios_table
php artisan make:migration create_centros_salud_table
php artisan make:migration create_comunidades_table
php artisan make:migration add_fields_to_users_table
php artisan make:migration create_metas_ine_table
php artisan make:migration create_personas_table
php artisan make:migration create_defunciones_table
# (continuar con el resto de tablas)

# Ejecutar migraciones y seeders
php artisan migrate --seed

# Crear modelos
php artisan make:model Municipio
php artisan make:model CentroSalud
php artisan make:model Comunidad
php artisan make:model MetaIne
php artisan make:model Persona
php artisan make:model Defuncion

# Crear Resources de Filament
php artisan make:filament-resource CentroSalud --generate
php artisan make:filament-resource Comunidad --generate
php artisan make:filament-resource MetaIne --generate
php artisan make:filament-resource Usuario --generate
php artisan make:filament-resource Persona --generate
php artisan make:filament-resource Defuncion --generate
```

Tareas de esta fase:
- [ ] Instalar y configurar todo el stack (sección 3)
- [ ] Crear todas las migraciones y ejecutarlas
- [ ] Crear todos los modelos con relaciones
- [ ] Configurar el panel de Filament con grupos de navegación y roles
- [ ] Resource CentroSalud (solo superadmin)
- [ ] Resource Comunidad
- [ ] Resource MetaIne con importación Excel
- [ ] Resource Usuario con generación de contraseña temporal
- [ ] Resource Persona con filtros, búsqueda y exportación Excel
- [ ] Resource Defuncion con vinculación al padrón
- [ ] Filtro global por centro_salud_id en todos los Resources

### FASE 2 — Prestaciones mensuales

**Entregable**: el sistema acepta los totales mensuales, tiene el sistema de cero justificado y calcula acumulados.

```bash
php artisan make:filament-page Prestaciones
php artisan make:livewire FormularioPrestaciones
```

Tareas:
- [ ] Crear todas las migraciones de tablas de prestaciones
- [ ] Página selector de mes con estado visual (gris/amarillo/verde)
- [ ] Formulario con pestañas por sección
- [ ] Guardar cada sección de forma independiente
- [ ] Implementar modal de cero justificado (Idea 1)
- [ ] Campo de observaciones narrativas (Idea 5)
- [ ] Lógica de cierre y reapertura de mes
- [ ] Función de cálculo de acumulados por período CAI (helper reutilizable)



### FASE 3 — Estadísticas esenciales

**Entregable**: dashboard funcional con datos reales, pirámide, tabla de comunidades, cobertura.

```bash
php artisan make:filament-widget EstadisticasPoblacion
php artisan make:filament-widget AlertasWidget
php artisan make:filament-widget MigracionContexto
php artisan make:filament-page Piramide
php artisan make:filament-page ComunidadesPoblacion
php artisan make:filament-page IneVsReal
php artisan make:filament-page CoberturaProgramas
```

Tareas:
- [ ] Widget de estadísticas de población
- [ ] Widget de alertas activas
- [ ] Widget de contexto de migración (Idea 6)
- [ ] Pirámide poblacional doble INE vs. real con Chart.js
- [ ] Tabla por comunidades con exportación Excel
- [ ] Página INE vs. real con bloque de migración (Idea 6 completa)
- [ ] Gráfico de cobertura por programa con selector de período

### FASE 4 — Módulos clínicos individuales

**Entregable**: carnet de vacunación, crecimiento, módulo materno completos.

```bash
php artisan make:filament-resource VacunaNino --generate
php artisan make:filament-resource CrecimientoInfantil --generate
php artisan make:filament-resource MicronutrienteNino --generate
php artisan make:filament-resource Embarazo --generate
php artisan make:filament-resource ControlPrenatal --generate
php artisan make:filament-resource Parto --generate
php artisan make:filament-resource Anticoncepcion --generate
```

Tareas:
- [ ] Carnet de vacunación con semáforo PAI
- [ ] Helper de evaluación de esquema PAI por edad
- [ ] Control de crecimiento con clasificación nutricional OMS automática
- [ ] Gráfica de evolución peso/talla por niño (Livewire + Chart.js)
- [ ] Micronutrientes por niño individual
- [ ] Registro de embarazadas activas con alertas
- [ ] Controles prenatales vinculados al embarazo
- [ ] Registro de partos con cierre automático del embarazo
- [ ] Seguimiento de puerperio con semáforo de controles
- [ ] Anticoncepción por mujer

### FASE 5 — Informe CAI y exportación

**Entregable**: informe CAI completo en Excel y PDF.

```bash
php artisan make:filament-page InformeCAI
php artisan make:export InformeCAIExport
```

Tareas:
- [ ] Página generador de informe CAI con selector de período
- [ ] Clase InformeCAIExport con múltiples hojas (Maatwebsite)
- [ ] Vista Blade para PDF del informe (DomPDF)
- [ ] Todas las secciones del informe con datos reales
- [ ] Sección de ceros justificados (Idea 1 en informe)
- [ ] Sección de observaciones narrativas (Idea 5 en informe)
- [ ] Bloque de contexto de migración en informe (Idea 6 en informe)
- [ ] Vista consolidada para superadmin (múltiples centros)

---

## 13. COMANDOS ÚTILES DURANTE EL DESARROLLO

```bash
# Rehacer todas las migraciones con seeders (solo en desarrollo)
php artisan migrate:fresh --seed

# Crear usuario Filament manualmente
php artisan make:filament-user

# Limpiar caché de vistas y configuración
php artisan optimize:clear

# Compilar assets de Tailwind/Vite (necesario al cambiar estilos)
npm run dev          # modo desarrollo con hot reload
npm run build        # compilar para producción

# Ver rutas registradas
php artisan route:list

# Ver lista de modelos y sus tablas
php artisan model:show Persona
```

---

## 14. DESPLIEGUE EN BANAHOSTING (cPanel)

1. Subir todos los archivos del proyecto (sin la carpeta `node_modules`)
2. Subir la carpeta `vendor/` (compilada localmente con `composer install --no-dev`)
3. Subir la carpeta `public/build/` (compilada con `npm run build`)
4. Crear la base de datos en cPanel → MySQL Databases
5. Importar el schema desde phpMyAdmin
6. Configurar `.env` de producción (APP_ENV=production, APP_DEBUG=false, credenciales DB)
7. En cPanel → Domains: apuntar el Document Root a `/public_html/sicsal/public`
8. Configurar PHP 8.2 o 8.3 en MultiPHP Manager
9. Ejecutar via terminal SSH (si está disponible): `php artisan migrate --seed`

---

## 15. NOTAS IMPORTANTES

- Sistema de primer nivel de atención en Bolivia (no tiene hospitalización)
- Los programas siguen las normas del Ministerio de Salud de Bolivia (SNIS 301a/2024)
- **Desnutrición Cero**: prioridad nacional para menores de 5 años
- **Chispitas Nutritivas**: multimicronutrientes del programa nacional (6-23 meses)
- **Carmelo**: complemento nutricional para adultos mayores de 60 años
- **Nutribebé**: alimento complementario para menores de 2 años
- **Nutri Mamá**: alimento complementario para embarazadas y madres en lactancia
- Los informes van a la Jefatura Municipal de Salud (no directamente al SEDES)
- El sistema es paralelo al papel por ahora (no reemplaza el registro físico)
- Diseñado para escalar a múltiples municipios bajo un superadmin central
- El formulario SNIS 301a se usa como referencia de estructura, pero el informe del sistema es propio (para la Jefatura Municipal, no para el SEDES directamente)

---

*SICSAL v2.0 — Stack: Laravel 13 + Filament 5 + Livewire 4 + PHP 8.3*
*Centro de Salud C.S.A. HORNOMA — Cochabamba, Bolivia*
*Documento generado el 2 de abril de 2026*




"Eres un desarrollador Laravel senior. Usa este plan para construir el sistema SICSAL. Empieza por la Fase 1: instalación y migraciones."















