param([switch]$NoAbrirNavegador)
$ErrorActionPreference = 'Stop'
$project = $PSScriptRoot
$installRoot = Join-Path $env:LOCALAPPDATA 'Hogarys'
$mysqlVersion = '8.4.9'
$phpVersion = '8.4.26'
$mysqlRoot = Join-Path $installRoot "mysql-$mysqlVersion\mysql-$mysqlVersion-winx64"
$mysqlData = Join-Path $installRoot 'mysql-data'
$phpRoot = Join-Path $installRoot "php-$phpVersion"
$mysqlExe = Join-Path $mysqlRoot 'bin\mysqld.exe'
$mysqlClient = Join-Path $mysqlRoot 'bin\mysql.exe'
$phpExe = Join-Path $phpRoot 'php.exe'
$logs = Join-Path $env:TEMP 'Hogarys-logs'

function Descargar-Y-Extraer([string]$Url, [string]$Archive, [string]$Destination) {
    if (!(Test-Path -LiteralPath $Archive -PathType Leaf)) {
        Write-Host "Descargando $([IO.Path]::GetFileName($Archive))..."
        & curl.exe -fL --retry 3 --output $Archive $Url
        if ($LASTEXITCODE -ne 0) { throw "No se pudo descargar $Url (curl: $LASTEXITCODE)." }
    }
    if (!(Test-Path -LiteralPath $Destination -PathType Container)) {
        Write-Host "Extrayendo $([IO.Path]::GetFileName($Archive))..."
        Expand-Archive -LiteralPath $Archive -DestinationPath $Destination -Force
    }
}

function Invoke-MySql([string]$Query) {
    $output = & $mysqlClient --protocol=TCP --host=127.0.0.1 --port=3306 --user=root --batch --skip-column-names "--execute=$Query" 2>&1
    if ($LASTEXITCODE -ne 0) { throw "MySQL rechazó la consulta: $($output -join ' ')" }
    return ($output -join "`n").Trim()
}

function Invoke-MySqlInput([string]$Sql) {
    $startInfo = New-Object Diagnostics.ProcessStartInfo
    $startInfo.FileName = $mysqlClient
    $startInfo.Arguments = '--protocol=TCP --host=127.0.0.1 --port=3306 --user=root --default-character-set=utf8mb4 --batch --skip-column-names'
    $startInfo.WorkingDirectory = $project
    $startInfo.UseShellExecute = $false
    $startInfo.CreateNoWindow = $true
    $startInfo.RedirectStandardInput = $true
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
    $process = [Diagnostics.Process]::Start($startInfo)
    $bytes = [Text.Encoding]::UTF8.GetBytes($Sql + "`n")
    $process.StandardInput.BaseStream.Write($bytes, 0, $bytes.Length)
    $process.StandardInput.Close()
    $output = $process.StandardOutput.ReadToEnd()
    $errors = $process.StandardError.ReadToEnd()
    $process.WaitForExit()
    if ($process.ExitCode -ne 0) { throw "MySQL rechazó la operación: $($errors.Trim()) $($output.Trim())" }
    return $output.Trim()
}

function Test-PuertoLocal([int]$Port) {
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $connection = $client.ConnectAsync('127.0.0.1', $Port)
        return ($connection.Wait(500) -and $client.Connected)
    } catch { return $false } finally { $client.Dispose() }
}

