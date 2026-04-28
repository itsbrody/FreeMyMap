# FreeMyMap

🗺️ Free your map links – convert proprietary map URLs (Google Maps, Apple Maps) to [OpenStreetMap](https://www.openstreetmap.org). Worldwide, free, open.

> Fork of [Maps2BayernAtlas](https://github.com/LukaWe/Maps2BayernAtlas) by LukaWe (MIT License).  
> Removed Bavaria/BayernAtlas restriction. Now works globally with OpenStreetMap as target.

![PHP](https://img.shields.io/badge/PHP-8.0+-blue)
![License](https://img.shields.io/badge/License-MIT-green)
![No Dependencies](https://img.shields.io/badge/Dependencies-none-brightgreen)

## What Changed from the Original

| Feature | Maps2BayernAtlas | FreeMyMap |
|---|---|---|
| Target map | BayernAtlas (Bavaria only) | OpenStreetMap (worldwide) |
| Geographic limit | Bavaria polygon check | None – works globally |
| Coordinate transform | WGS84 → UTM Zone 32N | WGS84 only (no transform needed) |
| Supported sources | Google Maps, OSM | Google Maps, Apple Maps |
| Rate Limiting | ✅ | ✅ |

## Features

- ✅ **Worldwide** – No geographic restrictions
- ✅ **Multi-Source** – Google Maps & Apple Maps URLs
- ✅ **OpenStreetMap Target** – Free, open, community-maintained maps
- ✅ **REST API** – Programmatic access with JSON
- ✅ **Rate Limiting** – Built-in abuse protection (configurable)
- ✅ **Multi-Language** – German & English UI
- ✅ **No Dependencies** – Pure PHP, no external libraries
- ✅ **Standalone Version** – `standalone.html` works locally without a server
- ✅ **Clipboard Fallback** – Works in Brave and `file://` contexts

## Supported URL Formats

| Source | Format | Example |
|---|---|---|
| Google Maps | Shortened | `https://maps.app.goo.gl/xyz123` (server only) |
| Google Maps | Place/Marker | `https://www.google.de/maps/place/.../@lat,lon/data=!3d...!4d...` |
| Google Maps | Coordinates | `https://www.google.com/maps/@48.137,11.575,15z` |
| Google Maps | Search query | `https://www.google.com/maps?q=48.137,11.575` |
| Apple Maps | Link | `https://maps.apple.com/?ll=48.137,11.575` |

## Quick Start

### Standalone (no server needed)

Just open `standalone.html` in any browser. Done.

### With PHP Server

```bash
git clone https://github.com/itsbrody/FreeMyMap.git
cd FreeMyMap
php -S localhost:8080
```

Open `http://localhost:8080` in your browser.

**Requirements:** PHP 8.0+, `allow_url_fopen = On` (for short URL expansion)

## Configuration

Edit `includes/config.php`:

```php
// Rate Limiting (requests per minute per IP)
const API_RATE_LIMIT = 10;

// Allowed Origins for API access (empty = allow all)
const API_ALLOWED_ORIGINS = [
    'https://yourdomain.com',
];

// IPs that bypass rate limiting
const IP_WHITELIST = [
    '127.0.0.1',
];
```

## API

```
POST /api/convert
Content-Type: application/json

{"url": "https://www.google.com/maps/@41.6938,44.8015,15z"}
```

Response:

```json
{
  "success": true,
  "osm_url": "https://www.openstreetmap.org/#map=16/41.693800/44.801500",
  "coordinates": {"lat": 41.6938, "lon": 44.8015},
  "zoom": 16
}
```

| HTTP Code | Description |
|---|---|
| 200 | Successful conversion |
| 400 | Bad request (missing/invalid URL) |
| 403 | Origin not allowed |
| 429 | Rate limit exceeded |

## Project Structure

```
freemymap/
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── includes/
│   ├── config.php
│   ├── classes.php
│   └── api.php
├── lang/
│   ├── de.json
│   └── en.json
├── standalone.html
├── index.php
├── .htaccess
├── LICENSE
└── README.md
```

## License

MIT License – see [LICENSE](LICENSE).

Based on [Maps2BayernAtlas](https://github.com/LukaWe/Maps2BayernAtlas) by LukaWe.

---

**Free Software · Free Data · Free Maps** 🌍
