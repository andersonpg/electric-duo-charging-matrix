=== Electric Duo EV Charging Matrix ===
Contributors: theelectricduo
Tags: ev, electric vehicle, charging, charging network, tesla supercharger
Requires at least: 5.8
Tested up to: 7.2
Requires PHP: 7.4
Stable tag: 1.1.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Interactive, SEO-friendly matrix of which EV charging apps and car brands work with which US charging networks. Dark theme matched to TheElectricDuo.com.

== Description ==

Shows two matrices from one shortcode:

1. **Charging apps** (automaker apps, aggregators and network apps) and the networks they unlock.
2. **Car brands** and their in-car / Plug & Charge access to each network.

Networks covered: Tesla Supercharger, Electrify America, IONNA, EVgo, ChargePoint, Blink, FLO, Shell Recharge, EV Connect, Francis Energy, bp pulse, Rivian Adventure Network, Mercedes-Benz HPC, Red E, PowerFlex and Walmart.

**Built for wide tables and phones**

* Icon-only cells and vertical network headers fit 16 networks in about 960px.
* The header row stays pinned to the top of the screen as you scroll down the page (below your fixed site menu), and the first column stays in view when the table scrolls sideways.
* Network picker (show or hide columns, or jump to the top 5), left/right scroll arrows, and a full screen mode.
* On phones each row becomes a card that lists only the networks it works with (one tap shows the rest).
* Search, category filter and a "works with network" filter on both sheets.
* Click any icon, row or network name for the details, notes and sources.
* Arrow-key navigation across the grid, visible focus states, CSV export of the current view.

**Built for search**

* The whole matrix is server-rendered semantic HTML (real table, caption, header scopes). Nothing depends on JavaScript to be indexed.
* Dataset and FAQPage structured data (JSON-LD), with a FAQ generated from the data.
* "About the networks" section with official links and source links, plus a "last reviewed" date.
* Scripts and styles load only on pages that contain the shortcode.

== Installation ==

1. Upload the `electric-duo-charging-matrix` folder to `/wp-content/plugins/`, or upload the zip under Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Add `[ev_charging_matrix]` to a page. A full-width page template works best.
4. Optional: Settings > EV Charging Matrix for intro text, FAQ and structured-data switches, and the data editor.

== Shortcode ==

`[ev_charging_matrix]`

* `view="apps"` or `view="brands"`: show one sheet only (default: both).
* `default="brands"`: which sheet opens first.
* `title="..."`: custom H2 title. `heading_level="2"` to `"4"` sets the top heading level.
* `intro="0"`, `faq="0"`, `schema="0"`: switch parts off.
* `height="640"`: scroll inside a box of this height (320 to 1600) instead of letting the table grow with the page. Without it the header row pins to the top of the screen.
* `sticky_top="80"`: height in pixels of your fixed site menu, so the pinned header sits below it. Leave it out to detect the menu automatically.
* `align="wide"` or `align="full"`: lets the matrix break out of a narrow content column in block themes. In Divi and other page builders, place the shortcode in a full-width section.

You can link straight to a sheet with `#edcm-apps` or `#edcm-brands` on the page that holds the first matrix.

== Updating the data ==

Settings > EV Charging Matrix has a JSON editor. Export the current data, edit it, paste it back and save; it is validated first and the previous data stays live if anything is wrong. Status codes are `PC` (Plug & Charge), `BOTH` (in-car + app), `APP` (in-app), `ROAM` (roaming) and `NO` (not integrated). Update `meta.last_reviewed` when you re-check the data so the page shows the new date.

Developers: filters `edcm_data`, `edcm_faq_items`, `edcm_faq_networks`, `edcm_top_networks`, `edcm_schema_graph` and `edcm_html`.

== Frequently Asked Questions ==

= The pinned header is hidden behind my site menu. =
The plugin looks for a fixed menu at the top of the screen and sits below it. If your theme hides it from that check, enter its height under Settings > EV Charging Matrix > Pinned header offset, or use `sticky_top="80"` in the shortcode.

= Can the table scroll inside a box instead? =
Yes. Set a Fixed table height in the settings, or use `height="640"` in the shortcode. The header then sticks inside the box. The Full screen button always uses a box.

= The fonts look different from my theme. =
The plugin uses your theme's heading font variable when present and falls back to Oswald/Inter/Poppins. If your theme does not load them, tick "Load fonts" in the plugin settings.

= Where does the data come from? =
Automaker help pages, network announcements and press coverage. See DATA-REVIEW.md in the plugin folder for every change made in the October 2026 review and the list of cells that could not be independently verified.

== Changelog ==

