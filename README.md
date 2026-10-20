# Lekhoni: headless WooCommerce theme

Lekhoni (লেখনী) is a Bangla stationery and gift shop. WordPress and WooCommerce
are the **back end** (products, orders, customers, content), and a **Next.js app**
is the storefront customers actually visit. This theme is the WordPress half: it
registers the content fields and menus, and sends visitors of the WordPress
front end to the Next.js site.

```
 Customer's browser
        │
        ▼
 Next.js storefront  (lekhoni/, http://localhost:3000)
   • pages, cart, checkout, account
   • /api/* route handlers keep secrets server-side (httpOnly cookies)
        │  GraphQL (server to server)
        ▼
 WordPress + WooCommerce  (http://woo.local/graphql)
   • WPGraphQL, WooGraphQL, WPGraphQL for ACF, WPGraphQL JWT Authentication
   • this theme: ACF fields, menus, headless redirects, reset-email links,
     contact inbox, newsletter subscribers, bank transfer details in GraphQL
```

The browser never talks to WordPress directly. Every GraphQL call is made by
the Next.js server, so tokens and session ids stay out of client JavaScript.

---

## 1. Requirements

### WordPress plugins

| Plugin | Why |
|---|---|
| WooCommerce | Store, products, orders, customers |
| WPGraphQL | GraphQL API (`/graphql`) |
| WPGraphQL WooCommerce (WooGraphQL) | Products, cart, checkout and customer in GraphQL |
| Advanced Custom Fields PRO | Content fields: Home Shop, About, Contact and Author Profile (section 4) |
| WPGraphQL for ACF | Exposes those fields in GraphQL (`homeShop`, `aboutPage`, `contactPage`, `authorProfile`) |
| **WPGraphQL JWT Authentication** | Customer login (`login`, `refreshJwtAuthToken`) |

The first five are listed in `inc/install.php` (TGM Plugin Activation prompts
for them). **WPGraphQL JWT Authentication is not on wordpress.org and not in
that list.** Install it manually from
<https://github.com/wp-graphql/wp-graphql-jwt-authentication/releases>
(the release `.zip`), then activate it.

### Software
- PHP 8.2+, MySQL 8 (the Local by Flywheel site "woo" provides both)
- Node.js 20+ for the Next.js app

---

## 2. Setup

### WordPress

1. Activate the **Lekhoni** theme and the plugins above.

   > **Quickest start:** after step 2, open **Theme Settings → Demo Import**,
   > pick a homepage demo (Stationery, E-commerce or Cosmetics) and click
   > **Import demo**. It sets up steps 4–7 for you (pages, Reading, menus,
   > style and Theme Settings and, if ticked, the store settings) and fills
   > the shop with that demo's products and posts. See "Demo import" in section 4.
2. Add these to `wp-config.php` above `/* That's all, stop editing! */`:

   ```php
   // Address of the Next.js storefront (no trailing slash). Used for the
   // front-end redirect and for links in password reset emails.
   define('LEKHONI_FRONTEND_URL', 'http://localhost:3000');

   // Signs customer login tokens. Long random string; keep it secret.
   // Changing it logs every customer out.
   define('GRAPHQL_JWT_AUTH_SECRET_KEY', 'paste-a-long-random-string-here');

   // Shared with the storefront's .env.local (same value): lets WordPress
   // tell the storefront to refresh right after Theme Settings or menus are
   // saved. Optional; without it changes show within a few minutes.
   define('LEKHONI_REVALIDATE_SECRET', 'another-long-random-string');
   ```

   Generate each key with, for example,
   `node -e "console.log(require('crypto').randomBytes(48).toString('base64url'))"`.
3. **Settings → Permalinks:** use any "pretty" structure (for example Post name).
   Product URLs must look like `/product/cap/` and categories like
   `/product-category/clothing/`.
4. **Home page:** create a page with one of the three home templates, set it
   as the static front page (Settings → Reading), and fill in its fields
   (section 4). What `/` shows follows "Your homepage displays":

   | Settings → Reading | Storefront `/` |
   |---|---|
   | A static page with the **Home Shop** template | Stationery shop home (`design/home-shop.html`) |
   | A static page with the **Home – E-commerce** template | Multi-department store home (`design/home-ecommerce.html`) |
   | A static page with the **Home – Cosmetics** template | Beauty store home (`design/home-cosmetics.html`) |
   | A static page with any other template | That page's title, image and content |
   | **Your latest posts** (or no page chosen) | Blog home (`design/index.html`) |

   The blog home shows the Site Title and Tagline (Settings → General) as
   its intro, the newest **sticky** post as the featured post (else the
   newest post), recent posts with a "load more" button (batch size: "Blog
   pages show at most"), the blog sidebar, four featured products (else best
   sellers) and the newsletter band. In this mode category, tag and author
   archives sit under `/`, and the "Posts page", if one is set, redirects to `/`.
   Changes show within a few minutes (cache).

   With a static front page, its own slug (for example `/home` from a menu
   link) redirects (307) to `/`, as WordPress does.
5. **Appearance → Menus:** create a menu and tick **Primary menu** (header),
   and another for **Footer menu** (footer "Links" column). Until a menu is
   assigned, the header shows Home and Shop, and the footer shows Shop,
   My Account, About and Contact.
6. **Theme Settings** (admin menu): logo, **style preset**, announcement bar,
   social profiles, header buttons and footer (section 4). Pick the preset
   that matches the home template (E-commerce or Cosmetics) for the full look. Optional: the defaults match the design.
7. **WooCommerce → Settings:** set the currency (BDT shows ৳), enable payment
   methods (cash on delivery works out of the box) and add shipping zones.
   For **Direct bank transfer**, fill in the account details and the
   **Instructions** text; see "Payment methods" in section 4.

### Next.js storefront (`lekhoni/`)

```bash
cd lekhoni
npm install
```

Create `lekhoni/.env.local`:

```
WP_GRAPHQL_URL=http://woo.local/graphql
WP_SITE_URL=http://woo.local
# Same value as in wp-config.php (optional; instant refresh after saving)
LEKHONI_REVALIDATE_SECRET=another-long-random-string
```

