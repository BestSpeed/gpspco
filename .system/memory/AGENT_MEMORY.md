# Agent Memory — باشگاه مجریان / BestSpeed/gpspco

## Current State
_What is being worked on right now, what is unfinished, and the immediate next steps. Always update this section. If the outcome of the most recent action is unknown, say so explicitly._

**v1.6.6 COMPLETE & PUSHED: `f455c34` on `arena/d26bb630-gpspco` (local HEAD == remote tip).**

**Verification (all green):** `cd /home/user/gpspco/.phplint && node lint.js /home/user/gpspco/mojrian-club` → 29/29; `node smoke.js /home/user/gpspco/mojrian-club` → **45/45 RESULT: OK**; `python3 audit_symbols.py` → **audit OK: no unknown symbols**. `mojrian-club.zip` regenerated (60 files = 65 − 5 deleted modules, ~1.21 MB) & committed. README changelog has the v1.6.6 entry; plugin version `MJC_VERSION = '1.6.6'`.

Nothing in progress. Next task will be a new user request.

## Task
_What the user asked for, in their terms. Preserve active acceptance criteria and consequential scope decisions. Remove obsolete narrative when necessary, but do not lose requirements that still affect the work._

Build «باشگاه مجریان» WordPress plugin in /home/user/gpspco per AGENT_BUILD_PROMPT.md (Persian/RTL, Jalali, Elementor widget, mockup-faithful UI). **v1.6.6 (10 items) — ALL DONE:** (1) rewards button «تبدیل انجام شد»→«تبدیل امتیاز» ✓; (2) greeting «{full_name ?: میهمان} خوش آمدید» ✓ (test: میهمان → «ندا محمدی»); (3) «پیام مدیر» full-width at top (`mjc-mgr-full`) ✓; (4) sidebar from LEFT to RIGHT (`.mjc-acc-layout{grid-template-columns:260px 1fr}` + side first in DOM) ✓; (5) notifications last-3 beside «آخرین کمپین‌ها» + «مشاهده همه» + NEW «اعلانات» page in top menu ✓; (6) «امتیاز شما»→«فعالیت‌های شما» under پیام مدیر with 4 stats (دریافتی/فعلی/تعداد پروژه‌های ثبت شده/تعداد دعوتی‌ها) ✓; (7) profile avatar upload ≤500KB jpg/png/webp → `avatar_id` upsert (old attachment deleted) + sidebar avatar ✓; (8) project+profile forms tidied (CSS polish: focus rings, radius 8-10, label accent bars, styled file inputs) ✓; (9) دوره‌ها/بانک دانش/نظرسنجی‌ها/کیف پول/پایه فنی COMPLETELY removed (files, hooks, tables, CPTs, menus, settings, roles caps, guide seed migration w/ seed_version 2→3, CSS, description) + QR leftovers of کارت عضویت removed (js initQr, qrcode CDN enqueue, .mjc-qr CSS) ✓; (10) admin `fix_menu_order` (admin_menu:99) → داشبورد first, جوایز CPT second ✓. Prior: v1.6.2–v1.6.5 as before.

## User Constraints & Corrections
_Explicit standing instructions and corrections the user stated about how the work should be done, including anything the user rejected. Only what the user explicitly directed — never infer. Never drop an entry unless the user reversed it or it applied only to a task that has finished._

- **«مبلغ پروژه را مدیر سایت در زمان تایید مشخص میکنه»** — member form must NOT contain مبلغ field.
- **Settings must let admin choose points basis قیمت یا متراژ and rate for each** («حتما حواست باشه»).
- Features removal (v1.6.6 «کلا هیچ اثری»): دوره‌ها/بانک دانش/نظرسنجی‌ها/کیف پول/پایه فنی fully gone (done, grep-verified). **اعلان‌ها RE-ADDED** per v1.6.6 (page + 3-item box). «پروفایل من» = «تکمیل اطلاعات کاربر» kept. کارت عضویت kept but QR-less (render_card cleaned).
- **«صفحه تبدیل امتیازات به جوایز دقیقا مثل این تصویر...»** — pixel-exact mockup + extracted icons (v1.6.0); button label now «تبدیل امتیاز» (overrides mockup text).
- **«پیامهای مدیر... نمایش داده بشه»** — «پیام مدیر» REMAINS (now full-width top).
- **«زمانی که ذخیره میشود باید این فرم همیشه با مشخصات کاربر پر باشد»** — profile form always prefilled (v1.6.3).
- **Admin menu badges:** red circle + white number left of menu name for approval menus; total sum beside «باشگاه مجریان» (now members/projects/rewards only).
- **WP admin account:** no approval, absent from members list, never role-demoted, full access. v1.6.6 hardening: `MJC_Members::set_status` now REFUSES staff rows (returns false before any update).
- **Never wipe/reseed user data on upgrades** (ensure_icons non-destructive; rewards seed wipe only when seed_version < 2; guide seed migration only deletes the stale feature article).
- Dashboard banner admin-uploadable; projects list-only + green «+ ایجاد پروژه جدید»; mockups authoritative; Elementor widget required; rewards preloaded+CRUD.

