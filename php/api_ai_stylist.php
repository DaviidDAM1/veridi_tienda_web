<?php
require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../config/imagenes.php";

if (PHP_SESSION_NONE === session_status()) {
	$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'secure' => $isHttps,
		'httponly' => true,
		'samesite' => $isHttps ? 'None' : 'Lax'
	]);
}

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$|^https?://([a-z0-9-]+\.)*vercel\.app$|^https?://([a-z0-9-]+\.)*masterendaw\.es$#i', $origin)) {
	header('Access-Control-Allow-Origin: ' . $origin);
	header('Access-Control-Allow-Credentials: true');
}
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
	http_response_code(405);
	echo json_encode([
		'ok' => false,
		'message' => 'Metodo no permitido. Usa GET o POST.'
	], JSON_UNESCAPED_UNICODE);
	exit;
}

function mapProducto(array $producto): array
{
	$idProducto = (int)($producto['id_producto'] ?? 0);
	$categoria = (string)($producto['categoria'] ?? '');
	$estilo = (string)($producto['estilo'] ?? '');
	if (normalizeText($categoria) === 'gorras') {
		$estilo = 'casual';
	}
	return [
		'id_producto' => $idProducto,
		'nombre' => (string)($producto['nombre'] ?? ''),
		'precio' => (float)($producto['precio'] ?? 0),
		'categoria' => $categoria,
		'color' => (string)($producto['color'] ?? ''),
		'estilo' => $estilo,
		'imagen' => obtenerImagenProducto($idProducto, (string)($producto['nombre'] ?? ''))
	];
}

function normalizeText(string $value): string
{
	$value = mb_strtolower(trim($value), 'UTF-8');
	$replacements = [
		'á' => 'a',
		'é' => 'e',
		'í' => 'i',
		'ó' => 'o',
		'ú' => 'u',
		'ü' => 'u',
		'ñ' => 'n'
	];
	return strtr($value, $replacements);
}

function detectStyleFromMessage(string $message): ?string
{
	$text = normalizeText($message);
	if ($text === '') {
		return null;
	}

	if (preg_match('/gym|gimnasio|deporte|deportiv|entreno|running|correr|sport|futbol/', $text)) {
		return 'deportivo';
	}

	if (preg_match('/formal|elegante|oficina|trabajo|evento|cena|boda/', $text)) {
		return 'formal';
	}

	if (preg_match('/casual|informal|diario|dia a dia|urbano|street|calle/', $text)) {
		return 'casual';
	}

	return null;
}

function normalizeStyleValue(?string $style): string
{
	$text = normalizeText((string)($style ?? ''));
	if ($text === '') {
		return '';
	}

	if (preg_match('/gym|gimnasio|deporte|deportiv|entreno|running|correr|sport|futbol/', $text)) {
		return 'deportivo';
	}

	if (preg_match('/formal|elegante|oficina|trabajo|evento|cena|boda/', $text)) {
		return 'formal';
	}

	if (preg_match('/casual|informal|diario|dia a dia|urbano|street|calle/', $text)) {
		return 'casual';
	}

	return $text;
}

function detectExcludedStylesFromMessage(string $message): array
{
	$text = normalizeText($message);
	if ($text === '') {
		return [];
	}

	if (preg_match('/salir de noche|de noche|look de noche|outfit de noche|fiesta|discoteca|cita/', $text)) {
		return ['deportivo'];
	}

	return [];
}

function detectLayerPreferenceFromMessage(string $message): string
{
	$text = normalizeText($message);
	if ($text === '') {
		return '';
	}

	$winterRequested = preg_match('/\binvierno\b|\bfrio\b|\bhelad|\bbajas temperaturas\b|\babrigate\b|\babrigar/', $text) === 1;
	$summerRequested = preg_match('/\bverano\b|\bcalor\b|\bcaluroso\b|\balta[s]? temperaturas\b|\bsofocante\b/', $text) === 1;

	if ($winterRequested && !$summerRequested) {
		return 'require';
	}

	if ($summerRequested && !$winterRequested) {
		return 'forbid';
	}

	return '';
}

function isShortBottomCandidate(array $producto): bool
{
	$categoria = normalizeText((string)($producto['categoria'] ?? ''));
	if (!in_array($categoria, ['pantalones', 'vaqueros'], true)) {
		return false;
	}

	$nombre = normalizeText((string)($producto['nombre'] ?? ''));
	if ($nombre === '') {
		return false;
	}

	return preg_match('/\b(short|bermuda|corto|corta|cortos|cortas)\b/', $nombre) === 1;
}

function chooseAutoStyleForOutfit(
	array $scored,
	array $slotCategories,
	array $excludedStyles = [],
	?string $requiredColor = null,
	bool $strictColorMode = false
): ?string {
	$mandatorySlots = ['top_main', 'bottom', 'shoes'];
	$styleStats = [];

	foreach ($scored as $candidate) {
		if (!is_array($candidate)) {
			continue;
		}
		if (isStyleExcluded($excludedStyles, $candidate)) {
			continue;
		}
		if ($strictColorMode && !isColorCompatible($requiredColor, $candidate)) {
			continue;
		}

		$style = normalizeStyleValue((string)($candidate['estilo'] ?? ''));
		if ($style === '') {
			continue;
		}

		$category = normalizeText((string)($candidate['categoria'] ?? ''));
		$slot = null;
		foreach ($slotCategories as $slotName => $allowedCategories) {
			if (in_array($category, $allowedCategories, true)) {
				$slot = (string)$slotName;
				break;
			}
		}
		if ($slot === null) {
			continue;
		}

		if (!isset($styleStats[$style])) {
			$styleStats[$style] = [
				'mandatory_slots' => [],
				'total_score' => 0.0,
				'count' => 0
			];
		}

		if (in_array($slot, $mandatorySlots, true)) {
			$styleStats[$style]['mandatory_slots'][$slot] = true;
		}
		$styleStats[$style]['total_score'] += (float)($candidate['_score'] ?? 0);
		$styleStats[$style]['count']++;
	}

	if (empty($styleStats)) {
		return null;
	}

	$bestStyle = null;
	$bestMandatoryCovered = -1;
	$bestScore = -INF;
	$bestCount = -1;

	foreach ($styleStats as $style => $stats) {
		$mandatoryCovered = count($stats['mandatory_slots']);
		$totalScore = (float)$stats['total_score'];
		$count = (int)$stats['count'];

		if (
			$bestStyle === null
			|| $mandatoryCovered > $bestMandatoryCovered
			|| ($mandatoryCovered === $bestMandatoryCovered && $totalScore > $bestScore)
			|| ($mandatoryCovered === $bestMandatoryCovered && $totalScore === $bestScore && $count > $bestCount)
		) {
			$bestStyle = (string)$style;
			$bestMandatoryCovered = $mandatoryCovered;
			$bestScore = $totalScore;
			$bestCount = $count;
		}
	}

	return $bestStyle;
}

function isStyleExcluded(array $excludedStyles, array $producto): bool
{
	if (empty($excludedStyles)) {
		return false;
	}

	$productStyle = normalizeStyleValue((string)($producto['estilo'] ?? ''));
	if ($productStyle === '') {
		return false;
	}

	foreach ($excludedStyles as $excludedStyle) {
		if ($productStyle === normalizeStyleValue((string)$excludedStyle)) {
			return true;
		}
	}

	return false;
}

function isStyleCompatible(?string $preferredStyle, array $producto): bool
{
	$preferred = normalizeStyleValue($preferredStyle);
	if ($preferred === '') {
		return true;
	}

	$productStyle = normalizeStyleValue((string)($producto['estilo'] ?? ''));
	if ($productStyle === '') {
		return false;
	}

	return $productStyle === $preferred;
}

function detectColorsFromMessage(string $message): array
{
	$text = normalizeText($message);
	if ($text === '') {
		return [];
	}

	$aliases = [
		'negro' => ['negro'],
		'blanco' => ['blanco'],
		'gris' => ['gris', 'gris claro', 'gris oscuro'],
		'azul' => ['azul', 'azul claro', 'azul oscuro'],
		'verde' => ['verde'],
		'rojo' => ['rojo'],
		'rosa' => ['rosa'],
		'beige' => ['beige']
	];

	$colors = [];
	foreach ($aliases as $token => $mappedColors) {
		if (strpos($text, $token) !== false) {
			foreach ($mappedColors as $mapped) {
				$colors[normalizeText($mapped)] = true;
			}
		}
	}

	return array_keys($colors);
}

function normalizeColorValue(string $value): string
{
	$normalized = normalizeText($value);
	$map = [
		'negra' => 'negro',
		'blanca' => 'blanco',
		'grisaceo' => 'gris',
		'grisacea' => 'gris',
		'azabache' => 'negro',
		'oscuro' => 'negro',
		'claro' => 'blanco'
	];

	return $map[$normalized] ?? $normalized;
}

function getColorFamilies(): array
{
	return [
		'negro' => ['negro'],
		'blanco' => ['blanco'],
		'gris' => ['gris', 'gris claro', 'gris oscuro'],
		'azul' => ['azul', 'azul claro', 'azul oscuro'],
		'verde' => ['verde'],
		'rojo' => ['rojo'],
		'rosa' => ['rosa'],
		'beige' => ['beige']
	];
}