```bash
npm run dev      # http://localhost:3000
npm run build && npm start   # production
```

> **Next.js 16 note:** this app uses Next 16 with `cacheComponents: true`.
> Code that reads `cookies()` or `searchParams` must sit inside `<Suspense>`,
> and `params` and `searchParams` are Promises. The version's own docs are in
> `lekhoni/node_modules/next/dist/docs/`; read them before using an API you
> are unsure of (see `lekhoni/AGENTS.md`).

---

## 3. Theme files

| File | Purpose |
|---|---|
| `functions.php` | Theme supports, menu locations (`primary` "Primary menu", `footer` "Footer menu"), includes `inc/*` |
| `inc/acf-settings.php` | **Theme Settings** admin page: the theme's global settings (ACF options page; GraphQL `themeSettings { themeOptions }`) |
| `inc/install.php` | TGM Plugin Activation: required plugin list |
| `inc/acf-helpers.php` | Shared helpers for PHP-defined ACF fields (`lekhoni_acf_field()`, `lekhoni_icon_choices()`) |
| `inc/acf-about.php` | ACF field group **About Page** (shown on the About template; GraphQL `aboutPage`) |
| `inc/acf-contact.php` | ACF field group **Contact Page** (shown on the Contact template; GraphQL `contactPage`) |
| `inc/contact.php` | Contact form: **Contact Messages** admin inbox and the `sendContactMessage` GraphQL mutation |
| `inc/acf-home.php` | ACF field group **Home Shop**: every home page section in one group, one tab per section (shown on the Home Shop template; GraphQL `homeShop`) |
| `inc/acf-home-ecommerce.php` | ACF field group **Home – E-commerce** (template "Home – E-commerce"; GraphQL `homeEcommerce`) |
| `inc/acf-home-cosmetics.php` | ACF field group **Home – Cosmetics** (template "Home – Cosmetics"; GraphQL `homeCosmetics`) |
| `inc/shop-fields.php` | **Icon** on product categories (GraphQL `productCategory { icon }`) and **Swatch Colour** on attribute terms (GraphQL `swatchColor`) |
| `inc/routines.php` | Routine bundle discount: a negative cart fee when every product of a cosmetics routine is in the cart |
| `inc/acf-author.php` | ACF field group **Author Profile** on the user profile screen (GraphQL `authorProfile`) |
| `inc/newsletter.php` | Newsletter: **Subscribers** admin list with CSV export, and the `subscribeNewsletter` GraphQL mutation |
| `inc/payments.php` | GraphQL `bankTransfer { instructions accounts }`: the Direct bank transfer accounts, shown at checkout (when that method is picked) and on the order-received page |
| `inc/email-login.php` | Email link login: the `sendLoginLink` and `loginWithEmailLink` GraphQL mutations (switched on under Theme Settings → Login; section 6) |
| `inc/demo-import.php` | Demo import engine: the steps, lookups by demo marker, and removal |
| `inc/demo-import-admin.php` | **Theme Settings → Demo Import** page, its AJAX endpoints and the "import the demo" notice after activation |
| `inc/demo-import-cli.php` | `wp lekhoni demo list` / `import --demo=<id>` / `remove` |
| `demo/<id>/` (`stationery`, `ecommerce`, `cosmetics`), `demo/shared/` | The demo packages: `content.json`, `images/` and `preview.jpg` per demo, plus images several demos share (built by the storefront's `scripts/build-demo.mjs`; don't edit by hand) |
| `inc/acf-export-*.json` | An old ACF export of the first Home Shop fields. Not loaded; outdated reference only |
| `inc/headless.php` | Headless helpers (below) |
| `inc/preview.php` | Storefront previews of drafts and unsaved changes (section 4, "Previews") |
| `templates/template-home.php` | "Home Shop" page template |
| `templates/template-home-ecommerce.php` | "Home – E-commerce" page template |
| `templates/template-home-cosmetics.php` | "Home – Cosmetics" page template |
| `templates/template-about.php` | "About" page template |
| `templates/template-contact.php` | "Contact" page template |
| `lib/class-tgm-plugin-activation.php` | TGM library |

### `inc/headless.php`
- `lekhoni_frontend_url()` returns `LEKHONI_FRONTEND_URL` (filterable with
  `lekhoni_frontend_url`; it defaults to `http://localhost:3000`).
- **Front-end redirect:** visits to WordPress pages are sent with a 302 to the same
  path on the storefront. These are skipped: admin, previews, Customizer,
  AJAX, cron, REST, GraphQL, feeds and robots.
- The admin bar's **Visit Site** link points to the storefront.
- **Storefront links:** the links for published posts, pages, products,
  categories, tags, product categories and authors point to the storefront.
  This covers "View" in the admin lists, "View post" in the editor, the admin
  bar, and emails (`http://woo.local/which-pen-suits-you/` becomes
  `{LEKHONI_FRONTEND_URL}/which-pen-suits-you`). These keep their WordPress link:
  - drafts, private and password-protected posts (their "Preview" opens a
    storefront preview instead; section 4, "Previews");
  - every GraphQL request, because WPGraphQL's `uri` is built from these links;
  - a product or product category whose permalink base isn't `/product/` or
    `/product-category/` (Settings → Permalinks).

  The filter `lekhoni_frontend_link_routes` changes which kinds are
  rewritten.
- **Instant refresh:** `lekhoni_revalidate($tags)` posts to the storefront's
  `/api/revalidate` (secret header from `LEKHONI_REVALIDATE_SECRET`; doesn't
  wait for an answer). It runs when Theme Settings are saved
  (`theme-settings`), when a menu or the menu locations change (`menus`), and
  when the Site Title, Tagline or Site Icon change. Without the secret nothing
  is sent.
- **GraphQL `siteIcon { small large }`:** the Site Icon (Settings → General),
  used as the storefront's favicon.
