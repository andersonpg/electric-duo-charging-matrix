# Data review log

Review date: **November 23, 2026** (Revision 1.0.5). Dataset: `data/matrix-data.json` (16 networks, 30 apps, 20 car brands). Originally reviewed October 26, 2026; ChargeHub added Nov 5, 2026 (Revision 1.0.4).

## Revision 1.0.5 (Nov 23, 2026): Full audit

* **GM + Electrify America**: `gm` (app) and `brand_gm` (brand) updated from **In-app** / **In-car + app** to **Plug & Charge** (`PC`). GM Energy Pass Plug & Charge is live at Electrify America for GM EVs with native CCS ports, with native NACS-port GM EVs scheduled for early 2027.
* **Tesla + IONNA**: `brand_tesla` (brand) updated from **Not integrated** (`NO`) to **Plug & Charge** (`PC`). In November 2026, IONNA partnered with Presto Charging to enable Autocharge / Plug & Charge for Tesla vehicles (pre-2021 Teslas may need a CCS retrofit). `tesla_app` remains `NO` at IONNA because setup runs through Presto rather than the Tesla app.
* **Octopus Charge (North America)**: `octopus` at Tesla Supercharger corrected from **In-app** (`APP`) to **Not integrated** (`NO`). Octopus Charge (North America) does not include Tesla Superchargers, Electrify America, or IONNA. `octopus` at EV Connect updated from **Roaming** (`ROAM`) to **In-app** (`APP`) as a named partner network.
* **Bluedot**: Updated row notes to reflect that Bluedot was acquired by Epic Charging on May 1, 2026, wound down its standalone consumer app, and shifted to commercial, fleet, and autonomous EV charging.
* **Rechecked with no status change**: Rivian Plug & Charge (RAN, Tesla, Electrify America, IONNA), Genesis In-App Charging (Electrify America still listed as coming soon), Stellantis Free2move Charge at Tesla Superchargers (in-app / adapter) and IONNA (app; Plug & Charge rolling out), Honda/Acura at IONNA (Plug & Charge rolling out), and Walmart EV Charging (proprietary Walmart app / card terminal; no third-party roaming).

The original spreadsheet carried no sources. This review checked the cells it could against first-party pages (automaker, network and retailer pages) and recent trade-press coverage, and corrected what the evidence contradicted. Cells with no source behind them were **left as they were in the original spreadsheet**; they are listed under "Not independently verified" so you know where to look first on the next pass.

## Revision 1.0.1 (Oct 26, 2026): Ford and bp pulse

The first pass set Ford to "Not integrated" at bp pulse because bp pulse was missing from a Mar 2025 Ford partner list. That was wrong: Ford's current BlueOval Charge Network page lists bp pulse, and Ford Authority (Sep 2, 2026) reports Plug & Charge is live at bp pulse's 150 kW chargers, with the 400 kW chargers having had it for months. Both Ford cells are now **Plug & Charge**.

What the evidence does not say: Ford Authority's report rests on a post by a Ford engineer, not a Ford or bp press release, and it names no models, model years or software versions. The cell text says so.

I rechecked the other two corrections that rested on absence, this time against current first-party pages:

* **Ford, PowerFlex** stays "Not integrated". PowerFlex is not among the 22 networks on Ford's BlueOval Charge Network page (data dated Apr 2026).
* **Honda / Acura, IONNA** stays "Not integrated". Honda and Acura appear on neither IONNA's "How can I pay for charging?" app list nor its Plug & Charge brand list (checked Oct 26, 2026).

Absence is weaker evidence than a confirmation. If either automaker announces support, change the cell.

## Revision 1.0.2 (Oct 26, 2026): Ford in-car and in-app start

Patrick confirmed that for Ford, a session that can be started in the Ford app can also be started in the car, and the reverse. On the car-brand sheet, Ford's four remaining "In-app" cells (Blink, EV Connect, Mercedes-Benz HPC, Red E) are now "In-car + app". On the app sheet the Ford row stays "In-app" (it is the app sheet) and each note says the session can also start from the SYNC screen. This rests on the owner's statement, not a published Ford page.