function detectPrimaryColorFromMessage(string $message): ?string
{
	$text = normalizeText($message);
	if ($text === '') {
		return null;
	}

	$positions = [];
	foreach (array_keys(getColorFamilies()) as $token) {
		$pos = mb_strpos($text, $token, 0, 'UTF-8');
		if ($pos !== false) {
			$positions[] = ['token' => $token, 'pos' => (int)$pos];
		}
	}

	if (empty($positions)) {
		return null;
	}

	usort($positions, static function ($a, $b) {
		return ((int)$a['pos']) <=> ((int)$b['pos']);
	});

	return (string)$positions[0]['token'];
}

function detectStrictMonochromeIntent(string $message): bool
{
	$text = normalizeText($message);
	if ($text === '') {
		return false;
	}

	// Detect requests such as: "entero negro", "completamente negro", "totalmente de color rojo".
	if (preg_match('/\b(entero|entera|completo|completa|completamente|totalmente|todo|toda)\b/', $text)) {
		return true;
	}

	if (preg_match('/\b(de pies a cabeza|monocromatico|monocromatico)\b/', $text)) {
		return true;
	}

	return false;
}

function isColorCompatible(?string $requiredColor, array $producto): bool
{
	$required = normalizeColorValue((string)($requiredColor ?? ''));
	if ($required === '') {
		return true;
	}

	$families = getColorFamilies();
	$allowed = isset($families[$required]) ? $families[$required] : [$required];
	$allowedNormalized = array_map(static function ($item) {
		return normalizeText((string)$item);
	}, $allowed);

	$productColor = normalizeText((string)($producto['color'] ?? ''));
	if ($productColor === '') {
		return false;
	}

	return in_array($productColor, $allowedNormalized, true);
}

function scoreProducto(
	array $producto,
	?array $baseProduct,
	?string $preferredStyle,
	array $preferredColors,
	?float $presupuesto
): float {
	$score = 0.0;
	$productoStyle = normalizeText((string)($producto['estilo'] ?? ''));
	$productoColor = normalizeText((string)($producto['color'] ?? ''));
	$productoCategoria = normalizeText((string)($producto['categoria'] ?? ''));
	$precio = (float)($producto['precio'] ?? 0);

	if ($preferredStyle !== null && $productoStyle === normalizeText($preferredStyle)) {
		$score += 4.0;
	}

	if (!empty($preferredColors) && in_array($productoColor, $preferredColors, true)) {
		$score += 3.0;
	}

	if ($baseProduct !== null) {
		$baseColor = normalizeText((string)($baseProduct['color'] ?? ''));
		$baseStyle = normalizeText((string)($baseProduct['estilo'] ?? ''));
		$baseCategoria = normalizeText((string)($baseProduct['categoria'] ?? ''));

		if ($baseColor !== '' && $productoColor === $baseColor) {
			$score += 2.5;
		}

		if ($baseStyle !== '' && $productoStyle === $baseStyle) {
			$score += 2.0;
		}

		if ($baseCategoria !== '' && $productoCategoria === $baseCategoria) {
			$score -= 2.5;
		}
	}

	if ($presupuesto !== null && $presupuesto > 0) {
		if ($precio <= $presupuesto) {
			$score += 1.0;
		}
	}

	$score += max(0.0, 1.2 - ($precio / 120.0));

	return $score;
}

function pickByCategory(array $productos, array $categorias): ?array
{
	foreach ($productos as $producto) {
		$categoria = mb_strtolower((string)($producto['categoria'] ?? ''), 'UTF-8');
		foreach ($categorias as $c) {
			if ($categoria === $c) {
				return $producto;
			}
		}
	}
	return null;
}

function pickFromScoredByCategories(array $scored, array $allowedCategories, array $selectedIds): ?array
{
	foreach ($scored as $candidate) {
		$idCandidate = (int)($candidate['id_producto'] ?? 0);
		if ($idCandidate <= 0 || isset($selectedIds[$idCandidate])) {
			continue;
		}
		$candidateCategory = normalizeText((string)($candidate['categoria'] ?? ''));
		if (!in_array($candidateCategory, $allowedCategories, true)) {
			continue;
		}
		unset($candidate['_score']);
		return $candidate;
	}

	return null;
}

function pickFromScoredByCategoriesWithStyle(
	array $scored,
	array $allowedCategories,
	array $selectedIds,
	?string $preferredStyle,
	?string $requiredColor = null,
	bool $strictColorMode = false,
	array $excludedStyles = []
): ?array {
	$preferred = normalizeStyleValue($preferredStyle);

	foreach ($scored as $candidate) {
		$idCandidate = (int)($candidate['id_producto'] ?? 0);
		if ($idCandidate <= 0 || isset($selectedIds[$idCandidate])) {
			continue;
		}
		$candidateCategory = normalizeText((string)($candidate['categoria'] ?? ''));
		if (!in_array($candidateCategory, $allowedCategories, true)) {
			continue;
		}
		if (isStyleExcluded($excludedStyles, $candidate)) {
			continue;
		}
		if ($preferred !== '' && !isStyleCompatible($preferred, $candidate)) {
			continue;
		}
		if ($strictColorMode && !isColorCompatible($requiredColor, $candidate)) {
			continue;
		}
		unset($candidate['_score']);
		return $candidate;
	}

	if ($preferred !== '' || $strictColorMode) {
		return null;
	}

	return pickFromScoredByCategories($scored, $allowedCategories, $selectedIds);
}

function stripInternalProductFields(array $products): array
{
	$out = [];
	foreach ($products as $product) {
		if (!is_array($product)) {
			continue;
		}
		if (array_key_exists('_score', $product)) {
			unset($product['_score']);
		}
		$out[] = $product;
	}

	return $out;
}

function buildRecommendedFromOutfit(array $outfit): array
{
	$recommended = [];
	foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
		$item = $outfit[$slot] ?? null;
		if (!is_array($item)) {
			continue;
		}
		$recommended[] = $item;
	}

	return $recommended;
}

function calculateOutfitTotal(array $outfit): float
{
	$total = 0.0;
	foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
		$item = $outfit[$slot] ?? null;
		if (!is_array($item)) {
			continue;
		}
		$total += (float)($item['precio'] ?? 0);
	}

	return round($total, 2);
}

function slotLabel(string $slot): string
{
	$labels = [
		'top_main' => 'Camiseta',
		'top_layer' => 'Capa superior',
		'bottom' => 'Pantalon',
		'shoes' => 'Calzado',
		'extra' => 'Gorra'
	];

	return $labels[$slot] ?? 'Prenda';
}

function buildProductWhy(
	array $product,
	string $slot,
	?string $preferredStyle,
	array $preferredColors,
	?float $presupuesto,
	float $outfitTotal,
	bool $budgetRespected
): array {
	$productStyleRaw = (string)($product['estilo'] ?? '');
	$productStyle = normalizeStyleValue($productStyleRaw);
	$wantedStyle = normalizeStyleValue($preferredStyle);
	$productColorRaw = (string)($product['color'] ?? '');
	$productColor = normalizeText($productColorRaw);
	$price = (float)($product['precio'] ?? 0);

	if ($wantedStyle !== '') {
		if ($productStyle !== '' && $productStyle === $wantedStyle) {
			$styleReason = 'Encaja con el estilo solicitado: ' . $wantedStyle . '.';
		} elseif ($productStyle !== '') {
			$styleReason = 'Aporta variedad de estilo (' . $productStyle . ') frente al estilo solicitado (' . $wantedStyle . ').';
		} else {
			$styleReason = 'Se eligio por combinacion general del look.';
		}
	} else {
		$styleReason = $productStyle !== ''
			? 'Se selecciono por su estilo ' . $productStyle . ' y compatibilidad del conjunto.'
			: 'Se selecciono por compatibilidad general del conjunto.';
	}

	if (!empty($preferredColors)) {
		if ($productColor !== '' && in_array($productColor, $preferredColors, true)) {
			$colorReason = 'Respeta tu preferencia de color: ' . $productColorRaw . '.';
		} elseif ($productColor !== '') {
			$colorReason = 'Aporta contraste de color (' . $productColorRaw . ') para equilibrar el outfit.';
		} else {
			$colorReason = 'El color se priorizo por armonia global del outfit.';
		}
	} else {
		$colorReason = $productColor !== ''
			? 'Color elegido para combinar bien con el resto: ' . $productColorRaw . '.'
			: 'Color elegido por armonia del conjunto.';
	}

	if ($presupuesto !== null && $presupuesto > 0) {
		if ($budgetRespected) {
			$budgetReason = 'Precio de la prenda (€' . number_format($price, 2, ',', '.') . ') compatible con el presupuesto total.';
		} else {
			$budgetReason = 'Se priorizo esta prenda por calidad de combinacion aunque el total supere el presupuesto.';
		}
	} else {
		$budgetReason = 'No se aplico limite de presupuesto en esta recomendacion.';
	}

	$summary = 'Elegida como ' . mb_strtolower(slotLabel($slot), 'UTF-8')
		. ' por estilo, color y equilibrio del presupuesto del look.';

	return [
		'slot' => $slot,
		'slot_label' => slotLabel($slot),
		'style' => $styleReason,
		'color' => $colorReason,
		'budget' => $budgetReason,
		'summary' => $summary,
		'outfit_total' => $outfitTotal,
		'budget_limit' => $presupuesto
	];
}

