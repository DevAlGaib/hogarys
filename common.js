const SITE_HEADER = `
  <div class="nav-wrap">
    <a href="index.html" class="logo">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M3 11L12 4L21 11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M5 10V20H19V10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M10 20V14H14V20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Hogary's
    </a>
    <nav>
      <ul>
        <li><a href="mapa.html">Muy cerca de ti</a></li>
        <li><a href="listado.html?op=comprar">Comprar</a></li>
        <li><a href="listado.html?op=rentar">Rentar</a></li>
        <li><a href="perfil.html">Vender</a></li>
        <li><a id="como-funciona" href="index.html#como-funciona">Cómo funciona</a></li>
        <li><a href="contactos.html">Contacto</a></li>
      </ul>
    </nav>
    <div class="nav-actions" id="navActions">
      <button class="btn btn-ghost" data-open="login">Iniciar sesión</button>
      <button class="btn btn-solid" data-open="register">Crear cuenta</button>
      <button class="menu-toggle" aria-label="Abrir menú"><span></span><span></span><span></span></button>
    </div>
  </div>
`;

const SITE_MODALS = `
  <div class="modal-overlay" id="registerModal">
    <div class="modal">
      <button class="modal-close" data-close>&times;</button>
      <h3>Crea tu cuenta</h3>
      <p class="modal-sub">Regístrate para guardar propiedades y contactar propietarios.</p>
      <form id="registerForm">
        <div class="field"><label for="regName">Nombre completo</label><input id="regName" type="text" placeholder="Tu nombre"></div>
        <div class="field"><label for="regEmail">Correo electrónico</label><input id="regEmail" type="email" placeholder="tucorreo@ejemplo.com"></div>
        <div class="field"><label for="regPass">Contraseña</label><input id="regPass" type="password" placeholder="••••••••"></div>
        <div class="modal-msg" id="registerMsg"></div>
        <button type="submit" class="btn btn-solid">Crear cuenta</button>
      </form>
      <div class="modal-switch">¿Ya tienes cuenta? <button type="button" data-switch="login">Inicia sesión</button></div>
      <div class="modal-terms">
      <p>Al continuar, aceptas los <a href="terminos.html">Términos y Condiciones</a> de Hogary's.</p>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="loginModal">
    <div class="modal">
      <button class="modal-close" data-close>&times;</button>
      <h3>Inicia sesión</h3>
      <p class="modal-sub">Bienvenido de vuelta.</p>
      <form id="loginForm">
        <div class="field"><label for="logEmail">Correo electrónico</label><input id="logEmail" type="email" placeholder="tucorreo@ejemplo.com"></div>
        <div class="field"><label for="logPass">Contraseña</label><input id="logPass" type="password" placeholder="••••••••"></div>
        <div class="modal-msg" id="loginMsg"></div>
        <button type="submit" class="btn btn-solid">Entrar</button>
      </form>
      <div class="modal-switch">¿Aún no tienes cuenta? <button type="button" data-switch="register">Regístrate</button></div>
    </div>
  </div>
`;

const SITE_FOOTER = `
  <div class="footer-grid">
    <div>
      <a href="index.html" class="logo" style="font-size:1.3rem;">Hogary's</a>
      <p style="color:var(--ink-soft);font-size:0.9rem;margin-top:14px;max-width:32ch;">
        La plataforma para comprar, vender y rentar inmuebles con confianza.
      </p>
    </div>
    <div>
      <h4>Explorar</h4>
      <ul>
        <li><a href="listado.html?op=comprar">Comprar</a></li>
        <li><a href="listado.html?op=rentar">Rentar</a></li>
        <li><a href="perfil.html">Vender</a></li>
      </ul>
    </div>
    <div>
      <h4>Empresa</h4>
      <ul>
        <li><a href="contactos.html">Nosotros</a></li>
        <li><a href="contactos.html">Contacto</a></li>
        <li><a href="contactos.html">Ayuda</a></li>
      </ul>
    </div>
    <div>
      <h4>Legal</h4>
      <ul>
        <li><a href="terminos.html">Términos y Condiciones</a></li>
        <li><a href="terminos.html">Privacidad</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© 2026 Hogary's. Proyecto académico.</span>
    <span>Culiacán, Sinaloa, México</span>
  </div>
`;

