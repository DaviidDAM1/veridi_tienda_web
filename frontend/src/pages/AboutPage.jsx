import { Link } from 'react-router-dom';

function AboutPage() {
  const metricas = [
    { valor: '+12k', etiqueta: 'Clientes satisfechos' },
    { valor: '48h', etiqueta: 'Enviamos tu pedido' },
    { valor: '98%', etiqueta: 'Valoraciones positivas' }
  ];

  const pilares = [
    {
      titulo: 'Diseno con caracter',
      texto: 'Creamos prendas con identidad propia para que tu look se note sin esfuerzo.'
    },
    {
      titulo: 'Calidad que dura',
      texto: 'Seleccionamos tejidos resistentes y comodos para que cada pieza rinda temporada tras temporada.'
    },
    {
      titulo: 'Produccion consciente',
      texto: 'Reducimos desperdicio y cuidamos cada proceso para construir una moda mas responsable.'
    }
  ];

  const hitos = [
    {
      ano: '2021',
      titulo: 'Nace Veridi',
      texto: 'Comenzamos con una coleccion corta enfocada en basicos premium de estilo urbano.'
    },
    {
      ano: '2023',
      titulo: 'Comunidad en crecimiento',
      texto: 'Miles de clientes empezaron a formar parte de la familia Veridi dentro y fuera de Espana.'
    },
    {
      ano: 'Hoy',
      titulo: 'Evolucion constante',
      texto: 'Seguimos innovando en diseno, fit y experiencia para ofrecer moda con personalidad.'
    }
  ];

  return (
    <main className="about-page">
      <section className="about-hero">
        <div className="about-hero-glow" aria-hidden="true"></div>
        <span className="about-badge">Sobre Veridi</span>
        <h1>Moda urbana con alma premium</h1>
        <p>
          En Veridi combinamos estilo contemporaneo, materiales de calidad y una vision clara:
          ayudarte a vestir con confianza todos los dias.
        </p>

        <div className="about-metrics">
          {metricas.map((item) => (
            <article key={item.etiqueta} className="about-metric-card">
              <strong>{item.valor}</strong>
              <span>{item.etiqueta}</span>
            </article>
          ))}
        </div>
      </section>

      <section className="about-section about-story">
        <div className="about-story-content">
          <h2>Quienes somos</h2>
          <p>
            Veridi nace para cerrar la distancia entre una marca con identidad y la comodidad real del dia a dia.
            Cada coleccion se construye buscando equilibrio entre presencia, versatilidad y calidad.
          </p>
          <p>
            Nos obsesionan los detalles: patronaje, acabados y seleccion de tejidos. El objetivo es simple,
            que cada prenda te quede bien, dure y transmita actitud.
          </p>
        </div>

        <aside className="about-quote-card">
          <p>
            "No seguimos tendencias por inercia. Creamos piezas para que marquen tu estilo y no al reves."
          </p>
          <span>Equipo Veridi</span>
        </aside>
      </section>

      <section className="about-section">
        <h2>Nuestros pilares</h2>
        <div className="about-pillars-grid">
          {pilares.map((pilar) => (
            <article key={pilar.titulo} className="about-pillar-card">
              <h3>{pilar.titulo}</h3>
              <p>{pilar.texto}</p>
            </article>
          ))}
        </div>
      </section>

      <section className="about-section about-timeline-section">
        <h2>Como hemos evolucionado</h2>
        <div className="about-timeline">
          {hitos.map((hito) => (
            <article key={hito.ano + hito.titulo} className="about-timeline-item">
              <span className="about-year">{hito.ano}</span>
              <h3>{hito.titulo}</h3>
              <p>{hito.texto}</p>
            </article>
          ))}
        </div>
      </section>

      <section className="about-section about-cta">
        <h2>Descubre la experiencia Veridi</h2>
        <p>
          Si quieres ver nuestras colecciones o resolver cualquier duda, estamos para ayudarte.
        </p>
        <div className="about-cta-actions">
          <Link to="/tienda" className="about-btn about-btn-primary">Ver catalogo</Link>
          <Link to="/contacto" className="about-btn about-btn-ghost">Hablar con nosotros</Link>
        </div>
      </section>
    </main>
  );
}

export default AboutPage;