function Leer-ConfiguracionLocal([string]$Path) {
    $settings = @{}
    foreach ($line in Get-Content -LiteralPath $Path) {
        $line = $line.Trim()
        if (!$line -or $line.StartsWith('#')) { continue }
        $separator = $line.IndexOf('=')
        if ($separator -lt 1) { continue }
        $key = $line.Substring(0, $separator).Trim()
        $value = $line.Substring($separator + 1).Trim()
        if ($value.Length -ge 2 -and (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'")))) {
            $value = $value.Substring(1, $value.Length - 2)
        }
        $settings[$key] = $value
    }
    return $settings
}

$mysqlProcess = $null
$previousMysqlPassword = $env:MYSQL_PWD
try {
    New-Item -ItemType Directory -Path $installRoot, $logs -Force | Out-Null
    $downloads = Join-Path $installRoot 'downloads'
    New-Item -ItemType Directory -Path $downloads -Force | Out-Null

    Descargar-Y-Extraer `
        "https://cdn.mysql.com/Downloads/MySQL-8.4/mysql-$mysqlVersion-winx64.zip" `
        (Join-Path $downloads "mysql-$mysqlVersion-winx64.zip") `
        (Split-Path $mysqlRoot -Parent)
    Descargar-Y-Extraer `
        "https://windows.php.net/downloads/releases/php-$phpVersion-Win32-vs17-x64.zip" `
        (Join-Path $downloads "php-$phpVersion-Win32-vs17-x64.zip") `
        $phpRoot

    foreach ($executable in @($mysqlExe, $mysqlClient, $phpExe)) {
        if (!(Test-Path -LiteralPath $executable -PathType Leaf)) { throw "El paquete no contiene el ejecutable esperado: $executable" }
    }

    $phpModules = & $phpExe -d "extension_dir=$phpRoot\ext" -d extension=mbstring -d extension=pdo_mysql -m 2>&1
    if ($LASTEXITCODE -ne 0 -or $phpModules -notcontains 'mbstring' -or $phpModules -notcontains 'pdo_mysql') {
        throw "PHP no pudo cargar mbstring y pdo_mysql. Comprueba Microsoft Visual C++ Redistributable 2015-2022 x64. Salida: $($phpModules -join ' ')"
    }

    $envPath = Join-Path $project 'servidor\.env'
    if (!(Test-Path -LiteralPath $envPath -PathType Leaf)) {
        $secretBytes = New-Object byte[] 48
        $random = [Security.Cryptography.RandomNumberGenerator]::Create()
        try { $random.GetBytes($secretBytes) } finally { $random.Dispose() }
        $secret = [Convert]::ToBase64String($secretBytes).TrimEnd('=').Replace('+', '-').Replace('/', '_')
        @(
            'DB_HOST=127.0.0.1',
            'DB_PORT=3306',
            'DB_NAME=hogary''s',
            'DB_USER=root',
            'DB_PASSWORD=',
            "JWT_SECRET=$secret"
        ) | Set-Content -LiteralPath $envPath -Encoding ASCII
        Write-Host 'Se creó servidor/.env con una clave de sesión aleatoria.'
    }
    $settings = Leer-ConfiguracionLocal $envPath
    $dbUser = if ($settings.DB_USER) { $settings.DB_USER } else { 'root' }
    $dbPassword = if ($settings.ContainsKey('DB_PASSWORD')) { $settings.DB_PASSWORD } else { '' }
    $dbName = if ($settings.DB_NAME) { $settings.DB_NAME } else { "hogary's" }
    if ($dbName -ne "hogary's") { throw "DB_NAME debe ser hogary's porque los scripts SQL del proyecto usan ese esquema." }
    Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue

    if (!(Test-Path -LiteralPath $mysqlData -PathType Container)) {
        New-Item -ItemType Directory -Path $mysqlData -Force | Out-Null
        Write-Host 'Inicializando el directorio local de MySQL...'
        $initOut = Join-Path $logs 'mysql-initialize.out.log'
        $initErr = Join-Path $logs 'mysql-initialize.err.log'
        $init = Start-Process -FilePath $mysqlExe -ArgumentList @(
            "--basedir=`"$mysqlRoot`"", "--datadir=`"$mysqlData`"", '--initialize-insecure', '--console'
        ) -Wait -PassThru -WindowStyle Hidden -RedirectStandardOutput $initOut -RedirectStandardError $initErr
        if ($init.ExitCode -ne 0) { throw "No se pudo inicializar MySQL. Revisa $initErr" }
    }

    if (!(Test-PuertoLocal 3306)) {
        Write-Host 'Iniciando MySQL en 127.0.0.1:3306...'
        $mysqlOut = Join-Path $logs 'mysql.out.log'
        $mysqlErr = Join-Path $logs 'mysql.err.log'
        $mysqlProcess = Start-Process -FilePath $mysqlExe -ArgumentList @(
            "--basedir=`"$mysqlRoot`"", "--datadir=`"$mysqlData`"", '--port=3306', '--bind-address=127.0.0.1', '--console'
        ) -PassThru -WindowStyle Hidden -RedirectStandardOutput $mysqlOut -RedirectStandardError $mysqlErr
    }

    $ready = $false
    $authenticatedWithBlankPassword = $false
    $timer = [Diagnostics.Stopwatch]::StartNew()
    while ($timer.Elapsed.TotalSeconds -lt 45) {
        if ($mysqlProcess -and $mysqlProcess.HasExited) { throw "MySQL se detuvo. Revisa $mysqlErr" }
        if (Test-PuertoLocal 3306) {
            try {
                $null = Invoke-MySql 'SELECT 1'
                $ready = $true
                $authenticatedWithBlankPassword = $true
            } catch {
                if ($dbPassword) {
                    $env:MYSQL_PWD = $dbPassword
                    try { $null = Invoke-MySql 'SELECT 1'; $ready = $true } catch { Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue }
                }
            }
            if ($ready) { break }
        }
        Start-Sleep -Milliseconds 500
    }
    if (!$ready) { throw "MySQL no aceptó conexiones en 127.0.0.1:3306. Revisa los registros en $logs" }
    if ($authenticatedWithBlankPassword -and $dbUser -eq 'root' -and $dbPassword) {
        $escapedPassword = $dbPassword.Replace('\', '\\').Replace("'", "''")
        $null = Invoke-MySqlInput "ALTER USER 'root'@'localhost' IDENTIFIED BY '$escapedPassword';"
        $env:MYSQL_PWD = $dbPassword
    }

    $stateQuery = "SELECT CASE WHEN (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='hogary''s')=0 THEN 'empty' WHEN (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='hogary''s')=9 AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema='hogary''s' AND table_name='propiedades' AND column_name='amenidades') THEN 'schema' ELSE 'incomplete' END"
    $databaseState = Invoke-MySql $stateQuery
    if ($databaseState -eq 'schema') {
        $propertyCount = [int](Invoke-MySql "SELECT COUNT(*) FROM ``hogary's``.propiedades")
        $databaseState = if ($propertyCount -ge 10) { 'ready' } else { 'incomplete' }
    }
    if ($databaseState -eq 'empty') {
        Write-Host 'Creando esquema y datos iniciales...'
        Push-Location $project
        try {
            foreach ($sqlFile in @('hogarys.sql', 'servidor/db/migracion.sql', 'servidor/db/datos_iniciales.sql')) {
                $result = & $mysqlClient --protocol=TCP --host=127.0.0.1 --port=3306 --user=root "--execute=source $sqlFile" 2>&1
                if ($LASTEXITCODE -ne 0) { throw "Falló $sqlFile`: $($result -join ' ')" }
            }
        } finally { Pop-Location }
    } elseif ($databaseState -eq 'ready') {
        Write-Host 'La base hogary''s ya está lista; se conservaron sus datos.'
    } else {
        throw "La base hogary's está vacía parcialmente o no coincide con este esquema. No se modificaron datos. Revisa la migración manualmente."
    }

    if ($dbUser -ne 'root') {
        $escapedUser = $dbUser.Replace('\', '\\').Replace("'", "''")
        $escapedPassword = $dbPassword.Replace('\', '\\').Replace("'", "''")
        $null = Invoke-MySqlInput "CREATE USER IF NOT EXISTS '$escapedUser'@'localhost' IDENTIFIED BY '$escapedPassword'; ALTER USER '$escapedUser'@'localhost' IDENTIFIED BY '$escapedPassword'; GRANT ALL PRIVILEGES ON ``hogary's``.* TO '$escapedUser'@'localhost';"
    }

    $launcher = Join-Path $project 'arrancar-hogarys.ps1'
    $launcherArgs = @('-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $launcher)
    if ($NoAbrirNavegador) { $launcherArgs += '-NoAbrirNavegador' }
    if ($null -eq $previousMysqlPassword) { Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue } else { $env:MYSQL_PWD = $previousMysqlPassword }
    & powershell.exe @launcherArgs
    if ($LASTEXITCODE -ne 0) { throw 'La configuración terminó, pero el launcher no pudo verificar la API.' }
} catch {
    if ($null -eq $previousMysqlPassword) { Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue } else { $env:MYSQL_PWD = $previousMysqlPassword }
    Write-Error "No se pudo preparar Hogarys: $($_.Exception.Message). No se eliminó la base ni sus archivos. Registros: $logs"
    exit 1
}