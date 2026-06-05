import { useEffect, useMemo, useRef, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import api, { buildBackendAssetUrl } from '../services/api';
import { useIsMobile } from '../utils/responsive';

const AI_STYLIST_STATE_KEY = 'veridi:ai-stylist-state';

function loadAiStylistState() {
  try {
    const raw = localStorage.getItem(AI_STYLIST_STATE_KEY);
    if (!raw) return {};
    const parsed = JSON.parse(raw);
    if (!parsed || typeof parsed !== 'object') return {};
    return parsed;
  } catch (error) {
    return {};
  }
}

const QUICK_PROMPTS = [
  'Quiero un outfit casual para diario',
  'Recomiendame un look para salir de noche',
  'Busco prendas deportivas comodas',
  'Quiero combinar colores neutros'
];

const SLOT_ORDER = ['top_main', 'top_layer', 'bottom', 'shoes', 'extra'];
const SLOT_LABELS = {
  top_main: 'Camiseta',
  top_layer: 'Capa superior (opcional)',
  bottom: 'Pantalon',
  shoes: 'Shoes',
  extra: 'Gorra (opcional)'
};

function getOpenAiStatusText(meta) {
  const status = String(meta?.openai_status || '');
  if (meta?.llm_used) {
    return { tone: 'ok', text: 'Recomendacion generada con OpenAI + validacion de catalogo.' };
  }
  if (status === 'openai_no_config') {
    return { tone: 'warn', text: 'OpenAI no esta configurado. Se uso el motor local de recomendaciones.' };
  }
  if (status === 'openai_no_curl') {
    return { tone: 'warn', text: 'No hay soporte cURL en PHP para OpenAI. Se uso el motor local de recomendaciones.' };
  }
  if (status === 'openai_fallback') {
    return { tone: 'warn', text: 'OpenAI no respondio en este intento. Se aplico fallback local automaticamente.' };
  }
  return { tone: 'warn', text: 'Se uso el motor local de recomendaciones para esta respuesta.' };
}

function AiStylistChat() {
  const location = useLocation();
  const navigate = useNavigate();
  const isMobile = useIsMobile();
  const handledSearchRef = useRef('');

  const persistedState = useMemo(() => loadAiStylistState(), []);

  const [open, setOpen] = useState(Boolean(persistedState.open));
  const [maximized, setMaximized] = useState(Boolean(persistedState.maximized));
  const [message, setMessage] = useState(String(persistedState.message || ''));
  const [presupuesto, setPresupuesto] = useState(String(persistedState.presupuesto || ''));
  const [loading, setLoading] = useState(false);
  const [addingOutfit, setAddingOutfit] = useState(false);
  const [addingProductId, setAddingProductId] = useState(0);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState(String(persistedState.notice || ''));
  const [result, setResult] = useState(
    persistedState.result && typeof persistedState.result === 'object' ? persistedState.result : null
  );

  const hasResult = useMemo(() => Boolean(result?.ok), [result]);
  const openAiStatus = useMemo(() => getOpenAiStatusText(result?.meta), [result?.meta]);

  const submitPrompt = async (promptText, options = {}) => {
    const text = String(promptText || '').trim();
    const baseProductId = Number(options.baseProductId || 0);
    if (!text && baseProductId <= 0) {
      setError('Escribe una consulta para el asistente.');
      return;
    }

    setLoading(true);
    setError('');
    setNotice('');

    try {
      const payload = {};
      if (text) {
        payload.message = text;
      }
      if (baseProductId > 0) {
        payload.base_product_id = baseProductId;
      }

      const budgetRaw = options.budgetOverride !== undefined ? options.budgetOverride : presupuesto;
      const budgetValue = Number(budgetRaw);
      if (Number.isFinite(budgetValue) && budgetValue > 0) {
        payload.presupuesto = budgetValue;
      }

      const response = await api.post('/php/api_ai_stylist.php', payload);
      const data = response?.data;

      if (!data?.ok) {
        setError(data?.message || 'No se pudo obtener recomendacion.');
        setResult(null);
        return;
      }

      setResult(data);
    } catch (e) {
      const backendMessage = e?.response?.data?.message;
      const backendMeta = e?.response?.data?.meta;
      const attempts = Number(backendMeta?.openai_attempts || 0);

      if (backendMessage) {
        if (attempts > 0) {
          setError(`${backendMessage} (intentos: ${attempts})`);
        } else {
          setError(backendMessage);
        }
      } else {
        setError('No se pudo obtener recomendacion.');
      }
      setResult(null);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    await submitPrompt(message);
  };

  const handleAddOutfitToCart = async () => {
    const slots = ['top_main', 'top_layer', 'bottom', 'shoes', 'extra'];
    const ids = [];

    slots.forEach((slot) => {
      const id = Number(result?.outfit?.[slot]?.id_producto || 0);
      if (id > 0 && !ids.includes(id)) {
        ids.push(id);
      }
    });

    if (ids.length === 0) {
      setError('No hay productos en el outfit para agregar al carrito.');
      return;
    }

    setAddingOutfit(true);
    setError('');
    setNotice('');

    try {
      const response = await api.post('/php/api_carrito.php', {
        action: 'add_outfit',
        product_ids: ids
      });

      const data = response?.data;
      if (!data?.ok) {
        if (data?.requiresLogin) {
          setError('Inicia sesion para agregar el outfit completo al carrito.');
          return;
        }
        setError(data?.message || 'No se pudo agregar el outfit al carrito.');
        return;
      }

      setNotice('Outfit agregado al carrito.');
    } catch (e) {
      setError('No se pudo agregar el outfit al carrito.');
    } finally {
      setAddingOutfit(false);
    }
  };

  const handleAddProductToCart = async (product) => {
    const productId = Number(product?.id_producto || 0);
    if (productId <= 0) {
      setError('Producto no valido para agregar al carrito.');
      return;
    }

    setAddingProductId(productId);
    setError('');
    setNotice('');

    try {
      const response = await api.post('/php/api_carrito.php', {
        action: 'add_outfit',
        product_ids: [productId]
      });

      const data = response?.data;
      if (!data?.ok) {
        if (data?.requiresLogin) {
          setError('Inicia sesion para agregar productos al carrito.');
          return;
        }
        setError(data?.message || 'No se pudo agregar el producto al carrito.');
        return;
      }

      setNotice(`${product.nombre || 'Producto'} agregado al carrito.`);
    } catch (e) {
      setError('No se pudo agregar el producto al carrito.');
    } finally {
      setAddingProductId(0);
    }
  };

  const handleRestoreAgent = async () => {
    setLoading(true);
    setError('');
    setNotice('');

    try {
      const response = await api.post('/php/api_ai_stylist.php', { action: 'reset' });
      const data = response?.data;

      setMessage('');
      setPresupuesto('');
      setResult(null);

      if (data?.ok) {
        setNotice('Asistente restaurado. Ya puedes pedir una recomendacion nueva.');
      } else {
        setNotice('Se limpio el chat local.');
      }
    } catch (e) {
      setMessage('');
      setPresupuesto('');
      setResult(null);
      setNotice('Se limpio el chat local.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    try {
      localStorage.setItem(
        AI_STYLIST_STATE_KEY,
        JSON.stringify({
          open,
          maximized,
          message,
          presupuesto,
          notice,
          result
        })
      );
    } catch (storageError) {
      // Ignore storage failures (quota/private mode).
    }
  }, [open, maximized, message, presupuesto, notice, result]);

  useEffect(() => {
    const onEsc = (event) => {
      if (event.key === 'Escape') {
        setOpen(false);
        setMaximized(false);
      }
    };
    window.addEventListener('keydown', onEsc);
    return () => window.removeEventListener('keydown', onEsc);
  }, []);

  useEffect(() => {
    const handleOpenFromProduct = (event) => {
      const detail = event?.detail || {};
      const baseProductId = Number(detail.baseProductId || 0);
      const messageText = String(detail.message || '').trim();
      const budgetText = String(detail.presupuesto || '').trim();

      setOpen(true);

      if (budgetText) {
        setPresupuesto(budgetText);
      }

      const finalMessage = messageText || (baseProductId > 0 ? 'Quiero un outfit con esta prenda.' : '');
      if (finalMessage) {
        setMessage(finalMessage);
      }

      if (baseProductId > 0 || finalMessage) {
        submitPrompt(finalMessage, {
          baseProductId,
          budgetOverride: budgetText
        });
      }
    };

    window.addEventListener('veridi:open-ai-outfit', handleOpenFromProduct);
    return () => window.removeEventListener('veridi:open-ai-outfit', handleOpenFromProduct);
  }, []);

  useEffect(() => {
    const search = String(location.search || '');
    if (!search) {
      handledSearchRef.current = '';
      return;
    }

    if (handledSearchRef.current === search) {
      return;
    }

    const params = new URLSearchParams(search);
    const aiOpen = params.get('ai') === 'open';
    const baseProductId = Number(params.get('base_product_id') || 0);
    const messageParam = String(params.get('message') || '').trim();
    const presupuestoParam = String(params.get('presupuesto') || '').trim();

    if (!aiOpen && baseProductId <= 0 && !messageParam) {
      handledSearchRef.current = search;
      return;
    }

    handledSearchRef.current = search;
    setOpen(true);

    if (presupuestoParam) {
      setPresupuesto(presupuestoParam);
    }

    const finalMessage = messageParam || (baseProductId > 0 ? 'Quiero un outfit con esta prenda.' : '');
    if (finalMessage) {
      setMessage(finalMessage);
    }

    if (baseProductId > 0 || finalMessage) {
      submitPrompt(finalMessage, {
        baseProductId,
        budgetOverride: presupuestoParam
      });
    }

    navigate(location.pathname, { replace: true });
  }, [location.pathname, location.search, navigate]);

  return (
    <div className="ai-stylist-widget" aria-label="Asistente de estilo Veridi">
      <button
        type="button"
        className="ai-stylist-fab"
        onClick={() => {
          setOpen((prev) => {
            const next = !prev;
            if (!next) {
              setMaximized(false);
            }
            return next;
          });
        }}
        aria-label={open ? 'Cerrar asistente IA' : 'Abrir asistente IA'}
        title={open ? 'Cerrar asistente IA' : 'Abrir asistente IA'}
      >
        <span className="ai-stylist-fab-icon" aria-hidden="true">◉</span>
        <span className="ai-stylist-fab-text">{isMobile ? 'AI' : 'IA'}</span>
      </button>

      <section className={`ai-stylist-panel ${open ? 'open' : ''} ${maximized ? 'maximized' : ''}`}>
        <div className="ai-stylist-header">
          <p className="ai-stylist-kicker">Asistente Veridi</p>
          <h3>Stylist IA</h3>
          <p>
            Pideme recomendaciones y te sugiero outfits con productos reales del catalogo.
          </p>
          <button
            type="button"
            className="ai-stylist-maximize"
            onClick={() => setMaximized((prev) => !prev)}
            aria-label={maximized ? 'Restaurar tamano del chat' : 'Maximizar chat'}
            title={maximized ? 'Restaurar tamano' : 'Maximizar'}
          >
            {maximized ? '↙' : '↗'}
          </button>
          <button
            type="button"
            className="ai-stylist-close"
            onClick={() => {
              setOpen(false);
              setMaximized(false);
            }}
            aria-label="Cerrar chat"
          >
            x
          </button>
        </div>

        <div className="ai-stylist-secondary-actions">
          <button type="button" className="ai-stylist-reset" onClick={handleRestoreAgent} disabled={loading}>
            Restaurar agente
          </button>
        </div>

        <form className="ai-stylist-form" onSubmit={handleSubmit}>
          <label htmlFor="ai-stylist-message">Tu consulta</label>
          <textarea
            id="ai-stylist-message"
            rows="3"
            placeholder="Ej: Quiero un outfit casual para salir por la tarde"
            value={message}
            onChange={(event) => setMessage(event.target.value)}
          />

          <div className="ai-stylist-form-row">
            <div className="ai-stylist-budget">
              <label htmlFor="ai-stylist-budget">Presupuesto maximo (opcional)</label>
              <input
                id="ai-stylist-budget"
                type="number"
                min="0"
                step="1"
                placeholder="Ej: 80"
                value={presupuesto}
                onChange={(event) => setPresupuesto(event.target.value)}
              />
            </div>

            <button type="submit" disabled={loading}>
              {loading ? 'Pensando...' : 'Pedir recomendacion'}
            </button>
          </div>

        </form>

        <div className="ai-stylist-quick-prompts">
          {QUICK_PROMPTS.map((prompt) => (
            <button
              key={prompt}
              type="button"
              disabled={loading}
              onClick={() => {
                setMessage(prompt);
                submitPrompt(prompt);
              }}
            >
              {prompt}
            </button>
          ))}
        </div>

        {error && <p className="ai-stylist-error">{error}</p>}
        {notice && <p className="ai-stylist-notice">{notice}</p>}

        {hasResult && (
          <div className="ai-stylist-result">
            <div className="ai-stylist-reply">{result.reply_text}</div>
            <p className={`ai-stylist-engine ${openAiStatus.tone}`}>{openAiStatus.text}</p>

            <div className="ai-stylist-actions">
              <button
                type="button"
                className="ai-stylist-add-outfit"
                onClick={handleAddOutfitToCart}
                disabled={addingOutfit}
              >
                {addingOutfit ? 'Agregando...' : 'Agregar outfit al carrito'}
              </button>
            </div>

            <div className="ai-stylist-outfit">
              <h4>Outfit sugerido</h4>
              <ul>
                {SLOT_ORDER.map((slot) => {
                  const product = slot === 'bottom'
                    ? (result?.outfit?.bottom || result?.outfit?.pantalon)
                    : result?.outfit?.[slot];
                  const reason = result?.outfit_reasons?.[slot];

                  return (
                    <li key={slot}>
                      <strong>{SLOT_LABELS[slot]}:</strong> {product?.nombre || 'Sin sugerencia'}
                      {product && reason && (
                        <div className="ai-stylist-why">
                          <p><strong>Estilo:</strong> {reason.style}</p>
                          <p><strong>Color:</strong> {reason.color}</p>
                          <p><strong>Presupuesto:</strong> {reason.budget}</p>
                        </div>
                      )}
                    </li>
                  );
                })}
              </ul>
            </div>

            <div className="ai-stylist-products">
              {(result.recommended_products || []).map((product) => (
                <article key={product.id_producto} className="ai-stylist-product-card">
                  <img src={buildBackendAssetUrl(product.imagen)} alt={product.nombre} />
                  <div>
                    <h5>{product.nombre}</h5>
                    <p>{Number(product.precio || 0).toFixed(2)} EUR</p>
                    <button
                      type="button"
                      className="ai-stylist-product-add-btn"
                      onClick={() => handleAddProductToCart(product)}
                      disabled={addingProductId === Number(product.id_producto)}
                    >
                      {addingProductId === Number(product.id_producto) ? 'Agregando...' : 'Agregar al carrito'}
                    </button>
                  </div>
                </article>
              ))}
            </div>
          </div>
        )}
      </section>

      {open && <div className="ai-stylist-backdrop" onClick={() => setOpen(false)} aria-hidden="true" />}
    </div>
  );
}

export default AiStylistChat;
