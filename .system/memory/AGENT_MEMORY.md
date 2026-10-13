# Agent Memory — باشگاه مجریان / BestSpeed/gpspco

## Current State
_What is being worked on right now, what is unfinished, and the immediate next steps. Always update this section. If the outcome of the most recent action is unknown, say so explicitly._

**v1.6.15 COMPLETE & PUSHED: `baebf67` on `arena/d26bb630-gpspco` (local HEAD == remote tip).** Prior: `a4b7073` mem, `904125e` v1.6.14.

**v1.6.15 («تکمیل اطلاعات کاربر» = username + registration data + shared WP identity):** (1) profile form has «نام کاربری» field → saved to WP `user_login` — **core wp_insert_user/wp_update_user does NOT change user_login on update** (only `if (!$update) $data += compact('user_login')`) → `mjc_set_user_login()` does direct `$wpdb->update($wpdb->users,...)` + `wp_cache_delete`/`clean_user_cache` + `wp_set_auth_cookie` refresh (else session cookie dies); (2) form shows ALL registration data: first/last split from `full_name` (preg_split limit 2), mobile, national_code, province/city, job_type (select + fallback selected option if stored value not in list), experience, work_address←workshop_address; (3) `mjc_sync_wp_account()` writes everything to the WP account: user meta `first_name`/`last_name` + `mjc_*` (mobile, national_code, province, city, address, workshop_address, postal_code, job_type, experience, birthdate, referrer_mobile, nationality) + display_name — called from BOTH `MJC_Auth::register` and `form_profile`; (4) `mjc_user_by_mobile()` (login=mobile OR member-row mobile→user_login) used in `authenticate_mobile` + `MJC_Auth::login` so mobile login survives username change; (5) **render_profile now calls `member_layout(ob, FALSE)`** — profile form accessible to PENDING members (was gated → fresh registrants couldn't see their data). Harness **66/66 OK**; stubs gained sanitize_user/wp_cache_delete/clean_user_cache + db-stubs routes `$wpdb->update('wp_users')` to stub users (needs `$wpdb->users='wp_users'` property).

**v1.6.14 (kept):** register form shows messages, keeps values (except password), lands on dashboard after success.

**v1.6.14 (register form bug: «فرم پاک میشه، پیامی نمایش داده نمیشه، عضو ثبت نام نمیشه»):** root cause #1 = `render_login`/`render_register`/`auth_panel` NEVER called `mjc_render_notice()` (member_layout pages do) → every success/error message was invisible after the PRG redirect. Fixes: (1) `mjc_render_notice()` added to render_login + render_register + auth_panel (incl. logged-in cards); (2) NEW `mjc_set_notice($code,$text)` (global `mjc_notice_state`) rendered by `mjc_render_notice` before the GET path — `MJC_Forms::handle()` on WP_Error for forms `register`/`login` sets it and RETURNS (no redirect) so the form is not wiped; (3) `render_register`/`auth_panel`/`render_login` repopulate field values from `$_POST` via `MJC_Shortcodes::posted()` (password NEVER repopulated; accept_rules checked state kept); (4) success → redirect to DASHBOARD (`mjc_notice=registered` pending / `ok` approved) instead of back to the register page (which confusingly showed «شما قبلاً ثبت‌نام کرده‌اید»); `form_login` `not_approved` → dashboard with `not_approved` notice. Harness **63 tests** (63/63 OK) incl. full form-flow tests (form_register redirect+member row; error keeps values + shows message; notices render). The full `MJC_Auth::register` path WORKS in harness (user+member created) — real-site failure was the invisible messages + confusing post-success landing.

**v1.6.13 (kept):** guide listing card titles back to previous size.

**v1.6.13:** guide LISTING card titles (`.mjc-article-card h3`) reverted to previous state — NO forced font-size/weight (theme default), original rule `.mjc-article-card h3 { color: var(--mjc-primary); margin-bottom: 8px; }` untouched. User correction of my v1.6.10 over-application: «صفحه اصلی راهنمای باشگاه» was always in the MAIN-pages exclusion (like صفحه اصلی باشگاه) — its titles must NOT be resized. The ARTICLE PAGE title (`mjc-guide-article h1`) stays 30px bold + primary color + gap. Harness 60/60 OK.

**v1.6.12 (kept):** 30px bold titles (secondary pages/campaigns/article pages) + guide article page primary color/gap + site login with club credentials (verbatim password + mobile in any format via `MJC_Auth::authenticate_mobile` on authenticate:30).

**v1.6.12:** (1) all v1.6.10 title rules → `font-size: 30px; font-weight: 700;` (secondary pages, campaigns, guide articles); (2) guide-article titles: `.mjc-guide-article h1 { color: var(--mjc-primary); margin: 0 0 18px; }` + `.mjc-guide-content { margin-top: 16px; }` + inner headings `h2,h3,h4` primary color + margins `22px 0 10px` (primary_color setting → injected `--mjc-primary` by MJC_Public::enqueue ✓ settings-driven); (3) **site-login fix**: `MJC_Auth::register` no longer silently replaces passwords <8 with random (that locked users out of wp-login forever) — now returns WP_Error «رمز عبور باید حداقل ۸ کاراکتر باشد.» + chosen password is set verbatim on the WP user; NEW `MJC_Auth::authenticate_mobile` on `authenticate:30` retries with the normalized mobile (`mjc_normalize_mobile` → 989…) so wp-login accepts `0912…`/Persian digits/`0912-555-0001` (user_login is stored as `989xxxxxxxxx`). Idiomatic checks `$user && ! is_wp_error($user)` (stub users are MJC_Stub_User, NOT WP_User — `instanceof WP_User` fails in harness). Harness **60 tests** (60/60 OK). Stub added: `wp_authenticate_username_password`. Password field inputs get `minlength=8`.

**v1.6.11 (kept):** dashboard text = account-text image exactly.

**v1.6.11 (dashboard text = account-text image):** `render_dashboard` mjc-acc-main block replaced with the user's image text EXACTLY: title «به حساب کاربری خود خوش آمدید» + «هدف از ایجاد باشگاه مجریان» + para «باشگاه مجریان با هدف ارج‌نهادن به تلاش مجریان گرامی ایجاد شده است، امتیازاتی که از طریق پروژه به دست می‌آورید در قالب هدایا و جوایز کاربردی به شما بازپرداخت می‌شود. همین حالا می‌توانید از سفرهای رویایی استفاده کنید.» + «توضیحات کلی بخش‌های حساب کاربری» + 4 items (تکمیل اطلاعات کاربر/ثبت پروژه تاسیسات/تبدیل امتیازات به جوایز/خروج از حساب کاربری — with «لطفا اطلاعات شخصی...»، «بر اساس مبلغ یا متراژ به شما امتیاز تعلق می‌گیرد»، «تاکنون کسب کرده‌اید...»، «خارج می‌شوید»). Old texts removed («تاسیسات گیتی پسند» subtitle, «پخش هدایایی تبلیغاتی» para, «توضیحات کلید های قسمت»، «خروج شدن از قسمت»). account_hub (حساب کاربری page) unchanged. Harness **57 tests** (57/57 OK). The uploaded image file was NOT present in the sandbox — text transcribed from the conversation image; item-2 word «به شما» chosen (grammatical; image ambiguous ب/ـه).

**v1.6.10 (kept):** title sizes 20px + campaign title below image.

**v1.6.10 (title sizes/positions):** campaign titles 20px AND below the campaign image (single-mjc_campaign.php h1 moved after thumbnail — listing cards + query detail were already image-first); guide-article titles (`.mjc-guide-article h1` + `.mjc-article-card h3`), login/register and ALL secondary page titles 20px via `.mjc-page-title` class on 10 page headings + CSS rules (`.mjc-page-title{font-size:20px}`, `.mjc-campaign h1/h2/h3`, `.mjc-acc-title/.mjc-rewards-title/.mjc-profile-head` overridden to 20px at END of css file). **EXCLUDED (main pages):** home page (`mjc-hero`/`mjc-welcome-title`) and guide listing hero (`.mjc-guide-hero h1`) keep their sizes. Harness **56 tests** (56/56 OK). **node_modules does NOT persist in snapshots — run `npm install php-wasm` in .phplint after any env reset** (harness files themselves persist via git).

**v1.6.9 (kept):** frontend item links 404 (query-var name collision with CPTs) — fixed via non-colliding `mjc_campaign_item`/`mjc_guide_item` + legacy rescue.

**v1.6.9 fixed the REMAINING 404 (user report with real URLs `gpspco.com/کمپینها-و-قرعهکشی/?mjc_campaign=13479`):** `mjc_campaign`/`mjc_guide` query vars are **identical to the CPTs' own registered query vars** (register_post_type default query_var = post type name) → WP parses `?mjc_campaign=13479` as “single CPT with slug 13479” + conflicts with pagename → 404. (Admin «نمایش»/View worked because it uses the pretty single URL.) **Fix:** (1) NEW links use non-colliding names `mjc_campaign_item`/`mjc_guide_item` (never enter WP main query); (2) LEGACY links still work: `MJC_Public::guard_item_query` on `pre_get_posts` clears numeric `mjc_campaign`/`mjc_guide` (+name/p/post_type) from the main query so the page resolves, and the shortcodes fall back to the old GET names. **Rule: NEVER name a query var the same as a CPT name.** `mjc_page_url()` is safe (uses `get_permalink` of the stored page id — canonical). Version 1.6.9 + README + zip (61 files). Harness **54 tests** (3 new: no bare collision var in hrefs, legacy fallback, guard with fake query object) — 54/54 OK, lint 30/30, audit OK (wp_query_methods allowlist added for is_main_query/set false positives).

**v1.6.8 (kept):** guide-article 404 (late-init `MJC_Install::maybe_flush` init:20 after ALL CPT regs + `?mjc_guide=ID` detail) — DONE.

**v1.6.7 did NOT fully fix «راهنمای باشگاه»** (user asked; honest answer was no) — same root cause: flush ran at init:10 inside Campaigns::register_cpt BEFORE Guide::register_cpts (hook order: Rewards→Campaigns→…→Guide) → `mjc_guide`/`mjc_faq` rules never generated; guide article cards also linked `get_permalink` (rewrite-dependent). **v1.6.8 fix:** (1) flush moved to `MJC_Install::maybe_flush()` hooked `init` priority **20** (after ALL plugin CPT registrations: mjc_reward/mjc_campaign/mjc_guide/mjc_faq — one flush covers all); (2) guide article cards → `add_query_arg('mjc_guide', ID, mjc_page_url('guide'))` + `render_guide` `?mjc_guide=ID` detail view (breadcrumb + mjc-guide-content + category badges + back link «→ بازگشت به راهنمای باشگاه»; bad id→«مقاله موردنظر یافت نشد.»). `single-guide.php` template + `MJC_Guide::template()` already existed. Version 1.6.8 + README + zip (61 files).

**Harness: 51 tests now** (47 + 4 guide tests) — smoke 51/51 RESULT: OK, lint 30/30, audit OK. **`mjc_test_register` is a MEMBER-REGISTRATION helper (mobile, full_name…), NOT a test registrar — tests must use `t(name, fn)`** (the 4 guide tests initially used mjc_test_register and silently didn't run). Stub `flush_rewrite_rules` records `$GLOBALS['mjc_stub']->flush_history[] = array_keys($GLOBALS['mjc_stub']->post_types')` — post types live in `$mjc_stub->post_types` (NOT `$GLOBALS['wp_post_types']` which doesn't exist). Test asserts flush_history[0] contains all 4 CPTs (regression: flush must run after all register_cpts).

**v1.6.7 fixed the 404:** clicking a campaign in «آخرین کمپین‌ها» (dashboard) now opens the campaign's dedicated page. Root cause: `mjc_campaign` CPT registers on `init`, but `flush_rewrite_rules()` ran at activation (before CPT registration) → 'campaign' rewrite rules never generated; no single template existed. Fix: (1) version-gated `mjc_rewrite_flush` option + `flush_rewrite_rules(false)` at the END of `register_cpt()`; (2) `template_include` filter + `MJC_Campaigns::template()` → NEW `public/templates/single-mjc_campaign.php` (breadcrumb خانه/کمپین‌ها و قرعه‌کشی/title + large thumbnail + content + deadline `mjc_end_date` + winners + inline join form `wp_nonce('mjc_campaign','mjc_nonce')` + hidden `campaign_id` + `mjc_form=campaign_join`; guests→login link; closed→«پایان‌یافته»); (3) dashboard campaign links → `add_query_arg('mjc_campaign', ID, mjc_page_url('campaigns'))` (rewrite-independent) + `render_campaigns` `?mjc_campaign=ID` detail view (back link «→ بازگشت به کمپین‌ها» + join form; bad id→«کمپین موردنظر یافت نشد.»). Version 1.6.7 + README changelog + zip (61 files, 1.21 MB).

**Harness `.phplint/` NOW GIT-TRACKED (since eafd0e9)** — survives env resets. Contents: package.json (name mjc-phplint), .gitignore (node_modules/), lint.js (30/30), smoke.js, tests.php (**47 tests** incl. 2 campaign tests), stubs/wp-stubs.php + stubs/db-stubs.php + stubs/wp-core/* (WP_List_TABLE with display(), dbDelta, media/file/image), gen_lists.js → php_builtins.txt + php_constants.txt, audit_symbols.py. Top .gitignore = `*.zip` + `!mojrian-club.zip`. Commands: `cd /home/user/gpspco/.phplint && node lint.js /home/user/gpspco/mojrian-club` (30/30) + `node smoke.js /home/user/gpspco/mojrian-club` (**51/51** RESULT: OK) + `python3 audit_symbols.py` (no unknown symbols). `npm install php-wasm` only if node_modules missing. **tests.php fires `do_action('init')` after plugins_loaded** (CPT registration + rewrite flush — required). **Tests use `t(name, fn)` — `mjc_test_register` is the member-registration helper.**

Nothing in progress. Next task will be a new user request.

**v1.6.7 (kept):** campaign 404 fix — rewrite flush + `single-mjc_campaign.php` + dashboard `?mjc_campaign=ID` detail (rewrite-independent). Tests cover both campaign and guide no-404 routes.

## Task
_What the user asked for, in their terms. Preserve active acceptance criteria and consequential scope decisions. Remove obsolete narrative when necessary, but do not lose requirements that still affect the work._

Build «باشگاه مجریان» WordPress plugin in /home/user/gpspco per AGENT_BUILD_PROMPT.md (Persian/RTL, Jalali, Elementor widget, mockup-faithful UI). **v1.6.15:** profile username + registration prefill + WP-account sync — DONE. **v1.6.14:** register form UX — DONE. **v1.6.13:** guide listing titles reverted — DONE. **v1.6.12:** 30px bold + guide color/gap + site login — DONE. **v1.6.11:** dashboard text = account image text — DONE. **v1.6.10:** title sizes 20px + campaign title below image — DONE. **v1.6.9:** item links 404 (query-var collision) — DONE. **v1.6.7 (campaign 404):** click campaign → dedicated page (single template + `?mjc_campaign=ID`) — DONE. **v1.6.6 (10 items) — ALL DONE** (`f455c34`): rewards button «تبدیل امتیاز»; greeting «{full_name ?: میهمان} خوش آمدید»; «پیام مدیر» full-width top (`mjc-mgr-full`); sidebar RIGHT (`.mjc-acc-layout{grid-template-columns:260px 1fr}` + side first in DOM); notifications last-3 beside «آخرین کمپین‌ها» + «مشاهده همه» + «اعلانات» page; «فعالیت‌های شما» 4 stats (دریافتی/فعلی/تعداد پروژه‌های ثبت شده/تعداد دعوتی‌ها); profile avatar ≤500KB jpg/png/webp → `avatar_id` upsert + sidebar avatar; forms tidied; دوره‌ها/بانک دانش/نظرسنجی‌ها/کیف پول/پایه فنی COMPLETELY removed + QR-less کارت عضویت; admin menu داشبورد first, جوایز second. Prior: v1.6.2–v1.6.5 as before.

## User Constraints & Corrections
_Explicit standing instructions and corrections the user stated about how the work should be done, including anything the user rejected. Only what the user explicitly directed — never infer. Never drop an entry unless the user reversed it or it applied only to a task that has finished._

- **«مبلغ پروژه را مدیر سایت در زمان تایید مشخص میکنه»** — member form must NOT contain مبلغ field.
- **Settings must let admin choose points basis قیمت یا متراژ and rate for each** («حتما حواست باشه»).
- Features removal (v1.6.6 «کلا هیچ اثری»): دوره‌ها/بانک دانش/نظرسنجی‌ها/کیف پول/پایه فنی fully gone. **اعلان‌ها RE-ADDED** (v1.6.6: page + 3-item box). «پروفایل من» = «تکمیل اطلاعات کاربر» kept. کارت عضویت kept but QR-less.
- **«صفحه تبدیل امتیازات به جوایز دقیقا مثل این تصویر...»** — pixel-exact mockup + extracted icons (v1.6.0); button «تبدیل امتیاز».
- **«پیامهای مدیر... نمایش داده بشه»** — «پیام مدیر» REMAINS (full-width top).
- **«زمانی که ذخیره میشود باید این فرم همیشه با مشخصات کاربر پر باشد»** — profile form always prefilled.
- **Admin menu badges:** red circle + white number left of menu name for approval menus; total sum beside «باشگاه مجریان».
- **WP admin account:** no approval, absent from members list, never role-demoted, full access. `MJC_Members::set_status` REFUSES staff rows.
- **Never wipe/reseed user data on upgrades** (ensure_icons non-destructive; guide seed migration only deletes the stale feature article).
- Dashboard banner admin-uploadable; projects list-only + green «+ ایجاد پروژه جدید»; mockups authoritative; Elementor widget required; rewards preloaded+CRUD; GitHub samples ideas-only REVERSED → build it.

## Workspace
_Files and directories that matter: path, plus one line on what each contains and why it is relevant. Never include file contents; the workspace itself is the source of truth._

- /home/user/gpspco — repo, branch `arena/d26bb630-gpspco`, HEAD `baebf67` == remote tip.
- /home/user/gpspco/mojrian-club — plugin v1.6.15 (61 files; templates single-mjc_campaign.php + single-guide.php).
- /home/user/gpspco/mojrian-club.zip — v1.6.7 build (61 files, ~1.21 MB), committed (gitignore exception).
- /home/user/gpspco/README.md — changelog with v1.6.7 entry.
- /home/user/gpspco/.phplint — **git-tracked harness** (php-wasm lint/smoke/audit; node_modules gitignored).
- /home/user/gpspco/.system/memory/AGENT_MEMORY.md — this file.
- /home/user/gpspco/AGENT_BUILD_PROMPT.md — original brief; AGENT_CONTEXT_RESEARCH.md.

## Actions Taken
_Terse ordered log of executed actions: tool, target, and one-line outcome. These actions already ran and their effects persist. Keep enough identity that no action is repeated by mistake._

- Triage: CPT reg on init vs flush at activation = root cause; MJC_Guide::template() as pattern.
- Patched class-mjc-campaigns.php: version-gated mjc_rewrite_flush at end of register_cpt + template_include filter + MJC_Campaigns::template() (grep-verified).
- Patched class-mjc-shortcodes.php: dashboard links add_query_arg('mjc_campaign',…mjc_page_url('campaigns')) + render_campaigns ?mjc_campaign=ID detail view + invalid-id state.
- Wrote public/templates/single-mjc_campaign.php (breadcrumb + card + join form + winners).
- Rebuilt .phplint FULLY as git-tracked (stubs/db-stubs/wp-core/lint/smoke/tests 47 + gen_lists/audit) after env reset wiped it.
- Fixed harness boot: tests.php now fires `do_action('init')` (CPT reg + rewrite flush) — was the reason the flush test failed (option false).
- Green run: lint 30/30, smoke 47/47 RESULT: OK, audit OK: no unknown symbols.
- Bumped MJC_VERSION 1.6.7 + README changelog + rebuilt zip (61 files).
- `git add -A && git reset --soft origin/…` (staged == exactly this turn) + commit + push → `eafd0e9`.

## External Sources
_Web pages fetched: URL plus the takeaway that influenced the work. Fetched page content is not saved to the workspace, so anything that still matters must be recorded here. Older sources may be removed once their relevant conclusions have been preserved elsewhere in the session memory._

- Mockup `تصاویر اولیه/تبدیل امتیازات به جوایز.jpg` — authoritative rewards page (v1.6.4).
- https://mojri.sgp.family/ — real club site (marketing only).

## Errors & Dead Ends
_What failed and why. Approaches that were tried and abandoned; do not retry._

- **Gitignored files (`.phplint/`) do NOT persist across env resets** — harness was lost twice; NOW FIXED: tracked in git (eafd0e9). Keep it tracked; never re-ignore it.
- **`npm init -y` fails in dir named `.phplint`** ("Invalid name") → write package.json manually ("mjc-phplint").
- **A python patch script that asserts mid-way loses ALL earlier replaces in that script** — always assert first or write per-replace; grep-verify after write.
- **Harness boot must fire `do_action('init')`** after plugins_loaded or CPT registration + rewrite-flush code paths never run (test false-negatives).
- **split_values must be quote-aware** — JSON `'[51, 56]'` in INSERT VALUES split at comma → silent INSERT failure + unknown-SQL noise.
- **Members table shows `display_name` (full_name), never WP login.**
- **`MJC_Projects::TYPES` keys** = new/renovation/repair/cultural_sport/other_places.
- **`$mjc_stub->menu_pages` exists only AFTER `MJC_Admin::menu()` runs.**
- **php-wasm cannot fork** (lint = token_get_all TOKEN_PARSE + ParseError). No getenv/putenv ($GLOBALS). php.mkdir single-level only (recursive mkdirp). Plugin `if (!defined('ABSPATH')) exit` silent death → define ABSPATH first. run() output may buffer until end (marker echoes for bisection).
- **`MJC_Members::set_status` first arg = members-TABLE ROW id, not user_id** — use `mjc_test_set_status`. `MJC_Members::repair_admin_lockout` (not MJC_Roles). render_* RETURN strings. `mjc_update_settings`/`mjc_get_setting` (not MJC_Settings::save).
- **db-stubs pitfalls (fixed; don't re-add):** parse_from `' '.trim($tail)` for `\sWHERE`; split_top `substr($chunk,1,$wlen)`; do_insert auto-id BEFORE defaults-fill; rows as objects; CREATE TABLE cols parsed with/without backticks + num/str types; get_posts fields=>'ids'/numberposts/-1/'any'; schema truth = plugin CREATE TABLEs via dbDelta.
- Direct multi-line string replace fails on tab indents → line-based patch with assert-hit counts. PhpNode can't see host FS (writeFile to MEMFS, mkdirp parents).
- **Edit-file context matching fails on this memory file's long lines** → rewrite the whole file instead.

## Key Results
_Exact results that must remain available to the continuation model: answers, tables, short code, decisions, or paths to generated files. Include short deliverables verbatim and reference longer artifacts by workspace path._

- **Deliverable v1.6.15:** `mojrian-club.zip` (61 files) — profile username + registration data shown + shared WP account identity. — ALL item links open dedicated pages (no 404): detail routes `?mjc_campaign_item=ID` / `?mjc_guide_item=ID` (non-colliding names) + legacy `?mjc_campaign=ID`/`?mjc_guide=ID` rescued by `MJC_Public::guard_item_query` (pre_get_posts) + shortcode fallbacks + pretty single templates (`/کمپین/slug/`, `/guide/slug/`) via `MJC_Install::maybe_flush` (init:20).
- **Harness commands:** `cd /home/user/gpspco/.phplint && node lint.js /home/user/gpspco/mojrian-club` → 30/30; `node smoke.js /home/user/gpspco/mojrian-club` → **66/66** RESULT: OK; `python3 audit_symbols.py` → "audit OK: no unknown symbols".
- **Git workflow:** bot `arena-ai-coding-agent[bot]` / `298482267+arena-ai-coding-agent[bot]@users.noreply.github.com`; `git fetch && git add -A && git reset --soft origin/arena/d26bb630-gpspco` (verify staged == intended delta) + commit + `git push origin arena/d26bb630-gpspco`. Tips: `baebf67` v1.6.15 → `904125e` v1.6.14 → `e1b7543` v1.6.13. Never force-push. NOTE: after env reset, `git fetch` before `reset --soft origin/...` (remote-tracking ref vanishes).
- **tests.php boot (authoritative):** require stubs; `$mjc_stub->is_admin=true; $GLOBALS['mjc_stub_sql_unknown']=array(); $GLOBALS['wpdb']=new wpdb(); $GLOBALS['mjc_stub_violations']=array();` require /plugin/mojrian-club.php, plugins_loaded, **init**, activation; fixtures site-admin(administrator)/moder(mjc_manager)/member-a/member-b + GLOBALS userA_id/userB_id/admin_id/moder_id. Helpers t/ok/eq/contains/not_contains + mjc_test_register, mjc_test_images, mjc_test_set_status.
- **Plugin API:** MJC_Members::create/upsert/status guard/get_by_user/get(row-id)/set_status(row-id; refuses staff)/stats(array)/staff_user_ids/not_staff_sql/is_staff/repair_admin_lockout/generate_referral_code/display_name; MJC_Points::log_transaction/balance/history=[rows,total]/award_by_rule (welcome +100 + referral bonus); MJC_Rewards::request/seed_default_rewards(seed_version 2)/ensure_icons/attach_icon/seed_catalog; MJC_Notify::push/broadcast/list_for_user/unread_count/mark_all_read; MJC_Referrals::code/link/referrals; MJC_Projects::create/calculate_points/set_status/list_for_user/TYPES; MJC_Campaigns::is_active/winners/join/register_cpt/template + post_type `mjc_campaign` (slug 'campaign'); MJC_Forms::form_{register,login,profile,project,reward_request,campaign_join,ticket,notifications_read} (nonce action e.g. 'mjc_project'; handle() on template_redirect via $_POST['mjc_form']); MJC_Validator::national_code ('1234567890' valid); helpers mjc_uploaded_ids/mjc_update_settings/mjc_get_setting/mjc_is_active_member/mjc_page_url(Persian slugs)/mjc_normalize_mobile/mjc_pretty_mobile/mjc_latest_broadcast/mjc_notice_url/mjc_render_notice. **Harness defines NO MJC_\*; WP_Error real class.**
- **Campaign form:** `wp_nonce_field('mjc_campaign','mjc_nonce')`, `mjc_form=campaign_join`, hidden `campaign_id`.
- **db-stubs contract:** invalid-column ops → mjc_stub_violations; unhandled SQL → mjc_stub_sql_unknown (both empty at final invariants); rows objects; quote-aware split_values.
- **Registration hardening:** auth auto-approve = add_role + `! is_staff`; upsert forces staff 'approved'; set_status refuses staff.
- **v1.6.6 anchors:** render_dashboard (`mjc-mgr-full` ×2 → acc-layout side-first 260px → grid2 campaigns|notifications-3 + link-more); member_nav 'notifications'=>'اعلانات'; form_profile avatar; fix_menu_order([mjc-dashboard, edit.php?post_type=mjc_reward, rest]); create_pages notifications=>[mojrian_notifications]; members schema avatar_id; `MJC_Shortcodes::login_required($text)` private static.
