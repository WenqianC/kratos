# Custom Module Map

Last updated: 2026-06-12

This document describes the current custom code in this Kratos theme fork. It is a maintenance map, not a rewrite plan.

Follow the performance and coding constraints in `AGENTS.md` when changing these modules.

## Loading Chain

- `functions.php` loads `custom/custom.php`.
- `inc/theme-core.php` enqueues `custom/custom.css` and `custom/custom.js` on the front end.
- `custom/custom.php` loads:
  - `custom/module-upload-policy.php`
  - `custom/module-content-display.php`
  - `custom/module-post-pagination.php`
  - `custom/module-post-list-filter.php`
  - `custom/module-user-admin.php`
  - `custom/module-auth-security.php`
  - `custom/module-major-update.php`
  - `custom/module-editor-rules.php`
  - `custom/module-search-protection.php`
  - `custom/module-reply-to-me.php`
  - `custom/module-comment-tools.php`
  - `custom/module-blocklist.php`
  - `custom/module-media-library.php`
  - `custom/module-bookmark.php`
  - `custom/module-default-avatars.php`

## File Overview

### `custom/custom.php`

Main custom entry file. It only loads the feature modules in a stable order.

Keep this file small. New custom behavior should usually live in a dedicated `custom/module-*.php` file.

### `custom/module-disabled-snippets.php`

Archive for disabled or occasional-use snippets.

- This file is not loaded by `custom/custom.php`.
- Snippets in this file do not affect runtime behavior.
- Move code out of this file only after confirming with the site owner.
- Use this file for old commented-out code that may be useful later.

### `custom/module-upload-policy.php`

Upload and image policy:

- Allows broad uploads with `ALLOW_UNFILTERED_UPLOADS`.
- Disables WordPress large-image scaling.
- Disables automatic EXIF rotation.
- Disables responsive `srcset` generation.
- Blocks SVG, SVGZ, HTML, HTM, XML, and XHTML uploads by extension, case-insensitively.

Performance note: upload checks only run during uploads. They do not add normal page-view load.

### `custom/module-content-display.php`

Front-end display helpers:

- Applies `make_clickable` to post content.
- Defines `dn_is_show_post_stats()`.
- Used by templates to show heat/likes only to users who can edit the current post.

### `custom/module-post-pagination.php`

Multipage post and page navigation:

- Keeps direct page-number links to at most 7: the first 5 and last 2 pages.
- Adds a page selector containing every page and selects the current page by default.
- Generates preview pagination with the `page` query argument so scheduled and unpublished previews can change pages correctly.
- Keeps the existing previous-page, next-page, and single-post top/bottom pagination positions.

Performance note: pagination uses WordPress's existing in-request page count and adds no queries or AJAX requests.

### `custom/module-post-list-filter.php`

Admin post-list author scope:

- Applies only to the Published, Draft, and Trash tabs for posts.
- Defaults those tabs to the current user's posts.
- Adds a "我的文章 / 全部文章" dropdown to the existing post-list filters.
- Keeps the current status tab when filtering by an author-column link.
- "全部文章" preserves the site's existing permission rules and does not grant access to additional content.

Performance note: the default view adds an `author` condition to the existing main post query. A small inline admin script updates only the author links already rendered on the page. It adds no separate queries, user lookups, AJAX requests, or counts.

### `custom/module-user-admin.php`

Admin user tools:

- Hides selected Advanced Post Types Order controls for editor/author roles.
- Records last login time and IP in user meta.
- Adds last login time, last login IP, and registration time columns to the Users list.
- Allows IP search on the Users list.
- Replaces the delete-user reassignment dropdown with a numeric user ID input to avoid a huge user dropdown.

Performance note: normal login recording is light. IP search uses user meta queries and should be used as an admin tool, not as a high-frequency workflow.

### `custom/module-auth-security.php`

Registration, lost-password, mail, and account safety:

- Adds custom proof questions to registration and lost-password forms.
- Validates the submitted answer.
- Appends a custom notice to all WordPress mail.
- Adds request IP information to password reset mail.
- Blocks selected sensitive username keywords at registration.
- Disables front-end password reset for administrator accounts and a specific protected account.