## Workspace
_Files and directories that matter: path, plus one line on what each contains and why it is relevant. Never include file contents; the workspace itself is the source of truth._

- /home/user/gpspco — repo. **Git: local HEAD == origin/arena/d26bb630-gpspco tip == `f455c34` v1.6.6 (pushed).** `.gitignore` = `*.zip` + `!mojrian-club.zip` + `.phplint/`.
- /home/user/gpspco/mojrian-club — plugin source **v1.6.6** (60 php/css/js files).
- /home/user/gpspco/mojrian-club.zip — v1.6.6 build (committed).
- /home/user/gpspco/README.md — changelog (v1.6.6 entry present).
- **/home/user/gpspco/.phplint — PERSISTENT test harness (gitignored, survives env resets because it is inside the repo checkout).** `package.json` + `node_modules/php-wasm@0.2.0`; `lint.js` (token_get_all TOKEN_PARSE — no forking!), `smoke.js` (mounts plugin→/plugin, stubs→/stubs, stubs/wp-core→/tmp/wp = ABSPATH); `tests.php` (**45 tests**); `audit_symbols.py`; `php_builtins.txt`/`php_constants.txt` (generated via gen_lists.js); `stubs/{wp-stubs.php,db-stubs.php,wp-core/...}`.

## Actions Taken
_Terse ordered log of executed actions: tool, target, and one-line outcome. These actions already ran and their effects persist._

- Stage-1 removals (5 modules + all traces) already in tree at turn start; trace cleanup: CSS (.mjc-badge-grade/.mjc-survey-item/.mjc-inquiry-result), metabox comment, guide hero text, plugin Description.
- Stage-2 UI: render_dashboard rewrite (mjc-mgr-full ×2, mjc-stats-4, acc-layout side-first 260px, avatar, greeting, notifications-3 + «مشاهده همه»), member_nav +notifications, rewards button «تبدیل امتیاز», form_profile avatar field + save (media_handle_upload + avatar_id upsert + old attachment delete), fix_menu_order in MJC_Admin (admin_menu:99).
- Stage-3 CSS: acc-layout 260px/1fr, stats-4, avatar-img, link-more, form polish.
- Harness rebuilt INSIDE repo (.phplint) after env wipe: stubs (ABSPATH/OBJECT/MB_IN_BYTES/KB_IN_BYTES/DOING_AUTOSAVE defines; wpdb::insert/update/delete/get_charset_collate/esc_like; get_posts fields=ids + posts_per_page semantics + post_status 'any'; WP_List_Table::display renders items via column_default; wp-core/{upgrade,file,image,media,class-wp-list-table} mounted at /tmp/wp/).
- **db-stubs critical fixes (re-discovered after wipe):** parse_from prepends `' '` so `\sWHERE\s+` matches trimmed tails (THE big bug — WHERE never matched); split_top compares `substr($chunk,1,$wlen)` (leading-space offset); split_values is quote-aware (JSON `'[51, 56]'` in INSERTs); do_insert assigns auto-id BEFORE defaults-fill (isset(0) pitfall) and stores rows as objects; CREATE TABLE columns parsed with OR without backticks + type→num/str for defaults; dbDelta-based schema = truth (violations on invalid columns).
- Plugin fixes this turn: **`MJC_Shortcodes::login_required()` added** (was called but undefined — fatal for guests on profile/rewards); **`MJC_Members::set_status` staff guard** (return false before update); QR traces removed (js initQr, public qrcode enqueue, .mjc-qr CSS).
- tests.php rewritten to real APIs (see Key Results). Final: 45/45.
- README.md changelog + feature list updated; version 1.6.6; zip rebuilt (60 files); git add -A → reset --soft origin → staged diff verified (23 files, +216/−1275) → commit `f455c34` → push.

