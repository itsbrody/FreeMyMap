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
        
        return null;
    }
    
    private static function validated(float $lat, float $lon): ?array {
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return null;
        }
        return ['lat' => $lat, 'lon' => $lon];
    }
    
    private static function resolveShortUrl(string $url): ?string {
        if (!ini_get('allow_url_fopen')) {
            return null;
        }
        
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'follow_location' => 0,
                'timeout' => 5,
                'max_redirects' => 0,
            ]
        ]);
        
        $resolved = $url;
        for ($i = 0; $i < 5; $i++) {
            $headers = @get_headers($resolved, true, $context);
            if ($headers === false) break;
            
            $location = $headers['Location'] ?? $headers['location'] ?? null;
            if (is_array($location)) {
                $location = end($location);
            }
            if ($location) {
                $resolved = $location;
            } else {
                break;
            }
        }
        
        return ($resolved !== $url) ? $resolved : null;
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
