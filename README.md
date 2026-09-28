# Promo Radar

A Morocco-focused offer tracker: Laravel 13 REST API, Nuxt 4 client, PostgreSQL, Redis queues, and an isolated Playwright extraction worker. The API is versioned for a future mobile app.

## Architecture

- **Catalog**: products, retailer offers, price history, online/store availability, city, freshness, and source provenance.
- **Collection**: JSON feed, browser extraction profile (JSON-LD + CSS selectors), and authenticated import endpoint for CSV/JSON exports from Ultimate Web Scraper.
- **Processing**: scheduled collection, normalized upserts, meaningful-change detection, keyword watches, deduplicated in-app alerts, and queued optional mail.
- **Delivery**: `/api/v1` offers, authentication with Sanctum bearer tokens, watches, notifications, and admin source operations.

## Run with Docker

Copy `.env.example` to `.env`, set `APP_KEY` (a Laravel base64 key), change the initial admin credentials in `.env`, then `docker compose up -d --build`. Run migrations in the API container: `docker compose exec api php artisan migrate --force`. Open the Nuxt client on port 3000. The first account is created with `php artisan promo:owner` using `OWNER_EMAIL` and `OWNER_PASSWORD`. Remove those variables after setup. API is on port 8000. The scheduler and queue worker are separate containers. Docker build generates the standard Laravel skeleton, then overlays versioned custom code in `backend/`.

The application does not bundle retailer credentials or claim to have live offers. Add a source profile after confirming a retailer's extraction permission and page structure. Use JSON feed where available. For Ultimate Web Scraper, schedule a cloud extraction and export the result as JSON/CSV, then send it to the authenticated `/api/v1/admin/imports` endpoint (or upload it from a trusted integration). Its published cloud product can push finished files to a webhook, but the exact payload/headers were not documented in the pages reviewed, so automatic webhook compatibility is intentionally not claimed. A provider-specific webhook parser can be added when a real sample is available.

## Source profiles

The owner creates `POST /api/v1/admin/sources` with name, retailer, driver (`json_feed` or `browser`), URL, allowed host, interval in minutes, and optional mapping. JSON feed mapping uses keys such as `title`, `price`, `old_price`, `url`, `image`, `sku`. Browser profiles use CSS selectors and optional pagination selector. Browser worker extracts JSON-LD Product data first and applies selectors as a fallback. URL allowlisting, robots.txt checks, rate limits and page caps are enforced at the worker.

The import endpoint accepts `{source_id, rows: [...]}`. Each row can use canonical fields: `title`, `price`, `original_price`, `url`, `image_url`, `sku`, `brand`, `category`, `city`, `channel`, `description`, `currency`; source mapping converts alternate column names. A stable source URL or SKU is required to avoid duplicates. Prices are stored as integer minor units; MAD values use two decimal places.

## API overview

- Public: `GET /api/v1/offers`, `GET /api/v1/offers/{offer}`, `GET /api/v1/filters`
- Auth: `POST /api/v1/register`, `POST /api/v1/login`, `POST /api/v1/logout`, `GET /api/v1/me`
- Watches: `GET/POST /api/v1/watches`, `PUT/DELETE /api/v1/watches/{watch}`
- Inbox: `GET /api/v1/notifications`, `POST /api/v1/notifications/{id}/read`, `PUT /api/v1/preferences`
- Owner: `GET/POST /api/v1/admin/sources`, `PUT/DELETE /api/v1/admin/sources/{source}`, `POST /api/v1/admin/sources/{source}/run`, `POST /api/v1/admin/imports`

Catalog filters: search, channel, city, retailer, brand, category, minimum/maximum price, minimum discount, freshness, sort and pagination. Watches combine required/excluded phrases, channels, cities, brands, retailers, maximum price and minimum discount. Alerts are unique for a watch, offer and material revision.

## Limits

This is source code, deliberately not executed per request. The Dockerfiles resolve dependencies and create the standard framework scaffold at build time; dependency versions are bounded in the manifests, but no lockfiles or build verification are included. Browser extraction is a configurable engine, not a guarantee that every retailer can be scraped. CAPTCHA, login, anti-bot systems and changing layouts require source-specific work. Do not enable a retailer before checking its permission and data quality.
