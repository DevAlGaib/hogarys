# Hogary's — Guía de instalación y funcionamiento

La aplicación usa una API PHP con PDO conectada a MySQL. Node sigue disponible como backend alternativo. Ambos sirven la web/API en el puerto `3000`: ejecuta solo uno a la vez. MySQL escucha en `3306`.

## Archivos del proyecto

- `api.php`: API PHP. Valida solicitudes, usa consultas preparadas, autentica usuarios, administra fotos y accede a MySQL.
- `router.php`: router del servidor PHP integrado. Sirve páginas/recursos y dirige `/api/...` y `/uploads/...` a la API.
- `servidor/config.php`: carga la configuración privada desde `servidor/.env` sin cambiar ese archivo; define también valores de respaldo.
- `api.js`: cliente común del navegador. Envía y recibe JSON, adjunta el token de sesión y elige el origen de la API.
- `.htaccess` y `servidor/.htaccess`: reglas para Apache; protegen archivos internos y habilitan las rutas amigables.
- `servidor/server.js`: backend Node/Express que se conserva como alternativa.
- `servidor/db/`: scripts de migración y datos iniciales.

## Dónde se guardan los datos

- La configuración local está en `servidor/.env`: host, puerto, nombre de base, usuario, contraseña y clave para firmar sesiones. PHP y Node leen los mismos valores.
- La base activa es `hogary's`, en el directorio de datos MySQL `%LOCALAPPDATA%\Hogarys\mysql-data`. No está dentro de la carpeta del proyecto.
- Las imágenes iniciales están en `video_imagenes/`; las que cargan usuarios se guardan en `servidor/uploads/` y su URL queda en MySQL.
- El navegador guarda el token de sesión en `localStorage`; los usuarios, propiedades, favoritos, citas y reseñas viven en MySQL.
- Los ejecutables instalados para esta computadora están fuera del proyecto: PHP en `%LOCALAPPDATA%\Hogarys\php-8.4.26` y MySQL en `%LOCALAPPDATA%\Hogarys\mysql-8.4.9`.

No borres `mysql-data` si necesitas conservar la base. Para mover también los registros a otro equipo, exporta MySQL aparte; compartir solo los archivos del proyecto no copia esa base.

## Iniciar en esta computadora

Para iniciar todo y abrir la página con un comando, ejecuta desde PowerShell en la carpeta del proyecto:

En una computadora nueva, prepara PHP, MySQL y la base por primera vez con PowerShell:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\preparar-hogarys.ps1
```

El preparador descarga PHP 8.4.26 y MySQL 8.4.9 oficiales en `%LOCALAPPDATA%\Hogarys`, inicializa MySQL solo si aún no hay directorio de datos, crea `servidor/.env` si no existe y carga los SQL solo en una base vacía. Al finalizar inicia la aplicación. Requiere conexión a Internet y Microsoft Visual C++ Redistributable 2015-2022 x64. No borra ni reemplaza una base existente; si detecta un esquema parcial, se detiene para proteger los datos.

En equipos que ya están preparados, o para iniciar de nuevo después de reiniciar Windows, ejecuta:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\arrancar-hogarys.ps1
```

El script conserva las rutas y opciones instaladas, admite espacios en la ruta y comprueba la API con MySQL antes de abrir el navegador. Reutiliza los puertos que ya están activos sin iniciar procesos duplicados. Los servidores quedan en segundo plano y los registros se guardan en `%TEMP%\Hogarys-logs`. Agrega `-NoAbrirNavegador` para omitir el navegador. `ExecutionPolicy Bypass` solo se aplica a esa ejecución; no cambia la política guardada del equipo.

También puedes usar el procedimiento manual siguiente.

Los servidores son procesos portátiles, no servicios de Windows. Al reiniciar la PC, abre dos terminales PowerShell. Los siguientes comandos usan las rutas instaladas en esta computadora.

**Terminal 1: MySQL**