## Revision 1.0.3 (Oct 26, 2026): Volvo Plug & Charge at Tesla Superchargers

Patrick sent a Motor1 article (Jun 21, 2026). I checked it against electrive (Jun 19) and Electrek (Jun 18); all three agree. Volvo announced Plug & Charge in the US on Jun 18, 2026: live on the EX90, and on the EX60 from its US launch later in 2026, at **Tesla Superchargers and IONNA**. The feature has to be activated first, and a 2025 EX90 needs the newer NVIDIA Orin computer configuration (Electrek). The EX90 has a CCS1 port and uses an adapter at Superchargers; the EX60 has a native NACS port.

Changes: Volvo's Tesla Supercharger cell goes to Plug & Charge on both sheets (was in-app on the app sheet, in-car + app on the brand sheet). The Volvo IONNA cell was already Plug & Charge; its note now carries the same details. The Volvo row notes and the Tesla and IONNA network source lists are updated. Status changes in this review are now 41 (was 39).

What the evidence does not say: no model years beyond the 2025 EX90 hardware point, no software version, and no word on other Volvo models (EX30, EX40). Those stay as app or in-car start. The articles say more networks are expected, but name none. I could not open Volvo's own press release (volvocars.com), so the claims rest on the three reports. I dropped "free" from the old "free dealer hardware update" wording for the 2025 EX90 because none of the three articles says it is free.

## Revision 1.0.4 (Nov 5, 2026): ChargeHub app

Patrick asked for the ChargeHub app to be added, with current information because bp pulse joined it recently. ChargeHub is a new "Aggregator" row after Octopus Charge on the app sheet (30 apps now), checked against ChargeHub's own pages, IONNA's FAQ and trade press.

ChargeHub's networks page splits networks into those you can pay for in the app and those it only lists. Of this matrix's 16 networks, eight are payable and show **In-app**: IONNA, ChargePoint, Blink, FLO, Shell Recharge, EV Connect, bp pulse and Red E. The other eight show **Not integrated**: Tesla, Electrify America, EVgo, Francis Energy, Rivian RAN, Mercedes HPC, PowerFlex and Walmart.

* **bp pulse**: ChargeHub's own post (Aug 24, 2026) says activation is live in the app: 840 DC fast chargers at 96 sites in 22 states, all over 100 kW, about a third with NACS. NACS Daily (Oct 30, 2026) reports bp pulse also joined ChargeHub's Passport Hub roaming platform (850+ charge points at 95+ sites). ChargeHub's newsroom and blog listings (checked Nov 5, 2026) carry no post about the Oct 30 news, so that part rests on the NACS report. It names no automaker or e-mobility provider that gains bp pulse access, so no other row changed.
* **IONNA**: confirmed from both sides. ChargeHub's post (Jun 10, 2026) and IONNA's own FAQ, which lists ChargeHub among the apps that can start a remote session.
* **Fees**: ChargeHub charges a per-session activation fee that ChargeHub Plus waives. The row states no price, because it is not on a dated first-party page.

Also changed: the IONNA and bp pulse network descriptions now mention ChargeHub, and ChargeHub sources were added to both. Plugin version is now 1.1.3. `meta.last_reviewed` stays Oct 26, 2026, because only ChargeHub-related facts were rechecked, not the whole dataset.

What the evidence does not say: ChargeHub's networks page has no date, and it lags on small networks (networks added in the May 2026 Saascharge integration still sit in its "other" list). Francis Energy and Walmart are absent from it, so those two "Not integrated" cells are absence-based. Tesla, Electrify America, EVgo, Rivian, Mercedes and PowerFlex appear on the page as listed but not payable.

## Sync (Nov 5, 2026): data file brought in line with this log

