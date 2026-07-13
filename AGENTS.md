# AGENTS.md — Stouu Games

> This file is intended for AI coding agents working on the Stouu Games project.

## Project overview

Stouu Games is a browser-based HTML5 game portal. It is a static, multi-page website served from ordinary HTML, CSS, JavaScript and a small amount of PHP. There is no build system, framework or package manager in the repository.

- **Repository root:** `/Users/roc/workspace/stouuGames`
- **Primary domain served:** `stouu.com` (referenced in PHP landing script and asset URLs)
- **Purpose:** Present a catalogue of mobile-friendly H5 games, drive traffic to individual game detail pages, and load each game inside an iframe on the play page.
- **Target devices:** Mobile-first, rem-based responsive layout scaled around a 640 px design width.

## Technology stack

| Layer | Technology |
|-------|------------|
| Markup | HTML5 |
| Styling | Plain CSS (`static/css/common.css`) using rem units for mobile scaling |
| Client logic | Plain/vanilla JavaScript (no framework) |
| Server logic | PHP 7+/8 compatible scripts (`ss.php`, `s2s.php`, `s3s.php`) |
| Game hosting | External CDN: `https://oss.gamescdn.top` for game bundles, `https://img.gamescdn.top` for icons/images, `https://icons.gamescdn.top` for category icons |
| Analytics/tracking | jQuery 3.7.1 loaded from CDN; custom logging to `log.appcdn.top`; Google AdSense / AdX; Taboola snippet |
| Ads | Google AdSense (`ca-pub-6740781966751472`) and Google Ad Manager (AdX) slots |

No `package.json`, `pyproject.toml`, `Cargo.toml`, `composer.json`, build tools, test runners, CI/CD configuration, Dockerfile or server configuration files exist in this repository.

## Project structure

```
/Users/roc/workspace/stouuGames
├── 404.html                 # Generic nginx 404 page
├── ads.txt                  # Google AdSense seller declaration
├── bg.jpg                   # Background image used on landing page
├── category.html            # Category listing page
├── game.html                # Game detail page
├── index.html               # Home page
├── play.html                # Fullscreen iframe game player
├── privacy_policy.html      # Privacy policy static page
├── search.html              # Search results page
├── s2s.php                  # AdCash/Adscore postback + fraud check endpoint
├── s3s.php                  # PropellerAds conversion + Cloudflare + Adscore endpoint
├── ss.php                   # Cloudflare + Turnstile + Adscore secure landing page
├── terms-condition.html     # Terms of service static page
└── static/
    ├── css/
    │   └── common.css       # All shared styles (mobile-first, ~636 lines)
    ├── images/              # UI icons and sprites
    └── js/
        ├── ads.js           # AdSense/AdX loader and render helpers
        ├── cate.js          # Category page behaviour
        ├── data.js          # Game catalogue data + shared SDK (`window.HUHUSdk`)
        ├── detail.js        # Game detail page behaviour
        ├── index.js         # Home page behaviour
        ├── play.js          # Iframe game player behaviour
        └── search.js        # Search results page behaviour
```

## Page flow

1. **Landing/security gate:** `ss.php` is a Cloudflare + Turnstile + Adscore protected landing page. On successful verification it redirects to `index.html`.
2. **Home:** `index.html` loads `data.js` and `index.js`, renders a side category menu and several category game panels.
3. **Category:** `category.html?tag=<category>` lists every game in a category.
4. **Detail:** `game.html?id=<id>` shows the game icon, rating placeholder, play button, related games and description.
5. **Play:** `play.html?id=<id>` embeds the game in a full-page iframe.
6. **Search:** `search.html?s=<query>` renders name-matched games.

## Core JavaScript SDK

All pages that show game data load `static/js/data.js`, which defines `window.HUHUSdk`:

- `getDomain()` — returns `window.location.origin`.
- `getImgUrl(e)` — prefixes image paths with `https://img.gamescdn.top`.
- `getIconUrl(e)` — returns a category SVG from `https://icons.gamescdn.top`.
- `getGameUrl(e)` — prefixes game URLs with `https://oss.gamescdn.top`.
- `getUrlParam(e)` — parses query-string values.
- `getCategory()` — groups `HUHUSdk.list` by `category`.
- `searchGames(slug)` — case-insensitive substring search by game name.
- `getGameById(id)` — finds a game by its `id`.
- `getRandomData(e, t)` — returns `t` unique random indices from `[0, e)`.
- `list` — large embedded array of game objects (`id`, `category`, `name`, `url`, `description`, `instruction`, `image`, `isPortrait`).

Each page script creates its own DOM elements from this data. There is no shared templating system.

## PHP server scripts

The repository contains three standalone PHP scripts for traffic validation and ad network postbacks. They are not a web framework; each is a single procedural file.

