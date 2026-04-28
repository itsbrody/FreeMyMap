<?php
/**
 * FreeMyMap - Configuration
 * 
 * Converts proprietary map URLs (Google Maps, Apple Maps) to OpenStreetMap.
 * Fork of Maps2BayernAtlas by LukaWe (MIT License)
 * Modified: Removed Bavaria/BayernAtlas restriction, global OSM support.
 */

// Rate Limiting (requests per minute per IP)
const API_RATE_LIMIT = 10;

// Allowed Origins for API access (empty array = allow all)
const API_ALLOWED_ORIGINS = [
    // 'https://yourdomain.com',
];

// IPs that bypass rate limiting
const IP_WHITELIST = [
    '127.0.0.1',
    '::1',
];

// Default OSM zoom level
const DEFAULT_ZOOM = 16;