Every logged correction was checked against `data/matrix-data.json`: all 41 status changes, plus the renames, removals, column order and the Tesla adapter note. 39 status changes matched. Two did not: Volvo's Tesla Supercharger cells (both sheets) still held their pre-1.0.3 values, so Revision 1.0.3 had been logged but never reached the data file. It is now applied: Plug & Charge on both sheets, the Volvo row notes, the Volvo IONNA notes, and the Tesla and IONNA source lists. A re-run of the same check finds no mismatches.

One wording change from 1.0.3: InsideEVs reports that Volvo fits the Nvidia Orin computer for free at a service visit on a 2025 EX90, so the cell text now says so, attributed to InsideEVs. Volvo's own release (as carried by Automotive World, Jun 18, 2026) says only that 2025 EX90s need the Orin configuration and gives no price, and volvocars.com could not be opened, so "free" rests on one outlet.

Also in this sync: the stylesheet in the plugin folder was an older build without the theme-override fixes from 1.1.1, and it was replaced with the 1.1.1 build. `meta.version` now reads 1.0.4 (it still said 1.0.2). The plugin is now version 1.1.4 and the zip was rebuilt from the folder.

## Requested edits

| Request | Result |
| --- | --- |
| Add Walmart as a charging network | Added as the last column of both sheets. All existing apps and brands are "Not integrated" (no automaker or roaming integration announced). A new app row, "Walmart App (EV Charging)", shows Walmart's own app working at Walmart sites. |
| Remove Circle K Charge, EVCS and 7-Charge | Removed from both sheets, from the data and from every note. |
| Add the ChargeHub app (Nov 5, 2026) | Added as an Aggregator row after Octopus Charge. In-app at 8 of the 16 networks; the other 8 are "Not integrated". See Revision 1.0.4. |
| Move IONNA after Electrify America | Order is now Tesla, Electrify America, IONNA, EVgo, ChargePoint ... in both sheets. |

## Corrections (41 status changes)

"Was" is the original spreadsheet value. Codes: PC = Plug & Charge, BOTH = in-car + app, APP = in-app, ROAM = roaming, NO = not integrated.

### IONNA column (source: IONNA FAQ, "What charging apps work at an IONNA Rechargery?" and "How can I pay for charging?")

| Row | Network | Was | Now | Why |
| --- | --- | --- | --- | --- |
| Porsche (app + brand) | IONNA | NO | PC | IONNA lists Plug & Charge and the My Porsche app as supported. |
| Volvo (app + brand) | IONNA | NO | PC | Plug & Charge on EX90 (live) and EX60 (at US launch), per Volvo's Jun 2026 announcement as reported by electrive, Electrek and Motor1; Volvo Cars app on other models. |
| Honda / Acura (app + brand) | IONNA | PC | NO | Honda is a founding member, but Honda and Acura apps are not on IONNA's supported-app or Plug & Charge lists. Absence-based (rechecked Oct 26, 2026); re-check if Honda announces support. |
| Stellantis (app) | IONNA | PC | APP | Founding member, but IONNA lists no Plug & Charge for Stellantis; Free2move Charge app start only. |
| Stellantis (brand) | IONNA | PC | BOTH | Same evidence; in-car routing plus Free2move Charge. |
| ChargePoint app | IONNA | NO | APP | IONNA says the ChargePoint app works at its sites (RFID cards and Tap to Charge do not). |

Also rewritten: Ford is **not** an IONNA founding member (it joined through the BlueOval Charge Network, Mar 2025).

### Tesla Supercharger column