### `ss.php` — Secure landing page
- Reads Cloudflare headers (`HTTP_CF_BOT_SCORE`, `HTTP_CF_THREAT_SCORE`, `HTTP_CF_CONNECTING_IP`).
- Validates with Cloudflare Turnstile server-side.
- Loads Adscore client-side and can send postbacks to AdCash / PropellerAds.
- On success, redirects to `https://stouu.com/index.html`.

### `s2s.php` — Server-to-server validation
- Receives `subid` from query string.
- Calls `https://api.adscore.com/v2/score` by IP.
- Sends approved/rejected postbacks to AdCash.

### `s3s.php` — Conversion postback
- Receives `subid`.
- Checks Cloudflare bot/threat score.
- Calls Adscore API and, if `score >= 60 && status === "clean"`, fires PropellerAds conversion pixel.

> **Security note for agents:** All three PHP files contain hardcoded secret keys and conversion endpoints (`TURNSTILE_KEY`, `AD_SCORE_KEY`, AdCash/Propeller URLs, Adscore keys, AdSense client ID, ad slots). Do not expose, rotate unnecessarily or commit additional copies of these values. If you modify these files, keep secrets in the same locations or migrate them to environment variables after confirming deployment requirements.

## Styling conventions

- The layout is mobile-first and uses rem units.
- The base rem value is calculated dynamically in JS as `100 * viewportWidth / 640`, so 1 rem ≈ 1/6.4 of the viewport width.
- Shared component classes:
  - `page-head`, `page-foot` — header and footer.
  - `ui-sidepanel` — slide-out category menu.
  - `page-home-catepanel` / `page-list-itemcontainer` — game grid/list layouts.
  - `page-game-detail`, `page-game-preview-foot-playbtn` — detail page.
- CSS is not split by page; all styles live in `static/css/common.css`.
- Some inline styles remain in `play.html` and search markup.

## Code style

- JavaScript is written as plain IIFE modules, not ES modules.
- Mixed indentation and coding style across files; no linter config is present.
- Comments are a mix of English and Chinese.
- Hardcoded API keys, client IDs and placeholder strings (e.g. `ca-pub-xxxxxxxxxxxx`, `xxxxxxxxxxxxxxxxx@gmail.com`) exist in several files.
- Version cache-busting query strings are appended manually (e.g. `common.css?v=20201021`, `index.js?v=20251121`).

## Adding or modifying games

To add a new game:

1. Append a new object to `window.HUHUSdk.list` in `static/js/data.js` with these fields:
   - `id` (string or number, unique)
   - `category` (string, must match an existing category or a new one will be created automatically)
   - `name` (string)
   - `url` (string, path under `https://oss.gamescdn.top`, e.g. `/GameName/index.html`)
   - `description` (string)
   - `instruction` (string)
   - `image` (string, path under `https://img.gamescdn.top`, e.g. `/GameName.jpg`)
   - `isPortrait` (number, 0 or 1)
2. Ensure the image and game bundle are available on the external CDN.
3. Bump the cache-busting query string on `data.js` or the consuming pages if needed.

No build step or JSON data file exists; the catalogue is literally the JavaScript array in `data.js`.

## Ads configuration

`static/js/ads.js` defines `AdsSDK` and switches between two ad providers via `adConfig`:

- `adConfig.adsType === 1` — Google AdSense (default in the live setup).
- `adConfig.adsType !== 1` — Google Ad Manager (AdX) with hardcoded slot definitions.

Pages also contain inline Google AdSense `<ins>` tags and Taboola flush snippets.

## Build and test commands

There is no build system and no automated test suite. To preview locally:

```bash
# From the repository root
python3 -m http.server 8000
# or
php -S localhost:8000
```

Then open `http://localhost:8000/index.html`.

PHP scripts require a PHP server (e.g. `php -S localhost:8000`) because they read `$_SERVER` headers and call external APIs. The Cloudflare/Adscore features will only behave correctly behind Cloudflare or with spoofed headers for local testing.

## Deployment considerations

- The site is static HTML/CSS/JS with a few PHP endpoints; deploy by copying files to any web server that supports PHP.
- Cloudflare is expected in front of the PHP scripts so the `HTTP_CF_*` headers are populated.
- External CDN domains (`gamescdn.top`, `appcdn.top`) must remain reachable from end users.
- AdSense/AdX configuration is hardcoded; changing ad accounts requires editing `static/js/ads.js` and the inline snippets in HTML files.

## Important caveats

- **No package manager / dependency manifest.** jQuery and ad scripts are loaded from public CDNs directly.
- **No tests.** Add tests manually if required; there is no test runner configured.
- **Hardcoded secrets.** Be extremely careful not to leak keys when editing PHP files or sharing snippets.
- **Inconsistent branding.** Footer strings vary across pages (`Stouu Games`, `Mobi Games`, placeholder emails). Verify before publishing.
- **No API/server environment variables.** All configuration is inline.
