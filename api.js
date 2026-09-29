const directorioSitio = location.protocol.startsWith('http')
  ? new URL('.', location.href).pathname.replace(/\/$/, '')
  : '';
const API_BASE = (typeof window.HOGARYS_API === 'string')
  ? window.HOGARYS_API.replace(/\/$/, '')
  : (location.port === '5501' || location.protocol === 'file:'
    ? 'http://localhost:3000'
    : location.origin + directorioSitio);

async function api(ruta, opciones = {}) {
  const token = localStorage.getItem('hogarysToken');
  const headers = { 'Content-Type': 'application/json' };
  if (token) headers.Authorization = 'Bearer ' + token;

  let res;
  try {
    res = await fetch(API_BASE + '/api' + ruta, {
      method: opciones.method || 'GET',
      headers,
      body: opciones.body !== undefined ? JSON.stringify(opciones.body) : undefined
    });
  } catch (e) {
    throw new Error('No se pudo conectar con la API. Verifica que Apache/PHP o el servidor configurado estén encendidos.');
  }

  let data = null;
  try { data = await res.json(); } catch (e) { /* respuesta sin cuerpo */ }

  if (!res.ok) {
    if (res.status === 401 && token) {
      // Token vencido o inválido: limpiamos la sesión local.
      localStorage.removeItem('hogarysToken');
      localStorage.removeItem('hogarysSesion');
    }
    const err = new Error((data && data.error) || 'Error del servidor.');
    err.status = res.status;
    throw err;
  }
  return data;
}

/* Escapa texto antes de meterlo en innerHTML: ahora los datos los escriben otros usuarios y viven en la BD. */
function esc(valor) {
  return String(valor == null ? '' : valor)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

/* Las fotos subidas por usuarios viven en el servidor (/uploads/...). */
function fixUrl(u) {
  return (typeof u === 'string' && u.startsWith('/uploads/')) ? API_BASE + u : u;
}
function quitarBaseUrl(u) {
  return (typeof u === 'string' && API_BASE && u.startsWith(API_BASE + '/uploads/')) ? u.slice(API_BASE.length) : u;
}