- **Password reset links** go to the storefront's `/reset-password` page. Both
  WordPress's email (`retrieve_password_message`) and WooCommerce's email
  (the `lost-password` endpoint) are rewritten to
  `{LEKHONI_FRONTEND_URL}/reset-password?key=…&login=…`.

---

## 4. Editing content

### Demo import (wp-admin → Theme Settings → Demo Import)

One click fills a fresh install with a লেখনী demo, built from the design.
**Pick the homepage demo first:** each card shows a screenshot of its design.

| Demo | Home template, style | Shop |
|---|---|---|
| **Stationery shop** (`stationery`) | Home Shop, Stationery | 12 stationery products in 8 categories (notebooks, pens, mugs, gift boxes…); notebook (ruling) and gift box (colour × size) are variable; 18 reviews; coupon `LEKHONI10` |
| **E-commerce store** (`ecommerce`) | Home – E-commerce, E-commerce | 17 products in 6 departments with icons (electronics, fashion, home & kitchen, beauty, sports, books); smartwatch (colour, with swatches) and shirt (size) are variable; deals, category rows, the MEGA15 coupon banner; coupon `MEGA15` |
| **Cosmetics store** (`cosmetics`) | Home – Cosmetics, Cosmetics | 13 beauty products in 6 categories with images; 6 skin concerns as product tags; lipstick in 5 shades with swatch colours; morning and night routines (15% bundle discount); 3 extra "বিউটি টিপস" posts; coupon `GLOW10` |

Every demo also brings the 12 blog posts with two authors (role Author,
random passwords, `@example.com` emails), the About, Contact and ব্লগ (Blog)
pages, the "প্রধান মেনু" (Shop with that demo's category submenu) and
"ফুটার মেনু" menus, and its announcement bar text.

**Options on the page:**
- **Use the demo homepage, menus, style and Theme Settings** (ticked): fills
  the demo's Home page and the About and Contact fields (replacing what's
  there), makes that Home the front page and ব্লগ the posts page, assigns the
  menus, and sets Theme Settings: the demo's **style preset**, announcement,
  social links and footer topics.
- **Store settings for Bangladesh** (ticked): currency ৳ (BDT, no decimals),
  store country Bangladesh (Dhaka), a "বাংলাদেশ" shipping zone (flat ৳60, free
  over ৳১,০০০), cash on delivery on, and the demo's coupon.
- **Remove the installed demo first** (ticked; shown when a different demo is
  installed): deletes the installed demo's content before importing the new
  one, so the shop doesn't mix two demos. Untick to keep both.

**How it works:**
- **Small steps:** remove previous demo (when asked), images, terms, products,
  posts, pages, menus, settings, then finish. Each is its own AJAX request,
  so slow hosts don't time out. A full import takes under a minute.
- **The page:** shows the demo choices, a requirements check (WooCommerce, ACF
  PRO, WPGraphQL, WooGraphQL, writable uploads, GD/Imagick, PHP memory) and a
  progress log.
- **Re-running is safe:** every created item carries the meta
  `_lekhoni_demo = "<kind>:<key>"`, so importing the same demo again updates
  its items instead of adding copies. The option `lekhoni_demo_state`
  remembers which demo is installed.
- **Existing items with the same slug are used, never taken over:** pages
  (home, about, contact, blog), categories, attributes and authors are marked
  `_lekhoni_demo_adopted` and are never deleted. An existing page's fields
  are only replaced with the first option ticked.
- **Remove demo content** deletes exactly what the demo created: products
  with their variations and reviews, posts, pages it created, images and
  their files, menus, terms, authors, attributes, the shipping zone and the
  coupon. Your own content stays, and so do changed settings (currency, Theme
  Settings). A homepage or posts page that pointed at a deleted page is reset.
- **Storefront refresh:** after import and removal, the storefront is told to
  refresh every cache tag (needs `LEKHONI_REVALIDATE_SECRET`; otherwise
  within a few minutes).
- **Prompt after activation:** a notice suggests the import until it's done
  or dismissed.
- **Command line:** `wp lekhoni demo list`,
  `wp lekhoni demo import [--demo=<id>] [--keep] [--no-site] [--no-store]`
  (`--keep`: don't remove a different installed demo first) and
  `wp lekhoni demo remove [--yes]`.

**Changing the demos:** edit `lekhoni/scripts/demo-content/`:
`products.mjs`, `posts.mjs` and `site.mjs` (stationery; the posts and
Theme Settings are shared), `ecommerce.mjs`, `cosmetics.mjs`, and
`pages.json` (About/Contact). Then run `node scripts/build-demo.mjs` from
`lekhoni/`. It renders the design's SVGs with sharp (images used by more than
one demo go to `demo/shared/`), takes a screenshot of each design page for the
preview when Chrome or Edge is installed (`CHROME_PATH` to point at one),
writes `demo/<id>/`, and stops if any reference (`@image:`, `@product:`,
`@term:`, `@page:`) points at something missing.

### Theme Settings (wp-admin → Theme Settings): header and footer

The theme's **global settings**. They are stored as site options, not on any
page, so they apply to every page of the storefront. One ACF options page
(`inc/acf-settings.php`) with a tab per area; GraphQL
`themeSettings { themeOptions { … } }`. Everything is optional. An empty
field uses the design's default, and a switch that was never saved counts as
on, so an untouched page looks exactly like the design.