## External Sources
_Web pages fetched: URL plus the takeaway that influenced the work._

- Mockup `تصاویر اولیه/تبدیل امتیازات به جوایز.jpg` — authoritative rewards page (icons extracted; button label now «تبدیل امتیاز» per task).

## Errors & Dead Ends
_What failed and why. Approaches tried and abandoned; do not retry._

- **`/tmp` and dirs outside `/home/user/gpspco` are NOT persisted** across env resets — harness now lives in `/home/user/gpspco/.phplint` (gitignored). npm install php-wasm re-runs if node_modules is stripped (it survives in-workspace currently).
- **php-wasm cannot fork** — `passthru(PHP_BINARY.' -l')` dies "Unable to fork"; lint = `token_get_all($src, TOKEN_PARSE)` catching ParseError.
- **`php.run()` output** arrives via 'output' event AND/OR return value — smoke.js prints `log` if no event fired; output may buffer until run ends (no live progress).
- **`parse_from` needs a leading space** — WP query tails are trimmed (`WHERE ...` at pos 0), all matchers use `\sKEYWORD`; always `$tail = ' '.trim($tail)`.
- **split_top word compare** must use `substr($chunk,1,$wlen)` — chunk starts AT the whitespace.
- **split_values must be quote-aware** — JSON arrays `'[51, 56]'` in VALUES otherwise split at comma → INSERT mismatch → silent project-create failure.
- **do_insert auto-id before defaults-fill** — fill with 0 then `!isset($row['id'])` is false (id=0 is set).
- **get_posts stub: don't default posts_per_page to 5** when caller passes numberposts; honor `fields=>ids`, `numberposts=>-1`, `post_status=>'any'`.
- **set_status($member_id)** = members-TABLE row id (NOT user_id) — tests must `mjc_test_set_status($user_id,...)` resolving row id via get_by_user. MJC_Roles::repair_admin_lockout does NOT exist — it is **MJC_Members::repair_admin_lockout**.
- **Render entrypoints RETURN strings** (no echo) — tests capture return values, not ob.
- Register: mobile normalized to `989...` (user_login); required fields full_name/national_code(10-digit checksum for profile)/province/job_type; profile form field names: national_code, work_address, birth_year/month/day (not id_number).
- MJC_Projects::TYPES keys = new/renovation/repair/cultural_sport/other_places. MJC_Points::log_transaction($uid,$amount,$action,$description,$ref_type,$ref_id). MJC_Notify::push($uid,$title,$body,$url=''); mark_all_read. MJC_Referrals::code($uid) (not ensure_code). Rewards meta: mjc_points_cost/mjc_stock/mjc_icon. Page slugs are PERSIAN (داشبورد، اعلانات، ...). set_status awards welcome points — tests use balance DELTAS.
- `MJC_Settings::save` doesn't exist — use `mjc_update_settings`/`mjc_get_setting`. stats() returns ARRAY (not object).
- Assert-guarded python patches: always verify exact whitespace via sed/cat -A first; line-based patching more reliable than multi-line literals.
- Audit: skip .js fn collection, PHP keyword list, lowercase `::name` = method, known classes = php_core|elementor|wp|plugin|stub, Elementor method/const whitelists.

## Key Results
_Exact results needed by the continuation model._