function buildOutfitReasons(
	array $outfit,
	?string $preferredStyle,
	array $preferredColors,
	?float $presupuesto,
	float $outfitTotal,
	bool $budgetRespected
): array {
	$reasons = [];
	foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
		$product = $outfit[$slot] ?? null;
		if (!is_array($product)) {
			continue;
		}

		$reasons[$slot] = buildProductWhy(
			$product,
			$slot,
			$preferredStyle,
			$preferredColors,
			$presupuesto,
			$outfitTotal,
			$budgetRespected
		);
	}

	return $reasons;
}

function buildProductReasons(array $recommended, array $outfitReasons, array $outfit): array
{
	$out = [];
	foreach ($recommended as $product) {
		if (!is_array($product)) {
			continue;
		}

		$id = (int)($product['id_producto'] ?? 0);
		if ($id <= 0) {
			continue;
		}

		$reason = null;
		foreach ($outfitReasons as $slotReason) {
			if (!is_array($slotReason)) {
				continue;
			}
			if ((string)($slotReason['slot'] ?? '') === '') {
				continue;
			}
			$slot = (string)$slotReason['slot'];
			$outfitProduct = $outfit[$slot] ?? null;
			if (is_array($outfitProduct) && (int)($outfitProduct['id_producto'] ?? 0) === $id) {
				$reason = $slotReason;
				break;
			}
		}

		if ($reason === null) {
			$reason = [
				'slot' => null,
				'slot_label' => 'Recomendacion',
				'style' => 'Seleccionada por afinidad general de estilo con el conjunto.',
				'color' => 'Seleccionada por combinacion de color con las prendas principales.',
				'budget' => 'Incluida por su aporte global al equilibrio del look.',
				'summary' => 'Recomendada como opcion complementaria para completar el outfit.'
			];
		}

		$out[(string)$id] = $reason;
	}

	return $out;
}

function withPantalonAlias(array $outfit): array
{
	if (!array_key_exists('pantalon', $outfit)) {
		$outfit['pantalon'] = $outfit['bottom'] ?? null;
	}

	return $outfit;
}

function enforceStrictStyleSelection(array $outfit, array $recommended, ?string $preferredStyle): array
{
	$strictStyle = normalizeStyleValue($preferredStyle);
	if ($strictStyle === '') {
		return [
			'outfit' => withPantalonAlias($outfit),
			'recommended_products' => $recommended,
			'removed_items' => 0,
			'applied' => false
		];
	}

	$removedItems = 0;
	$normalized = [
		'top_main' => is_array($outfit['top_main'] ?? null) ? $outfit['top_main'] : null,
		'top_layer' => is_array($outfit['top_layer'] ?? null) ? $outfit['top_layer'] : null,
		'bottom' => is_array($outfit['bottom'] ?? null) ? $outfit['bottom'] : (is_array($outfit['pantalon'] ?? null) ? $outfit['pantalon'] : null),
		'shoes' => is_array($outfit['shoes'] ?? null) ? $outfit['shoes'] : null,
		'extra' => is_array($outfit['extra'] ?? null) ? $outfit['extra'] : null
	];

	foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
		$current = $normalized[$slot] ?? null;
		if (!is_array($current)) {
			continue;
		}
		if (!isStyleCompatible($strictStyle, $current)) {
			$normalized[$slot] = null;
			$removedItems++;
		}
	}

	$outfitIds = [];
	foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
		$id = (int)(is_array($normalized[$slot] ?? null) ? ($normalized[$slot]['id_producto'] ?? 0) : 0);
		if ($id > 0) {
			$outfitIds[$id] = true;
		}
	}

	$filteredRecommended = [];
	$seen = [];
	foreach ($recommended as $item) {
		if (!is_array($item)) {
			continue;
		}
		$id = (int)($item['id_producto'] ?? 0);
		if ($id <= 0 || isset($seen[$id])) {
			continue;
		}
		if (!isStyleCompatible($strictStyle, $item)) {
			$removedItems++;
			continue;
		}
		if (!isset($outfitIds[$id])) {
			continue;
		}
		$filteredRecommended[] = $item;
		$seen[$id] = true;
	}

	if (empty($filteredRecommended)) {
		$filteredRecommended = buildRecommendedFromOutfit($normalized);
	}

	return [
		'outfit' => withPantalonAlias($normalized),
		'recommended_products' => $filteredRecommended,
		'removed_items' => $removedItems,
		'applied' => true
	];
}

function buildOutfitSignature(array $outfit): string
{
	$slots = ['top_main', 'top_layer', 'bottom', 'shoes', 'extra'];
	$parts = [];
	foreach ($slots as $slot) {
		$item = $outfit[$slot] ?? null;
		if ($slot === 'bottom' && !is_array($item)) {
			$item = $outfit['pantalon'] ?? null;
		}
		$id = (int)(is_array($item) ? ($item['id_producto'] ?? 0) : 0);
		$parts[] = (string)$id;
	}

	return implode('-', $parts);
}

function buildSlotPools(array $pool, array $slotCategories, ?string $preferredStyle = null, array $excludedStyles = []): array
{
	$poolBySlot = [
		'top_main' => [],
		'top_layer' => [],
		'bottom' => [],
		'shoes' => [],
		'extra' => []
	];

	foreach ($pool as $product) {
		if (!is_array($product)) {
			continue;
		}
		$category = normalizeText((string)($product['categoria'] ?? ''));
		foreach ($slotCategories as $slot => $allowedCategories) {
			if (in_array($category, $allowedCategories, true)) {
				$poolBySlot[$slot][] = $product;
				break;
			}
		}
	}

	$preferred = normalizeStyleValue($preferredStyle);

	foreach ($poolBySlot as $slot => $items) {
		$matchingStyle = [];
		foreach ($items as $item) {
			if (isStyleExcluded($excludedStyles, $item)) {
				continue;
			}
			if ($preferred === '' || isStyleCompatible($preferred, $item)) {
				$matchingStyle[] = $item;
			}
		}
		// Strict mode: keep only requested style, even if slot becomes empty.
		$poolBySlot[$slot] = array_values($matchingStyle);
	}

	return $poolBySlot;
}

function applyStrictColorToSlotPools(array $poolBySlot, ?string $requiredColor, bool $strictColorMode): array
{
	if (!$strictColorMode) {
		return $poolBySlot;
	}

	foreach ($poolBySlot as $slot => $items) {
		$filtered = [];
		foreach ($items as $item) {
			if (isColorCompatible($requiredColor, $item)) {
				$filtered[] = $item;
			}
		}
		$poolBySlot[$slot] = array_values($filtered);
	}

	return $poolBySlot;
}

function diversifyOutfitWithHistory(
	array $outfit,
	array $pool,
	array $slotCategories,
	?float $budget,
	bool $mandatoryRequired,
	?string $preferredStyle,
	?string $requiredColor = null,
	bool $strictColorMode = false,
	array $excludedStyles = []
): array
{
	$normalized = [
		'top_main' => is_array($outfit['top_main'] ?? null) ? $outfit['top_main'] : null,
		'top_layer' => is_array($outfit['top_layer'] ?? null) ? $outfit['top_layer'] : null,
		'bottom' => is_array($outfit['bottom'] ?? null) ? $outfit['bottom'] : (is_array($outfit['pantalon'] ?? null) ? $outfit['pantalon'] : null),
		'shoes' => is_array($outfit['shoes'] ?? null) ? $outfit['shoes'] : null,
		'extra' => is_array($outfit['extra'] ?? null) ? $outfit['extra'] : null
	];

	$poolBySlot = buildSlotPools($pool, $slotCategories, $preferredStyle, $excludedStyles);
	$poolBySlot = applyStrictColorToSlotPools($poolBySlot, $requiredColor, $strictColorMode);
	$recentSignatures = isset($_SESSION['ai_stylist_recent_outfits']) && is_array($_SESSION['ai_stylist_recent_outfits'])
		? $_SESSION['ai_stylist_recent_outfits']
		: [];

	$baseSignature = buildOutfitSignature($normalized);
	$candidateSlots = [];
	foreach (['top_layer', 'extra', 'top_main', 'bottom', 'shoes'] as $slot) {
		$currentId = (int)(is_array($normalized[$slot] ?? null) ? ($normalized[$slot]['id_producto'] ?? 0) : 0);
		$hasAlternative = false;
		foreach ($poolBySlot[$slot] as $candidate) {
			if ((int)($candidate['id_producto'] ?? 0) !== $currentId) {
				$hasAlternative = true;
				break;
			}
		}
		if ($hasAlternative) {
			$candidateSlots[] = $slot;
		}
	}

	$bestVariant = $normalized;
	$bestSignature = $baseSignature;

	for ($attempt = 0; $attempt < 16; $attempt++) {
		if (empty($candidateSlots)) {
			break;
		}

		$variant = $normalized;
		$slotCount = min(count($candidateSlots), random_int(1, 2));
		$slotsPicked = [];
		while (count($slotsPicked) < $slotCount) {
			$slot = $candidateSlots[array_rand($candidateSlots)];
			if (!in_array($slot, $slotsPicked, true)) {
				$slotsPicked[] = $slot;
			}
		}

		foreach ($slotsPicked as $slot) {
			$currentId = (int)(is_array($variant[$slot] ?? null) ? ($variant[$slot]['id_producto'] ?? 0) : 0);
			$window = array_slice($poolBySlot[$slot], 0, 6);
			if (empty($window)) {
				continue;
			}

			$candidate = null;
			for ($pickTry = 0; $pickTry < 8; $pickTry++) {
				$raw = $window[array_rand($window)];
				$candidateId = (int)($raw['id_producto'] ?? 0);
				if ($candidateId <= 0 || $candidateId === $currentId) {
					continue;
				}

				$isUnique = true;
				foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $otherSlot) {
					if ($otherSlot === $slot) {
						continue;
					}
					$otherId = (int)(is_array($variant[$otherSlot] ?? null) ? ($variant[$otherSlot]['id_producto'] ?? 0) : 0);
					if ($otherId === $candidateId) {
						$isUnique = false;
						break;
					}
				}

				if ($isUnique) {
					$candidate = $raw;
					break;
				}
			}

			if ($candidate !== null) {
				$variant[$slot] = $candidate;
			}
		}

		if ($mandatoryRequired) {
			if (!is_array($variant['top_main'] ?? null) || !is_array($variant['bottom'] ?? null) || !is_array($variant['shoes'] ?? null)) {
				continue;
			}
		}

		$total = calculateOutfitTotal($variant);
		if ($budget !== null && $budget > 0 && $total > $budget) {
			continue;
		}

		$signature = buildOutfitSignature($variant);
		if ($signature === $baseSignature) {
			continue;
		}

		$bestVariant = $variant;
		$bestSignature = $signature;
		if (!in_array($signature, $recentSignatures, true)) {
			break;
		}
	}

	$recentSignatures[] = $bestSignature;
	$recentSignatures = array_values(array_unique($recentSignatures));
	if (count($recentSignatures) > 10) {
		$recentSignatures = array_slice($recentSignatures, -10);
	}
	$_SESSION['ai_stylist_recent_outfits'] = $recentSignatures;

	return withPantalonAlias($bestVariant);
}