| Tab | Fields | When empty |
|---|---|---|
| Branding | Logo, logo for dark mode, logo width (px) · **Style:** preset (Stationery, E-commerce, Cosmetics) and an optional accent colour | Text logo from the Site Title (Settings → General) with the accent dot: "লেখনী." · Stationery preset, the design's accent |
| Announcement Bar | Show, visitors can close it, icon, text, link text, link URL (`/shop` or `https://…`), show from / until (site timezone) | "৳১,০০০-এর বেশি অর্ডারে ফ্রি ডেলিভারি · আজই অর্ডার করুন" with the truck icon |
| Social Profiles | Repeater: network (Facebook, X, Instagram, YouTube, LinkedIn, RSS) and URL | Icons hidden in the header, footer and blog sidebar |
| Login | **Email link login** on/off, link valid for (5–60 minutes), email subject (`{site}` = Site Title), email text | **Off.** 15 minutes, "{site}-এ লগইন করার লিংক". See "Email link login" in section 6. |
| Header | Switches: social icons, search, wishlist, My Account, dark mode toggle | All on. The cart is always shown; the menu is Appearance → Menus → Primary menu. |
| Footer | Column 1 About (title, text, social icons) · Column 2 Recent posts (title, number) · Column 3 Topics (title; post categories, post tags or product categories; number) · Column 4 Links (title; Appearance → Menus → **Footer menu**) · Bottom bar (payment badges, copyright) | Design texts. Each column has a **Show** switch; empty columns are hidden and the rest close up. Copyright: `© {year} {site} · WordPress ও Next.js দিয়ে তৈরি`, where `{year}` and `{site}` are filled in. |

- **Announcement bar:** a visitor who closes the bar doesn't see it again,
  until its text or link changes. The schedule is checked in the browser, so
  it works on cached pages.
- **Style preset:** changes colours, card corners and the heading font across
  the whole storefront, in light and dark mode (`<html data-preset>`, tokens
  in `lekhoni/app/globals.css`, same values as `design/src/input.css`).
  E-commerce: orange accent, cool grey, squarer cards, Hind Siliguri headings.
  Cosmetics: berry accent, soft blush, rounder cards, Tiro Bangla headings.
  Any preset works with any home template. The accent colour replaces the
  preset's; pick one dark enough for white button text.
- **Favicon:** the Site Icon (Settings → General), falling back to
  `lekhoni/public/favicon.ico`.
- **When changes show:** right after **Save Settings**, when
  `LEKHONI_REVALIDATE_SECRET` is set on both sides (section 2). Otherwise
  within a few minutes. Menu changes refresh the same way.
- **Storefront code:** `lekhoni/lib/theme-settings.ts` (`getThemeSettings()`,
  one cached request with all defaults), used by `Header`, `Footer`,
  `AnnouncementBar`, `header/Logo`, `layout/SocialLinks` and the blog sidebar.

Field keys and names are permanent. ACF stores values by them, so renaming a
field in `inc/acf-settings.php` loses its saved content.

### Home page: ACF field group "Home Shop"

All home page settings live in one field group (`inc/acf-home.php`), shown on
pages using the **Home Shop** template. There is one tab per section, in
homepage order. In GraphQL everything is under `page(id: "/", idType: URI) { homeShop { … } }`.
Every field is optional: an empty section falls back to WooCommerce or
WordPress data, and a section with nothing to show is hidden.

| Tab | Fields | When empty |
|---|---|---|
| Slider | repeater: eyebrow, title, description, image, buy now URL, collection URL | No slider |
| Trust | repeater: icon, title, description | No trust strip |
| Categories | categories (in order) | Top-level categories that have products |
| Flash Sale | title, ends at (site timezone), products (max 12, in order) | "ডিল অফ দ্য ডে"; best-selling on-sale products; countdown to the earliest product sale end date, or none |
| Featured Products | section title, 3 tab labels, products per tab (max 8 each) | Newest / most popular / on sale. A tab with no products is hidden. |
| Promo Banners | screen-reader title, banners: eyebrow, title, background (accent/sale), text, button link, image | Section hidden |
| Spotlight | badge, product (1), highlights (check list) | Best-selling in-stock variable product (then any in-stock product). Variable products get the variant picker. |
| Best Sellers | title, products (max 8) | The 8 most popular products |
| Reviews | title, show (auto / reviews / testimonials / both), testimonials: name, location, rating, verified, text, photo | **Auto** shows approved 4–5★ WooCommerce product reviews, or the testimonials when there are none |
| Brands | title, brands: name, logo, optional link | WooCommerce product brands that have products, else hidden |
| Blog | title, "see all" text, category, number of posts | Latest 3 posts; "see all" goes to the posts page (Settings → Reading) |
| Newsletter | title, text, success message, small print | Default texts; sign-ups go to **Subscribers** |

Field keys and names are permanent. ACF stores values by them, so renaming a
field in `inc/acf-home.php` loses its saved content.

Changes appear on the storefront within about 60 seconds (data is revalidated
every 60s).

### Home page: templates "Home – E-commerce" and "Home – Cosmetics"

Two more home designs, each a page template with its own field group (one
tab per section, in page order). Use one as the static front page. Every
section has a **Show This Section** switch (never saved = on) and falls back
to automatic content when its fields are empty; sections with nothing to show
are hidden.

**Home – E-commerce** (`inc/acf-home-ecommerce.php`, design
`design/home-ecommerce.html`):

| Tab | Fields | When empty |
|---|---|---|
| Hero | Department menu on/off and title; slides (badge, title, text, button, image, background tint); up to two side banners | No slides: hero hidden. Menu: the Departments tab's categories |
| Services | Up to four items (icon, title, text) | Delivery, returns, genuine products, payment |
| Departments | Title; categories in order | Top-level product categories. Icons: Products → Categories → **Icon** |
| Deals | Title, countdown end, products | Best-selling products on sale; countdown from the earliest sale end date. "Sold" bars for products with stock management |
| Popular Products | Title, three tab labels, products per tab | Newest, best sellers, highest rated |
| Coupon Banner | Badge, coupon code, title, text, button | Hidden without a title (create the coupon under Marketing → Coupons) |
| Category Rows | Repeater: category, title, icon, banner (badge, title, image, tint), products | Rows hidden. Products: the category's best sellers |
| Promo Banners | Repeater: badge, title, image, link, tint (three per row) | Hidden |
| Product Lists | Titles of three automatic lists | Highest rated, newest, on sale (3 each) |
| Brands | Title; brands (name, logo, link) | WooCommerce product brands |
| Newsletter | "Recently viewed" on/off; newsletter title, text, success message | Design texts |

**Home – Cosmetics** (`inc/acf-home-cosmetics.php`, design
`design/home-cosmetics.html`):

