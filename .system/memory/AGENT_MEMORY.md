# Agent Memory — باشگاه مجریان / BestSpeed/gpspco

## Current State
_What is being worked on right now, what is unfinished, and the immediate next steps. Always update this section. If the outcome of the most recent action is unknown, say so explicitly._

**v1.6.7 COMPLETE & PUSHED: `eafd0e9` on `arena/d26bb630-gpspco` (local HEAD == remote tip).** Prior: `f455c34` v1.6.6.

**v1.6.7 fixed the 404:** clicking a campaign in «آخرین کمپین‌ها» (dashboard) now opens the campaign's dedicated page. Root cause: `mjc_campaign` CPT registers on `init`, but `flush_rewrite_rules()` ran at activation (before CPT registration) → 'campaign' rewrite rules never generated; no single template existed. Fix: (1) version-gated `mjc_rewrite_flush` option + `flush_rewrite_rules(false)` at the END of `register_cpt()`; (2) `template_include` filter + `MJC_Campaigns::template()` → NEW `public/templates/single-mjc_campaign.php` (breadcrumb خانه/کمپین‌ها و قرعه‌کشی/title + large thumbnail + content + deadline `mjc_end_date` + winners + inline join form `wp_nonce('mjc_campaign','mjc_nonce')` + hidden `campaign_id` + `mjc_form=campaign_join`; guests→login link; closed→«پایان‌یافته»); (3) dashboard campaign links → `add_query_arg('mjc_campaign', ID, mjc_page_url('campaigns'))` (rewrite-independent) + `render_campaigns` `?mjc_campaign=ID` detail view (back link «→ بازگشت به کمپین‌ها» + join form; bad id→«کمپین موردنظر یافت نشد.»). Version 1.6.7 + README changelog + zip (61 files, 1.21 MB).