function hash(texto){
  let h = 0;
  for(let i = 0; i < texto.length; i++){
    h = (h * 31 + texto.charCodeAt(i)) % 100000;
  }
  return h;
}

function alternarSecciones() {
  const secVisitante = document.getElementById('seccionVisitante');
  const secUsuario = document.getElementById('seccionSesionIniciada');
  const secBand = document.getElementById('seccionBand');
  const secTuto = document.getElementById('como-funciona');
  
  if (!secVisitante || !secUsuario) return;

  const sesion = getSesion();
  if (sesion) {   
    secVisitante.style.display = 'none';
    secUsuario.style.display = 'block';
    if(secBand) secBand.style.display = 'none';
    if(secTuto) secTuto.style.display = 'none';
  } else {
    secVisitante.style.display = 'flex';
    secUsuario.style.display = 'none';
    if(secBand) secBand.style.display = 'block';
    if(secTuto) secTuto.style.display = 'block';
  }
}

function getUsuarios(){
  return JSON.parse(localStorage.getItem('hogarysUsuarios')) || [];
}

function guardarUsuarios(lista){
  localStorage.setItem('hogarysUsuarios', JSON.stringify(lista));
}

function getSesion(){
  return JSON.parse(localStorage.getItem('hogarysSesion')) || null;
}

function iniciarSesion(usuario){
  localStorage.setItem('hogarysSesion', JSON.stringify({ nombre: usuario.nombre, email: usuario.email }));
}

function cerrarSesion(){
  localStorage.removeItem('hogarysSesion');
  window.location.reload();
}

function actualizarPerfil(email, datos){
  const usuarios = getUsuarios();
  const idx = usuarios.findIndex(u => u.email === email);
  if(idx > -1){
    usuarios[idx] = Object.assign({}, usuarios[idx], datos);
    guardarUsuarios(usuarios);
    const sesionActual = getSesion();
    if(sesionActual && sesionActual.email === email){
      iniciarSesion(usuarios[idx]);
    }
  }
}

function getPropiedadesUsuarios(){
  return JSON.parse(localStorage.getItem('hogarysPropiedadesUsuarios')) || [];
}

function guardarPropiedadesUsuarios(lista){
  localStorage.setItem('hogarysPropiedadesUsuarios', JSON.stringify(lista));
}

function agregarPropiedadUsuario(datos){
  const lista = getPropiedadesUsuarios();
  const nueva = Object.assign({ id: 'u' + Date.now() }, datos);
  lista.push(nueva);
  guardarPropiedadesUsuarios(lista);
  return nueva;
}

function actualizarPropiedadUsuario(id, datos){
  const lista = getPropiedadesUsuarios();
  const idx = lista.findIndex(p => String(p.id) === String(id));
  if(idx > -1){
    lista[idx] = Object.assign({}, lista[idx], datos);
    guardarPropiedadesUsuarios(lista);
  }
}

function eliminarPropiedadUsuario(id){
  guardarPropiedadesUsuarios(getPropiedadesUsuarios().filter(p => String(p.id) !== String(id)));
}

function getTodasPropiedades(){
  const base = (typeof PROPERTIES !== 'undefined') ? PROPERTIES : [];
  return base.concat(getPropiedadesUsuarios());
}

function getFavoritosMap(){
  return JSON.parse(localStorage.getItem('hogarysFavoritos')) || {};
}

function guardarFavoritosMap(mapa){
  localStorage.setItem('hogarysFavoritos', JSON.stringify(mapa));
}

