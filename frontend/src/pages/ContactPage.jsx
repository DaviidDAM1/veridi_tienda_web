import { useEffect, useState } from 'react';
import api from '../services/api';
import { openAuthPanel } from '../utils/auth';

function ContactPage() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [contactoInfo, setContactoInfo] = useState({
    email_web: 'info@veridi.com',
    logueado: false,
    email_usuario: '',
    nombre_usuario: ''
  });

  const [form, setForm] = useState({
    nombre: '',
    email: '',
    contrasena: '',
    tipo: '',
    mensaje: ''
  });

  const loadContacto = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get('/php/api_contacto.php');
      const data = response.data;

      if (!data?.ok) {
        throw new Error(data?.message || 'No se pudo cargar la página de contacto.');
      }

      const info = data.contacto || {};
      setContactoInfo(info);
      setForm((prev) => ({
        ...prev,
        nombre: info.nombre_usuario || '',
        email: info.email_usuario || ''
      }));
    } catch (err) {
      setError('No se pudo cargar la información de contacto.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadContacto();
  }, []);

  const handleChange = (field, value) => {
    setForm((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setSuccess('');
    setError('');

    try {
      const response = await api.post('/php/api_contacto.php', form);
      const data = response.data;

      if (!data?.ok) {
        setError(data?.message || 'No se pudo enviar el mensaje.');
        return;
      }

      setSuccess(data.message || 'Mensaje enviado correctamente.');
      setForm((prev) => ({ ...prev, contrasena: '', tipo: '', mensaje: '' }));
    } catch (err) {
      setError('No se pudo enviar el mensaje.');
    }
  };

  if (loading) {
    return (
      <main>
        <section className="contacto-page">
          <div className="contacto-container">
            <p style={{ color: 'var(--veridi-text-secondary)' }}>Cargando contacto...</p>
          </div>
        </section>
      </main>
    );
  }

  const logueado = Boolean(contactoInfo.logueado);

  return (
    <main>
      <section className="contacto-page">
        <div className="contacto-intro">
          <h1>Contacto</h1>
          <p className="intro-text">
            Bienvenido a Veridi. Nos encantaría saber de ti. Si tienes alguna pregunta, sugerencia o necesitas ayuda,
            no dudes en ponerte en contacto con nosotros. Nuestro equipo está aquí para ayudarte.
          </p>
          <p className="email-info">
            <strong>📧 Email de contacto:</strong>{' '}
            <a href={`mailto:${contactoInfo.email_web || 'info@veridi.com'}`} className="email-link">{contactoInfo.email_web || 'info@veridi.com'}</a>
          </p>
        </div>

        {success && <div className="success-message">{success}</div>}
        {error && <div className="error-message">{error}</div>}

        <div className="contacto-container">
          <div className="form-wrapper">
            <h2>Enviar un mensaje</h2>

            {!logueado && (
              <div className="info-message" style={{ background: 'rgba(212, 175, 55, 0.1)', border: '2px solid var(--veridi-gold)', padding: 15, borderRadius: 8, marginBottom: 20, textAlign: 'center' }}>
                🔒 <strong>Debes iniciar sesión para enviar mensajes.</strong><br />
                Usa el botón <strong>Login / Registro</strong> del encabezado.
                <div style={{ marginTop: 8 }}>
                  <button type="button" onClick={() => openAuthPanel('login')} style={{ color: 'var(--veridi-gold)', background: 'transparent', border: 0, cursor: 'pointer', padding: 0, font: 'inherit' }}>
                    Ir a iniciar sesión
                  </button>
                </div>
              </div>
            )}

            <form onSubmit={handleSubmit} className="form-contacto">
              <div className="form-group">
                <label htmlFor="nombre">Nombre <span className="required">*</span></label>
                {logueado && form.nombre ? (
                  <>
                    <input type="text" id="nombre" name="nombre" value={form.nombre} readOnly className="email-readonly" />
                    <small className="form-info">Tu nombre de cuenta</small>
                  </>
                ) : (
                  <input type="text" id="nombre" name="nombre" placeholder="Debes iniciar sesión" disabled style={{ opacity: 0.5, cursor: 'not-allowed' }} />
                )}
              </div>

              <div className="form-group">
                <label htmlFor="email">Email <span className="required">*</span></label>
                {logueado && form.email ? (
                  <>
                    <input type="email" id="email" name="email" value={form.email} readOnly className="email-readonly" />
                    <small className="form-info">Este es tu email de cuenta verificado</small>
                  </>
                ) : (
                  <input type="email" id="email" name="email" placeholder="Debes iniciar sesión" disabled style={{ opacity: 0.5, cursor: 'not-allowed' }} />
                )}
              </div>

              <div className="form-group">
                <label htmlFor="contrasena">Contraseña de tu correo <span className="required">*</span></label>
                <input
                  type="password"
                  id="contrasena"
                  name="contrasena"
                  value={form.contrasena}
                  onChange={(e) => handleChange('contrasena', e.target.value)}
                  placeholder={logueado ? 'Ingresa tu contraseña' : 'Debes iniciar sesión'}
                  disabled={!logueado}
                  required={logueado}
                  style={!logueado ? { opacity: 0.5, cursor: 'not-allowed' } : undefined}
                />
                {logueado && <small className="form-info">Mínimo 6 caracteres</small>}
              </div>

              <div className="form-group">
                <label htmlFor="tipo">Tipo de asunto <span className="required">*</span></label>
                <select
                  id="tipo"
                  name="tipo"
                  value={form.tipo}
                  onChange={(e) => handleChange('tipo', e.target.value)}
                  disabled={!logueado}
                  required={logueado}
                  style={!logueado ? { opacity: 0.5, cursor: 'not-allowed' } : undefined}
                >
                  <option value="">-- {logueado ? 'Selecciona un asunto' : 'Debes iniciar sesión'} --</option>
                  {logueado && (
                    <>
                      <option value="consulta">Consulta</option>
                      <option value="queja">Queja</option>
                      <option value="reclamacion">Reclamación</option>
                      <option value="otro">Otro</option>
                    </>
                  )}
                </select>
              </div>

              <div className="form-group">
                <label htmlFor="mensaje">Mensaje <span className="required">*</span></label>
                <textarea
                  id="mensaje"
                  name="mensaje"
                  rows="6"
                  value={form.mensaje}
                  onChange={(e) => handleChange('mensaje', e.target.value)}
                  placeholder={logueado ? 'Escribe tu mensaje aquí...' : 'Debes iniciar sesión para enviar mensajes'}
                  disabled={!logueado}
                  required={logueado}
                  style={!logueado ? { opacity: 0.5, cursor: 'not-allowed' } : undefined}
                ></textarea>
              </div>

              <button
                type="submit"
                className="btn-enviar"
                disabled={!logueado}
                style={!logueado ? { opacity: 0.5, cursor: 'not-allowed', background: '#666' } : undefined}
              >
                {logueado ? 'Enviar mensaje' : '🔒 Inicia sesión para enviar'}
              </button>
            </form>
          </div>
        </div>
      </section>
    </main>
  );
}

export default ContactPage;
