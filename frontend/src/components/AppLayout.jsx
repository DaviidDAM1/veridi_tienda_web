import { useEffect, useMemo, useRef, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import api, { buildBackendAssetUrl, BACKEND_BASE_URL } from '../services/api';
import ThemeToggle from './ui/ThemeToggle';

function normalizeImagePath(path) {
  const value = String(path || '').trim();
  if (!value) return buildBackendAssetUrl('img/user-default.svg');
  if (value.startsWith('http://') || value.startsWith('https://')) return value;
  return buildBackendAssetUrl(value);
}

function formatPedidoId(id) {
  return String(Number(id) || 0).padStart(6, '0');
}

function formatStars(count) {
  const value = Math.max(1, Math.min(5, Number(count) || 1));
  return '★'.repeat(value) + '☆'.repeat(5 - value);
}

function AppLayout({ children }) {
  const location = useLocation();
  const isWelcomePage = location.pathname === '/bienvenida';
  const authWrapperRef = useRef(null);

  const [loadingUser, setLoadingUser] = useState(true);
  const [currentUser, setCurrentUser] = useState(null);
  const [contador, setContador] = useState({ carrito: 0, deseos: 0 });
  const [historialPedidos, setHistorialPedidos] = useState([]);
  const [valoracionesUsuario, setValoracionesUsuario] = useState([]);

  const [authPanelOpen, setAuthPanelOpen] = useState(false);
  const [authTab, setAuthTab] = useState('login');
  const [authMessage, setAuthMessage] = useState('');
  const [authMessageType, setAuthMessageType] = useState('');
  const [submittingAuth, setSubmittingAuth] = useState(false);

  const [loginForm, setLoginForm] = useState({ email: '', password: '' });
  const [registerForm, setRegisterForm] = useState({ nombre: '', email: '', password: '', password_confirm: '' });

  const [profileOpen, setProfileOpen] = useState(false);
  const [photoActionsOpen, setPhotoActionsOpen] = useState(false);
  const [photoEditOpen, setPhotoEditOpen] = useState(false);
  const [nameEditOpen, setNameEditOpen] = useState(false);
  const [photoViewerOpen, setPhotoViewerOpen] = useState(false);
  const [profileMessage, setProfileMessage] = useState('');
  const [profileMessageType, setProfileMessageType] = useState('');
  const [savingProfile, setSavingProfile] = useState(false);
  const [editName, setEditName] = useState('');
  const [editPhoto, setEditPhoto] = useState(null);

  const displayName = useMemo(() => {
    const name = String(currentUser?.nombre || 'Usuario');
    return name.length > 15 ? `${name.slice(0, 15)}...` : name;
  }, [currentUser]);

  const loadUser = async () => {
    try {
      const response = await api.get('/php/api_usuario.php');
      const data = response.data;
      if (!data?.ok) return;

      setContador(data.contador || { carrito: 0, deseos: 0 });

      if (data.logueado && data.usuario) {
        setCurrentUser(data.usuario);
        setHistorialPedidos(data.historial_pedidos || []);
        setValoracionesUsuario(data.valoraciones || []);
        setEditName(data.usuario.nombre || '');
      } else {
        setCurrentUser(null);
        setHistorialPedidos([]);
        setValoracionesUsuario([]);
        setEditName('');
      }
    } catch (error) {
      setCurrentUser(null);
      setHistorialPedidos([]);
      setValoracionesUsuario([]);
      setContador({ carrito: 0, deseos: 0 });
    } finally {
      setLoadingUser(false);
    }
  };

  useEffect(() => {
    loadUser();
  }, [location.pathname]);

  useEffect(() => {
    const handleCounterUpdate = (event) => {
      const detail = event?.detail || {};
      setContador((prev) => ({
        carrito: Number.isFinite(Number(detail.carrito)) ? Number(detail.carrito) : prev.carrito,
        deseos: Number.isFinite(Number(detail.deseos)) ? Number(detail.deseos) : prev.deseos
      }));

      api.get('/php/api_usuario.php')
        .then((response) => {
          const data = response?.data;
          if (data?.ok && data?.contador) {
            setContador({
              carrito: Number(data.contador.carrito || 0),
              deseos: Number(data.contador.deseos || 0)
            });
          }
        })
        .catch(() => {});
    };

    window.addEventListener('veridi:update-contador', handleCounterUpdate);
    return () => {
      window.removeEventListener('veridi:update-contador', handleCounterUpdate);
    };
  }, []);

  useEffect(() => {
    if (!authPanelOpen) return undefined;

    const handleOutside = (event) => {
      if (authWrapperRef.current && !authWrapperRef.current.contains(event.target)) {
        setAuthPanelOpen(false);
      }
    };

    const handleEsc = (event) => {
      if (event.key === 'Escape') {
        setAuthPanelOpen(false);
      }
    };

    document.addEventListener('click', handleOutside);
    document.addEventListener('keydown', handleEsc);

    return () => {
      document.removeEventListener('click', handleOutside);
      document.removeEventListener('keydown', handleEsc);
    };
  }, [authPanelOpen]);

  useEffect(() => {
    document.body.style.overflow = profileOpen ? 'hidden' : '';
    return () => {
      document.body.style.overflow = '';
    };
  }, [profileOpen]);

  const openAuthPanel = (tab) => {
    setAuthTab(tab === 'register' ? 'register' : 'login');
    setAuthPanelOpen(true);
    setAuthMessage('');
    setAuthMessageType('');
  };

  useEffect(() => {
    if (isWelcomePage || currentUser) return undefined;

    const handleOpenAuthRequest = (event) => {
      const requestedTab = event?.detail?.tab;
      openAuthPanel(requestedTab);
    };

    window.addEventListener('veridi:open-auth', handleOpenAuthRequest);
    return () => {
      window.removeEventListener('veridi:open-auth', handleOpenAuthRequest);
    };
  }, [isWelcomePage, currentUser]);

  const handleRegister = async (event) => {
    event.preventDefault();
    setSubmittingAuth(true);
    setAuthMessage('');
    setAuthMessageType('');
    try {
      const response = await api.post('/php/api_auth_react.php', {
        action: 'register',
        ...registerForm
      });
      const data = response.data;

      if (!data?.ok) {
        setAuthMessage(data?.message || 'No se pudo completar el registro.');
        setAuthMessageType('error');
        return;
      }

      setAuthMessage(data.message || 'Registro completado. Ahora puedes iniciar sesión.');
      setAuthMessageType('success');
      setAuthTab('login');
      setRegisterForm({ nombre: '', email: '', password: '', password_confirm: '' });
    } catch (error) {
      setAuthMessage('No se pudo completar el registro.');
      setAuthMessageType('error');
    } finally {
      setSubmittingAuth(false);
    }
  };

  const handleLogin = async (event) => {
    event.preventDefault();
    setSubmittingAuth(true);
    setAuthMessage('');
    setAuthMessageType('');
    try {
      const response = await api.post('/php/api_auth_react.php', {
        action: 'login',
        ...loginForm
      });
      const data = response.data;

      if (!data?.ok) {
        setAuthMessage(data?.message || 'No se pudo iniciar sesión.');
        setAuthMessageType('error');
        return;
      }

      setAuthPanelOpen(false);
      setLoginForm({ email: '', password: '' });
      await loadUser();
    } catch (error) {
      setAuthMessage('No se pudo iniciar sesión.');
      setAuthMessageType('error');
    } finally {
      setSubmittingAuth(false);
    }
  };

  const handleLogout = async () => {
    try {
      await api.post('/php/api_auth_react.php', { action: 'logout' });
    } finally {
      setCurrentUser(null);
      setHistorialPedidos([]);
      setValoracionesUsuario([]);
      setContador({ carrito: 0, deseos: 0 });
      setProfileOpen(false);
      // Redirect to the home page after logout. If BACKEND_BASE_URL is
      // configured (production), go there; otherwise go to the SPA root '/'.
      try {
        const target = (typeof BACKEND_BASE_URL === 'string' && BACKEND_BASE_URL.length) ? BACKEND_BASE_URL : '/';
        window.location.href = target;
      } catch (e) {
        // ignore redirect errors
      }
    }
  };

  const handleSaveName = async (event) => {
    event.preventDefault();
    setSavingProfile(true);
    setProfileMessage('');
    setProfileMessageType('');
    try {
      const formData = new FormData();
      formData.append('action', 'name');
      formData.append('nombre', editName);

      const response = await api.post('/php/api_perfil_react.php', formData);
      const data = response.data;

      if (!data?.ok) {
        setProfileMessage(data?.message || 'No se pudo actualizar el perfil.');
        setProfileMessageType('error');
        return;
      }

      setProfileMessage(data.message || 'Perfil actualizado correctamente.');
      setProfileMessageType('success');
      setNameEditOpen(false);
      await loadUser();
    } catch (error) {
      setProfileMessage('No se pudo actualizar el perfil.');
      setProfileMessageType('error');
    } finally {
      setSavingProfile(false);
    }
  };

  const handleSavePhoto = async (event) => {
    event.preventDefault();
    if (!editPhoto) {
      setProfileMessage('Selecciona una imagen válida.');
      setProfileMessageType('error');
      return;
    }

    setSavingProfile(true);
    setProfileMessage('');
    setProfileMessageType('');
    try {
      const formData = new FormData();
      formData.append('action', 'photo');
      formData.append('foto_perfil', editPhoto);

      const response = await api.post('/php/api_perfil_react.php', formData);
      const data = response.data;

      if (!data?.ok) {
        setProfileMessage(data?.message || 'No se pudo actualizar la foto.');
        setProfileMessageType('error');
        return;
      }

      setProfileMessage(data.message || 'Perfil actualizado correctamente.');
      setProfileMessageType('success');
      setPhotoEditOpen(false);
      setEditPhoto(null);
      await loadUser();
    } catch (error) {
      setProfileMessage('No se pudo actualizar la foto.');
      setProfileMessageType('error');
    } finally {
      setSavingProfile(false);
    }
  };

  const togglePhotoActions = () => {
    setPhotoActionsOpen((prev) => !prev);
    setPhotoEditOpen(false);
    setNameEditOpen(false);
  };

  const openNameEditor = () => {
    setNameEditOpen((prev) => !prev);
    setPhotoActionsOpen(false);
    setPhotoEditOpen(false);
  };

  return (
    <>
      {!isWelcomePage && (
      <header>
        <div className="header-container">
          <div className="header-left">
            <div className="logo">
              <Link to="/" title="Volver a inicio">
                <img src={buildBackendAssetUrl('img/Logo.png')} alt="Veridi Logo" className="logo-img" />
              </Link>
            </div>
          </div>

          <div className="header-center">
            <nav className="nav-principal">
              <Link to="/" className="nav-link nav-main">Inicio</Link>
              <Link to="/tienda" className="nav-link nav-main">Catálogo</Link>
              <Link to="/contacto" className="nav-link nav-main">Contacto</Link>
              <Link to="/sobre-nosotros" className="nav-link nav-main">Sobre nosotros</Link>
              <Link to="/valoraciones" className="nav-link nav-main">Valoraciones</Link>
              {currentUser?.rol === 'admin' && (
                <Link to="/admin" className="nav-link nav-main" title="Panel de administrador">Admin</Link>
              )}
            </nav>
          </div>

          <div className="header-right">
            <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginRight: 8 }}>
              <ThemeToggle />
            </div>
            <div className="user-section">
              {loadingUser ? null : currentUser ? (
                <>
                  <span className="user-greeting">Bienvenido, <span className="user-name-value">{displayName}</span></span>

                  <button type="button" className="icon-button profile-btn" title="Área personal" aria-label="Abrir área personal" onClick={() => setProfileOpen(true)}>
                    <span className="icon">👤</span>
                  </button>

                  <Link to="/carrito" className="cart-badge icon-only" title={`Ver carrito (${contador.carrito})`} aria-label={`Ir al carrito (${contador.carrito})`}>
                    <span aria-hidden="true">🛒</span>
                    {Number(contador.carrito) > 0 && <span className="badge">{contador.carrito}</span>}
                  </Link>

                  <Link to="/lista-deseos" className="wishlist-badge icon-only" title={`Ver favoritos (${contador.deseos})`} aria-label={`Ir a favoritos (${contador.deseos})`}>
                    <span aria-hidden="true">❤️</span>
                    {Number(contador.deseos) > 0 && <span className="badge">{contador.deseos}</span>}
                  </Link>

                  <button type="button" className="nav-link logout-btn" title="Cerrar sesión" onClick={handleLogout}>Cerrar sesión</button>
                </>
              ) : (
                <div className="auth-inline-wrapper" ref={authWrapperRef}>
                  <div className="auth-actions">
                    <button
                      type="button"
                      className={`nav-link auth-btn auth-open-btn ${authPanelOpen && authTab === 'login' ? 'active' : ''}`}
                      onClick={() => (authPanelOpen && authTab === 'login' ? setAuthPanelOpen(false) : openAuthPanel('login'))}
                      title="Iniciar sesión"
                    >
                      Iniciar sesión
                    </button>
                    <button
                      type="button"
                      className={`nav-link auth-btn auth-open-btn ${authPanelOpen && authTab === 'register' ? 'active' : ''}`}
                      onClick={() => (authPanelOpen && authTab === 'register' ? setAuthPanelOpen(false) : openAuthPanel('register'))}
                      title="Registrarse"
                    >
                      Registrarse
                    </button>
                  </div>

                  <div className={`auth-panel ${authPanelOpen ? 'open' : ''}`} aria-hidden={authPanelOpen ? 'false' : 'true'}>
                    <div className="auth-tabs" role="tablist" aria-label="Autenticación">
                      <button type="button" className={`auth-tab-btn ${authTab === 'login' ? 'active' : ''}`} onClick={() => setAuthTab('login')}>Entrar</button>
                      <button type="button" className={`auth-tab-btn ${authTab === 'register' ? 'active' : ''}`} onClick={() => setAuthTab('register')}>Registrarse</button>
                    </div>

                    {authMessage && (
                      <div className={`auth-inline-message ${authMessageType === 'error' ? 'error-message' : 'success-message'}`}>
                        {authMessage}
                      </div>
                    )}

                    <form onSubmit={handleLogin} className={`auth-inline-form auth-form-login ${authTab === 'login' ? 'active' : ''}`}>
                      <label htmlFor="header-login-email">Email</label>
                      <input
                        type="email"
                        id="header-login-email"
                        placeholder="tu@email.com"
                        required
                        value={loginForm.email}
                        onChange={(event) => setLoginForm((prev) => ({ ...prev, email: event.target.value }))}
                      />

                      <label htmlFor="header-login-password">Contraseña</label>
                      <input
                        type="password"
                        id="header-login-password"
                        placeholder="Contraseña"
                        required
                        value={loginForm.password}
                        onChange={(event) => setLoginForm((prev) => ({ ...prev, password: event.target.value }))}
                      />

                      <button type="submit" disabled={submittingAuth}>{submittingAuth ? 'Procesando...' : 'Iniciar sesión'}</button>
                    </form>

                    <form onSubmit={handleRegister} className={`auth-inline-form auth-form-register ${authTab === 'register' ? 'active' : ''}`}>
                      <label htmlFor="header-register-nombre">Nombre</label>
                      <input
                        type="text"
                        id="header-register-nombre"
                        placeholder="Tu nombre"
                        required
                        value={registerForm.nombre}
                        onChange={(event) => setRegisterForm((prev) => ({ ...prev, nombre: event.target.value }))}
                      />

                      <label htmlFor="header-register-email">Email</label>
                      <input
                        type="email"
                        id="header-register-email"
                        placeholder="tu@email.com"
                        required
                        value={registerForm.email}
                        onChange={(event) => setRegisterForm((prev) => ({ ...prev, email: event.target.value }))}
                      />

                      <label htmlFor="header-register-password">Contraseña</label>
                      <input
                        type="password"
                        id="header-register-password"
                        placeholder="Mínimo 6 caracteres"
                        required
                        value={registerForm.password}
                        onChange={(event) => setRegisterForm((prev) => ({ ...prev, password: event.target.value }))}
                      />

                      <label htmlFor="header-register-password-confirm">Confirmar contraseña</label>
                      <input
                        type="password"
                        id="header-register-password-confirm"
                        placeholder="Repite tu contraseña"
                        required
                        value={registerForm.password_confirm}
                        onChange={(event) => setRegisterForm((prev) => ({ ...prev, password_confirm: event.target.value }))}
                      />

                      <button type="submit" disabled={submittingAuth}>{submittingAuth ? 'Procesando...' : 'Crear cuenta'}</button>
                    </form>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </header>
      )}

      {!isWelcomePage && currentUser && (
        <>
          <div className={`profile-modal-overlay ${profileOpen ? 'open' : ''}`} onClick={() => setProfileOpen(false)}></div>
          <div className={`profile-modal ${profileOpen ? 'open' : ''}`} role="dialog" aria-modal="true" aria-labelledby="profile-modal-title" aria-hidden={!profileOpen}>
            <button type="button" className="profile-modal-close" aria-label="Cerrar área personal" onClick={() => setProfileOpen(false)}>&times;</button>

            <div className="profile-header-block">
              <button type="button" className="profile-avatar-trigger" title="Gestionar foto de perfil" onClick={togglePhotoActions}>
                <img src={normalizeImagePath(currentUser.foto_perfil)} alt="Foto de perfil" className="profile-avatar" />
              </button>
              <div>
                <h2 id="profile-modal-title">Área personal</h2>
                <p className="profile-subtitle">Tu información y actividad en Veridi</p>
              </div>
            </div>

            {profileMessage && (
              <div className={`profile-message ${profileMessageType === 'success' ? 'profile-message-success' : 'profile-message-error'}`}>
                {profileMessage}
              </div>
            )}

            <div className="profile-actions-wrap">
              {photoActionsOpen && (
                <div className="profile-actions-card">
                  <div className="profile-actions-head">
                    <p className="profile-actions-title">Foto de perfil</p>
                    <button type="button" className="profile-actions-close" aria-label="Cerrar opciones de foto" onClick={() => setPhotoActionsOpen(false)}>&times;</button>
                  </div>
                  <div className="profile-actions-buttons">
                    <button type="button" className="profile-small-btn" onClick={() => setPhotoViewerOpen(true)}>Ver foto de perfil</button>
                    <button type="button" className="profile-small-btn" onClick={() => { setPhotoActionsOpen(false); setPhotoEditOpen(true); setNameEditOpen(false); }}>Modificar foto de perfil</button>
                  </div>
                </div>
              )}
            </div>

            {photoEditOpen && (
              <form className="profile-edit-form" onSubmit={handleSavePhoto}>
                <div className="profile-actions-head">
                  <p className="profile-actions-title">Modificar foto de perfil</p>
                  <button type="button" className="profile-actions-close" aria-label="Cerrar edición de foto" onClick={() => setPhotoEditOpen(false)}>&times;</button>
                </div>

                <div className="profile-edit-grid">
                  <div className="profile-edit-field">
                    <label htmlFor="perfil-foto-react">Foto de perfil (JPG, PNG, WEBP · máx 5MB)</label>
                    <input type="file" id="perfil-foto-react" name="foto_perfil" accept="image/jpeg,image/png,image/webp" required onChange={(event) => setEditPhoto(event.target.files?.[0] || null)} />
                  </div>
                </div>

                <button type="submit" className="profile-save-btn" disabled={savingProfile}>{savingProfile ? 'Guardando...' : 'Guardar foto'}</button>
              </form>
            )}

            {nameEditOpen && (
              <form className="profile-edit-form" onSubmit={handleSaveName}>
                <div className="profile-edit-grid">
                  <div className="profile-edit-field">
                    <label htmlFor="perfil-nombre-react">Nombre de usuario</label>
                    <input type="text" id="perfil-nombre-react" name="nombre" value={editName} maxLength={100} required onChange={(event) => setEditName(event.target.value)} />
                  </div>
                </div>

                <button type="submit" className="profile-save-btn" disabled={savingProfile}>{savingProfile ? 'Guardando...' : 'Guardar nombre'}</button>
              </form>
            )}

            <div className="profile-data-grid">
              <div className="profile-field">
                <span className="profile-label">Correo</span>
                <div className="profile-value">{currentUser.email || ''}</div>
              </div>
              <div className="profile-field">
                <span className="profile-label">Nombre de usuario</span>
                <button type="button" className="profile-value-btn" onClick={openNameEditor}>{currentUser.nombre || 'Usuario'}</button>
              </div>
              <div className="profile-field">
                <span className="profile-label">Contraseña</span>
                <div className="profile-value">{currentUser.password_masked || '********'}</div>
              </div>
            </div>

            <div className="profile-section">
              <h3>Historial de pedidos</h3>
              {historialPedidos.length === 0 ? (
                <p className="profile-empty">Aún no tienes pedidos registrados.</p>
              ) : (
                <div className="profile-list">
                  {historialPedidos.map((pedido) => (
                    <article className="profile-card-item" key={pedido.id_pedido}>
                      <div>
                        <p className="profile-card-title">Pedido #{formatPedidoId(pedido.id_pedido)}</p>
                        <p className="profile-card-meta">{new Date(pedido.fecha).toLocaleString('es-ES')} · Estado: {String(pedido.estado || '').charAt(0).toUpperCase() + String(pedido.estado || '').slice(1)}</p>
                      </div>
                      <div className="profile-card-right">
                        <strong>€{Number(pedido.total).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                        <Link to={`/confirmacion/${pedido.id_pedido}`}>Ver</Link>
                      </div>
                    </article>
                  ))}
                </div>
              )}
            </div>

            <div className="profile-section">
              <h3>Mis valoraciones</h3>
              {valoracionesUsuario.length === 0 ? (
                <p className="profile-empty">Todavía no has dejado valoraciones.</p>
              ) : (
                <div className="profile-list">
                  {valoracionesUsuario.map((valoracion) => (
                    <article className="profile-card-item" key={`${valoracion.id_pedido}_${valoracion.fecha}`}>
                      <div>
                        <p className="profile-card-title">Pedido #{formatPedidoId(valoracion.id_pedido)}</p>
                        <p className="profile-card-meta">{new Date(valoracion.fecha).toLocaleString('es-ES')}</p>
                        <p className="profile-card-comment">{String(valoracion.comentario || '').trim() !== '' ? valoracion.comentario : 'Sin comentario adicional.'}</p>
                      </div>
                      <div className="profile-card-right profile-stars">{formatStars(valoracion.estrellas)}</div>
                    </article>
                  ))}
                </div>
              )}
            </div>
          </div>

          <div className={`profile-photo-viewer-overlay ${photoViewerOpen ? 'open' : ''}`} onClick={() => setPhotoViewerOpen(false)}></div>
          <div className={`profile-photo-viewer ${photoViewerOpen ? 'open' : ''}`} aria-hidden={!photoViewerOpen}>
            <button type="button" className="profile-photo-viewer-close" aria-label="Cerrar vista de foto" onClick={() => setPhotoViewerOpen(false)}>&times;</button>
            <img src={normalizeImagePath(currentUser.foto_perfil)} alt="Foto de perfil ampliada" />
          </div>
        </>
      )}

      {children}

      {!isWelcomePage && (
      <footer className="footer">
        <div className="footer-container">
          <div className="footer-col footer-brand">
            <div className="brand-header">
              <h3>Veridi</h3>
              <div className="brand-divider"></div>
            </div>
            <p className="brand-description">Moda masculina moderna y exclusiva. Calidad, diseño e innovación en cada prenda.</p>
            <div className="brand-social-preview">
              <span className="social-label">Conecta con nosotros</span>
            </div>
          </div>

          <div className="footer-col footer-info">
            <div className="col-header">
              <h4>Información</h4>
              <div className="col-divider"></div>
            </div>
            <ul className="info-links">
              <li><Link to="/contacto" className="footer-link">📞 Contacto</Link></li>
              <li><Link to="/sobre-nosotros" className="footer-link">ℹ️ Sobre Nosotros</Link></li>
              <li><Link to="/politica" className="footer-link">🔒 Política de Privacidad</Link></li>
            </ul>
          </div>

          <div className="footer-col footer-social">
            <div className="col-header">
              <h4>Síguenos</h4>
              <div className="col-divider"></div>
            </div>
            <div className="redes">
              <a href="https://www.instagram.com" target="_blank" rel="noreferrer" className="social-link instagram" title="Síguenos en Instagram">
                <span className="icon">📷</span> Instagram
              </a>
              <a href="https://www.twitter.com" target="_blank" rel="noreferrer" className="social-link twitter" title="Síguenos en X">
                <span className="icon">𝕏</span> X (Twitter)
              </a>
              <a href="https://www.tiktok.com" target="_blank" rel="noreferrer" className="social-link tiktok" title="Síguenos en TikTok">
                <span className="icon">🎵</span> TikTok
              </a>
            </div>
          </div>
        </div>

        <div className="footer-divider"></div>

        <div className="footer-bottom">
          <p className="copyright">&copy; {new Date().getFullYear()} <span className="brand-name">Veridi</span> • Todos los derechos reservados</p>
          <p className="footer-tagline">Diseñado con lujo y precisión</p>
        </div>
      </footer>
      )}
    </>
  );
}

export default AppLayout;