| Tab | Fields | When empty |
|---|---|---|
| Hero | Image badge, rating line, slides (eyebrow, title, text, two buttons, portrait image shown in an arch) | No slides: hero hidden. The rating line shows the store's average review rating |
| Promises | Up to four items (icon, text) | Genuine, cruelty-free, paraben-free, easy returns |
| Categories | Eyebrow, title, categories | Top-level categories with their Thumbnails |
| Skin Concerns | Eyebrow, title, "All" chip, concerns (chip label + **product tag**), products | Hidden without concerns. Products: best sellers with any of the concern tags. The chips filter on the page |
| Best Sellers | Eyebrow, title, products | The 8 best sellers. Variable products show colour dots from their attribute terms' **Swatch Colour** |
| Routines | Eyebrow, title, text, bundle discount (%), routines (tab name, icon, steps: label + product) | Hidden without routines. Discount 15% |
| Ingredients | Eyebrow, title, ingredients (name, icon, tint, text, good for, link) | Hidden |
| Offer | Eyebrow, countdown end, title, text, button, image | Hidden without a title. The offer itself (e.g. a free gift) is set up in WooCommerce |
| Reviews | Eyebrow, title, source, testimonials (name, skin type, rating, verified, text, product) | Product reviews rated 4–5, else the testimonials |
| Photo Gallery | Eyebrow, title, text, link (e.g. Instagram), photos | Hidden without photos |
| Beauty Tips | Title, link text, category, number of posts | The latest 3 posts |
| Newsletter | Title, text, success message, small print | Design texts |

- **Routine bundles** (`inc/routines.php`): when the cart holds every product
  of a routine, WooCommerce adds a negative fee, e.g. "রুটিন ছাড়: সকালের রুটিন",
  worth the bundle discount on one set. The cart, checkout and order
  confirmation show it. "Add the whole routine" only appears when every step
  is a simple, in-stock product (variable products need a shade first).
- **Category icons and swatches** (`inc/shop-fields.php`): Products →
  Categories → edit → **Icon**; Products → Attributes → (e.g. Shade) →
  Configure terms → edit → **Swatch Colour**.
- **Storefront code:** `lekhoni/app/components/home-ecommerce/` and
  `home-cosmetics/` (data in `data.ts`, one cached request per home), shared
  helpers in `home-kit/`. Changes show within a few minutes (cache).

### Subscribers (wp-admin → Subscribers)

The newsletter band's sign-ups are stored in WordPress. No email provider is
connected.

- The **Subscribers** list shows email, source (`home`, `blog`, …) and date, is
  searchable, and has an **Export CSV** button. The menu badge counts new
  sign-ups since you last opened the list.
- Signing up twice with the same email (any letter case) doesn't create a
  duplicate, and the answer is "subscribed" either way, so the form can't be
  used to find out who's subscribed. Trashing an entry unsubscribes them.
- **How a sign-up travels:** form → `POST /api/subscribe` (honeypot field
  `website`, 5 per visitor per 10 minutes) → GraphQL
  `subscribeNewsletter(input: { email, source }) { success code }`. That allows
  1 per email per minute and 60 per hour site-wide.
- The `<SubscribeBand />` component can be placed on any page. Give each one its
  own `id` and a `source`.

### About page: template "About" + ACF group "About Page"

Edit the page with slug **`about`** and choose the **About** template
(`templates/template-about.php`). The fields only appear in the editor with
that template; they are defined in `inc/acf-about.php` and exposed to GraphQL
as `page { aboutPage { … } }`.

| Section | Source | Storefront behaviour |
|---|---|---|
| Hero | eyebrow, title, highlighted title part, description, primary/secondary button (link), image | Empty title uses the page title. Empty image uses the featured image. |
| Mission | **Page content** (the normal editor) | Rendered with the design's article styles |
| Values | section title + repeater (icon, title, description) | Hidden when empty |
| Stats | repeater (value, label) | Hidden when empty |
| Team | section title + repeater (photo, name, role, profile link, bio) | Hidden when empty |
| Contact CTA | title, text, button | Hidden when the title is empty |

The storefront page is `/about`. It always reads the page with slug `about`,
so keep that slug.

### Contact page: template "Contact" + ACF group "Contact Page"

Edit the page with slug **`contact`** and choose the **Contact** template.
The fields are in `inc/acf-contact.php` (GraphQL `page { contactPage { … } }`).

| Section | Fields | Storefront behaviour |
|---|---|---|
| Header | page title + **Subtitle** | |
| Info cards | repeater: icon, label, value, optional link (`mailto:`, `tel:`, `https://`, `/path`) | Hidden when empty |
| Contact form | title, note, **Subject Options** (one per line), **Send Messages To**, success message | Empty subjects hide the subject field. Empty recipient uses the site admin email. The recipient is **not** exposed in GraphQL. |
| FAQ | section title + repeater (question, answer) | Native `<details>`; hidden when empty |
| Map | Google Maps embed URL, location label, placeholder note | Only `https://www.google.com/maps/embed…` URLs are embedded; otherwise the design's placeholder is shown |

### Contact messages (wp-admin → Contact Messages)

Every form submission is **saved in WordPress** and also **emailed** to the
recipient, with the sender in Reply-To. If the email fails, the message is
still saved and its detail box says "Email sent: No".

- **Contact Messages** menu with an unread-count badge; unread rows are bold.
- Columns: subject, name, email, message excerpt, received date. The list is
  searchable and messages can be trashed.
- Opening a message marks it as read and shows a **Reply by email** button.
- The Dashboard widget **Recent contact messages** shows the latest 5 and the unread count.
- Messages can't be created in the admin; they only come from the form.

**How a message travels:** `/contact` form → `POST /api/contact` (Next.js:
honeypot field `website`, max 5 per visitor per 10 min) → GraphQL
`sendContactMessage(input: { name, email, subject, message }) { success code }`
→ `lekhoni_contact_submit()`. That validates the name (2–100 characters), the
email, the message (20–5000 characters) and that the subject is one of the
configured options. It allows 1 message per email per minute and 30 per hour
site-wide, then stores and emails the message. Error codes (`invalid_email`,
`too_many`, …) are translated to Bangla by the Next.js route.