function getFavoritos(email){
  return getFavoritosMap()[email] || [];
}

function esFavorito(email, id){
  return getFavoritos(email).some(x => String(x) === String(id));
}

function toggleFavorito(email, id){
  const mapa = getFavoritosMap();
  const lista = mapa[email] || [];
  const idx = lista.findIndex(x => String(x) === String(id));
  if(idx > -1){ lista.splice(idx, 1); } else { lista.push(id); }
  mapa[email] = lista;
  guardarFavoritosMap(mapa);
}

function formatPrice(precio, op){
  const num = Number(precio || 0).toLocaleString('es-MX');
  return op === 'rentar' ? `$${num} MXN / mes` : `$${num} MXN`;
}

function pintarNavSesion(){
  const nav = document.getElementById('navActions');
  if(!nav) return;
  const sesion = getSesion();
  if(sesion){
    nav.innerHTML = `
      <a href="perfil.html" style="font-size:0.9rem;text-decoration:none;font-weight:600;">Hola, ${sesion.nombre}</a>
      <button class="btn btn-ghost" id="logoutBtn">Cerrar sesión</button>
      <button class="menu-toggle" aria-label="Abrir menú"><span></span><span></span><span></span></button>
    `;
    document.getElementById('logoutBtn').addEventListener('click', cerrarSesion);
    setupMenuToggle();
  }
}

function setupMenuToggle(){
  const menuToggle = document.querySelector('.menu-toggle');
  const navList = document.querySelector('nav ul');
  if(!menuToggle || !navList) return;

  menuToggle.addEventListener('click', () => {
    menuToggle.classList.toggle('active');
    navList.classList.toggle('open');
  });

  navList.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      menuToggle.classList.remove('active');
      navList.classList.remove('open');
    });
  });
}

