/* Datos de demostración — en un backend real vendrían de una base de datos */
const PROPERTIES = [
  {
    id: 1,
    op: 'comprar',
    tipo: 'Casa',
    precio: 3990000,
    ciudad: 'Mazatlán',
    colonia: 'Residencia',
    recamaras: 3,
    banos: 2,
    m2: 190,
    accent: '#65b82e',
    desc: 'Casa de dos plantas con acabados modernos, cocina integral, patio trasero techado y cochera para dos autos',
    amenidades: ['Cochera para 2 autos', 'Cocina integral', 'Seguridad 24h', 'Área de lavado'],
    agente: 'Mariana Niebla',
    fotos : [
      "imagenes/imagenes/propiedades/1/1.png",
      "imagenes/imagenes/propiedades/1/2.png",
      "imagenes/imagenes/propiedades/1/3.png",
      "imagenes/imagenes/propiedades/1/4.png"
    ]

  },
  {
    id: 2,
    op: 'rentar',
    tipo: 'Casa',
    precio: 14500,
    ciudad: 'Culiacán',
    colonia: 'Col. Tres Ríos',
    recamaras: 2,
    banos: 4,
    m2: 434,
    accent: '#2D5A3D',
    desc: 'Casa amueblada en zona céntrica, ideal para pareja o profesionista. Incluye agua y mantenimiento del edificio.',
    amenidades: ['Amueblado', 'Área común', 'Estacionamiento', 'Incluye agua', 'Cerca de transporte público'],
    agente: 'Jorge Almada',
    fotos : [
      "imagenes/imagenes/propiedades/2/1.png",
      "imagenes/imagenes/propiedades/2/2.png",
      "imagenes/imagenes/propiedades/2/3.png",
      "imagenes/imagenes/propiedades/2/4.png"
    ]
  },
  {
    id: 3,
    op: 'comprar',
    tipo: 'Casa',
    precio: 15550000,
    ciudad: 'Culiacán',
    colonia: 'Fracc. La Primavera',
    recamaras: 3,
    banos: 4,
    m2: 450,
    accent: '#65b82e',
    desc: 'Residencia de dos plantas en fraccionamiento privado con alberca, jardín amplio y estudio independiente en planta baja.',
    amenidades: ['Alberca', 'Jardín amplio', 'Estudio independiente', 'Fraccionamiento privado', 'Cochera techada', 'Cuarto de servicio'],
    agente: 'Marisol Téllez',
    fotos : [
      "imagenes/imagenes/propiedades/3/1.png",
      "imagenes/imagenes/propiedades/3/2.png",
      "imagenes/imagenes/propiedades/3/3.png",
      "imagenes/imagenes/propiedades/3/4.png"
    ]
  },
  {
    id: 4,
    op: 'rentar',
    tipo: 'Departamento',
    precio: 18000,
    ciudad: 'Culiacán',
    colonia: 'Col. Guadalupe',
    recamaras: 2,
    banos: 2,
    m2: 117,
    accent: '#2D5A3D',
    desc: 'Departemento amplio, cocina remodelada y cerca de escuelas y centros comerciales.',
    amenidades: ['Patio amplio', 'Cocina remodelada', 'Cerca de escuelas', 'Cochera', 'Mascotas permitidas'],
    agente: 'Luis Peraza',
    fotos : [
      "imagenes/imagenes/propiedades/4/1.png",
      "imagenes/imagenes/propiedades/4/2.png",
      "imagenes/imagenes/propiedades/4/3.png",
      "imagenes/imagenes/propiedades/4/4.png"
    ]
  },
  {
    id: 5,
    op: 'comprar',
    tipo: 'Casa',
    precio: 7900000,
    ciudad: 'Culiacán',
    colonia: 'Fracc. Isla Musala',
    recamaras: 3,
    banos: 4,
    m2: 361,
    accent: '#B8622E',
    desc: 'Casa en fraccionamiento privado con áreas verdes comunes, alberca del club de residentes y seguridad en caseta.',
    amenidades: ['Club de residentes', 'Alberca común', 'Caseta de vigilancia', 'Áreas verdes', 'Cochera techada', 'Cuarto de lavado'],
    agente: 'Marisol Téllez',
    fotos : [
      "imagenes/imagenes/propiedades/5/1.png",
      "imagenes/imagenes/propiedades/5/2.png",
      "imagenes/imagenes/propiedades/5/3.png",
      "imagenes/imagenes/propiedades/5/4.png"
    ]
  },
  {
    id: 6,
    op: 'rentar',
    tipo: 'Departamento',
    precio: 11000,
    ciudad: 'Culiacán',
    colonia: 'Col. Centro',
    recamaras: 2,
    banos: 2,
    m2: 90,
    accent: '#2D5A3D',
    desc: 'Loft de un ambiente en el corazón de la ciudad, perfecto para una persona sola o estudiante.',
    amenidades: ['Zona céntrica', 'Amueblado', 'Wifi incluido', 'Seguridad', 'Cerca de universidades', 'Área de lavandería'],
    agente: 'Jorge Almada',
    fotos : [
      "imagenes/imagenes/propiedades/6/1.png",
      "imagenes/imagenes/propiedades/6/2.png",
      "imagenes/imagenes/propiedades/6/3.png",
      "imagenes/imagenes/propiedades/6/4.png"
    ]
  },
  {
    id: 7,
    op: 'comprar',
    tipo: 'Casa',
    precio: 4500000,
    ciudad: 'Culiacán',
    colonia: 'Col. Chapultepec',
    recamaras: 3,
    banos: 3,
    m2: 192,
    accent: '#65b82e',
    desc: 'Casa residencial en una de las zonas más consolidadas de la ciudad, con doble altura en la sala y acabados de lujo.',
    amenidades: ['Doble altura en sala', 'Cocina con isla', 'Cuarto de TV', 'Cochera para 3 autos', 'Jardín trasero', 'Cisterna y planta de luz'],
    agente: 'Ana Beltrán',
    fotos : [
      "imagenes/imagenes/propiedades/7/1.png",
      "imagenes/imagenes/propiedades/7/2.png",
      "imagenes/imagenes/propiedades/7/3.png",
      "imagenes/imagenes/propiedades/7/4.png"
    ]
  },
  {
    id: 8,
    op: 'rentar',
    tipo: 'Departamento',
    precio: 20000,
    ciudad: 'Culiacán',
    colonia: 'Col. Nakayama',
    recamaras: 2,
    banos: 3,
    m2: 97,
    accent: '#2D5A3D',
    desc: 'Departamento en edificio con vigilancia, cerca de plazas comerciales y a unos minutos del centro.',
    amenidades: ['Vigilancia 24h', 'Cerca de plazas comerciales', 'Balcón', 'Estacionamiento techado', 'Área de asadores', 'Gimnasio del edificio'],
    agente: 'Luis Peraza',
    fotos : [
      "imagenes/imagenes/propiedades/8/1.png",
      "imagenes/imagenes/propiedades/8/2.png",
      "imagenes/imagenes/propiedades/8/3.png",
      "imagenes/imagenes/propiedades/8/4.png"
    ]
  },
  {
    id: 9,
    op: 'comprar',
    tipo: 'Casa',
    precio: 4350000,
    ciudad: 'Culiacán',
    colonia: 'Fracc. Isla Musala',
    recamaras: 3,
    banos: 3,
    m2: 432,
    accent: '#B8622E',
    desc: 'Casa en fraccionamiento privado con áreas verdes comunes, alberca del club de residentes y seguridad en caseta.',
    amenidades: ['Club de residentes', 'Alberca común', 'Caseta de vigilancia', 'Áreas verdes', 'Cochera techada', 'Cuarto de lavado'],
    agente: 'Marisol Téllez',
    fotos : [
      "imagenes/imagenes/propiedades/9/1.png",
      "imagenes/imagenes/propiedades/9/2.png",
      "imagenes/imagenes/propiedades/9/3.png",
      "imagenes/imagenes/propiedades/9/4.png"
    ]
  },
  {
    id: 10,
    op: 'rentar',
    tipo: 'Casa',
    precio: 15500,
    ciudad: 'Culiacán',
    colonia: 'Col. Miguel Alemán',
    recamaras: 3,
    banos: 2,
    m2: 140,
    accent: '#2D5A3D',
    desc: 'Casa familiar cerca de escuelas y avenidas principales, con cochera cubierta y patio de servicio.',
    amenidades: ['Cerca de escuelas', 'Cochera cubierta', 'Patio de servicio', 'Cocina equipada', 'Portón eléctrico', 'Zona bien comunicada'],
    agente: 'Jorge Almada',
    fotos : [
      "imagenes/imagenes/propiedades/10/1.png",
      "imagenes/imagenes/propiedades/10/2.png",
      "imagenes/imagenes/propiedades/10/3.png",
      "imagenes/imagenes/propiedades/10/4.png"
    ]
  },
];