import { useEffect, useState } from 'react';
import api from '../services/api';
import { openAuthPanel } from '../utils/auth';

const initialCreate = {
  nombre: '',
  descripcion: '',
  precio: '',
  color: '',
  estilo: 'casual',
  material: '',
  id_categoria: '',
  stock_inicial: 0
};

const initialEdit = {
  id_producto: '',
  nombre: '',
  descripcion: '',
  precio: '',
  color: '',
  estilo: 'casual',
  material: '',
  id_categoria: ''
};

const initialDelete = { id_producto: '' };
const initialStock = { id_producto: '', id_talla: '', delta: '' };

function AdminPage() {
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [requiresLogin, setRequiresLogin] = useState(false);
  const [requiresAdmin, setRequiresAdmin] = useState(false);

  const [categorias, setCategorias] = useState([]);
  const [tallas, setTallas] = useState([]);
  const [productos, setProductos] = useState([]);
  const [usuarios, setUsuarios] = useState([]);

  const [createForm, setCreateForm] = useState(initialCreate);
  const [editForm, setEditForm] = useState(initialEdit);
  const [deleteForm, setDeleteForm] = useState(initialDelete);
  const [stockForm, setStockForm] = useState(initialStock);

  const loadData = async () => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get('/php/api_admin.php');
      const data = response.data;

      if (data?.requiresLogin) {
        setRequiresLogin(true);
        setRequiresAdmin(false);
        return;
      }

      if (data?.requiresAdmin) {
        setRequiresAdmin(true);
        setRequiresLogin(false);
        return;
      }

      if (!data?.ok) {
        setError(data?.message || 'No se pudo cargar el panel administrador.');
        return;
      }

      setRequiresLogin(false);
      setRequiresAdmin(false);
      const payload = data.data || {};
      setCategorias(payload.categorias || []);
      setTallas(payload.tallas || []);
      setProductos(payload.productos || []);
      setUsuarios(payload.usuarios || []);
      setStockForm((prev) => ({ ...prev, id_talla: prev.id_talla || String(payload.tallas?.[0]?.id_talla || '') }));
    } catch (err) {
      const status = err?.response?.status;
      const server = err?.response?.data;
      if (status === 401 || server?.requiresLogin) {
        setRequiresLogin(true);
      } else if (status === 403 || server?.requiresAdmin) {
        setRequiresAdmin(true);
      } else {
        setError('No se pudo cargar el panel administrador.');
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const submitAction = async (payload, clearMode = '') => {
    setSubmitting(true);
    setError('');
    setSuccess('');
    try {
      const response = await api.post('/php/api_admin.php', payload);
      const data = response.data;

      if (!data?.ok) {
        setError(data?.message || 'No se pudo procesar la acción.');
        return;
      }

      setSuccess(data.message || 'Acción ejecutada correctamente.');

      const next = data.data || {};
      setCategorias(next.categorias || []);
      setTallas(next.tallas || []);
      setProductos(next.productos || []);
      setUsuarios(next.usuarios || []);

      if (clearMode === 'create') setCreateForm(initialCreate);
      if (clearMode === 'edit') setEditForm(initialEdit);
      if (clearMode === 'delete') setDeleteForm(initialDelete);
      if (clearMode === 'stock') setStockForm((prev) => ({ ...initialStock, id_talla: prev.id_talla || '' }));
    } catch (err) {
      setError('No se pudo procesar la acción.');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <main>
        <section className="producto-detalle-container" style={{ maxWidth: 1300 }}>
          <h1>Panel de Administrador</h1>
          <p>Cargando panel...</p>
        </section>
      </main>
    );
  }

  if (requiresLogin) {
    return (
      <main>
        <section className="producto-detalle-container" style={{ maxWidth: 1300 }}>
          <h1>Panel de Administrador</h1>
          <div className="error-message">Debes iniciar sesión para acceder al panel.</div>
          <button type="button" className="btn-ver" onClick={() => openAuthPanel('login')}>Iniciar sesión</button>
        </section>
      </main>
    );
  }

  if (requiresAdmin) {
    return (
      <main>
        <section className="producto-detalle-container" style={{ maxWidth: 1300 }}>
          <h1>Panel de Administrador</h1>
          <div className="error-message">No tienes permisos de administrador.</div>
        </section>
      </main>
    );
  }

  return (
    <main>
      <section className="producto-detalle-container" style={{ maxWidth: 1300 }}>
        <h1 style={{ marginBottom: 8 }}>Panel de Administrador</h1>
        <p style={{ color: 'var(--veridi-text-secondary)', marginBottom: 20 }}>Gestiona productos, stock, visibilidad y usuarios registrados.</p>

        {success && <div className="success-message" style={{ marginBottom: 20 }}>{success}</div>}
        {error && <div className="error-message" style={{ marginBottom: 20 }}>{error}</div>}

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: 16, marginBottom: 24 }}>
          <div style={{ background: 'var(--veridi-surface)', border: '1px solid var(--veridi-border)', borderRadius: 10, padding: 16 }}>
            <h3 style={{ fontSize: 18 }}>Crear producto</h3>
            <form onSubmit={(e) => { e.preventDefault(); submitAction({ action: 'create_product', ...createForm }, 'create'); }} style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
              <input type="text" value={createForm.nombre} onChange={(e) => setCreateForm((p) => ({ ...p, nombre: e.target.value }))} placeholder="Nombre" required />
              <textarea rows="3" value={createForm.descripcion} onChange={(e) => setCreateForm((p) => ({ ...p, descripcion: e.target.value }))} placeholder="Descripción"></textarea>
              <input type="number" step="0.01" min="0.01" value={createForm.precio} onChange={(e) => setCreateForm((p) => ({ ...p, precio: e.target.value }))} placeholder="Precio" required />
              <input type="text" value={createForm.color} onChange={(e) => setCreateForm((p) => ({ ...p, color: e.target.value }))} placeholder="Color" />
              <select value={createForm.estilo} onChange={(e) => setCreateForm((p) => ({ ...p, estilo: e.target.value }))} required>
                <option value="casual">Casual</option>
                <option value="formal">Formal</option>
                <option value="deportivo">Deportivo</option>
              </select>
              <input type="text" value={createForm.material} onChange={(e) => setCreateForm((p) => ({ ...p, material: e.target.value }))} placeholder="Material" />
              <select value={createForm.id_categoria} onChange={(e) => setCreateForm((p) => ({ ...p, id_categoria: e.target.value }))} required>
                <option value="">Selecciona categoría</option>
                {categorias.map((categoria) => (
                  <option key={categoria.id_categoria} value={categoria.id_categoria}>{categoria.nombre}</option>
                ))}
              </select>
              <input type="number" min="0" value={createForm.stock_inicial} onChange={(e) => setCreateForm((p) => ({ ...p, stock_inicial: e.target.value }))} placeholder="Stock inicial por talla (ej: 20)" />
              <button type="submit" className="profile-save-btn" disabled={submitting}>{submitting ? 'Procesando...' : 'Crear producto'}</button>
            </form>
          </div>

          <div style={{ background: 'var(--veridi-surface)', border: '1px solid var(--veridi-border)', borderRadius: 10, padding: 16 }}>
            <h3 style={{ fontSize: 18 }}>Editar producto</h3>
            <form onSubmit={(e) => { e.preventDefault(); submitAction({ action: 'edit_product', ...editForm }, 'edit'); }} style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
              <input type="number" min="1" value={editForm.id_producto} onChange={(e) => setEditForm((p) => ({ ...p, id_producto: e.target.value }))} placeholder="ID producto" required />
              <input type="text" value={editForm.nombre} onChange={(e) => setEditForm((p) => ({ ...p, nombre: e.target.value }))} placeholder="Nuevo nombre" required />
              <textarea rows="3" value={editForm.descripcion} onChange={(e) => setEditForm((p) => ({ ...p, descripcion: e.target.value }))} placeholder="Nueva descripción"></textarea>
              <input type="number" step="0.01" min="0.01" value={editForm.precio} onChange={(e) => setEditForm((p) => ({ ...p, precio: e.target.value }))} placeholder="Nuevo precio" required />
              <input type="text" value={editForm.color} onChange={(e) => setEditForm((p) => ({ ...p, color: e.target.value }))} placeholder="Color" />
              <select value={editForm.estilo} onChange={(e) => setEditForm((p) => ({ ...p, estilo: e.target.value }))} required>
                <option value="casual">Casual</option>
                <option value="formal">Formal</option>
                <option value="deportivo">Deportivo</option>
              </select>
              <input type="text" value={editForm.material} onChange={(e) => setEditForm((p) => ({ ...p, material: e.target.value }))} placeholder="Material" />
              <select value={editForm.id_categoria} onChange={(e) => setEditForm((p) => ({ ...p, id_categoria: e.target.value }))} required>
                <option value="">Selecciona categoría</option>
                {categorias.map((categoria) => (
                  <option key={categoria.id_categoria} value={categoria.id_categoria}>{categoria.nombre}</option>
                ))}
              </select>
              <button type="submit" className="profile-save-btn" disabled={submitting}>{submitting ? 'Procesando...' : 'Guardar edición'}</button>
            </form>
          </div>

          <div style={{ background: 'var(--veridi-surface)', border: '1px solid var(--veridi-border)', borderRadius: 10, padding: 16 }}>
            <h3 style={{ fontSize: 18 }}>Eliminar producto</h3>
            <form onSubmit={(e) => { e.preventDefault(); submitAction({ action: 'delete_product', ...deleteForm }, 'delete'); }} style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
              <input type="number" min="1" value={deleteForm.id_producto} onChange={(e) => setDeleteForm({ id_producto: e.target.value })} placeholder="ID producto" required />
              <button type="submit" className="profile-save-btn" style={{ background: 'linear-gradient(135deg, #8f2323 0%, #d32f2f 100%)', color: '#fff' }} disabled={submitting}>
                {submitting ? 'Procesando...' : 'Eliminar producto'}
              </button>
            </form>
          </div>
        </div>

        <h2 style={{ fontSize: 22, marginTop: 20 }}>Productos y stock</h2>
        <div style={{ overflowX: 'auto', marginBottom: 24, border: '1px solid var(--veridi-border)', borderRadius: 10 }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 1020 }}>
            <thead>
              <tr style={{ background: 'rgba(212,175,55,0.15)' }}>
                <th style={{ padding: 10, textAlign: 'left' }}>ID</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Nombre</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Categoría</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Precio</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Estilo</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Stock total</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Estado</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Ocultar / mostrar</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Ajustar stock</th>
              </tr>
            </thead>
            <tbody>
              {productos.map((producto) => (
                <tr key={producto.id_producto} style={{ borderTop: '1px solid var(--veridi-border)' }}>
                  <td style={{ padding: 10 }}>{producto.id_producto}</td>
                  <td style={{ padding: 10 }}>{producto.nombre}</td>
                  <td style={{ padding: 10 }}>{producto.categoria || 'Sin categoría'}</td>
                  <td style={{ padding: 10 }}>€{Number(producto.precio).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                  <td style={{ padding: 10 }}>{String(producto.estilo || '').charAt(0).toUpperCase() + String(producto.estilo || '').slice(1)}</td>
                  <td style={{ padding: 10 }}>{producto.stock_total}</td>
                  <td style={{ padding: 10 }}>{Number(producto.oculto) === 1 ? 'Oculto' : 'Visible'}</td>
                  <td style={{ padding: 10 }}>
                    <button
                      type="button"
                      onClick={() => submitAction({ action: 'toggle_hide', id_producto: producto.id_producto, oculto: Number(producto.oculto) === 1 ? 0 : 1 })}
                      style={{ padding: '6px 10px', borderRadius: 6, border: '1px solid var(--veridi-gold)', background: 'transparent', color: 'var(--veridi-gold)', cursor: 'pointer' }}
                      disabled={submitting}
                    >
                      {Number(producto.oculto) === 1 ? 'Mostrar' : 'Ocultar'}
                    </button>
                  </td>
                  <td style={{ padding: 10 }}>
                    <form
                      onSubmit={(e) => {
                        e.preventDefault();
                        submitAction({ action: 'adjust_stock', id_producto: stockForm.id_producto || producto.id_producto, id_talla: stockForm.id_talla, delta: stockForm.delta }, 'stock');
                      }}
                      style={{ display: 'flex', gap: 6, alignItems: 'center' }}
                    >
                      <input type="hidden" value={stockForm.id_producto || producto.id_producto} />
                      <select name="id_talla" required value={stockForm.id_talla} onChange={(e) => setStockForm((p) => ({ ...p, id_producto: producto.id_producto, id_talla: e.target.value }))} style={{ padding: 5 }}>
                        {tallas.map((talla) => (
                          <option key={talla.id_talla} value={talla.id_talla}>{talla.nombre}</option>
                        ))}
                      </select>
                      <input
                        type="number"
                        required
                        placeholder="+/-"
                        value={stockForm.id_producto === producto.id_producto ? stockForm.delta : ''}
                        onChange={(e) => setStockForm((p) => ({ ...p, id_producto: producto.id_producto, delta: e.target.value }))}
                        style={{ width: 72, padding: 5 }}
                        title="Usa positivo para sumar y negativo para restar"
                      />
                      <button type="submit" style={{ padding: '6px 10px', borderRadius: 6, border: '1px solid var(--veridi-gold)', background: 'var(--veridi-gold)', color: 'var(--veridi-black)', cursor: 'pointer' }} disabled={submitting}>
                        Aplicar
                      </button>
                    </form>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <h2 style={{ fontSize: 22 }}>Usuarios registrados</h2>
        <div style={{ overflowX: 'auto', border: '1px solid var(--veridi-border)', borderRadius: 10 }}>
          <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: 900 }}>
            <thead>
              <tr style={{ background: 'rgba(212,175,55,0.15)' }}>
                <th style={{ padding: 10, textAlign: 'left' }}>ID</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Nombre</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Email</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Rol</th>
                <th style={{ padding: 10, textAlign: 'left' }}>Password (hash)</th>
              </tr>
            </thead>
            <tbody>
              {usuarios.map((usuario) => (
                <tr key={usuario.id_usuario} style={{ borderTop: '1px solid var(--veridi-border)' }}>
                  <td style={{ padding: 10 }}>{usuario.id_usuario}</td>
                  <td style={{ padding: 10 }}>{usuario.nombre}</td>
                  <td style={{ padding: 10 }}>{usuario.email}</td>
                  <td style={{ padding: 10 }}>{usuario.rol}</td>
                  <td style={{ padding: 10, fontFamily: 'Consolas, monospace', fontSize: 12, wordBreak: 'break-all' }}>{usuario.password}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </main>
  );
}

export default AdminPage;