| Row | Was | Now | Source |
| --- | --- | --- | --- |
| GM (app) | PC | APP | CleanTechnica, GM Energy Pass (Jun 9, 2026): in-app today; Plug & Charge only on NACS-native GM EVs, over-the-air update for others promised later in 2026. |
| GM (brand) | PC | BOTH | Same. |
| VW (app + brand) | APP | NO | InsideEVs (Mar 20, 2026): VW Plug & Charge does not work at Superchargers; start in the Tesla app. |
| Porsche (app + brand) | APP | PC | Cars.com (Jan 20, 2026): Plug & Charge added at Superchargers. |
| Lucid (app + brand) | APP | PC | Lucid public-charging page; InsideEVs. |
| Volvo (app + brand) | APP (app), BOTH (brand) | PC | Electrek (Jun 18, 2026), electrive (Jun 19) and Motor1 (Jun 21): Plug & Charge at Superchargers on the EX90 now and the EX60 at launch; activation required. |
| Toyota (app + brand) | APP | PC | Toyota Pressroom (updated Jan 21, 2026): Plug & Charge on 2026 BEVs after enrolment. |

Also: the Tesla adapter note now reads "CCS1 cars need an approved NACS adapter; newer NACS-port models plug in directly. Only Tesla-approved V3/V4 Superchargers work for non-Tesla vehicles (older V2 and some Tesla-only sites do not)." The Audi Q4 e-tron exclusion (TheChargePort, Oct 2026) was appended to the Audi cells.

### Electrify America column

| Row | Was | Now | Source |
| --- | --- | --- | --- |
| Nissan (app) | APP | PC | electrive (Oct 2, 2025): Plug & Charge on the 2026 LEAF at Electrify America. |
| Nissan (brand) | BOTH | PC | Same. |
| Genesis (app + brand) | APP | NO | Genesis Owners In-App Charging FAQ lists Electrify America as "coming soon". |

### Other networks

| Row | Network | Was | Now | Source |
| --- | --- | --- | --- | --- |
| Genesis (app + brand) | Shell Recharge | NO | APP | Genesis In-App Charging FAQ. |
| Genesis (app + brand) | EV Connect | NO | APP | Genesis In-App Charging FAQ. |
| Ford (app) | bp pulse | APP | PC | bp pulse is on Ford's BlueOval Charge Network list (Apr 2026); Ford Authority (Sep 2, 2026) reports Plug & Charge live at its 150 kW chargers. |
| Ford (brand) | bp pulse | BOTH | PC | Same. |
| Ford (brand) | Blink, EV Connect, Mercedes-Benz HPC, Red E | APP | BOTH | Ford sessions start from the Ford app or the SYNC screen interchangeably (owner-confirmed, Oct 2026). |
| Ford (app + brand) | PowerFlex | APP | NO | PowerFlex is not on Ford's BlueOval Charge Network list (Apr 2026). Absence-based. |
| Octopus Charge | EVgo, FLO | ROAM | APP | electrive (Aug 19, 2026): named partner networks of the Octopus Charge app. |
| Octopus Charge | Blink, bp pulse | NO | APP | Same. |

Octopus Charge was renamed "Octopus Charge (North America)" (free app, launched Aug 2026, 24+ partner networks).

### Text and notes rewritten

Network descriptions (port counts, ownership, what each network is), the GM row (renamed to "myChevrolet / myGMC / myCadillac (GM Energy Pass)"), and the notes for BMW, Kia, Genesis, Porsche, Volvo, Lucid, Nissan, Toyota, Stellantis, Shell Recharge and the GM, Nissan and Toyota brand rows.

## Not independently verified

These cells and facts came from the original spreadsheet and were **not** confirmed against a source in this review. Treat them as "probably right, last checked by someone else":