function enforceLayerPreferenceOnOutfit(
	array $outfit,
	array $scored,
	array $slotCategories,
	?string $preferredStyle,
	?string $requiredColor,
	bool $strictColorMode,
	array $excludedStyles,
	string $layerPreference
): array {
	$normalized = [
		'top_main' => is_array($outfit['top_main'] ?? null) ? $outfit['top_main'] : null,
		'top_layer' => is_array($outfit['top_layer'] ?? null) ? $outfit['top_layer'] : null,
		'bottom' => is_array($outfit['bottom'] ?? null) ? $outfit['bottom'] : (is_array($outfit['pantalon'] ?? null) ? $outfit['pantalon'] : null),
		'shoes' => is_array($outfit['shoes'] ?? null) ? $outfit['shoes'] : null,
		'extra' => is_array($outfit['extra'] ?? null) ? $outfit['extra'] : null
	];

	if ($layerPreference === 'forbid') {
		$removed = is_array($normalized['top_layer']);
		$normalized['top_layer'] = null;
		return [
			'outfit' => withPantalonAlias($normalized),
			'applied' => true,
			'missing_required_layer' => false,
			'added_layer' => false,
			'removed_layer' => $removed
		];
	}

	if ($layerPreference !== 'require') {
		return [
			'outfit' => withPantalonAlias($normalized),
			'applied' => false,
			'missing_required_layer' => false,
			'added_layer' => false,
			'removed_layer' => false
		];
	}

	if (is_array($normalized['top_layer'])) {
		return [
			'outfit' => withPantalonAlias($normalized),
			'applied' => true,
			'missing_required_layer' => false,
			'added_layer' => false,
			'removed_layer' => false
		];
	}

	$selectedIds = [];
	foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
		$id = (int)(is_array($normalized[$slot] ?? null) ? ($normalized[$slot]['id_producto'] ?? 0) : 0);
		if ($id > 0) {
			$selectedIds[$id] = true;
		}
	}

	$pick = pickFromScoredByCategoriesWithStyle(
		$scored,
		$slotCategories['top_layer'] ?? [],
		$selectedIds,
		$preferredStyle,
		$requiredColor,
		$strictColorMode,
		$excludedStyles
	);

	if ($pick !== null) {
		$normalized['top_layer'] = $pick;
		return [
			'outfit' => withPantalonAlias($normalized),
			'applied' => true,
			'missing_required_layer' => false,
			'added_layer' => true,
			'removed_layer' => false
		];
	}

	return [
		'outfit' => withPantalonAlias($normalized),
		'applied' => true,
		'missing_required_layer' => true,
		'added_layer' => false,
		'removed_layer' => false
	];
}

function enforceColdWeatherBottomRule(
	array $outfit,
	array $scored,
	array $slotCategories,
	?string $preferredStyle,
	?string $requiredColor,
	bool $strictColorMode,
	array $excludedStyles,
	bool $coldWeatherRequested
): array {
	$normalized = [
		'top_main' => is_array($outfit['top_main'] ?? null) ? $outfit['top_main'] : null,
		'top_layer' => is_array($outfit['top_layer'] ?? null) ? $outfit['top_layer'] : null,
		'bottom' => is_array($outfit['bottom'] ?? null) ? $outfit['bottom'] : (is_array($outfit['pantalon'] ?? null) ? $outfit['pantalon'] : null),
		'shoes' => is_array($outfit['shoes'] ?? null) ? $outfit['shoes'] : null,
		'extra' => is_array($outfit['extra'] ?? null) ? $outfit['extra'] : null
	];

	if (!$coldWeatherRequested) {
		return [
			'outfit' => withPantalonAlias($normalized),
			'applied' => false,
			'replaced_short' => false,
			'missing_non_short_bottom' => false
		];
	}

	$bottom = $normalized['bottom'];
	if (!is_array($bottom) || !isShortBottomCandidate($bottom)) {
		return [
			'outfit' => withPantalonAlias($normalized),
			'applied' => true,
			'replaced_short' => false,
			'missing_non_short_bottom' => false
		];
	}

	$selectedIds = [];
	foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
		$id = (int)(is_array($normalized[$slot] ?? null) ? ($normalized[$slot]['id_producto'] ?? 0) : 0);
		if ($id > 0) {
			$selectedIds[$id] = true;
		}
	}

	$replacement = null;
	$preferred = normalizeStyleValue($preferredStyle);
	foreach ($scored as $candidate) {
		$idCandidate = (int)($candidate['id_producto'] ?? 0);
		if ($idCandidate <= 0 || isset($selectedIds[$idCandidate])) {
			continue;
		}

		$candidateCategory = normalizeText((string)($candidate['categoria'] ?? ''));
		if (!in_array($candidateCategory, $slotCategories['bottom'] ?? [], true)) {
			continue;
		}
		if (isShortBottomCandidate($candidate)) {
			continue;
		}
		if (isStyleExcluded($excludedStyles, $candidate)) {
			continue;
		}
		if ($preferred !== '' && !isStyleCompatible($preferred, $candidate)) {
			continue;
		}
		if ($strictColorMode && !isColorCompatible($requiredColor, $candidate)) {
			continue;
		}

		$replacement = $candidate;
		unset($replacement['_score']);
		break;
	}

	if ($replacement !== null) {
		$normalized['bottom'] = $replacement;
		return [
			'outfit' => withPantalonAlias($normalized),
			'applied' => true,
			'replaced_short' => true,
			'missing_non_short_bottom' => false
		];
	}

	$normalized['bottom'] = null;

	return [
		'outfit' => withPantalonAlias($normalized),
		'applied' => true,
		'replaced_short' => false,
		'missing_non_short_bottom' => true
	];
}

