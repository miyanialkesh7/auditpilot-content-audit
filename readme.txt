=== AuditPilot – Smart Content Audit for WordPress ===
Contributors: alkesh7
Tags: content audit, seo audit, broken links, alt text, site audit
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Audit every page, post, and custom post type from one dashboard. Find broken links, missing alt text, SEO gaps, heading issues, and more.

== Description ==

**AuditPilot is the all-in-one content quality plugin for WordPress.** It scans your entire site and surfaces issues that hurt your SEO, user experience, and content quality — all from a single, easy-to-read dashboard.

Stop discovering problems when visitors — or Google — do. AuditPilot finds them first.

---

### 🔍 What Gets Audited

**Content Audit**
* Missing featured image
* Empty or blank post content
* Missing manual excerpt
* Short content below your word-count threshold
* Drafts sitting untouched for 30+ days

**Media Audit**
* Images missing alt text (accessibility & SEO)
* Broken media references inside content
* Oversized images above your KB threshold

**Heading Audit**
* H1 tags found inside post content (duplicate H1 risk)
* Multiple H1 tags on a single page
* Heading level jumps (e.g. H2 → H4 skipping H3)
* Long content with no subheadings

**Link Audit**
* Empty or placeholder links (`href=""`, `href="#"`)
* Broken internal links (verified against your WordPress URL structure)
* Broken external links with HTTP status codes *(optional — disabled by default)*
* Redirecting external links

**SEO Audit** *(works with Yoast SEO, Rank Math, and AIOSEO)*
* Missing SEO title
* Missing meta description
* Missing Open Graph image

**Builder Audit**
* Empty Gutenberg blocks
* Empty Elementor text and heading widgets

---

### 📊 Site-Wide Scoring

Every scan produces an **overall site score** plus individual scores for each audit category — Content, Media, Headings, Links, SEO, and Builder. Scores update with each new scan so you can track progress over time.

Severity levels keep priorities clear:
* 🔴 **Error** — Issues that directly damage SEO or user experience
* 🟡 **Warning** — Issues that reduce quality and should be fixed
* 🔵 **Info** — Improvements worth considering

---

### ⚡ Fast Batch Scanning

Scans run in the background in batches of 10 posts at a time so your server never gets overloaded. A real-time progress bar shows exactly where the scan is up to. When it's done, you land straight on your results.

---

### 📋 Filterable Results Table

Filter issues by:
* Category (Content, Media, Headings, Links, SEO, Builder)
* Severity (Error, Warning, Info)
* Post type (Pages, Posts, Custom Post Types)
* Keyword search across post titles and issue messages

One click takes you straight to the WordPress editor for any flagged post.

---

### 📤 Export to CSV

Export your full results to a CSV file and share with clients, teammates, or use in your own spreadsheet workflow — with one click.

---

### ⚙️ Flexible Settings

* Choose which **post types** to scan (Pages, Posts, WooCommerce Products, and any custom post type)
* Choose which **post statuses** to include (Published, Drafts, Pending, Private)
* Enable or disable individual audit modules
* Set your own thresholds for short content, old drafts, and large images
* Toggle external link checking on or off

---

### 🧩 Page Builder & SEO Plugin Support

| SEO Plugins | Page Builders |
|---|---|
| Yoast SEO | Gutenberg (Block Editor) |
| Rank Math | Elementor |
| AIOSEO | More coming soon |

---

### 👩‍💻 Developer Friendly

AuditPilot is built with clean, extensible code and follows WordPress coding standards throughout.

* Custom database tables for scalable result storage
* AJAX-based scanning — no page timeouts
* Nonces and capability checks on every request
* Translation ready with full `.pot` file
* RTL stylesheet ready

---

### 🔒 Privacy

This plugin only reads your existing WordPress content. It does not send any data to external servers. External link checking (when enabled) makes standard HTTP HEAD requests directly from your server to verify link status — no third-party service is involved.

---

**Perfect for:**
* Site owners wanting to clean up content before an SEO push
* Agencies running content audits for clients
* Developers handing off a completed site
* Anyone who wants to keep their WordPress content in top shape

== Installation ==

**Automatic Installation (Recommended)**

1. Log in to your WordPress dashboard
2. Go to **Plugins → Add New Plugin**
3. Search for **AuditPilot – Smart Content Audit for WordPress**
4. Click **Install Now**, then **Activate**

**Manual Installation**

1. Download the plugin ZIP file
2. Go to **Plugins → Add New Plugin → Upload Plugin**
3. Upload the ZIP and click **Install Now**, then **Activate**

**First Scan**

1. After activation, go to **AuditPilot** in the admin menu
2. Click **Start New Scan**
3. Watch the progress bar — results appear automatically when the scan finishes

== Frequently Asked Questions ==

= Will this plugin slow down my website for visitors? =

No. All scanning happens in the WordPress admin via background AJAX requests. Your front end is completely unaffected.

= How long does a scan take? =

It depends on your site size. A typical site with 100 posts scans in about 30–60 seconds with internal link checking. Enabling external link checking adds time proportional to how many external links your content contains.

= Does it work with WooCommerce? =

Yes. Select "Product" and "Product Variation" in Settings → Post Types and those will be included in your scan.

= Which SEO plugins are supported? =

Yoast SEO, Rank Math, and AIOSEO are all auto-detected. If none of these is active, the SEO audit module is skipped automatically.

= Is external link checking safe to enable? =

It is safe but can be slow on sites with many external links, as it makes one HTTP request per unique external URL. It is disabled by default. Enable it in Settings only when you specifically need to find broken external links.

= Can I schedule scans to run automatically? =

Not in version 1.0.0. Scheduled scans are on the roadmap for a future Pro version.

= Does it support multisite? =

The plugin is network-compatible but operates per-site. Multisite network-wide scanning is planned for a future release.

= Where are scan results stored? =

Results are stored in two custom database tables (`wp_apca_scans` and `wp_apca_issues`) in your own WordPress database. Nothing leaves your server.

= How do I delete all plugin data? =

Deactivating and deleting the plugin via the WordPress Plugins screen triggers the uninstall routine, which drops the custom database tables and removes all plugin options.

= Is it translation ready? =

Yes. The plugin is fully internationalized and ships with a `.pot` template file in the `/languages/` directory.

== Screenshots ==

1. **Dashboard** — Overall site score, category scores, and a summary of errors, warnings, and info issues from your latest scan.
2. **Scan in progress** — Real-time progress bar while the background scan processes your posts in batches.
3. **Results table** — Full filterable and searchable list of every issue found, with direct links to edit each post.
4. **Category breakdown** — At-a-glance view of issues grouped by audit category on the dashboard.
5. **Settings page** — Choose post types, post statuses, enabled audit modules, and configure thresholds.

== Changelog ==

= 1.0.1 =
* Increased minimum required PHP version to 8.1.
* Internal code quality improvements: added automated linting (WordPress Coding Standards), static analysis (PHPStan), and test coverage to the development workflow. No user-facing behavior changes.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.1 =
Now requires PHP 8.1 or higher. If your host runs PHP 7.4–8.0, upgrade PHP before updating this plugin.

= 1.0.0 =
First release of AuditPilot – Smart Content Audit for WordPress. Install and run your first scan to discover content issues across your site.