* **ChargeHub** (added Nov 5, 2026): the Francis Energy and Walmart "Not integrated" cells rest on those networks being absent from ChargeHub's undated networks page. bp pulse joining Passport Hub (Oct 30, 2026) rests on one trade report.
* **General Motors**: statuses for the smaller networks (Blink, FLO, Shell Recharge, EV Connect, Francis, bp pulse, Red E, PowerFlex). GM's own sources are thin or conflict on which networks have Plug & Charge: CleanTechnica says IONNA and EVgo; a GM-sponsored post says IONNA and ChargePoint. The matrix follows CleanTechnica (IONNA and EVgo).
* **Hyundai, Kia, Genesis (non-IONNA/EA/Shell/EV Connect cells), VW, Audi, Volvo, Polestar, Subaru, Honda/Acura**: most cells outside the corrections above, including Honda/Acura Plug & Charge at Tesla and EVgo.
* **Ford**: Plug & Charge at Tesla Superchargers and Electrify America carried over; the IONNA, bp pulse and PowerFlex cells were checked against Ford's partner page and press coverage. Which Ford models and software versions get Plug & Charge at bp pulse is not published.
* **Smaller networks** (Francis Energy, Red E, EV Connect, PowerFlex, FLO, Blink): who can use them through which app. PowerFlex in particular has almost no public integration information.
* **Walmart**: "no automaker or roaming integration" is true as of the sources found, but it is a statement about something not being announced. If Walmart joins a roaming platform, update the column.
* **Network facts** (stall and port counts, "open to all EVs", connector types) come from TheChargePort (Oct 20, 2026) and the sources listed in the plugin's "About the charging networks" section. They drift quickly.

Source access note: tesla.com, electrifyamerica.com and evgo.com were not reachable by the review tooling, so claims about those networks rely on automaker pages, TheChargePort and press coverage rather than the networks' own pages.

## Sources