function normalizeOutfitToBudget(
	array $outfit,
	array $pool,
	array $slotCategories,
	?float $budget,
	?string $preferredStyle,
	?string $requiredColor = null,
	bool $strictColorMode = false,
	array $excludedStyles = []
): array
{
	$normalized = [
		'top_main' => is_array($outfit['top_main'] ?? null) ? $outfit['top_main'] : null,
		'top_layer' => is_array($outfit['top_layer'] ?? null) ? $outfit['top_layer'] : null,
		'bottom' => is_array($outfit['bottom'] ?? null) ? $outfit['bottom'] : null,
		'shoes' => is_array($outfit['shoes'] ?? null) ? $outfit['shoes'] : null,
		'extra' => is_array($outfit['extra'] ?? null) ? $outfit['extra'] : null
	];
	$mandatorySlots = ['top_main', 'bottom', 'shoes'];

	$originalSignature = [];
	foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
		$originalSignature[$slot] = (int)(is_array($normalized[$slot]) ? ($normalized[$slot]['id_producto'] ?? 0) : 0);
	}

	$poolBySlot = buildSlotPools($pool, $slotCategories, $preferredStyle, $excludedStyles);
	$poolBySlot = applyStrictColorToSlotPools($poolBySlot, $requiredColor, $strictColorMode);

	foreach ($poolBySlot as $slot => $items) {
		usort($items, static function ($a, $b) {
			$priceA = (float)($a['precio'] ?? 0);
			$priceB = (float)($b['precio'] ?? 0);
			if ($priceA === $priceB) {
				return (int)($a['id_producto'] ?? 0) <=> (int)($b['id_producto'] ?? 0);
			}
			return $priceA <=> $priceB;
		});
		$poolBySlot[$slot] = $items;
	}

	foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
		$current = $normalized[$slot] ?? null;
		if (!is_array($current)) {
			continue;
		}
		if (isStyleCompatible($preferredStyle, $current)) {
			continue;
		}

		$foundReplacement = false;

		foreach ($poolBySlot[$slot] as $candidate) {
			$candidateId = (int)($candidate['id_producto'] ?? 0);
			if ($candidateId <= 0) {
				continue;
			}
			$used = false;
			foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $otherSlot) {
				if ($otherSlot === $slot) {
					continue;
				}
				$otherId = (int)(is_array($normalized[$otherSlot] ?? null) ? ($normalized[$otherSlot]['id_producto'] ?? 0) : 0);
				if ($otherId === $candidateId) {
					$used = true;
					break;
				}
			}

			if (!$used) {
				$normalized[$slot] = $candidate;
				$foundReplacement = true;
				break;
			}
		}

		if (!$foundReplacement) {
			$normalized[$slot] = null;
		}
	}

	$cheapestMandatoryTotal = 0.0;
	$mandatoryAvailability = true;
	foreach ($mandatorySlots as $slot) {
		if (!empty($poolBySlot[$slot])) {
			$cheapestMandatoryTotal += (float)($poolBySlot[$slot][0]['precio'] ?? 0);
		} elseif (!is_array($normalized[$slot] ?? null)) {
			$mandatoryAvailability = false;
		}
	}

	$mandatoryMustStay = $mandatoryAvailability && ($budget === null || $budget <= 0 || $cheapestMandatoryTotal <= $budget);

	foreach ($mandatorySlots as $slot) {
		if (is_array($normalized[$slot] ?? null)) {
			continue;
		}
		foreach ($poolBySlot[$slot] as $candidate) {
			$candidateId = (int)($candidate['id_producto'] ?? 0);
			if ($candidateId <= 0) {
				continue;
			}

			$used = false;
			foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $otherSlot) {
				if ($otherSlot === $slot) {
					continue;
				}
				$otherId = (int)(is_array($normalized[$otherSlot] ?? null) ? ($normalized[$otherSlot]['id_producto'] ?? 0) : 0);
				if ($otherId === $candidateId) {
					$used = true;
					break;
				}
			}

			if (!$used) {
				$normalized[$slot] = $candidate;
				break;
			}
		}
	}

	if ($budget === null || $budget <= 0) {
		return [
			'outfit' => withPantalonAlias($normalized),
			'recommended_products' => buildRecommendedFromOutfit($normalized),
			'total' => calculateOutfitTotal($normalized),
			'budget_respected' => true,
			'budget_adjusted' => false,
			'mandatory_required' => $mandatoryMustStay
		];
	}

	$total = calculateOutfitTotal($normalized);

	foreach (['extra', 'top_layer'] as $optionalSlot) {
		if ($total <= $budget) {
			break;
		}
		if ($normalized[$optionalSlot] !== null) {
			$normalized[$optionalSlot] = null;
			$total = calculateOutfitTotal($normalized);
		}
	}

	$replaceOrder = ['shoes', 'bottom', 'top_main', 'top_layer', 'extra'];
	$changed = true;
	while ($total > $budget && $changed) {
		$changed = false;
		foreach ($replaceOrder as $slot) {
			if (!$mandatoryMustStay && in_array($slot, $mandatorySlots, true) && !is_array($normalized[$slot] ?? null)) {
				continue;
			}
			$current = $normalized[$slot] ?? null;
			if (!is_array($current)) {
				continue;
			}

			$currentId = (int)($current['id_producto'] ?? 0);
			$currentPrice = (float)($current['precio'] ?? 0);
			$usedIds = [];
			foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $otherSlot) {
				if ($otherSlot === $slot) {
					continue;
				}
				$other = $normalized[$otherSlot] ?? null;
				if (!is_array($other)) {
					continue;
				}
				$usedIds[(int)($other['id_producto'] ?? 0)] = true;
			}

			$replacement = null;
			foreach ($poolBySlot[$slot] as $candidate) {
				$candidateId = (int)($candidate['id_producto'] ?? 0);
				if ($candidateId <= 0 || isset($usedIds[$candidateId])) {
					continue;
				}
				$candidatePrice = (float)($candidate['precio'] ?? 0);
				if ($candidateId === $currentId) {
					continue;
				}
				if ($candidatePrice < $currentPrice) {
					$replacement = $candidate;
					break;
				}
			}

			if ($replacement !== null) {
				$normalized[$slot] = $replacement;
				$total = calculateOutfitTotal($normalized);
				$changed = true;
				if ($total <= $budget) {
					break;
				}
			}
		}
	}

	$dropOrder = $mandatoryMustStay
		? ['extra', 'top_layer']
		: ['extra', 'top_layer', 'shoes', 'bottom', 'top_main'];

	foreach ($dropOrder as $slot) {
		if ($total <= $budget) {
			break;
		}
		if ($normalized[$slot] !== null) {
			$normalized[$slot] = null;
			$total = calculateOutfitTotal($normalized);
		}
	}

	if ($mandatoryMustStay) {
		foreach ($mandatorySlots as $slot) {
			if (is_array($normalized[$slot] ?? null)) {
				continue;
			}
			foreach ($poolBySlot[$slot] as $candidate) {
				$candidateId = (int)($candidate['id_producto'] ?? 0);
				if ($candidateId <= 0) {
					continue;
				}
				$used = false;
				foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $otherSlot) {
					if ($otherSlot === $slot) {
						continue;
					}
					$otherId = (int)(is_array($normalized[$otherSlot] ?? null) ? ($normalized[$otherSlot]['id_producto'] ?? 0) : 0);
					if ($otherId === $candidateId) {
						$used = true;
						break;
					}
				}
				if (!$used) {
					$normalized[$slot] = $candidate;
					break;
				}
			}
		}
	}

	$newSignature = [];
	foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
		$newSignature[$slot] = (int)(is_array($normalized[$slot]) ? ($normalized[$slot]['id_producto'] ?? 0) : 0);
	}
	$totalFinal = calculateOutfitTotal($normalized);

	return [
		'outfit' => withPantalonAlias($normalized),
		'recommended_products' => buildRecommendedFromOutfit($normalized),
		'total' => $totalFinal,
		'budget_respected' => $totalFinal <= $budget,
		'budget_adjusted' => $originalSignature !== $newSignature,
		'mandatory_required' => $mandatoryMustStay
	];
}

function getOpenAiApiKey(): string
{
	$key = trim((string)(getenv('OPENAI_API_KEY') ?: ''));
	if ($key !== '') {
		return $key;
	}

	$localConfig = __DIR__ . '/../config/openai.local.php';
	if (is_file($localConfig)) {
		$data = require $localConfig;
		if (is_array($data)) {
			$value = trim((string)($data['api_key'] ?? ''));
			if ($value !== '') {
				return $value;
			}
		}
	}

	return '';
}

