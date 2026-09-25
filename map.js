// Funciones del Caché 
function obtenerCacheCoordenadas() {
    return JSON.parse(localStorage.getItem('hogarysCoordenadas')) || {};
}

function guardarEnCache(id, coordenadas) {
    const cache = obtenerCacheCoordenadas();
    cache[id] = coordenadas;
    localStorage.setItem('hogarysCoordenadas', JSON.stringify(cache));
}

// Función parlas coordenadas
async function obtenerCoordenadas(direccion) {
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}`;
    try {
        const respuesta = await fetch(url);
        const datos = await respuesta.json();
        if (datos.length > 0) {
            return { lat: parseFloat(datos[0].lat), lng: parseFloat(datos[0].lon) };
        }
        return null; 
    } catch (error) {
        console.error("Error en la geocodificación:", error);
        return null;
    }
}

// Función principal del mapa
async function iniciarMapaConPropiedades() {
    const latitud = 24.8089;
    const longitud = -107.3940;

    const miMapa = L.map('contenedor-mapa').setView([latitud, longitud], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(miMapa);

    const catalogo = getTodasPropiedades();
    const cache = obtenerCacheCoordenadas(); 
    
const iconoHogarys = L.icon({
    iconUrl: 'casita.png',
    iconSize: [50, 40],      
    iconAnchor: [15, 40],    
    popupAnchor: [0, -42]    
});
    for (const propiedad of catalogo) {
        let coordenadasFinales = null;

        if (propiedad.lat && propiedad.lng) {
            coordenadasFinales = [propiedad.lat, propiedad.lng];
        } 
        else if (cache[propiedad.id]) {
            coordenadasFinales = [cache[propiedad.id].lat, cache[propiedad.id].lng];
        } 
        
        else {
            let coloniaLimpia = propiedad.colonia.replace('Col. ', '').replace('Fracc. ', '');
            let direccionBusqueda = `${coloniaLimpia}, ${propiedad.ciudad}, Sinaloa, México`;
            
            let resultado = await obtenerCoordenadas(direccionBusqueda);
            
            
            if (!resultado) {
                await new Promise(resolve => setTimeout(resolve, 1000));
                resultado = await obtenerCoordenadas(`${propiedad.ciudad}, Sinaloa, México`);
            }

            if (resultado) {
                coordenadasFinales = [resultado.lat, resultado.lng];
                
                guardarEnCache(propiedad.id, resultado);
            }

            
            await new Promise(resolve => setTimeout(resolve, 1000));
        }

       
        if (coordenadasFinales) {
            const precioFormateado = formatPrice(propiedad.precio, propiedad.op);
            
            const latVariada = coordenadasFinales[0] + (Math.random() - 0.5) * 0.005;
            const lngVariada = coordenadasFinales[1] + (Math.random() - 0.5) * 0.005;

            
            const marcador = L.marker([latVariada, lngVariada], { icon: iconoHogarys }).addTo(miMapa);

            const popupContenido = `
                <div style="font-family: 'Inter', sans-serif; text-align: center;">
                    <strong>${propiedad.tipo} en ${propiedad.colonia}</strong><br>
                    <span style="color: var(--terracotta); font-weight: bold;">${precioFormateado}</span>
                </div>
            `;
            marcador.bindPopup(popupContenido);

            
            const sidebar = document.getElementById('sidebar-propiedades');
            const tarjeta = document.createElement('div');
            tarjeta.className = 'sidebar-card';
            
            
            const fotoTarjeta = (propiedad.fotos && propiedad.fotos.length > 0) ? propiedad.fotos[0] : 'casita.png';

tarjeta.innerHTML = `
                <div class="card-header">
                    <img src="${fotoTarjeta}" alt="${propiedad.tipo}">
                    <div class="card-basic-info">
                        <h4>${propiedad.tipo} en ${propiedad.op}</h4>
                        <p>${propiedad.colonia}</p>
                        <strong>${precioFormateado}</strong>
                    </div>
                </div>
                <div class="card-details">
                    <div class="listing-specs" style="margin-bottom: 10px; display:flex; gap:10px; font-size:0.8rem; color:var(--ink-soft);">
                        <span>${propiedad.recamaras} recámaras</span>
                        <span>${propiedad.banos} baños</span>
                        <span>${propiedad.m2} m²</span>
                    </div>
                    <p>${propiedad.desc || 'Sin descripción disponible.'}</p>
                    <a href="detalles.html?id=${propiedad.id}" class="btn btn-solid" style="display: block; width: 100%; text-align: center; margin-top: 10px; padding: 10px; background: var(--terracotta); color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">Ver detalles completos</a>
                </div>
            `;

            
            tarjeta.addEventListener('click', (e) => {
               
                if (e.target.classList.contains('btn-detalles')) return;

                
                document.querySelectorAll('.sidebar-card').forEach(t => t.classList.remove('active'));
                
                
                tarjeta.classList.add('active');

               
                miMapa.flyTo([latVariada, lngVariada], 16, {
                    duration: 1.5
                });

                
                marcador.openPopup();
            });

            
            sidebar.appendChild(tarjeta);
        }
    }
}

iniciarMapaConPropiedades();