- **Harness commands:** `cd /home/user/gpspco/.phplint && node lint.js /home/user/gpspco/mojrian-club`; `node smoke.js /home/user/gpspco/mojrian-club`; `python3 audit_symbols.py` (exit 1 on unknowns). Expected: lint 29/29, smoke **45/45 RESULT: OK**, audit OK.
- **Git:** bot identity arena-ai-coding-agent[bot] / 298482267+arena-ai-coding-agent[bot]@users.noreply.github.com. Commit plan: `git add -A && git reset --soft origin/arena/d26bb630-gpspco` (verify staged diff == intended delta) + commit + push `git push origin arena/d26bb630-gpspco`. Remote tip **`f455c34`** v1.6.6.
- **php-wasm API:** `const { PhpNode } = require('php-wasm/PhpNode')`; `await php.binary`; mkdirp helper (mkdir has no -p); writeFile/readFile; listeners join e.detail arrays; PHP 8.4.1, NO mbstring (polyfills in wp-stubs), NO fork, getenv/putenv unreliable (use $GLOBALS).
- **tests.php boot (authoritative):** require /stubs/wp-stubs.php + /stubs/db-stubs.php; `$GLOBALS['mjc_stub']->is_admin=true; $GLOBALS['mjc_stub_sql_unknown']=array(); $GLOBALS['wpdb']=new wpdb();`; require /plugin/mojrian-club.php, do_action('plugins_loaded'), activation; fixtures site-admin(administrator)/moder(mjc_manager)/member-a/member-b + GLOBALS userA_id/userB_id/admin_id/moder_id. Helpers t/ok/eq/contains/not_contains + `mjc_test_register`, `mjc_test_images`, `mjc_test_set_status`. **45 tests** incl. new v1.6.6 ones (greeting, فعالیت‌های شما 4 stats, sidebar-before-main DOM order, پیام مدیر before layout, notif 3-vs-all, nav اعلانات, avatar save + 500KB reject, «تبدیل امتیاز» label, fix_menu_order, guest login_required box).
- **db-stubs contract:** schema truth = plugin CREATE TABLEs via dbDelta; invalid-column INSERT/UPDATE → violations; unhandled SQL/conditions → mjc_stub_sql_unknown (must be empty at end); rows are objects; auto-id first then defaults ('num'→0, 'str'→''); quote-aware split_values; `' '.trim($tail)` in parse_from.
- **Plugin API:** MJC_Members::create/upsert(hardened)/get_by_user/get(row-id)/set_status(row-id; NOW refuses staff)/stats(array)/staff_user_ids/not_staff_sql/is_staff(user_can: manage_options|mjc_manage_club|mjc_manage_members)/repair_admin_lockout/generate_referral_code/display_name; MJC_Points::log_transaction/balance(row points_balance)/history/award_by_rule; MJC_Rewards::request($uid,$rid,$address='')/seed_default_rewards(seed_version 2)/ensure_icons(no-arg, option mjc_rewards_icons_version)/attach_icon/seed_catalog; MJC_Notify::push/broadcast/list_for_user/unread_count/mark_all_read; MJC_Referrals::code/link/referrals; MJC_Projects::create/calculate_points/set_status/list_for_user/TYPES(new,renovation,repair,cultural_sport,other_places); MJC_Forms::form_{register,login,profile,project,reward_request,campaign_join,ticket,notifications_read} (verify nonce action = e.g. 'mjc_project' via $_POST['mjc_nonce']) + handle() on template_redirect (wp_safe_redirect then exit — stub redirect throws); MJC_Validator::national_code (checksum — '1234567890' valid); helpers mjc_uploaded_ids/mjc_update_settings/mjc_get_setting/mjc_is_active_member/mjc_page_url(Persian slugs)/mjc_pretty_mobile/mjc_normalize_mobile(→98...)/mjc_latest_broadcast/mjc_notice_url/mjc_render_notice. **WP_Error real class; harness defines NO MJC_*.**
- **v1.6.6 anchors:** render_dashboard (mjc-mgr-full ×2 → acc-layout side-first → grid2 campaigns|notifications-3); member_nav has 'notifications'=>'اعلانات'; form_profile avatar (mjc_uploaded_ids('avatar') → avatar_id upsert + wp_delete_attachment old); fix_menu_order($submenu['mjc-dashboard'] → [mjc-dashboard, edit.php?post_type=mjc_reward, rest]); CSS `.mjc-acc-layout{grid-template-columns:260px 1fr}`; menu badge markup `<span class="mjc-admin-badge awaiting-mod count-N">`; create_pages includes notifications=>[mojrian_notifications]; members schema has avatar_id.
- **login_required():** private static in MJC_Shortcodes — guest box + «ورود به باشگاه» link (added v1.6.6 — was called but undefined).
