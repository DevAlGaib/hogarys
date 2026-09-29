# Inicia MySQL y PHP con las opciones instaladas en esta computadora.
param([switch]$NoAbrirNavegador)
$ErrorActionPreference = 'Stop'
$project = $PSScriptRoot
$mysqlRoot = Join-Path $env:LOCALAPPDATA 'Hogarys\mysql-8.4.9\mysql-8.4.9-winx64'
$mysqlData = Join-Path $env:LOCALAPPDATA 'Hogarys\mysql-data'
$phpRoot = Join-Path $env:LOCALAPPDATA 'Hogarys\php-8.4.26'
$mysqlExe = Join-Path $mysqlRoot 'bin\mysqld.exe'
$phpExe = Join-Path $phpRoot 'php.exe'
$router = Join-Path $project 'router.php'
$url = 'http://127.0.0.1:3000/'

function Test-PuertoLocal([int]$Puerto) {
    $cliente = New-Object System.Net.Sockets.TcpClient
    try {
        $conexion = $cliente.ConnectAsync('127.0.0.1', $Puerto)
        return ($conexion.Wait(500) -and $cliente.Connected)
    } catch { return $false } finally { $cliente.Dispose() }
}

function Esperar-Puerto([int]$Puerto, $Proceso, [string]$Nombre, [string]$Log) {
    $reloj = [System.Diagnostics.Stopwatch]::StartNew()
    while ($reloj.Elapsed.TotalSeconds -lt 30) {
        if ($Proceso.HasExited) { throw "$Nombre se detuvo. Revisa: $Log" }
        if (Test-PuertoLocal $Puerto) { return }
        Start-Sleep -Milliseconds 300
    }
    throw "$Nombre no respondio en el puerto $Puerto en 30 segundos. Revisa: $Log"
}

try {
    foreach ($archivo in @($mysqlExe, $phpExe, $router)) {
        if (!(Test-Path -LiteralPath $archivo -PathType Leaf)) { throw "No existe: $archivo" }
    }
    if (!(Test-Path -LiteralPath $mysqlData -PathType Container)) { throw "No existe la carpeta de datos: $mysqlData" }
    $logs = Join-Path $env:TEMP 'Hogarys-logs'
    New-Item -ItemType Directory -Path $logs -Force | Out-Null
    $ejecucion = [guid]::NewGuid().ToString('N')
    if (Test-PuertoLocal 3306) {
        Write-Host 'El puerto 3306 ya esta activo; se reutilizara la conexion configurada.'
    } else {
        Write-Host 'Iniciando MySQL...'
        $mysqlLog = Join-Path $logs "mysql-$ejecucion.err.log"
        $mysqlArgs = @(
            "--basedir=`"$mysqlRoot`"", "--datadir=`"$mysqlData`"",
            '--port=3306', '--bind-address=127.0.0.1', '--console'
        )
        $mysqlProceso = Start-Process -FilePath $mysqlExe -ArgumentList $mysqlArgs -WindowStyle Hidden -PassThru `
            -RedirectStandardOutput (Join-Path $logs "mysql-$ejecucion.out.log") -RedirectStandardError $mysqlLog
        Esperar-Puerto 3306 $mysqlProceso 'MySQL' $mysqlLog
    }
    if (Test-PuertoLocal 3000) {
        Write-Host 'El puerto 3000 ya esta activo; verificando la aplicacion...'
    } else {
        Write-Host 'Iniciando PHP...'
        $phpLog = Join-Path $logs "php-$ejecucion.err.log"
        $phpArgs = @(
            '-d', "`"extension_dir=$phpRoot\ext`"",
            '-d', 'extension=mbstring', '-d', 'extension=pdo_mysql',
            '-d', 'post_max_size=110M', '-d', 'memory_limit=256M',
            '-S', '127.0.0.1:3000', '-t', "`"$project`"", "`"$router`""
        )
        $phpProceso = Start-Process -FilePath $phpExe -ArgumentList $phpArgs -WorkingDirectory $project -WindowStyle Hidden -PassThru `
            -RedirectStandardOutput (Join-Path $logs "php-$ejecucion.out.log") -RedirectStandardError $phpLog
        Esperar-Puerto 3000 $phpProceso 'PHP' $phpLog
    }
    # La consulta comprueba tambien la conexion de la API con MySQL.
    $respuesta = Invoke-WebRequest -Uri ($url + 'api/propiedades') -UseBasicParsing -TimeoutSec 15
    if ($respuesta.StatusCode -ne 200 -or !$respuesta.Content.TrimStart().StartsWith('[')) {
        throw 'El puerto 3000 no devolvio el catalogo esperado de Hogarys.'
    }
    $null = ConvertFrom-Json -InputObject $respuesta.Content
    Write-Host "Hogarys listo en: $url"
    Write-Host "Registros de arranque: $logs"
    Write-Host 'Los servidores quedan ejecutandose en segundo plano.'
    if (!$NoAbrirNavegador) { Start-Process $url }
} catch {
    Write-Error "No se pudo completar el arranque: $($_.Exception.Message). Consulta los registros en $env:TEMP\Hogarys-logs."
    exit 1
}