**Harness `.phplint/` NOW GIT-TRACKED (since eafd0e9)** — survives env resets. Contents: package.json (name mjc-phplint), .gitignore (node_modules/), lint.js (30/30), smoke.js, tests.php (**47 tests** incl. 2 campaign tests), stubs/wp-stubs.php + stubs/db-stubs.php + stubs/wp-core/* (WP_List_TABLE with display(), dbDelta, media/file/image), gen_lists.js → php_builtins.txt + php_constants.txt, audit_symbols.py. Top .gitignore = `*.zip` + `!mojrian-club.zip`. Commands: `cd /home/user/gpspco/.phplint && node lint.js /home/user/gpspco/mojrian-club` (30/30) + `node smoke.js /home/user/gpspco/mojrian-club` (47/47 RESULT: OK) + `python3 audit_symbols.py` (no unknown symbols). `npm install php-wasm` only if node_modules missing. **tests.php fires `do_action('init')` after plugins_loaded** (CPT registration + rewrite flush — required).

Nothing in progress. Next task will be a new user request.

## Task
_What the user asked for, in their terms. Preserve active acceptance criteria and consequential scope decisions. Remove obsolete narrative when necessary, but do not lose requirements that still affect the work._

Build «باشگاه مجریان» WordPress plugin in /home/user/gpspco per AGENT_BUILD_PROMPT.md (Persian/RTL, Jalali, Elementor widget, mockup-faithful UI). **v1.6.7 (campaign 404):** click campaign → dedicated page (rewrite flush after CPT reg + single template + `?mjc_campaign=ID` detail) — DONE. **v1.6.6 (10 items) — ALL DONE** (`f455c34`): rewards button «تبدیل امتیاز»; greeting «{full_name ?: میهمان} خوش آمدید»; «پیام مدیر» full-width top (`mjc-mgr-full`); sidebar RIGHT (`.mjc-acc-layout{grid-template-columns:260px 1fr}` + side first in DOM); notifications last-3 beside «آخرین کمپین‌ها» + «مشاهده همه» + «اعلانات» page; «فعالیت‌های شما» 4 stats (دریافتی/فعلی/تعداد پروژه‌های ثبت شده/تعداد دعوتی‌ها); profile avatar ≤500KB jpg/png/webp → `avatar_id` upsert + sidebar avatar; forms tidied; دوره‌ها/بانک دانش/نظرسنجی‌ها/کیف پول/پایه فنی COMPLETELY removed + QR-less کارت عضویت; admin menu داشبورد first, جوایز second. Prior: v1.6.2–v1.6.5 as before.

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

- /home/user/gpspco — repo, branch `arena/d26bb630-gpspco`, HEAD `eafd0e9` == remote tip.
- /home/user/gpspco/mojrian-club — plugin v1.6.7 (61 files; NEW public/templates/single-mjc_campaign.php).
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

- **Deliverable v1.6.7:** `mojrian-club.zip` (61 files) — campaign links open dedicated pages (no 404): rewrite rules rebuilt after CPT reg + `single-mjc_campaign.php` + rewrite-independent `?mjc_campaign=ID` detail. Tests: dashboard `mjc_campaign=` link; detail view join form + back link; bad id message; flush option == MJC_VERSION; template file exists.
- **Harness commands:** `cd /home/user/gpspco/.phplint && node lint.js /home/user/gpspco/mojrian-club` → 30/30; `node smoke.js /home/user/gpspco/mojrian-club` → 47/47 RESULT: OK; `python3 audit_symbols.py` → "audit OK: no unknown symbols".
- **Git workflow:** bot `arena-ai-coding-agent[bot]` / `298482267+arena-ai-coding-agent[bot]@users.noreply.github.com`; `git fetch && git add -A && git reset --soft origin/arena/d26bb630-gpspco` (verify staged == intended delta) + commit + `git push origin arena/d26bb630-gpspco`. Tips: `eafd0e9` v1.6.7 → `f455c34` v1.6.6 → `e6613a4` v1.6.5. Never force-push.
- **tests.php boot (authoritative):** require stubs; `$mjc_stub->is_admin=true; $GLOBALS['mjc_stub_sql_unknown']=array(); $GLOBALS['wpdb']=new wpdb(); $GLOBALS['mjc_stub_violations']=array();` require /plugin/mojrian-club.php, plugins_loaded, **init**, activation; fixtures site-admin(administrator)/moder(mjc_manager)/member-a/member-b + GLOBALS userA_id/userB_id/admin_id/moder_id. Helpers t/ok/eq/contains/not_contains + mjc_test_register, mjc_test_images, mjc_test_set_status.
- **Plugin API:** MJC_Members::create/upsert/status guard/get_by_user/get(row-id)/set_status(row-id; refuses staff)/stats(array)/staff_user_ids/not_staff_sql/is_staff/repair_admin_lockout/generate_referral_code/display_name; MJC_Points::log_transaction/balance/history=[rows,total]/award_by_rule (welcome +100 + referral bonus); MJC_Rewards::request/seed_default_rewards(seed_version 2)/ensure_icons/attach_icon/seed_catalog; MJC_Notify::push/broadcast/list_for_user/unread_count/mark_all_read; MJC_Referrals::code/link/referrals; MJC_Projects::create/calculate_points/set_status/list_for_user/TYPES; MJC_Campaigns::is_active/winners/join/register_cpt/template + post_type `mjc_campaign` (slug 'campaign'); MJC_Forms::form_{register,login,profile,project,reward_request,campaign_join,ticket,notifications_read} (nonce action e.g. 'mjc_project'; handle() on template_redirect via $_POST['mjc_form']); MJC_Validator::national_code ('1234567890' valid); helpers mjc_uploaded_ids/mjc_update_settings/mjc_get_setting/mjc_is_active_member/mjc_page_url(Persian slugs)/mjc_normalize_mobile/mjc_pretty_mobile/mjc_latest_broadcast/mjc_notice_url/mjc_render_notice. **Harness defines NO MJC_\*; WP_Error real class.**
- **Campaign form:** `wp_nonce_field('mjc_campaign','mjc_nonce')`, `mjc_form=campaign_join`, hidden `campaign_id`.
- **db-stubs contract:** invalid-column ops → mjc_stub_violations; unhandled SQL → mjc_stub_sql_unknown (both empty at final invariants); rows objects; quote-aware split_values.
- **Registration hardening:** auth auto-approve = add_role + `! is_staff`; upsert forces staff 'approved'; set_status refuses staff.
- **v1.6.6 anchors:** render_dashboard (`mjc-mgr-full` ×2 → acc-layout side-first 260px → grid2 campaigns|notifications-3 + link-more); member_nav 'notifications'=>'اعلانات'; form_profile avatar; fix_menu_order([mjc-dashboard, edit.php?post_type=mjc_reward, rest]); create_pages notifications=>[mojrian_notifications]; members schema avatar_id; `MJC_Shortcodes::login_required($text)` private static.
