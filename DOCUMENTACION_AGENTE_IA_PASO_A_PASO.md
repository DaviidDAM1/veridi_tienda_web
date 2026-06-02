# DOCUMENTACION AGENTE IA VERIDI (PASO A PASO)

## 1. Objetivo del Agente IA

El objetivo fue construir un asistente de estilo dentro de la tienda de Veridi que:

- Reciba una peticion en lenguaje natural del usuario.
- Genere un outfit con productos reales del catalogo.
- Respete reglas de negocio (slots obligatorios/opcionales).
- Permita flujo completo en la web (ver productos, usar presupuesto, agregar al carrito).


## 2. Arquitectura elegida

Se implemento una arquitectura simple y robusta:

- Backend PHP: logica del agente en un endpoint dedicado.
- Frontend React: widget flotante de chat en la pagina de tienda.
- Base de datos MySQL: fuente unica de productos reales.

Piezas principales:

- Endpoint IA: php/api_ai_stylist.php
- Widget IA: frontend/src/components/AiStylistChat.jsx
- Integracion en tienda: frontend/src/pages/TiendaPage.jsx
- Estilos del widget: css/styles.css


## 3. Paso a paso de implementacion

### Paso 1: Crear el endpoint base del agente

Se creo el endpoint php/api_ai_stylist.php para recibir consultas y responder en JSON.

Entrada inicial:

- message (texto del usuario)
- base_product_id (opcional)
- presupuesto (opcional)

Salida inicial:

- reply_text
- recommended_products
- outfit
- meta

Resultado: ya habia un punto unico para encapsular toda la logica de recomendacion.


### Paso 2: Implementar motor MVP heuristico

Antes de conectar OpenAI, se construyo un motor local (heuristico) para garantizar funcionamiento minimo aunque falle el modelo externo.

Se implemento:

- Deteccion de estilo desde el mensaje (casual, formal, deportivo).
- Deteccion de color desde el mensaje.
- Scoring de productos por estilo/color/precio.
- Seleccion de prendas por categorias permitidas.

Resultado: el agente funcionaba sin depender de APIs externas.


### Paso 3: Definir estructura de outfit por slots

Se estandarizo el outfit en slots para controlar reglas de negocio:

- top_main
- top_layer
- bottom (alias para UI: pantalon)
- shoes
- extra

Reglas acordadas:

- Obligatorios (si presupuesto lo permite): top_main, bottom/pantalon, shoes.
- Opcionales: top_layer y extra (gorra).

Resultado: salida consistente y mas facil de pintar en frontend.


### Paso 4: Construir UI del chat en React

Se creo AiStylistChat.jsx y se monto en la pagina de tienda.

Primera version:

- Panel visible con formulario.
- Envio de prompt al backend.
- Render de respuesta y productos sugeridos.

Evolucion:

- Se transformo en widget flotante con boton FAB.
- Popup con backdrop y cierre por ESC.
- Atajos de prompts rapidos.

Resultado: UX mas limpia y usable en desktop/mobile.


### Paso 5: Integrar flujo desde detalle de producto

Se anadio boton en detalle de producto para pedir:

- "Crear outfit con esta prenda"

El chat acepta parametros por URL (ai=open, base_product_id, message, presupuesto), se abre y auto-lanza la consulta.

Resultado: flujo contextual y mas natural para el usuario.


### Paso 6: Integrar OpenAI con fallback seguro

Se conecto OpenAI de forma opcional sin romper el MVP:

- Si hay API key valida -> intenta usar LLM.
- Si falla (sin key/error API/timeout) -> fallback heuristico.

Se anadieron helpers:

- lectura de key local/env
- llamada a chat completions
- saneado de salida del modelo

Resultado: mejor calidad de recomendacion con resiliencia en produccion.


### Paso 7: Añadir metadatos de depuracion y control

Para diagnosticar rapido se incorporaron campos meta:

- llm_used
- mvp_mode
- openai_configured
- curl_available
- outfit_total
- budget_respected
- budget_adjusted
- mandatory_required
- diversified

Resultado: observabilidad clara para pruebas y soporte.


### Paso 8: Forzar presupuesto por total de outfit

Problema detectado:

- El presupuesto se aplicaba por prenda, no por total del conjunto.

Solucion aplicada:

- Normalizacion posterior del outfit para ajustar el total global.
- Reemplazo por alternativas mas baratas.
- Eliminacion priorizada de slots opcionales si hace falta.

Resultado:

- El total del outfit respeta mejor el limite indicado.


### Paso 9: Hacer obligatorios camiseta + pantalon + calzado (si se puede)

Problema detectado:

- En ciertos casos faltaba calzado aunque negocio lo queria obligatorio.

Solucion aplicada:

