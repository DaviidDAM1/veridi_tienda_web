import { Link } from 'react-router-dom';
import { buildBackendAssetUrl } from '../services/api';
import LiquidEther from '../components/ui/LiquidEther';

function WelcomePage() {
  return (
    <div className="welcome-splash">
      {/* Liquid Ether full-screen background */}
      <div className="welcome-splash-bg" aria-hidden="true">
        <LiquidEther
          colors={['#0ea5e9', '#2563eb', '#8b5cf6', '#ffffff']}
          mouseForce={25}
          cursorSize={120}
          autoDemo={true}
          autoSpeed={0.4}
          autoIntensity={2.5}
          resolution={0.6}
        />
      </div>

      {/* Overlay gradient for readability */}
      <div className="welcome-splash-overlay" aria-hidden="true" />

      {/* Content */}
      <div className="welcome-splash-content">
        <img
          src={buildBackendAssetUrl('imgnuevas/LogoVeridi.png')}
          alt="Veridi Logo"
          className="welcome-splash-logo"
        />

        <div className="welcome-splash-badge">Colección 2026</div>

        <h1 className="welcome-splash-title">
          Bienvenido a<br />
          <span className="welcome-splash-brand">Veridi</span>
        </h1>

        <p className="welcome-splash-sub">
          Moda masculina exclusiva — estilo, calidad y carácter.
        </p>

        <Link to="/inicio" className="welcome-splash-btn">
          <span>Entrar</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </Link>

        <p className="welcome-splash-hint">Mueve el cursor para interactuar</p>
      </div>
    </div>
  );
}

export default WelcomePage;