= 1.1.6 =
* Mobile UI: simplified the toolbar on narrow/mobile screens (<= 760px) by hiding the search box, category filter, network filter, Networks column picker, CSV download, and Full screen buttons, and replacing them with an alphabetical App or Car Brand selector dropdown (matching the active Charging Apps or Car Brands tab).

= 1.1.5 =
* Data audit (Nov 23, 2026):
  * GM Energy Pass (app and brand) now shows Plug & Charge at Electrify America for native CCS-port GM EVs (native NACS-port GM EVs scheduled for early 2027).
  * Tesla (brand sheet) now shows Plug & Charge / Autocharge at IONNA via Presto setup (Nov 2026).
  * Octopus Charge (North America): corrected Tesla Supercharger from In-app to Not integrated (Tesla, Electrify America, and IONNA are not included in Octopus Charge North America) and updated EV Connect from Roaming to In-app.
  * Bluedot: updated notes to reflect its May 2026 acquisition by Epic Charging and the wind-down of the standalone consumer app (now fleet/enterprise-focused).
  * If you saved custom data in Settings, use Reset to defaults (or update your JSON) to pick up these changes.

= 1.1.4 =
* Data fix: the bundled data now includes the Volvo change described under 1.1.2 (Plug & Charge at Tesla Superchargers on both sheets, with matching IONNA notes and sources). Earlier builds carried the changelog entry but not the data. Volvo's 2025 EX90 hardware update is described as free, per InsideEVs. If you saved your own data in Settings, use Reset to defaults (or copy the Volvo change into your JSON) to pick it up.

= 1.1.3 =
* Data: added the ChargeHub app to the app sheet (Aggregator, after Octopus Charge). It can start and pay for sessions at IONNA, bp pulse, ChargePoint, Blink, FLO, Shell Recharge, EV Connect and Red E. Tesla, Electrify America, EVgo, Francis Energy, Rivian RAN, Mercedes HPC, PowerFlex and Walmart are not payable in ChargeHub. bp pulse went live in the ChargeHub app on Aug 24, 2026. If you saved your own data in Settings, use Reset to defaults (or copy the ChargeHub row into your JSON) to pick it up.

= 1.1.2 =
* Data fix: Volvo (app and brand) now shows Plug & Charge at Tesla Superchargers, not just IONNA. Volvo announced it on Jun 18, 2026: live on the EX90 and coming with the EX60 at US launch, activation required, and a 2025 EX90 needs the newer NVIDIA Orin computer. Other Volvo models still start sessions from the Volvo Cars app or the in-car screen. If you saved your own data in Settings, use Reset to defaults (or copy the Volvo change into your JSON) to pick it up.

= 1.1.1 =
* Fix: on themes that style every table in post content with !important rules (Divi custom CSS and similar), the pinned header was clipped and misaligned and rows were taller than designed. The table, header, row header and cell styles now hold their own values against those rules, on both the desktop grid and the phone cards.
* Fix: the sticky first column and header could stop sticking when a theme set overflow on tables.
* Fix: the first phone card now has the same divider lines between networks as the others.
* Link underlines, muted text and the detail pop-up headings are protected from the same theme rules.

= 1.1.0 =
* The header row is now pinned to the top of the screen while you scroll the page, on both sheets. The table grows with the page instead of scrolling inside a box; the first column and the pinned header still follow when it scrolls sideways. A fixed site menu is detected automatically (override with the new "Pinned header offset" setting or `sticky_top`). The old maximum height setting is replaced by "Fixed table height" (0 = pinned header, the default; or a number to scroll inside a box).
* Phones keep the card layout. Full screen still scrolls inside the window with the header stuck to it.

= 1.0.2 =
* Data fix: for Ford, a charging session started in the Ford app can also be started from the SYNC screen and vice versa. Ford's Blink, EV Connect, Mercedes-Benz HPC and Red E cells on the car-brand sheet now read "In-car + app" (were "In-app"), and the Ford app notes mention the car screen. Same reset-to-defaults note as 1.0.1 applies if you saved custom data.

= 1.0.1 =
* Data fix: Ford (app and brand) now shows Plug & Charge at bp pulse. bp pulse is on Ford's BlueOval Charge Network list, and Plug & Charge went live at its 150 kW chargers in Aug 2026. Ford's PowerFlex and Honda's IONNA cells were rechecked against current first-party pages and are unchanged. If you saved your own data in Settings, use Reset to defaults (or copy this fix into your JSON) to pick it up.

= 1.0.0 =
* First release. Converted from the standalone spreadsheet; Walmart added; Circle K Charge, EVCS and 7-Charge removed; IONNA placed after Electrify America; data reviewed October 2026.