Maintenance note: current behavior works for normal form submissions. If hardening is needed later, sanitize and type-check `$_POST['proof']` before string operations.

### `custom/module-major-update.php`

Major update workflow:

- Adds a side metabox to published posts.
- When checked, requires a 4-15 character note.
- Resets the post publish time to now.
- Appends the note to the title as `[note]`.

Intended behavior: old bracketed notes are not removed automatically. Authors can manually edit titles such as `[5.7更新第三章] [5.8更新第四章]`.

### `custom/module-editor-rules.php`

Post editor rules:

- Removes the author metabox from post editing.
- Adds a title-format notice under the post title.
- Changes tag input helper text.
- Blocks post submission if no tag is present in the classic editor form.

### `custom/module-search-protection.php`

Search and anti-scraping controls:

- Removes `post_content` and `post_excerpt` from non-admin search SQL.
- Blocks guest search with a 403 page.
- Caps non-admin queries requesting more than 50 posts, or all posts, down to 20 posts.

Performance note: this protects the small database server from expensive searches and large list requests.

Compatibility note: it can also affect non-admin plugin/API queries that legitimately ask for more than 50 posts.

### `custom/module-reply-to-me.php`

Adds a custom comment-management experience:

- Adds a "Replies to me" comments view and hides the redundant "Approved" view.
- Preserves the "Replies to me" and "Mine" scopes when searching comments.
- Keeps the global comment-view counts unchanged when viewing custom or isolated comment lists.
- Limits non-moderator admin comment searches to visible author names and comment content.
- Counts replies to the current user with a static per-request cache.
- Adds current-user status counts for moderated, spam, and trash comments with one grouped query.
- Replaces the admin-bar comments bubble with a lightweight link.
- Removes numeric comment counts from the browser tab title.
- Adds a dashboard widget showing the latest 5 replies with the available comment actions.
- Keeps Reply as the first action in the native dashboard activity widget so it has no leading separator.
- Hides global comment counts in the dashboard overview for non-moderators.

Performance note: the module avoids expensive top-bar count work, but the replies/count queries still touch the comments table and use subqueries. It is acceptable for moderate comment volume. If comment volume grows large, this should be the first comment module to profile.

Maintenance note: non-moderator comment isolation currently targets selected comment statuses. Default/all/approved behavior should be reviewed carefully before changing permissions.

### `custom/module-comment-tools.php`

Comment and front-end interaction:

- Removes Kratos comment notification hooks.
- Forces formatted paste in front-end wpDiscuz main-comment and reply fields to plain text.
- Removes the unapprove and spam actions from editable comment rows and the comments bulk-action menus.
- Confirms before moving an individual comment to Trash from the Dashboard or comments screen.
- Hides email/IP display for non-admins in the comments admin screen using CSS.

Privacy note: the email/IP hiding is cosmetic CSS. It hides values visually but does not remove them from the underlying admin page data.

### `custom/module-blocklist.php`

User-specific blocklist for tags and authors:

- Adds an admin menu page named "屏蔽设置".
- Stores each user's tag rules in user meta key `dn_blocked_tags`.
- Stores each user's blocked authors in user meta key `dn_blocked_authors` as author user IDs only.
- Allows up to 50 blocked authors.
- Shows blocked author nicknames in the admin page. If an account was deleted, it shows a muted "原作者账号已删除" placeholder and still allows removal.
- Protects author ID `48008` from being blocked.
- Allows up to 10 tag rules, 50 characters each.
- Validates that each tag rule can compile as a JavaScript regex.
- Injects the current user's tag rules and blocked author IDs into the front end.
- On each front-end page, scans `.article-panel` items, their `.tags a` labels, and their `data-dn-author-id` attribute.
- Hides matching articles on the current page only.
- Adds one temporary "show hidden articles" toggle near pagination.
- Adds a single-post "屏蔽作者" link next to the author name for logged-in users.