function callOpenAiStylist(
	string $apiKey,
	string $message,
	?float $presupuesto,
	?string $preferredStyle,
	?array $baseProduct,
	array $candidatePool,
	string $layerPreference = '',
	array $excludedStyles = [],
	?array &$debug = null
): ?array {
	$debug = [
		'stage' => 'start',
		'http_code' => null,
		'curl_errno' => null,
		'curl_error' => '',
		'response_excerpt' => ''
	];

	if ($apiKey === '' || empty($candidatePool) || !function_exists('curl_init')) {
		$debug['stage'] = 'preconditions';
		return null;
	}

	$model = trim((string)(getenv('OPENAI_MODEL') ?: 'gpt-4o-mini'));
	$baseInfo = $baseProduct ? [
		'id_producto' => (int)$baseProduct['id_producto'],
		'nombre' => (string)$baseProduct['nombre'],
		'categoria' => (string)$baseProduct['categoria'],
		'color' => (string)$baseProduct['color'],
		'estilo' => (string)$baseProduct['estilo']
	] : null;

	$systemPrompt = "Eres un estilista de moda para Veridi. Solo puedes recomendar IDs de productos presentes en la lista candidata.\n"
		. "Reglas obligatorias del outfit:\n"
		. "- top_main: categoria Camisetas (obligatorio si existe candidata).\n"
		. "- top_layer: categoria Chaquetas, Sudaderas o Abrigos (opcional).\n"
		. "- pantalon: categoria Pantalones o Vaqueros (obligatorio si existe candidata).\n"
		. "- shoes: categoria Calzado (obligatorio si existe candidata).\n"
		. "- extra: categoria Gorras (opcional).\n"
		. "- Si preferred_style viene informado (formal/casual/deportivo), SOLO puedes usar productos de ese estilo. Si para un slot no existe producto de ese estilo, devuelve null en ese slot.\n"
		. "- Si layer_preference='require', top_layer DEBE ir relleno (si no existe candidata valida, null).\n"
		. "- Si layer_preference='forbid', top_layer DEBE ser null.\n"
		. "No inventes productos ni IDs. Devuelve SOLO JSON valido con esta forma:\n"
		. "{\"reply_text\":\"...\",\"outfit\":{\"top_main\":id|null,\"top_layer\":id|null,\"pantalon\":id|null,\"shoes\":id|null,\"extra\":id|null},\"recommended_ids\":[id1,id2,...]}";

	$userPayload = [
		'message' => $message,
		'presupuesto' => $presupuesto,
		'preferred_style' => normalizeStyleValue($preferredStyle),
		'layer_preference' => $layerPreference,
		'excluded_styles' => $excludedStyles,
		'base_product' => $baseInfo,
		'candidate_products' => $candidatePool,
		'variation_hint' => (string)microtime(true)
	];

	$body = [
		'model' => $model,
		'temperature' => 0.45,
		'response_format' => ['type' => 'json_object'],
		'messages' => [
			['role' => 'system', 'content' => $systemPrompt],
			['role' => 'user', 'content' => json_encode($userPayload, JSON_UNESCAPED_UNICODE)]
		]
	];

	$ch = curl_init('https://api.openai.com/v1/chat/completions');
	curl_setopt_array($ch, [
		CURLOPT_POST => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 20,
		CURLOPT_HTTPHEADER => [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $apiKey
		],
		CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE)
	]);

	$responseRaw = curl_exec($ch);
	if ($responseRaw === false) {
		$debug['stage'] = 'curl_exec';
		$debug['curl_errno'] = curl_errno($ch);
		$debug['curl_error'] = (string)curl_error($ch);
		curl_close($ch);
		return null;
	}

	$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$debug['http_code'] = $httpCode;
	$debug['response_excerpt'] = mb_substr((string)$responseRaw, 0, 280, 'UTF-8');
	curl_close($ch);

	if ($httpCode < 200 || $httpCode >= 300) {
		$debug['stage'] = 'http_status';
		return null;
	}

	$decoded = json_decode($responseRaw, true);
	if (!is_array($decoded)) {
		$debug['stage'] = 'decode_top_level';
		return null;
	}

	$content = (string)($decoded['choices'][0]['message']['content'] ?? '');
	if ($content === '') {
		$debug['stage'] = 'empty_content';
		return null;
	}

	$parsed = json_decode($content, true);
	if (!is_array($parsed)) {
		$debug['stage'] = 'decode_model_json';
		$debug['response_excerpt'] = mb_substr($content, 0, 280, 'UTF-8');
		return null;
	}

	$debug['stage'] = 'ok';

	return $parsed;
}

function sanitizeOpenAiStylistResult(
	array $aiResult,
	array $candidatePool,
	?string $preferredStyle,
	?string $requiredColor = null,
	bool $strictColorMode = false,
	array $excludedStyles = []
): ?array
{
	$idMap = [];
	foreach ($candidatePool as $item) {
		$id = (int)($item['id_producto'] ?? 0);
		if ($id > 0) {
			$idMap[$id] = $item;
		}
	}

	if (empty($idMap)) {
		return null;
	}

	$allowed = [
		'top_main' => ['camisetas'],
		'top_layer' => ['chaquetas', 'sudaderas', 'abrigos'],
		'bottom' => ['pantalones', 'vaqueros'],
		'shoes' => ['calzado'],
		'extra' => ['gorras']
	];

	$outfitIds = [
		'top_main' => null,
		'top_layer' => null,
		'bottom' => null,
		'shoes' => null,
		'extra' => null
	];

	$rawOutfit = is_array($aiResult['outfit'] ?? null) ? $aiResult['outfit'] : [];
	$usedIds = [];
	$strictStyle = normalizeStyleValue($preferredStyle);

	foreach ($outfitIds as $slot => $_) {
		$rawId = $rawOutfit[$slot] ?? null;
		if ($slot === 'bottom' && ($rawId === null || $rawId === '')) {
			$rawId = $rawOutfit['pantalon'] ?? null;
		}
		if ($rawId === null || $rawId === '') {
			$outfitIds[$slot] = null;
			continue;
		}

		$id = (int)$rawId;
		if ($id <= 0 || !isset($idMap[$id])) {
			$outfitIds[$slot] = null;
			continue;
		}

		if (isset($usedIds[$id])) {
			$outfitIds[$slot] = null;
			continue;
		}

		$cat = normalizeText((string)($idMap[$id]['categoria'] ?? ''));
		if (!in_array($cat, $allowed[$slot], true)) {
			$outfitIds[$slot] = null;
			continue;
		}

		if ($strictStyle !== '' && !isStyleCompatible($strictStyle, $idMap[$id])) {
			$outfitIds[$slot] = null;
			continue;
		}

		if (isStyleExcluded($excludedStyles, $idMap[$id])) {
			$outfitIds[$slot] = null;
			continue;
		}

		if ($strictColorMode && !isColorCompatible($requiredColor, $idMap[$id])) {
			$outfitIds[$slot] = null;
			continue;
		}

		$outfitIds[$slot] = $id;
		$usedIds[$id] = true;
	}

	$recommendedIds = [];
	foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
		$id = $outfitIds[$slot];
		if ($id !== null) {
			$recommendedIds[] = $id;
		}
	}

	$rawRec = is_array($aiResult['recommended_ids'] ?? null) ? $aiResult['recommended_ids'] : [];
	foreach ($rawRec as $rawId) {
		$id = (int)$rawId;
		if ($id <= 0 || !isset($idMap[$id])) {
			continue;
		}
		if ($strictStyle !== '' && !isStyleCompatible($strictStyle, $idMap[$id])) {
			continue;
		}
		if (isStyleExcluded($excludedStyles, $idMap[$id])) {
			continue;
		}
		if ($strictColorMode && !isColorCompatible($requiredColor, $idMap[$id])) {
			continue;
		}
		if (!in_array($id, $recommendedIds, true)) {
			$recommendedIds[] = $id;
		}
		if (count($recommendedIds) >= 6) {
			break;
		}
	}

	$recommended = [];
	foreach ($recommendedIds as $id) {
		$recommended[] = $idMap[$id];
	}

	$outfit = [
		'top_main' => $outfitIds['top_main'] !== null ? $idMap[$outfitIds['top_main']] : null,
		'top_layer' => $outfitIds['top_layer'] !== null ? $idMap[$outfitIds['top_layer']] : null,
		'bottom' => $outfitIds['bottom'] !== null ? $idMap[$outfitIds['bottom']] : null,
		'shoes' => $outfitIds['shoes'] !== null ? $idMap[$outfitIds['shoes']] : null,
		'extra' => $outfitIds['extra'] !== null ? $idMap[$outfitIds['extra']] : null
	];
	$outfit = withPantalonAlias($outfit);

	$reply = trim((string)($aiResult['reply_text'] ?? ''));
	if ($reply === '') {
		$reply = 'He preparado una propuesta de outfit con productos de tu catalogo.';
	}

	return [
		'reply_text' => $reply,
		'recommended_products' => $recommended,
		'outfit' => $outfit
	];
}

