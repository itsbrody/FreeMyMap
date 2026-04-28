<?php
/**
 * FreeMyMap - API Handler
 * 
 * POST /api/convert
 * Body: { "url": "https://..." }
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes.php';

header('Content-Type: application/json; charset=utf-8');

// CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!empty(API_ALLOWED_ORIGINS)) {
    if (!in_array($origin, API_ALLOWED_ORIGINS)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Origin not allowed']);
        exit;
    }
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Rate limiting
$rateLimiter = new RateLimiter();
$clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$clientIp = explode(',', $clientIp)[0];

if (!$rateLimiter->check(trim($clientIp))) {
    http_response_code(429);
    echo json_encode([
        'success' => false, 
        'message' => 'Rate limit exceeded. Maximum ' . API_RATE_LIMIT . ' requests per minute.'
    ]);
    exit;
}

// Parse input
$input = json_decode(file_get_contents('php://input'), true);
$url = $input['url'] ?? $input['gmaps_url'] ?? null;

if (empty($url) || !is_string($url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid URL. Send {"url": "https://..."}']);
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^https?:\/\//', $url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
    exit;
}

// Extract & convert
$coords = CoordinateExtractor::extract($url);

if ($coords === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Could not extract coordinates from URL']);
    exit;
}

$zoom = DEFAULT_ZOOM;
$osmUrl = OsmUrlBuilder::build($coords['lat'], $coords['lon'], $zoom);

echo json_encode([
    'success' => true,
    'osm_url' => $osmUrl,
    'coordinates' => [
        'lat' => round($coords['lat'], 6),
        'lon' => round($coords['lon'], 6),
    ],
    'zoom' => $zoom,
]);