### Payment methods (WooCommerce → Settings → Payments)

The checkout lists the enabled methods in WooCommerce's order.

- **Titles and descriptions:** cash on delivery, direct bank transfer and
  check payments show Bangla text while their title or description is still
  WooCommerce's default English. Text you edit is shown as written.
- **Direct bank transfer:** the account details (account name, bank, account
  number, sort code, IBAN, BIC) are listed under the method at checkout when
  it is selected. Only the filled-in fields are shown. The order-received page
  then shows the **Instructions** text, the accounts and a reminder to use the
  order number as the payment reference. The data comes from the theme's
  GraphQL field `bankTransfer` (`inc/payments.php`), which returns nothing while
  the method is disabled.
- Changes show within a few minutes (cache).

### Previews (drafts, scheduled, pending, private and unsaved changes)
**Preview** on a post or page (in the editor or the Posts/Pages lists, and
**View** on a private one) opens it on the storefront, laid out like the live
page, with a **প্রিভিউ** bar on top and a **প্রিভিউ বন্ধ করুন** (close) button.
It shows the latest autosave when that is newer, so unsaved edits appear,
just like WordPress's own preview.

How it works (`inc/preview.php`, storefront `lib/preview.ts`):
1. The link goes to `wp-admin/admin-post.php?action=lekhoni_preview&post=ID`
   (with a nonce). That checks the user may edit the post, stores a one-time
   handoff token (2 minutes), and redirects to
   `{LEKHONI_FRONTEND_URL}/api/preview?token=…`. A logged-out user is sent to
   the login first.
2. The storefront trades the token (mutation `lekhoniStartPreview`, single
   use) for a session token. The session lasts 1 hour and is tied to that
   editor and that one post. It is kept in the httpOnly `lk-preview` cookie,
   and the storefront then opens `/preview/post/ID` or `/preview/page/ID`.
3. The preview page reads the post with query `lekhoniPreview(session, id)`,
   uncached. Edit rights are checked again on every request. An expired,
   reused or wrong token, or a visitor without the cookie, gets a "link
   expired" card.

The GraphQL request itself stays anonymous: the session token unlocks only
that post's content, nothing else. Preview pages are `noindex`. Comments,
share buttons, previous/next and related posts are left out.