- Calculo del minimo posible obligatorio.
- Si el presupuesto permite ese minimo, se obliga presencia de slots obligatorios.
- Si no permite, se devuelve mejor alternativa y se marca en meta.

Resultado:

- Comportamiento alineado con reglas de negocio y transparente para el usuario.


### Paso 10: Evitar outfits repetidos (variedad)

Problema detectado:

- Mismo prompt repetido devolvia siempre el mismo outfit.

Solucion aplicada:

- Historial de firmas de outfits recientes en sesion.
- Diversificacion controlada por slots con alternativas validas.
- Variacion adicional en llamada al modelo (temperature + hint).

Resultado:

- Misma intencion, recomendaciones diferentes pero coherentes.


### Paso 11: Añadir accion "Restaurar agente"

Necesidad funcional:

- Reiniciar el asistente para empezar una nueva consulta limpia.

Solucion aplicada:

- Boton "Restaurar agente" en UI.
- Limpia estado local del widget.
- Reinicia historial de diversidad en backend mediante action=reset.

Resultado:

- UX mas controlada para demos y pruebas repetidas.


### Paso 12: Mejoras de experiencia de uso

Mejoras finales en interfaz:

- Boton para maximizar chat.
- Ajuste para mostrar panel maximizado centrado.
- Etiqueta de outfit orientada a usuario: "Pantalon" en vez de "Bottom".

Resultado:

- Interfaz mas clara y presentable.


## 4. Problemas reales encontrados y resolucion

### Problema A: "Metodo no permitido"

Causa:

- El endpoint solo aceptaba un metodo en etapas iniciales.

Solucion:

- Se habilitaron GET/POST/OPTIONS correctamente.


### Problema B: Respuesta rota por warnings PHP

Causa:

- Variables no definidas en el endpoint generaban warnings y rompian JSON.

Solucion:

- Limpieza de bloques obsoletos y normalizacion de flujo.
- Ajuste de includes con __DIR__ para rutas robustas.


### Problema C: OpenAI no se usaba aunque habia key

Causa:

- Archivo de config local vacio/no cargado en runtime.

Solucion:

- Verificacion de lectura real de config.
- Reconfiguracion de key local y metadatos de estado.


### Problema D: Presupuesto no respetado

Causa:

- Filtro por precio individual, no por suma del outfit.

Solucion:

- Algoritmo de normalizacion por total final del conjunto.


### Problema E: Recomendaciones repetidas

Causa:

- Seleccion determinista con mismo input.

Solucion:

- Diversificacion con historial de firmas + variantes por slot.


### Problema F: Boton "Crear outfit con esta prenda" no siempre abria el flujo

Causa:

- El boton del detalle de producto dependia de un evento global custom para abrir el chat IA.
- En algunos escenarios de navegacion ese evento no resolvia el flujo completo de forma fiable.

Solucion:

- Se cambio el flujo a navegacion directa a la ruta de tienda con query params:
	- ai=open
	- base_product_id
	- message
- Con esto, el chat se abre y auto-lanza la recomendacion de forma determinista.


### Problema G: Maximizar chat no se veia bien

Causa:

- El panel maximizado quedaba anclado lateralmente y no siempre aprovechaba bien el viewport.

Solucion:

- Se redefinio el modo maximizado como modal centrado.
- Se ajustaron ancho/alto maximos y comportamiento responsive para desktop y movil.


### Problema H: Boton "Restaurar agente" poco visible

Causa:

- El boton se coloco inicialmente en una zona baja del panel y no era facil de descubrir.

Solucion:

- Se movio a la parte superior del chat para acceso rapido.
- Se mantuvo la accion de limpieza local + reset del historial de diversidad en backend.


## 5. Resultado final del agente

El agente IA final:

- Funciona en frontend real con widget flotante.
- Recomienda productos existentes del catalogo.
- Combina motor LLM + fallback heuristico.
- Aplica reglas de negocio por slots.
- Respeta presupuesto a nivel total de outfit.
- Introduce variedad entre consultas repetidas.
- Permite restaurar estado para empezar de cero.
- Se integra con carrito y flujo de compra.


## 6. Lecciones tecnicas clave

- Separar logica de negocio en backend evita exponer secretos y facilita control.
- Mantener fallback local es clave para resiliencia.
- Incluir metadatos de depuracion acelera mucho las pruebas.
- La calidad percibida del agente depende tanto de UX como del modelo.
- La variedad necesita logica explicita; no basta con cambiar prompts.


## 7. Posibles mejoras futuras

- Panel admin con estadisticas de uso/coste IA por dia.
- Cache de recomendaciones por patrones de consulta.
- Rate limiting por IP/usuario para despliegue publico.
- A/B testing de prompts de sistema para mejorar conversion.
- Re-ranking por disponibilidad de tallas antes de recomendar.
