<?php
/**
 * FreeMyMap - Core Classes
 * 
 * URL parsing, coordinate extraction, rate limiting.
 * No geographic restrictions – works worldwide.
 */

class CoordinateExtractor {
    
    /**
     * Extract lat/lon from proprietary map URLs.
     * Returns ['lat' => float, 'lon' => float] or null.
     */
    public static function extract(string $url): ?array {
        $url = trim($url);
        
        // Handle shortened Google Maps URLs (maps.app.goo.gl)
        if (preg_match('/maps\.app\.goo\.gl|goo\.gl\/maps/', $url)) {
            $resolved = self::resolveShortUrl($url);
            if ($resolved) {
                $url = $resolved;
            } else {
                return null;
            }
        }
        
        // Pattern 1: Google Maps !3d...!4d... (place markers with precise coords)
        if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $url, $m)) {
            return self::validated((float)$m[1], (float)$m[2]);
        }
        
        // Pattern 2: Google Maps @lat,lon (viewport center)
        if (preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $m)) {
            return self::validated((float)$m[1], (float)$m[2]);
        }
        
        // Pattern 3: Google Maps q=lat,lon (search query)
        if (preg_match('/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $m)) {
            return self::validated((float)$m[1], (float)$m[2]);
        }
        
        // Pattern 4: Apple Maps ll=lat,lon
        if (preg_match('/[?&]ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/', $url, $m)) {
            return self::validated((float)$m[1], (float)$m[2]);
        }
        
        // Pattern 5: Generic lat/lon in URL path or query
        if (preg_match('/(-?\d{1,3}\.\d{4,})[,\/](-?\d{1,3}\.\d{4,})/', $url, $m)) {
            $a = (float)$m[1];
            $b = (float)$m[2];
            if ($a >= -90 && $a <= 90 && $b >= -180 && $b <= 180) {
                return self::validated($a, $b);
            }
        }
        
        // Fallback: Extract place name from /place/.../ URL and geocode via Nominatim
        if (preg_match('#/place/([^/]+)/#', $url, $m)) {
            $result = self::geocodeFromPlacePath($m[1]);
            if ($result) {
                return $result;
            }
        }
        
        return null;
    }
    
    private static function validated(float $lat, float $lon): ?array {
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return null;
        }
        return ['lat' => $lat, 'lon' => $lon];
    }
    
    private static function resolveShortUrl(string $url): ?string {
        if (!function_exists('curl_init')) {
            return null;
        }
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible)',
            CURLOPT_RETURNTRANSFER => true,
        ]);
        curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($finalUrl && $finalUrl !== $url && $httpCode < 400)
            ? $finalUrl
            : null;
    }
    
    /**
     * Extract address from Google Maps /place/ path and geocode via Nominatim.
     * Tries progressively shorter query strings (drops leading name segments).
     */
    private static function geocodeFromPlacePath(string $placeSegment): ?array {
        if (!function_exists('curl_init')) {
            return null;
        }
        
        $decoded = urldecode(str_replace('+', ' ', $placeSegment));
        $parts = array_map('trim', explode(',', $decoded));
        
        // Try progressively: all parts, then drop first, then drop first two, ...
        // Stop when only one part is left (city-level minimum).
        $minParts = 1;
        for ($skip = 0; $skip <= count($parts) - $minParts; $skip++) {
            $query = implode(', ', array_slice($parts, $skip));
            
            $nomUrl = 'https://nominatim.openstreetmap.org/search?q='
                . urlencode($query) . '&format=json&limit=1';
            
            $ch = curl_init($nomUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT      => 'FreeMyMap/1.0 (https://fmm.tools.klare-beratung.de)',
                CURLOPT_TIMEOUT        => 10,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || $response === false) {
                continue;
            }
            
            $results = json_decode($response, true);
            if ($results && count($results) > 0) {
                $lat = (float)$results[0]['lat'];
                $lon = (float)$results[0]['lon'];
                return self::validated($lat, $lon);
            }
        }
        
        return null;
    }
}


class OsmUrlBuilder {
    
    public static function build(float $lat, float $lon, int $zoom = DEFAULT_ZOOM): string {
        $lat = round($lat, 6);
        $lon = round($lon, 6);
        $zoom = max(1, min(19, $zoom));
        return "https://www.openstreetmap.org/#map={$zoom}/{$lat}/{$lon}";
    }
}


class RateLimiter {
    
    private string $storageDir;
    
    public function __construct() {
        $this->storageDir = sys_get_temp_dir() . '/freemymap_ratelimit';
        if (!is_dir($this->storageDir)) {
            @mkdir($this->storageDir, 0755, true);
        }
    }
    
    public function check(string $ip): bool {
        if (in_array($ip, IP_WHITELIST)) {
            return true;
        }
        
        $file = $this->storageDir . '/' . md5($ip) . '.json';
        $now = time();
        $windowStart = $now - 60;
        
        $requests = [];
        if (file_exists($file)) {
            $data = @json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                $requests = array_filter($data, fn($ts) => $ts > $windowStart);
            }
        }
        
        if (count($requests) >= API_RATE_LIMIT) {
            return false;
        }
        
        $requests[] = $now;
        @file_put_contents($file, json_encode(array_values($requests)), LOCK_EX);
        
        return true;
    }
    
    public function cleanup(): void {
        $cutoff = time() - 120;
        foreach (glob($this->storageDir . '/*.json') as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }
}