function hogarysInit(){
  const headerEl = document.getElementById('site-header');
  const modalsEl = document.getElementById('site-modals');
  const footerEl = document.getElementById('site-footer');
  if(headerEl) headerEl.innerHTML = SITE_HEADER;
  if(modalsEl) modalsEl.innerHTML = SITE_MODALS;
  if(footerEl) footerEl.innerHTML = SITE_FOOTER;

  pintarNavSesion();
  setupMenuToggle();
  alternarSecciones();

  const registerModal = document.getElementById('registerModal');
  const loginModal = document.getElementById('loginModal');
  const open = (m) => m.classList.add('active');
  const close = (m) => m.classList.remove('active');

  document.querySelectorAll('[data-open="register"]').forEach(b => b.addEventListener('click', () => open(registerModal)));
  document.querySelectorAll('[data-open="login"]').forEach(b => b.addEventListener('click', () => open(loginModal)));

  document.querySelectorAll('[data-switch="login"]').forEach(b => b.addEventListener('click', () => { close(registerModal); open(loginModal); }));
  document.querySelectorAll('[data-switch="register"]').forEach(b => b.addEventListener('click', () => { close(loginModal); open(registerModal); }));

  document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => { close(registerModal); close(loginModal); }));
  [registerModal, loginModal].forEach(m => m.addEventListener('click', (e) => { if(e.target === m) close(m); }));
  document.addEventListener('keydown', (e) => { if(e.key === 'Escape'){ close(registerModal); close(loginModal); } });

  document.getElementById('registerForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const nombre = document.getElementById('regName').value.trim();
    const email = document.getElementById('regEmail').value.trim().toLowerCase();
    const clave = document.getElementById('regPass').value;
    const msg = document.getElementById('registerMsg');

    if(!nombre || !email || !clave){
      msg.textContent = 'Llena todos los campos.';
      msg.className = 'modal-msg error';
      return;
    }

    const usuarios = getUsuarios();
    if(usuarios.some(u => u.email === email)){
      msg.textContent = 'Ese correo ya está registrado.';
      msg.className = 'modal-msg error';
      return;
    }

    msg.innerHTML = '<span class="spinner"></span> Creando cuenta…';
    msg.className = 'modal-msg';

    setTimeout(() => {
      const nuevo = { nombre, email, clave: hash(clave) };
      usuarios.push(nuevo);
      guardarUsuarios(usuarios);
      iniciarSesion(nuevo);
      msg.textContent = `Cuenta creada. ¡Bienvenido, ${nombre}!`;
      msg.className = 'modal-msg ok';
      setTimeout(() => { close(registerModal); pintarNavSesion(); alternarSecciones(); }, 800);
    }, 900);
  });

  document.getElementById('loginForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const email = document.getElementById('logEmail').value.trim().toLowerCase();
    const clave = document.getElementById('logPass').value;
    const msg = document.getElementById('loginMsg');

    if(!email || !clave){
      msg.textContent = 'Llena todos los campos.';
      msg.className = 'modal-msg error';
      return;
    }

    msg.innerHTML = '<span class="spinner"></span> Conectando…';
    msg.className = 'modal-msg';

    setTimeout(() => {
      const usuarios = getUsuarios();
      const encontrado = usuarios.find(u => u.email === email && u.clave === hash(clave));

      if(!encontrado){
        msg.textContent = 'Correo o contraseña incorrectos.';
        msg.className = 'modal-msg error';
        return;
      }

      iniciarSesion(encontrado);
      msg.textContent = `Bienvenido de vuelta, ${encontrado.nombre}`;
      msg.className = 'modal-msg ok';
      setTimeout(() => { close(loginModal); pintarNavSesion(); alternarSecciones(); }, 800);
    }, 900);
  });

  const sliderContainer = document.getElementById('heroCarouselTrack');
  if (sliderContainer) {
    const catalogo = getTodasPropiedades();
    const propiedadesDestacadas = catalogo.slice(0, 6);
    let htmlSlider = '';

    propiedadesDestacadas.forEach((prop, index) => {
        const numeroImagen = index + 1;
        const foto1 = `imagenes/imagenes/propiedades/${numeroImagen}/1.png`;
        const foto2 = `imagenes/imagenes/propiedades/${numeroImagen}/2.png`;
        const foto3 = `imagenes/imagenes/propiedades/${numeroImagen}/3.png`;
        const precioF = formatPrice(prop.precio, prop.op);

        htmlSlider += `
            <div class="carousel-slide">
              <div class="slide-images">
                <img src="${foto1}" onerror="this.src='https://picsum.photos/seed/${prop.id}1/800/1000'">
                <img src="${foto2}" onerror="this.src='https://picsum.photos/seed/${prop.id}2/800/1000'">
                <img src="${foto3}" onerror="this.src='https://picsum.photos/seed/${prop.id}3/800/1000'">
              </div>
              <div class="slide-overlay"></div>
              <div class="hero-content slide-caption">
                <p class="hero-eyebrow">Elegidas para ti</p>
                <h1>${prop.tipo} en ${prop.colonia}</h1>
                <p>${precioF} • ${prop.recamaras} recámaras • ${prop.banos} baños • ${prop.m2} m²</p>
                <div class="hero-ctas">
                  <a href="detalles.html?id=${prop.id}" class="btn btn-solid">Ver detalles</a>
                </div>
              </div>
            </div>
        `;
    });

    sliderContainer.innerHTML = htmlSlider;

    let currentSlide = 0;
    const slides = sliderContainer.querySelectorAll('.carousel-slide');
    const totalSlides = slides.length;
    const btnNext = document.getElementById('cNext');
    const btnPrev = document.getElementById('cPrev');

    if(btnNext && btnPrev && totalSlides > 0){
      btnNext.addEventListener('click', () => {
        currentSlide = (currentSlide + 1) % totalSlides;
        sliderContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
      });
      btnPrev.addEventListener('click', () => {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        sliderContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
      });
    }
  }
}

document.addEventListener('DOMContentLoaded', hogarysInit);