* TheChargePort, EV charging networks (Oct 20, 2026): https://thechargeport.com/ev-charging-networks
* TheChargePort, Supercharger access by brand (Oct 20, 2026): https://thechargeport.com/supercharger-access
* InsideEVs, non-Tesla EVs that can use Superchargers (Mar 20, 2026): https://insideevs.com/news/735724/non-tesla-ev-tesla-supercharger-access/
* IONNA FAQ, supported apps: https://www.ionna.com/faq/what-charging-apps-work-at-an-ionna-rechargery/
* IONNA FAQ, how to pay: https://www.ionna.com/faq/how-can-i-pay-for-charging/
* Walmart, 100th EV fast-charging site (Aug 21, 2026): https://corporate.walmart.com/news/2026/08/21/walmart-energizes-100th-store-with-ev-fast-charging-in-monument-co
* Rivian Trackr, Rivian Plug & Charge networks (Sep 2026): https://riviantrackr.com/news/rivian-plug-and-charge-supported-networks/
* Rivian, Adventure Network update (Aug 2025): https://rivian.com/stories/rivian-adventure-network-08-2025
* electrive, Electrify America NACS pilot (Jul 1, 2026): https://www.electrive.com/2026/07/01/electrify-america-expands-nacs-pilot-to-major-charging-hubs/
* Mercedes-Benz USA, EV charging stations: https://www.mbusa.com/en/ev-charging-stations
* Ford, What is the Ford BlueOval Charge Network? (partner list, data dated Apr 2026): https://www.ford.com/support/how-tos/electric-vehicles/blueoval-charge-network/what-is-the-ford-blueoval-charge-network
* Ford Authority, Ford EV owners can now use Plug & Charge at bp pulse chargers (Sep 2, 2026): https://fordauthority.com/2026/09/ford-ev-owners-can-now-use-plug-charge-at-bp-pulse-chargers/
* IONNA FAQ, Plug & Charge: https://www.ionna.com/faq/will-plug-charge-work-at-ionna-rechargeries
* Ford and IONNA, BlueOval Charge Network (Mar 2025): https://www.macheforum.com/site/threads/blueoval-charge-network-adds-ionna.43926/latest
* Automotive Fleet, Ford adds Francis Energy, Blink and Red E (Nov 2023): https://www.automotive-fleet.com/news/ford-expands-charging-providers-to-surpass-106000-chargers
* CStore Dive, Shell and Volta (Nov 2025): https://www.cstoredive.com/news/shell-sell-volta-electric-vehicle-charging-jolt/805493/
* electrive, BMW Supercharger access (Dec 10, 2025): https://www.electrive.com/2025/12/10/bmw-evs-gain-supercharger-access-in-north-america
* Genesis Owners, In-App Charging FAQ: https://owners.genesis.com/us/en/resources/general-information/iac-app-faqs.html
* electrive, Octopus Charge in North America (Aug 19, 2026): https://www.electrive.com/2026/08/19/octopus-charge-brings-electroverse-style-app-to-north-america/
* S&P Global, Presto and bp pulse (May 28, 2026): https://autotechinsight.spglobal.com/news/5288530/presto-and-bp-pulse-collaborate-to-expand-fast-charging-access-for-commercial-ev-drivers-in-us
* CleanTechnica, GM Energy Pass (Jun 9, 2026): https://cleantechnica.com/2026/06/09/gm-energy-pass-one-universal-interface-for-public-charging/
* Toyota Pressroom, 2026 bZ charging (Jan 21, 2026): https://pressroom.toyota.com/2026-toyota-bz-goes-on-sale-with-upgraded-convenience-features-expanded-charging-network-options-for-all-toyota-bev-drivers/
* Cars.com, Porsche Plug & Charge at Superchargers (Jan 20, 2026): https://cars.com/articles/porsche-adds-plug-charge-capability-for-evs-at-tesla-superchargers-520538/
* electrive, Nissan Plug & Charge at Electrify America (Oct 2, 2025): https://www.electrive.com/2025/10/02/nissan-to-gain-plug-charge-on-the-electrify-america-network
* Kia Owners, Kia Charge Pass: https://owners.kia.com/us/en/kia-electrify.html
* Lucid, public charging: https://www.lucidmotors.com/knowledge/ownership/charging/public-charging
* Volvo Cars, public charging: https://www.volvocars.com/us/cars/electrification/charging-your-volvo/public/
* electrive, Volvo adds Plug & Charge to EX90 and upcoming EX60 (Jun 19, 2026): https://electrive.com/2026/06/19/usa-volvo-adds-plug-charge-to-ex90-and-upcoming-ex60
* Electrek, Volvo public EV charging for EX90 and EX60 (Jun 18, 2026): https://electrek.co/2026/06/18/volvo-public-ev-charging-ex90-ex60/
* Motor1, Volvo Just Made Charging Your EV Way Easier (Jun 21, 2026): https://www.motor1.com/news/799192/volvo-evs-plug-charge-fast/
* InsideEVs, EVgo Plug & Charge expansion: https://insideevs.com/news/715603/evgo-plug-and-charge-expansion/
* ChargeHub, bp pulse is now available in the ChargeHub app (Aug 24, 2026): https://chargehub.com/en/ev-charging-news/bp-pulse-chargehub-app
* NACS Daily, BP Pulse joins ChargeHub's Passport Hub network (Oct 30, 2026): https://convenience.org/stay-current/news/2026/october/30/bp-pulse-joins-chargehub%E2%80%99s-passport-hub-network
* ChargeHub, IONNA now compatible with the ChargeHub app (Jun 10, 2026; updated Aug 4, 2026): https://chargehub.com/en/ev-charging-news/ionna-now-compatible-with-chargehub-app
* ChargeHub, EV charging networks (undated, checked Nov 5, 2026): https://chargehub.com/en/networks.html
* ChargeHub, EV Connect integration (Jul 10, 2025): https://chargehub.com/en/ev-charging-news/ev-connect-chargehub-app-integration
* ChargeHub help center, starting a charging session (activation fee): https://mogilechargehub.freshdesk.com/en/support/solutions/articles/72000583136-starting-a-charging-session
* ChargeHub, newsroom and blog listings (checked Nov 5, 2026): https://chargehub.com/en/ev-charging-press-release
* Automotive World, Volvo launches Plug & Charge for EX60 and EX90 in the US (Jun 18, 2026): https://www.automotiveworld.com/news/volvo-launches-plug-charge-for-ex60-and-ex90-in-us/
* InsideEVs, The Volvo EX60 And EX90 Get An Important EV Fast-Charging Upgrade: https://insideevs.com/news/799188/volvo-ex60-ex90-ev-plug-and-charge/
