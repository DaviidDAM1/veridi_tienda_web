import { Link } from 'react-router-dom';
import { buildBackendAssetUrl } from '../services/api';

function WelcomePage() {
  return (
    <main className="welcome-main">
      <section className="welcome-card">
        <img src={buildBackendAssetUrl('img/Logo.png')} alt="Veridi Logo" className="welcome-logo" />
        <h1>Bienvenido a Veridi</h1>
        <p>Moda masculina exclusiva con estilo, calidad y personalidad.</p>
        <Link to="/" className="btn-productos">Entrar</Link>
      </section>
    </main>
  );
}

export default WelcomePage;
