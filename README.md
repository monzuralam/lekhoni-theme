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
2. Add these to `wp-config.php` above `/* That's all, stop editing! */`:

   ```php
   // Address of the Next.js storefront (no trailing slash). Used for the
   // front-end redirect and for links in password reset emails.
   define('LEKHONI_FRONTEND_URL', 'http://localhost:3000');

   // Signs customer login tokens. Long random string; keep it secret.
   // Changing it logs every customer out.
   define('GRAPHQL_JWT_AUTH_SECRET_KEY', 'paste-a-long-random-string-here');
   ```

   Generate a key with, for example,
   `node -e "console.log(require('crypto').randomBytes(48).toString('base64url'))"`.
3. **Settings → Permalinks:** use any "pretty" structure (for example Post name).
   Product URLs must look like `/product/cap/` and categories like
   `/product-category/clothing/`.
4. **Home page:** create a page with the **Home Shop** template, set it as
   the static front page (Settings → Reading), and fill in the **Home Shop**
   fields (section 4). What `/` shows follows "Your homepage displays":

   | Settings → Reading | Storefront `/` |
   |---|---|
   | A static page with the **Home Shop** template | Shop home (`design/home-shop.html`) |
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
5. **Appearance → Menus:** create a menu and tick **Primary menu**. Until a
   menu is assigned, the storefront shows the fallback links Home and Shop.
6. **WooCommerce → Settings:** set the currency (BDT shows ৳), enable payment
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
| `functions.php` | Theme supports, menu locations (`primary`, `footer`), includes `inc/*` |
| `inc/install.php` | TGM Plugin Activation: required plugin list |
| `inc/acf-helpers.php` | Shared helpers for PHP-defined ACF fields (`lekhoni_acf_field()`, `lekhoni_icon_choices()`) |
| `inc/acf-about.php` | ACF field group **About Page** (shown on the About template; GraphQL `aboutPage`) |
| `inc/acf-contact.php` | ACF field group **Contact Page** (shown on the Contact template; GraphQL `contactPage`) |
| `inc/contact.php` | Contact form: **Contact Messages** admin inbox and the `sendContactMessage` GraphQL mutation |
| `inc/acf-home.php` | ACF field group **Home Shop**: every home page section in one group, one tab per section (shown on the Home Shop template; GraphQL `homeShop`) |
| `inc/acf-author.php` | ACF field group **Author Profile** on the user profile screen (GraphQL `authorProfile`) |
| `inc/newsletter.php` | Newsletter: **Subscribers** admin list with CSV export, and the `subscribeNewsletter` GraphQL mutation |
| `inc/payments.php` | GraphQL `bankTransfer { instructions accounts }`: the Direct bank transfer accounts, shown at checkout (when that method is picked) and on the order-received page |
| `inc/acf-export-*.json` | An old ACF export of the first Home Shop fields. Not loaded; outdated reference only |
| `inc/headless.php` | Headless helpers (below) |
| `templates/template-home.php` | "Home Shop" page template |
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
- **Password reset links** go to the storefront's `/reset-password` page. Both
  WordPress's email (`retrieve_password_message`) and WooCommerce's email
  (the `lost-password` endpoint) are rewritten to
  `{LEKHONI_FRONTEND_URL}/reset-password?key=…&login=…`.

---

## 4. Editing content

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

### Other content
- **Product category images:** Products → Categories → Thumbnail.
- **Menu:** Appearance → Menus → location **Primary menu**. Nest items to make
  dropdowns (two levels are styled). A custom link with URL `#` becomes a
  label that only opens its dropdown. Links to `woo.local` become storefront
  paths, and other sites stay external.

---

## 5. Storefront pages

| Route | What it is |
|---|---|
| `/` | Home: the shop home (hero, trust strip, categories, flash sale, …), a plain page, or the blog home, depending on Settings → Reading (section 2, step 4) |
| `/about` | About page (WordPress page `about`, ACF "About Page") |
| `/contact` | Contact page (WordPress page `contact`, ACF "Contact Page"); form saves to wp-admin |
| `/shop` | All products: filters (category, max price, in stock), sort, pages |
| `/product-category/[...slug]` | Shop layout with a category preselected |
| `/product/[slug]` | Product: gallery, variant picker, tabs, reviews, related, recently viewed |
| `/cart`, `/checkout`, `/order-received` | Cart, checkout (WooCommerce `checkout` mutation; a signed-in customer's saved address is filled in; bank details under Direct bank transfer), confirmation (with bank details for bank transfer orders) |
| `/search` | Product search results (the header's search palette uses the same search) |
| Any other path (`app/[...uri]`) | Resolved with WordPress `nodeByUri`: the Posts page (e.g. `/blog`, with `/page/N`), category and tag archives, author archives (`/author/<slug>`), single posts and other pages (e.g. `/sample-page`). The front page's own slug redirects to `/`; anything else is a 404. |
| `/login`, `/register`, `/forgot-password`, `/reset-password` | Customer auth |
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

**Forgot / reset password:**
1. `/forgot-password` sends to `/api/auth/forgot`, which calls `sendPasswordResetEmail`.
   It always answers OK, so it can't be used to find out which emails are registered.
2. WooCommerce emails a link to `{LEKHONI_FRONTEND_URL}/reset-password?key=…&login=…`.
   This theme rewrites the link (section 3).
3. `/reset-password` sends to `/api/auth/reset`, which calls `resetUserPassword`.
   The key is valid for 24 hours and works only once.

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
- [ ] Primary menu assigned
- [ ] Test users and orders deleted

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
| Running WP-CLI or PHP scripts against Local | The system PHP lacks `mysqli`. Use Local's PHP, e.g. `"%APPDATA%\Local\lightning-services\php-8.2.29+0\bin\win64\php.exe" -c "%APPDATA%\Local\run\<site-id>\conf\php\php.ini" -d mysqli.default_port=<mysql port>`. The site id and MySQL port are in `%APPDATA%\Local\sites.json`. |