Performance note: no database-wide filtering is performed. Work is limited to logged-in users and the articles already rendered on the current page. This is friendly to the web and database servers.

Template dependency:

- Article cards must keep `.article-panel`.
- Article cards must include `data-dn-author-id` for author blocking.
- Tag links must remain under `.tags a`.
- Pagination notice expects `.paginations` when present.

### `custom/module-media-library.php`

Media library defaults:

- Redirects the Media Library to "mine" by default.
- Sets the Add Media modal to current-user attachments before the first AJAX request.

Performance note: this is intentionally server-friendly for large media libraries.

### `custom/module-bookmark.php`

User article bookmarks:

- Injects a bookmark button on logged-in single-post pages.
- Depends on the single-post toolbar selector `.share.float-md-right.text-center`.
- Stores bookmarks in user meta key `dn_bookmarks` as post ID => timestamp.
- Handles add/remove through AJAX action `dn_toggle_bookmark`.
- Validates new bookmark targets with `current_user_can('read_post', $post_id)`.
- Adds a "我的收藏" admin menu page.
- Shows title, author, publish time, bookmark time, post status, and remove action.
- Keeps showing existing post information for drafts/private/trash/future posts if the post still exists.
- Shows a deleted placeholder only when the post no longer exists.

Performance note: the admin list fetches all bookmarked post IDs for the current user, then sorts and paginates in PHP. This is fine for normal bookmark counts. If users may bookmark hundreds or thousands of posts, refactor to query only the current page.

### `custom/module-default-avatars.php`

Preset avatar selector and avatar upload limit:

- Defines a fixed list of preset avatar media IDs, CDN URLs, and display names.
- Adds the selector to user profile pages.
- Uses the existing avatar plugin's hidden `wp-user-avatar` input to save the selected media ID.
- Updates profile preview images immediately on selection.
- Limits avatar uploads from profile/user-edit pages to 100 KB.

Performance note: the preset selector is only rendered on profile pages. The page loads many small CDN images, so keep the preset list reasonably sized.

Maintenance dependency:

- Relies on the current avatar plugin's DOM and field names, especially `wp-user-avatar` and `.wp-user-avatar-container`.
- The 100 KB upload limit is based on the request referer, so it is practical but not a strong security boundary.

### `custom/custom.css`

Currently empty. It is still enqueued on the front end.

Use this for small global theme styling only. Avoid adding large CSS frameworks or heavy unused styles here.

### `custom/custom.js`

Currently only contains a placeholder. It is still enqueued on the front end.

Use this only for small front-end behavior that truly belongs on every page. Prefer feature-specific inline scripts or conditional enqueueing for heavier behavior.

## Template Touch Points

The following non-custom files depend on or support custom behavior:

- `pages/page-content.php`
  - Provides `.article-panel`, `data-dn-author-id`, and `.tags a` used by the blocklist.
  - Calls `dn_is_show_post_stats()` before displaying heat/likes.
  - Escapes author output.

- `pages/page-toolbar.php`
  - Provides `.share.float-md-right.text-center`, where the bookmark button is injected.
  - Escapes author avatar URL, author URL, author name, and author description.

- `single.php`
  - Adds the logged-in-only author block button next to the author name.
  - Calls `dn_is_show_post_stats()` before displaying heat/likes.
  - Outputs the author archive link.

## Current Watch List

- `custom/module-auth-security.php`: proof input handling can be hardened later with `wp_unslash()`, `sanitize_text_field()`, `is_string()`, and `mb_strtolower()`.
- `custom/module-search-protection.php`: `force_strict_posts_limit_for_bots()` is server-friendly, but broad. Review if a future feature needs non-admin queries over 50 posts.
- `custom/module-comment-tools.php`: comment email/IP hiding is visual only.
- `custom/module-reply-to-me.php`: comment SQL should be profiled if comment volume grows.
- `custom/module-bookmark.php`: bookmark admin list should be paged at query level if bookmark counts become large.
- `custom/module-default-avatars.php`: avatar upload limit detection can be made more reliable if needed.