```powershell
$mysqlRoot = Join-Path $env:LOCALAPPDATA 'Hogarys\mysql-8.4.9\mysql-8.4.9-winx64'
$mysqlData = Join-Path $env:LOCALAPPDATA 'Hogarys\mysql-data'
& (Join-Path $mysqlRoot 'bin\mysqld.exe') `
	"--basedir=$mysqlRoot" `
	"--datadir=$mysqlData" `
	--port=3306 `
	--bind-address=127.0.0.1 `
	--console
```

Déjala abierta; MySQL debe mostrar `ready for connections`.

**Terminal 2: PHP y la aplicación**

Desde la carpeta raíz del proyecto, ejecuta:

```powershell
$phpRoot = Join-Path $env:LOCALAPPDATA 'Hogarys\php-8.4.26'
$project = (Get-Location).Path
& (Join-Path $phpRoot 'php.exe') `
	-d "extension_dir=$phpRoot\ext" `
	-d extension=mbstring `
	-d extension=pdo_mysql `
	-d post_max_size=110M `
	-d memory_limit=256M `
	-S 127.0.0.1:3000 `
	-t $project (Join-Path $project 'router.php')
```

Déjala abierta y visita `http://localhost:3000/`. La consola indica los errores de PHP; el navegador muestra la página y sus datos. Para apagar cada proceso, usa `Ctrl+C` en su terminal.

## Primera instalación en otra computadora

1. Instala o prepara MySQL y crea/activa la base con los permisos necesarios.
2. Copia `servidor/.env.example` a `servidor/.env`; configura sus propios valores y una clave `JWT_SECRET` larga y aleatoria. No copies el `.env` de esta computadora.
3. En MySQL Workbench, ejecuta una sola vez y en este orden: `hogarys.sql`, `servidor/db/migracion.sql`, `servidor/db/datos_iniciales.sql`.
4. Instala PHP 8 o posterior y habilita las extensiones `pdo_mysql` y `mbstring`; instala Node solo si se usará el backend alternativo.
5. Inicia MySQL y luego PHP con el router como se explica arriba. En otra PC, ajusta `%LOCALAPPDATA%\Hogarys` a la ubicación donde se instalaron sus ejecutables.

No ejecutes otra vez los scripts SQL sobre una base que ya tenga la migración/datos: algunas instrucciones se ejecutan una sola vez y los datos de demostración pueden duplicarse.

## Cómo compartir

- Comparte el código, los HTML/CSS/JS, `router.php` y los scripts SQL.
- No compartas `servidor/.env`, contraseñas, tokens, `servidor/uploads/`, `node_modules/` ni el directorio local `mysql-data`.
- `servidor/.gitignore` excluye `.env`, `uploads/` y `node_modules/`. Los ejecutables y la base de esta PC están bajo `%LOCALAPPDATA%\Hogarys`, fuera del proyecto.
- Si sí necesitas entregar una copia de los datos, expórtala intencionalmente como respaldo separado y compártela solo con quien deba tener acceso a esa información.
- Las cuentas demo asociadas a propiedades no tienen contraseñas utilizables. El botón "Contactar" sigue siendo una demostración porque el esquema no tiene tabla de mensajes.

## Backend Node alternativo

Desde `servidor/`, ejecuta `npm install` una vez y después `npm start`. Node también lee `servidor/.env` y ocupa el puerto `3000`; detén PHP antes de iniciarlo. Live Server en el puerto `5501` sigue apuntando por defecto a Node en `localhost:3000`.

## Pruebas realizadas

- PHP 8.4 con `pdo_mysql` leyó la configuración local y consultó MySQL.
- La página respondió HTTP 200; `/api/propiedades` devolvió 10 filas y `/api/staff` devolvió 6.
- Registro/login, perfil, alta/edición/borrado de propiedad, favoritos, reseñas y citas pasaron con una cuenta temporal; sus datos se limpiaron al terminar.
- Los hashes bcrypt y tokens JWT de PHP y Node fueron aceptados por ambos backends.
- La ruta protegida devolvió 401 sin sesión, la propiedad inexistente devolvió 404 y el router no expuso `.env` ni `servidor/`.