Not previewed on the storefront: products (WooCommerce keeps WordPress's
preview), and the sections of the Home Shop, About and Contact templates (their
preview shows the page's editor content with a note).

### Other content
- **Product category images:** Products → Categories → Thumbnail.
- **Menu:** Appearance → Menus → location **Primary menu**. Nest items to make
  dropdowns (two levels are styled). A custom link with URL `#` becomes a
  label that only opens its dropdown. Links to `woo.local` become storefront
  paths, and other sites stay external. The **Footer menu** location fills
  the footer's Links column (one level; nested items are listed in order).

---

## 5. Storefront pages

| Route | What it is |
|---|---|
| `/` | Home: the stationery shop home, the e-commerce home, the cosmetics home, a plain page, or the blog home, depending on Settings → Reading and the front page's template (section 2, step 4) |
| `/about` | About page (WordPress page `about`, ACF "About Page") |
| `/contact` | Contact page (WordPress page `contact`, ACF "Contact Page"); form saves to wp-admin |
| `/shop` | All products: filters (category, max price, in stock), sort, pages |
| `/product-category/[...slug]` | Shop layout with a category preselected |
| `/product/[slug]` | Product: gallery, variant picker, tabs, reviews, related, recently viewed |
| `/cart`, `/checkout`, `/order-received` | Cart, checkout (WooCommerce `checkout` mutation; a signed-in customer's saved address is filled in; bank details under Direct bank transfer), confirmation (with bank details for bank transfer orders) |
| `/search` | Product search results (the header's search palette uses the same search) |
| Any other path (`app/[...uri]`) | Resolved with WordPress `nodeByUri`: the Posts page (e.g. `/blog`, with `/page/N`), category and tag archives, author archives (`/author/<slug>`), single posts and other pages (e.g. `/sample-page`). The front page's own slug redirects to `/`; anything else is a 404. |
| `/login`, `/register`, `/forgot-password`, `/reset-password` | Customer auth |
| `/preview/post/[id]`, `/preview/page/[id]` | Editor previews (section 4, "Previews"); `/api/preview` starts one, `/api/preview/exit` ends it |
| `/my-account`, `/my-account/orders`, `/my-account/orders/[id]`, `/my-account/addresses`, `/my-account/details`, `/my-account/wishlist` | My account (same base URL as WooCommerce's) |

---

## 6. Customer authentication

### How it works

Login uses **WPGraphQL JWT Authentication**. WordPress issues two tokens:

| Token | Lifetime (WP default) | Stored in cookie | Cookie lifetime |
|---|---|---|---|
| Auth token | 5 minutes | `lk-auth` | Same as the token |
| Refresh token | 1 year | `lk-refresh` | 30 days ("remember me") or browser session |

Both cookies are **httpOnly, SameSite=Lax, Secure in production**, so client
JavaScript can't read them. The Next.js server reads them and sends
`Authorization: Bearer <auth token>` to WordPress.

```
Login form ──POST /api/auth/login──▶ Next.js route
                                       │ GraphQL: login(username, password)
                                       ▼
                                   WordPress returns authToken + refreshToken
                                       │
              Set-Cookie: lk-auth, lk-refresh (httpOnly)
                                       ▼
Later request ──▶ Next.js server ──▶ lk-auth still valid?
                                       ├─ yes: use it
                                       └─ no:  refreshJwtAuthToken(lk-refresh)
                                               → new auth token
                                               (saved back to the cookie in route
                                                handlers; used for this render only
                                                in Server Components)
```

### Flows

**Login:** `/login` sends `{ username, password, remember }` to `/api/auth/login`.
The username can be an email or a WordPress username; WordPress has no phone
number login. On success the cart badge refreshes and the customer goes to
`?next=` (relative paths only) or `/my-account`. A guest cart carries over to the
customer.

**Register:** `/register` sends to `/api/auth/register`, which calls
`registerCustomer` and then `login`. Two calls are needed because WooGraphQL
can't return tokens in the registration response. The account is signed in
straight away. Note that this works even when WooCommerce's "Allow customers
to create an account on the My account page" setting is off.

**Password rules:** a new password (register, reset, change password under My
account → Details) needs at least 8 characters and must not be rated **weak**
on the strength meter. That means two of: upper and lower case, a digit, a
symbol, or 12+ characters. While the typed password is weak, the meter says
what to add and the submit button is disabled; the API routes reject weak
passwords too (`passwordError()` in `app/components/auth/validation.ts`).
Existing customers can still log in with older, weaker passwords.

**Forgot / reset password:**
1. `/forgot-password` sends to `/api/auth/forgot`, which calls `sendPasswordResetEmail`.
   It always answers OK, so it can't be used to find out which emails are registered.
2. WooCommerce emails a link to `{LEKHONI_FRONTEND_URL}/reset-password?key=…&login=…`.
   This theme rewrites the link (section 3).
3. `/reset-password` sends to `/api/auth/reset`, which calls `resetUserPassword`.
   The key is valid for 24 hours and works only once.

**Email link login** (only while Theme Settings → Login → Email Link Login is on):
1. On `/login`, "ইমেইল লিংক দিয়ে" swaps the password form for an email field.
   It sends to `/api/auth/email-link` (5 per visitor per 10 minutes), which
   calls `sendLoginLink`. The answer is always "if an account exists, a link
   was sent", so it can't be used to find customers.
2. WordPress emails a one-time link, in WooCommerce's email template, to
   `{LEKHONI_FRONTEND_URL}/login/link?token=…&next=…`.
3. `/login/link` shows a **লগইন করুন** button. Logging in waits for this
   click because email scanners (Outlook, Gmail) open links on their own and
   would use up the link. The click posts to `/api/auth/email-link/verify` →
   `loginWithEmailLink`, which returns the same JWT tokens as a password
   login. The cookies are set for 30 days, the cart carries over, and the
   customer goes to `next` or `/my-account`.

   Rules:
   - **Customer accounts only:** staff (anyone who can edit posts, manage
     WooCommerce, or edit users or options) get no email.
   - **One-time and expiring:** each link works once and expires after the
     set minutes. A new link cancels the previous one.
   - **Only a hash is stored:** the token is 256 random bits and WordPress
     keeps only its HMAC.
   - **Limits:** 1 link per email per minute, 5 per hour, 100 per hour site-wide.
   - **Switched off:** both mutations refuse, even for links already sent.

   Test locally with Mailpit (Local → the site → Tools → Mailpit).

**Logout:** `POST /api/auth/logout` clears `lk-auth` and `lk-refresh`, plus the
cart cookie `woo-session`, so the next person on that browser starts with an
empty cart. The customer's cart stays saved in their account.

### Auth API (Next.js route handlers)

| Endpoint | Body | Response |
|---|---|---|
| `POST /api/auth/login` | `{ username, password, remember? }` | `{ ok, customer: { databaseId, firstName, email } }` or `{ error }` (400/401/502) |
| `POST /api/auth/register` | `{ email, password, firstName, lastName?, phone? }` | Same as login; 409 if the email exists |
| `POST /api/auth/logout` | | `{ ok: true }` |
| `POST /api/auth/forgot` | `{ username }` | `{ ok: true }` |
| `POST /api/auth/reset` | `{ key, login, password }` | `{ ok: true }` or `{ error }` |
| `GET /api/auth/me` | | `{ signedIn, customer? }` |
| `POST /api/auth/email-link` | `{ email, next? }` | `{ ok: true }` (also for unknown emails), or `{ error }` (400/403 off/429) |
| `POST /api/auth/email-link/verify` | `{ token }` | `{ ok: true }` + login cookies, or `{ error }` (400 invalid, used or expired; 403 off) |

Error messages are returned in Bangla.

### Protecting pages: `lekhoni/proxy.ts`

`proxy.ts` (Next 16's replacement for `middleware.ts`) runs before rendering:
- `/my-account/*` without a login cookie redirects (307) to `/login?next=…`.
  `/my-account/wishlist` is open to guests, because the wishlist lives in the browser.
- `/login` and `/register` with a current `lk-auth` cookie redirect to `/my-account`.
- **WooCommerce URLs** (from its emails and the WordPress front-end redirect)
  are translated with a 308:

  | WooCommerce | Storefront |
  |---|---|
  | `/my-account/view-order/141` | `/my-account/orders/141` |
  | `/my-account/edit-address` (and `/billing`, `/shipping`) | `/my-account/addresses` |
  | `/my-account/edit-account` | `/my-account/details` |
  | `/my-account/lost-password` | `/forgot-password`, or `/reset-password` when it has `?key=` |

- Old storefront links `/account/*` redirect (308) to `/my-account/*`.

The proxy only checks that the cookies **exist**. The pages still verify the
session with WordPress, and an expired or revoked session falls back to the
page's own redirect.

### Using auth in code (`lekhoni/lib/auth.ts`)

```ts
import { authQuery, isSignedIn } from '@/lib/auth'

// Server Component: query as the signed-in customer
const { data } = await authQuery(`{ customer { firstName orderCount } }`)

// Route Handler / Server Function: also save a refreshed token to the cookie
const json = await authQuery(MUTATION, variables, { persist: true })
```

| Helper | Use |
|---|---|
| `authQuery(query, vars, { persist })` | GraphQL with the customer's token (a guest request if not signed in) |
| `getAuthToken({ persist })` | Current valid auth token, refreshing it if needed, or `null` |
| `isSignedIn()` | Whether the login cookies are present (fast; no network) |
| `setSession(auth, refresh?)` / `clearSession()` | Write or clear the cookies. **Route handlers / Server Functions only.** |
| `plainMessage(msg)` | Strip WordPress HTML from error messages |

Cart and checkout requests (`lib/cart.ts`) attach the auth token automatically,
so orders placed while signed in belong to the customer. WooCommerce saves
each order's address to the customer, and `/checkout` fills the form with it
(or with what was saved under My account → Addresses) on the next order;
see `savedAddressFields` in `app/components/account/address.ts`.

### Changing token lifetimes (WordPress)
Add to the theme or a small plugin:

```php
add_filter('graphql_jwt_auth_expire', fn() => 15 * MINUTE_IN_SECONDS); // auth token
```

Keep the auth token short; the refresh token renews it silently.

### Security notes
- Never expose `GRAPHQL_JWT_AUTH_SECRET_KEY` or `WP_GRAPHQL_URL` secrets to the
  browser (no `NEXT_PUBLIC_` prefix).
- Serve the live site over **HTTPS**; the cookies are `Secure` in production.
- `?next=` only accepts relative paths (`/…`, not `//…`), which prevents open redirects.
- To log every customer out at once, change `GRAPHQL_JWT_AUTH_SECRET_KEY`.
  To log out one customer, an admin can run the GraphQL mutation
  `updateUser(input: { id: "<user id>", refreshJwtUserSecret: true })`, which
  invalidates that user's existing tokens. `revokeJwtUserSecret: true` blocks
  new tokens until the secret is re-issued. The plugin has no button for this
  in wp-admin.

---

## 7. Cart, checkout and other browser state

| Name | Where | What |
|---|---|---|
| `woo-session` | httpOnly cookie | WooGraphQL cart session token (14 days) |
| `lk-auth`, `lk-refresh` | httpOnly cookies | Customer login (section 6) |
| `lk-order` | httpOnly cookie, path `/order-received`, 1 hour | Summary of the order just placed. Guests can't read orders back from WooGraphQL, so the confirmation page uses this cookie. |
| `lk-wishlist` | localStorage | Wishlist (per browser, not per account) |
| `lk-recent` | localStorage | Recently viewed product ids |
| `lk-shop-view` | localStorage | Shop grid/list choice |

Cart API: `GET/POST/PATCH/DELETE /api/cart`, `/api/cart/coupon`,
`/api/cart/shipping`. Checkout: `POST /api/checkout`.

---

## 8. Going live checklist

- [ ] `LEKHONI_FRONTEND_URL` set to the real storefront domain (reset emails use it)
- [ ] `GRAPHQL_JWT_AUTH_SECRET_KEY` set to a new random value on the live server
- [ ] `WP_GRAPHQL_URL` / `WP_SITE_URL` in the storefront's environment point at the live WordPress
- [ ] HTTPS on both sites
- [ ] `images.remotePatterns` in `lekhoni/next.config.ts` lists the live WordPress host.
  (`dangerouslyAllowLocalIP` is enabled only in development, for `woo.local`.)
- [ ] Currency, payment methods, shipping zones and coupons configured in WooCommerce
- [ ] Direct bank transfer (if enabled): real account details and **Instructions** text; customers see both
- [ ] Primary menu and Footer menu assigned
- [ ] Theme Settings: real social profile URLs, logo, announcement and copyright
- [ ] `LEKHONI_REVALIDATE_SECRET`: a new random value, the same in `wp-config.php` and the storefront's environment
- [ ] If email link login is on: WordPress can send email on the live server (an SMTP plugin), and a test link arrives
- [ ] Test users and orders deleted
- [ ] Demo content removed (Theme Settings → Demo Import → Remove demo content), or replaced with real products; the demo coupon (`LEKHONI10`, `MEGA15` or `GLOW10`) deleted if not wanted

---

## 9. Troubleshooting

| Problem | Cause / fix |
|---|---|
| Images from WordPress don't load in dev (`"url" parameter is not allowed`) | Next 16 blocks images from local IPs. `next.config.ts` allows them in dev via `dangerouslyAllowLocalIP`. Restart `next dev` after config changes. |
| CSS changes (for example new theme colours) don't appear | The dev server sometimes stops rebuilding CSS. Stop `npm run dev`; delete `lekhoni/.next` if needed; start again. |
| Login works but account pages say signed out | Check that `GRAPHQL_JWT_AUTH_SECRET_KEY` is defined and that the JWT plugin is active. Test with GraphQL: `mutation { login(input:{username:"…",password:"…"}) { authToken } }`. |
| Reset email links to the wrong site | `LEKHONI_FRONTEND_URL` in `wp-config.php` (must include `http://` or `https://`) |
| A Home Shop field change doesn't show | Wait about 60 seconds (revalidation), and check that the field has "Show in GraphQL" enabled. |
| Bank details don't show at checkout | Direct bank transfer must be enabled and its account row saved (WooCommerce → Settings → Payments). Check with GraphQL `{ bankTransfer { accounts { bankName accountNumber } } }`, then allow a few minutes for the cache. |
| `/home` (or another page) shows 404 | A menu link to the front page's slug redirects to `/`; other pages need to be published. Better: link the menu item to `/` directly. |
| `npm run build` fails with `Unexpected token '<', "<html>…" is not valid JSON` | WordPress answered with nginx's HTML error page: Local's PHP on Windows drops connections when the build floods it (`logs/nginx/error.log` shows "no live upstreams"). The build renders at most 4 pages per worker and retries failed pages (`next.config.ts`), and `lib/wp.js` retries queries. If it still happens, lower `staticGenerationMaxConcurrency`, or restart the site in Local. |
| Running WP-CLI or PHP scripts against Local | The system PHP lacks `mysqli`. Use Local's PHP, e.g. `"%APPDATA%\Local\lightning-services\php-8.2.29+0\bin\win64\php.exe" -c "%APPDATA%\Local\run\<site-id>\conf\php\php.ini" -d mysqli.default_port=<mysql port>`. The site id and MySQL port are in `%APPDATA%\Local\sites.json`. |