try {
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$payload = [
			'message' => isset($_GET['message']) ? (string)$_GET['message'] : '',
			'base_product_id' => isset($_GET['base_product_id']) ? (int)$_GET['base_product_id'] : 0,
			'presupuesto' => isset($_GET['presupuesto']) ? (float)$_GET['presupuesto'] : null
		];
	} else {
		$payload = json_decode(file_get_contents('php://input'), true);
		if (!is_array($payload)) {
			$payload = [];
		}
	}

	$action = trim((string)($payload['action'] ?? ''));
	if ($action === 'reset') {
		unset($_SESSION['ai_stylist_recent_outfits']);
		echo json_encode([
			'ok' => true,
			'message' => 'Asistente reiniciado correctamente.'
		], JSON_UNESCAPED_UNICODE);
		exit;
	}

	$message = trim((string)($payload['message'] ?? ''));
	$baseProductId = (int)($payload['base_product_id'] ?? 0);
	$presupuesto = isset($payload['presupuesto']) && $payload['presupuesto'] !== ''
		? (float)$payload['presupuesto']
		: null;

	if ($message === '' && $baseProductId <= 0) {
		http_response_code(400);
		echo json_encode([
			'ok' => false,
			'message' => 'Debes enviar message o base_product_id.'
		], JSON_UNESCAPED_UNICODE);
		exit;
	}

	$baseProduct = null;

	if ($baseProductId > 0) {
		$stmtBase = $conexion->prepare(
			"SELECT p.id_producto, p.nombre, p.precio, p.color, p.estilo, p.id_categoria, c.nombre AS categoria
			 FROM productos p
			 LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
			 WHERE p.id_producto = :id_producto AND (p.oculto = 0 OR p.oculto IS NULL)
			 LIMIT 1"
		);
		$stmtBase->bindValue(':id_producto', $baseProductId, PDO::PARAM_INT);
		$stmtBase->execute();
		$rowBase = $stmtBase->fetch(PDO::FETCH_ASSOC);

		if (!$rowBase) {
			http_response_code(404);
			echo json_encode([
				'ok' => false,
				'message' => 'La prenda base no existe o no esta disponible.'
			], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$baseProduct = mapProducto($rowBase);
	}

	$params = [];
	$whereParts = ['(p.oculto = 0 OR p.oculto IS NULL)'];

	$sql = "SELECT p.id_producto, p.nombre, p.precio, p.color, p.estilo, c.nombre AS categoria
			FROM productos p
			LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
			WHERE " . implode(' AND ', $whereParts) . "
			ORDER BY p.id_producto ASC
			LIMIT 120";

	$stmt = $conexion->prepare($sql);
	foreach ($params as $key => $value) {
		$stmt->bindValue($key, $value);
	}
	$stmt->execute();
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$candidates = [];
	if ($baseProduct !== null) {
		$candidates[] = $baseProduct;
	}
	foreach ($rows as $row) {
		$mapped = mapProducto($row);
		if ($baseProduct !== null && (int)$mapped['id_producto'] === (int)$baseProduct['id_producto']) {
			continue;
		}
		$candidates[] = $mapped;
	}

	$messageStyle = detectStyleFromMessage($message);
	$excludedStyles = detectExcludedStylesFromMessage($message);
	$preferredStyle = $messageStyle ?: ($baseProduct['estilo'] ?? null);
	$userExplicitStyle = normalizeStyleValue($messageStyle) !== '';
	if (!empty($excludedStyles) && $preferredStyle !== null) {
		$preferredNormalized = normalizeStyleValue($preferredStyle);
		$excludedNormalized = array_map('normalizeStyleValue', $excludedStyles);
		if (in_array($preferredNormalized, $excludedNormalized, true)) {
			$excludedStyles = [];
		}
	}
	$preferredColors = detectColorsFromMessage($message);
	$layerPreference = detectLayerPreferenceFromMessage($message);
	$strictColorMode = detectStrictMonochromeIntent($message);
	$requiredColor = $strictColorMode ? detectPrimaryColorFromMessage($message) : null;
	if ($strictColorMode && $requiredColor !== null) {
		$preferredColors = [normalizeColorValue($requiredColor)];
	}

	$scored = [];
	foreach ($candidates as $candidate) {
		$candidate['_score'] = scoreProducto($candidate, $baseProduct, $preferredStyle, $preferredColors, $presupuesto);
		$scored[] = $candidate;
	}

	usort($scored, static function ($a, $b) {
		$scoreA = (float)($a['_score'] ?? 0);
		$scoreB = (float)($b['_score'] ?? 0);
		if ($scoreA === $scoreB) {
			return ((float)($a['precio'] ?? 0)) <=> ((float)($b['precio'] ?? 0));
		}
		return $scoreB <=> $scoreA;
	});

	$slotCategories = [
		'top_main' => ['camisetas'],
		'top_layer' => ['chaquetas', 'sudaderas', 'abrigos'],
		'bottom' => ['pantalones', 'vaqueros'],
		'shoes' => ['calzado'],
		'extra' => ['gorras']
	];

	$autoSelectedStyle = null;
	if (normalizeStyleValue($preferredStyle) === '') {
		$autoSelectedStyle = chooseAutoStyleForOutfit($scored, $slotCategories, $excludedStyles, $requiredColor, $strictColorMode);
		if ($autoSelectedStyle !== null) {
			$preferredStyle = $autoSelectedStyle;
		}
	}

	$outfit = [
		'top_main' => null,
		'top_layer' => null,
		'bottom' => null,
		'shoes' => null,
		'extra' => null
	];

	$selectedIds = [];
	if ($baseProduct !== null) {
		$baseCategory = normalizeText((string)($baseProduct['categoria'] ?? ''));
		foreach ($slotCategories as $slot => $allowedCategories) {
			if (in_array($baseCategory, $allowedCategories, true)) {
				$outfit[$slot] = $baseProduct;
				$selectedIds[(int)$baseProduct['id_producto']] = true;
				break;
			}
		}
	}

	if ($outfit['top_main'] === null) {
		$pick = pickFromScoredByCategoriesWithStyle($scored, $slotCategories['top_main'], $selectedIds, $preferredStyle, $requiredColor, $strictColorMode, $excludedStyles);
		if ($pick !== null) {
			$outfit['top_main'] = $pick;
			$selectedIds[(int)$pick['id_producto']] = true;
		}
	}

	if ($outfit['top_layer'] === null) {
		$pick = pickFromScoredByCategoriesWithStyle($scored, $slotCategories['top_layer'], $selectedIds, $preferredStyle, $requiredColor, $strictColorMode, $excludedStyles);
		if ($pick !== null) {
			$outfit['top_layer'] = $pick;
			$selectedIds[(int)$pick['id_producto']] = true;
		}
	}

	if ($outfit['bottom'] === null) {
		$pick = pickFromScoredByCategoriesWithStyle($scored, $slotCategories['bottom'], $selectedIds, $preferredStyle, $requiredColor, $strictColorMode, $excludedStyles);
		if ($pick !== null) {
			$outfit['bottom'] = $pick;
			$selectedIds[(int)$pick['id_producto']] = true;
		}
	}

	if ($outfit['shoes'] === null) {
		$pick = pickFromScoredByCategoriesWithStyle($scored, $slotCategories['shoes'], $selectedIds, $preferredStyle, $requiredColor, $strictColorMode, $excludedStyles);
		if ($pick !== null) {
			$outfit['shoes'] = $pick;
			$selectedIds[(int)$pick['id_producto']] = true;
		}
	}

	if ($outfit['extra'] === null) {
		$pick = pickFromScoredByCategoriesWithStyle($scored, $slotCategories['extra'], $selectedIds, $preferredStyle, $requiredColor, $strictColorMode, $excludedStyles);
		if ($pick !== null) {
			$outfit['extra'] = $pick;
			$selectedIds[(int)$pick['id_producto']] = true;
		}
	}

	$recommended = buildRecommendedFromOutfit($outfit);

	$reply = 'Te sugiero una seleccion de prendas de Veridi para empezar tu look.';
	if ($baseProduct !== null) {
		$reply = 'Perfecto, tomo "' . $baseProduct['nombre'] . '" como prenda base y te propongo un outfit con productos reales del catalogo.';
	} elseif ($presupuesto !== null && $presupuesto > 0) {
		$reply = 'Te propongo un look dentro de un presupuesto maximo de €' . number_format($presupuesto, 2, ',', '.') . '.';
	}

	$apiKey = getOpenAiApiKey();
	$openAiRequired = true;
	$llmUsed = false;
	$openAiConfigured = $apiKey !== '';
	$curlAvailable = function_exists('curl_init');
	$scoredPool = stripInternalProductFields($scored);
	$candidatePool = array_values(array_slice($scoredPool, 0, 30));
	$openAiAttempts = 0;
	$openAiLastFailure = '';
	$openAiLastHttpCode = null;
	$openAiLastCurlError = '';
	$openAiLastStage = '';
	$openAiLastResponseExcerpt = '';

	if ($openAiRequired && !$openAiConfigured) {
		http_response_code(503);
		echo json_encode([
			'ok' => false,
			'message' => 'OpenAI no esta configurado en el servidor. La recomendacion IA no esta disponible.',
			'meta' => [
				'openai_required' => true,
				'openai_status' => 'openai_no_config'
			]
		], JSON_UNESCAPED_UNICODE);
		exit;
	}

	if ($openAiRequired && !$curlAvailable) {
		http_response_code(503);
		echo json_encode([
			'ok' => false,
			'message' => 'El servidor no tiene cURL habilitado, por lo que OpenAI no puede usarse.',
			'meta' => [
				'openai_required' => true,
				'openai_status' => 'openai_no_curl'
			]
		], JSON_UNESCAPED_UNICODE);
		exit;
	}

	if ($apiKey !== '') {
		for ($try = 1; $try <= 3; $try++) {
			$openAiAttempts = $try;
			$openAiDebug = null;
			$aiRaw = callOpenAiStylist($apiKey, $message, $presupuesto, $preferredStyle, $baseProduct, $candidatePool, $layerPreference, $excludedStyles, $openAiDebug);
			$openAiLastHttpCode = $openAiDebug['http_code'] ?? null;
			$openAiLastCurlError = (string)($openAiDebug['curl_error'] ?? '');
			$openAiLastStage = (string)($openAiDebug['stage'] ?? '');
			$openAiLastResponseExcerpt = (string)($openAiDebug['response_excerpt'] ?? '');
			if (!is_array($aiRaw)) {
				$openAiLastFailure = 'request_or_http';
				continue;
			}

			$aiClean = sanitizeOpenAiStylistResult($aiRaw, $candidatePool, $preferredStyle, $requiredColor, $strictColorMode, $excludedStyles);
			if (!is_array($aiClean)) {
				$openAiLastFailure = 'invalid_or_unsanitizable_payload';
				continue;
			}

			$reply = $aiClean['reply_text'];
			$recommended = $aiClean['recommended_products'];
			$outfit = $aiClean['outfit'];
			$llmUsed = true;
			break;
		}

		if ($openAiRequired && !$llmUsed) {
			$detailParts = [];
			if ($openAiLastHttpCode !== null) {
				$detailParts[] = 'http_code=' . (string)$openAiLastHttpCode;
			}
			if ($openAiLastStage !== '') {
				$detailParts[] = 'stage=' . $openAiLastStage;
			}
			if ($openAiLastCurlError !== '') {
				$detailParts[] = 'curl_error=' . $openAiLastCurlError;
			}
			$detailText = empty($detailParts) ? '' : (' Detalle: ' . implode(', ', $detailParts) . '.');

			http_response_code(502);
			echo json_encode([
				'ok' => false,
				'message' => 'OpenAI no respondio correctamente tras varios intentos.' . $detailText,
				'meta' => [
					'openai_required' => true,
					'openai_status' => 'openai_failed_hard',
					'openai_attempts' => $openAiAttempts,
					'openai_last_failure' => $openAiLastFailure,
					'openai_last_http_code' => $openAiLastHttpCode,
					'openai_last_stage' => $openAiLastStage,
					'openai_last_curl_error' => $openAiLastCurlError,
					'openai_last_response_excerpt' => $openAiLastResponseExcerpt
				]
			], JSON_UNESCAPED_UNICODE);
			exit;
		}
	}

	$budgetNormalization = normalizeOutfitToBudget($outfit, $scoredPool, $slotCategories, $presupuesto, $preferredStyle, $requiredColor, $strictColorMode, $excludedStyles);
	$outfit = $budgetNormalization['outfit'];
	$recommended = $budgetNormalization['recommended_products'];
	$outfitTotal = (float)($budgetNormalization['total'] ?? 0);
	$budgetRespected = (bool)($budgetNormalization['budget_respected'] ?? true);
	$budgetAdjusted = (bool)($budgetNormalization['budget_adjusted'] ?? false);
	$mandatoryRequired = (bool)($budgetNormalization['mandatory_required'] ?? false);

	$signatureBeforeDiversity = buildOutfitSignature($outfit);
	$outfit = diversifyOutfitWithHistory($outfit, $scoredPool, $slotCategories, $presupuesto, $mandatoryRequired, $preferredStyle, $requiredColor, $strictColorMode, $excludedStyles);
	$signatureAfterDiversity = buildOutfitSignature($outfit);
	$recommended = buildRecommendedFromOutfit($outfit);
	$strictEnforcement = enforceStrictStyleSelection($outfit, $recommended, $preferredStyle);
	$outfit = $strictEnforcement['outfit'];
	$recommended = $strictEnforcement['recommended_products'];
	$strictStyleApplied = (bool)($strictEnforcement['applied'] ?? false);
	$strictStyleRemovedItems = (int)($strictEnforcement['removed_items'] ?? 0);
	$layerEnforcement = enforceLayerPreferenceOnOutfit(
		$outfit,
		$scoredPool,
		$slotCategories,
		$preferredStyle,
		$requiredColor,
		$strictColorMode,
		$excludedStyles,
		$layerPreference
	);
	$outfit = $layerEnforcement['outfit'];
	$recommended = buildRecommendedFromOutfit($outfit);
	$layerRuleApplied = (bool)($layerEnforcement['applied'] ?? false);
	$layerRuleMissingRequired = (bool)($layerEnforcement['missing_required_layer'] ?? false);
	$layerRuleAddedLayer = (bool)($layerEnforcement['added_layer'] ?? false);
	$layerRuleRemovedLayer = (bool)($layerEnforcement['removed_layer'] ?? false);
	$coldWeatherRequested = $layerPreference === 'require';
	$coldBottomEnforcement = enforceColdWeatherBottomRule(
		$outfit,
		$scoredPool,
		$slotCategories,
		$preferredStyle,
		$requiredColor,
		$strictColorMode,
		$excludedStyles,
		$coldWeatherRequested
	);
	$outfit = $coldBottomEnforcement['outfit'];
	$recommended = buildRecommendedFromOutfit($outfit);
	$coldBottomRuleApplied = (bool)($coldBottomEnforcement['applied'] ?? false);
	$coldBottomRuleReplacedShort = (bool)($coldBottomEnforcement['replaced_short'] ?? false);
	$coldBottomRuleMissingNonShort = (bool)($coldBottomEnforcement['missing_non_short_bottom'] ?? false);
	$outfitTotal = calculateOutfitTotal($outfit);
	$budgetRespected = $presupuesto === null || $presupuesto <= 0 ? true : ($outfitTotal <= $presupuesto);
	$diversified = $signatureBeforeDiversity !== $signatureAfterDiversity;
	$strictStyleRequested = $userExplicitStyle;
	$missingMandatoryByStyle =
		!is_array($outfit['top_main'] ?? null)
		|| !is_array($outfit['bottom'] ?? null)
		|| !is_array($outfit['shoes'] ?? null);
	$strictColorRequested = $strictColorMode && $requiredColor !== null && $requiredColor !== '';
	$missingColorSlots = [];
	if ($strictColorRequested) {
		foreach (['top_main', 'top_layer', 'bottom', 'shoes', 'extra'] as $slot) {
			if (!is_array($outfit[$slot] ?? null)) {
				$missingColorSlots[] = mb_strtolower(slotLabel($slot), 'UTF-8');
			}
		}
	}

	if ($strictStyleRequested && $missingMandatoryByStyle) {
		$reply = 'Aplique filtro estricto por estilo y no hay suficientes prendas disponibles para completar camiseta, pantalon y calzado solo en ese estilo.';
	}

	if ($strictColorRequested && !empty($missingColorSlots)) {
		$reply = 'No existe prenda disponible de color ' . normalizeColorValue((string)$requiredColor)
			. ' para: ' . implode(', ', $missingColorSlots) . '.';
	}

	if ($layerPreference === 'require' && $layerRuleMissingRequired) {
		$reply = 'Busque un look de invierno/frio, pero no hay capa superior disponible que cumpla los filtros actuales.';
	}

	if ($layerPreference === 'forbid') {
		$reply = 'Prepare un look pensado para verano/calor, sin capa superior.';
	}

	if ($coldWeatherRequested && $coldBottomRuleMissingNonShort) {
		$reply = 'Busque un look de invierno/frio sin pantalones cortos, pero no hay pantalon largo disponible que cumpla los filtros actuales.';
	}

	$openAiStatus = 'openai_ok';
	if (!$llmUsed) {
		if (!$openAiConfigured) {
			$openAiStatus = 'openai_no_config';
		} elseif (!$curlAvailable) {
			$openAiStatus = 'openai_no_curl';
		} else {
			$openAiStatus = 'openai_fallback';
		}
	}

	$outfitReasons = buildOutfitReasons(
		$outfit,
		$preferredStyle,
		$preferredColors,
		$presupuesto,
		$outfitTotal,
		$budgetRespected
	);
	$productReasons = buildProductReasons($recommended, $outfitReasons, $outfit);

	if ($presupuesto !== null && $presupuesto > 0 && !$budgetRespected) {
		$reply = 'Con el presupuesto indicado no hay combinacion completa disponible. Te muestro la mejor alternativa posible.';
	}
	if ($presupuesto !== null && $presupuesto > 0 && !$mandatoryRequired) {
		$reply .= ' Con ese presupuesto no siempre es posible incluir camiseta, pantalon y calzado al mismo tiempo.';
	}
	if ($presupuesto !== null && $presupuesto > 0 && $budgetAdjusted) {
		$reply .= ' Ajuste el outfit para respetar el limite de €' . number_format($presupuesto, 2, ',', '.') . '.';
	}

	echo json_encode([
		'ok' => true,
		'reply_text' => $reply,
		'recommended_products' => $recommended,
		'outfit' => $outfit,
		'outfit_reasons' => $outfitReasons,
		'product_reasons' => $productReasons,
		'meta' => [
			'openai_required' => $openAiRequired,
			'openai_attempts' => $openAiAttempts,
			'mvp_mode' => !$llmUsed,
			'llm_used' => $llmUsed,
			'openai_status' => $openAiStatus,
			'openai_configured' => $openAiConfigured,
			'curl_available' => $curlAvailable,
			'outfit_total' => $outfitTotal,
			'budget_respected' => $budgetRespected,
			'budget_adjusted' => $budgetAdjusted,
			'diversified' => $diversified,
			'strict_style_applied' => $strictStyleApplied,
			'strict_style_removed_items' => $strictStyleRemovedItems,
			'layer_preference' => $layerPreference,
			'layer_rule_applied' => $layerRuleApplied,
			'layer_rule_missing_required_layer' => $layerRuleMissingRequired,
			'layer_rule_added_layer' => $layerRuleAddedLayer,
			'layer_rule_removed_layer' => $layerRuleRemovedLayer,
			'cold_bottom_rule_applied' => $coldBottomRuleApplied,
			'cold_bottom_rule_replaced_short' => $coldBottomRuleReplacedShort,
			'cold_bottom_rule_missing_non_short_bottom' => $coldBottomRuleMissingNonShort,
			'auto_selected_style' => $autoSelectedStyle,
			'strict_color_requested' => $strictColorRequested,
			'strict_color' => $strictColorRequested ? normalizeColorValue((string)$requiredColor) : '',
			'strict_color_missing_slots' => $missingColorSlots,
			'mandatory_required' => $mandatoryRequired,
			'preferred_style' => normalizeStyleValue($preferredStyle),
			'excluded_styles' => $excludedStyles,
			'used_base_product' => $baseProduct !== null,
			'used_budget' => $presupuesto !== null && $presupuesto > 0,
			'message' => $message
		]
	], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
	http_response_code(500);
	echo json_encode([
		'ok' => false,
		'message' => 'No se pudo generar la recomendacion en este momento.'
	], JSON_UNESCAPED_UNICODE);
}

