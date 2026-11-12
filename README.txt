GENOSYS — header, banner, image cards and WooCommerce products theme, version 1.5.86
Built on the user's selected 1.4.4 baseline.

INSTALL OR UPDATE
Upload genosys.zip in Appearance > Themes > Add New > Upload Theme.
When updating, select Replace current with uploaded. The theme folder remains genosys.
Activate if needed. Set Settings > General > Site Language to Persian.

LOGIN PAGE WITHOUT SITE FOOTER (1.5.86)
The standalone login page omits the shared visual footer. The wp_footer hook and
closing document tags remain in place, preserving footer-enqueued scripts.
Other pages retain their footer. Editor reset bundles exactly match this ZIP.

INDEPENDENT CLUB MEMBERSHIPS AND DELETION (1.5.85)
Customer and clinic/beauty-therapist memberships are independent. An authenticated
account and verified phone may register once for each type. A prior or canceled
membership locks only its own type; the other type stays available. Existing
1.5.84 records/claims retain their actual membership type. Each locked type shows
status instead of a form. After both types are registered there are no forms.
Names remain editable and prefilled; the verified account phone stays readonly
and server-enforced. Idempotent retries, phone-change checks and transaction locks
remain in place. Administrators can permanently delete a selected membership
after explicit confirmation. Its record, notes, metadata and associated claims
are removed atomically, reopening that type for registration if no other matching
record remains. Cancellation keeps the type locked. The other membership,
customer account, orders and explicit notification preferences remain intact.
Active membership flags, administration counts and exports reflect the result.
CSS/PHP editor resets exactly match this installed ZIP.

AUTHENTICATED ONE-TIME CLUB ENROLLMENT (1.5.84)
Fans-club requires login and returns to enrollment after SMS sign-in or profile
completion. Both audience forms display the current account verified phone as
readonly; the server uses that phone exclusively, with no billing-phone fallback.
Profile first and last name prefill the editable survey name. Existing submissions
show membership status instead of either form. Historical phone and account
submissions, including canceled and legacy records, cannot be overwritten or
reactivated by enrollment. Durable claims and shared phone locks prevent repeated
and concurrent enrollment. Identical request retries do not modify prior records.
Changing the account phone invalidates stale enrollment requests. Public responses
do not expose or claim another account or guest record. CSS/PHP editor resets
exactly match the source shipped in this ZIP.

VERIFIED ACCOUNT PHONE CHANGES (1.5.83)
The existing account dialog now uses the configured SMS.ir six-digit verification
template, API key and parameter. A purpose/user/session-bound, expiring one-use
challenge verifies the new number before any credential change. Resends, attempts
and user/phone/IP limits are enforced on the server. Taken numbers cannot be
claimed. The same WordPress user ID, username, profile, membership, preferences,
orders and historical billing/receipt records remain intact. The current session
stays signed in; other sessions are revoked after a successful verified change.
Future checkout contact follows the new number only if blank or matching the old
verified number. Existing separate contact data is preserved. Old verified-phone
guest history is retained through exact account-bound order/key proofs, without
reassigning orders or granting access to future orders using a retired number.
Login and registration share phone locks; legacy aliases cannot reactivate a
changed credential. The UI honors server deadlines and preserves unsaved profile
changes. CSS/PHP editor resets exactly match the source shipped in this ZIP.

GENOSYS CENTER SYMBOL (1.5.82)
The five bundled logo variants now use the circular cross and striped arms from
the supplied official logo reference. Only the central symbol was redrawn; all
existing lettering, colors, dimensions and positioning remain unchanged. The
transparent center and arm slots work on light and dark backgrounds. This covers
desktop/mobile navigation, footer, login and admin previews. Bundled SVG URLs use
a fresh version query; uploaded custom logos remain unchanged. CSS/PHP editor
reset copies exactly match the files shipped in this ZIP.

ACCOUNT NOTIFICATION PREFERENCES (1.5.81)
The account notifications tab now matches the four-switch reference. Each choice
autosaves to the signed-in customer only, with nonce checks, strict validation,
independent per-key writes, accessible switches and failure rollback. Order-status
and receipt-review SMS honor choices when queued and again before sending; opted-out
queued messages are suppressed permanently. Club promotional SMS respect account
opt-outs without changing membership. Existing owned club consent remains honored.
Newsletter is opt-in only, requires a valid account email and sends a monthly digest
of public posts/catalog products through WordPress mail in bounded batches, with
duplicate protection and safe unsubscribe. Calendar scheduling uses WordPress time.
No preference change itself sends SMS/email. Login OTP and order-confirmation SMS
remain unchanged. CSS/PHP editor resets match this installed ZIP exactly.

RELATED PRODUCT HOVER IMAGE COVERAGE (1.5.80)
Strengthened the shared product image selector so WooCommerce height:auto cannot
override the cards' full-height cover images. Related-product photos now fill
the complete card before and during hover, eliminating the exposed pale strip.
Existing hover zoom, gradients, links and placeholder containment are preserved.
All JavaScript and business logic are unchanged. Cache versions and exact
installed-ZIP CSS/PHP editor reset bundles were refreshed.

MODERN SHOPPING BAG ARTWORK (1.5.79)
Replaced the woven basket with a matte crimson Genosys shopping bag, dimensional
folds, fine cord handles and soft highlights. Only decorative SVG artwork, its
shadow and fold anchors changed. Cart handling, requests, product trajectory,
animation stages/timing, reduced-motion behavior, navbar and sidebar are unchanged.
Asset cache versions and exact installed-ZIP editor reset bundles were refreshed.

COMPANY PAGE THEME ALIGNMENT (1.5.78)
Removed the company video, poster, player requests and navbar overlay. /company/
now starts below the standard internal navbar, with a Farsi introduction using
the homepage crimson palette, warm backgrounds, rounded history cards and controls,
crimson global-presence details and a rounded facility image with a red caption.
All translated content, history interactions and the omitted address section are
preserved. Brand and homepage videos are unchanged. Editor resets match this ZIP.

FARSI COMPANY PAGE (1.5.77)
The /company/ route follows the supplied Genosys company page: original background
film, translated introduction, 14-year history carousel with 25 events, global
export map and 58 listed destinations, and the original production-facility image.
The dedicated location/address/map/contact-details block is intentionally omitted.
All page copy and the image caption are in Farsi; original product and certification
marks in photographs are preserved. History supports arrows, keyboard, mouse drag,
and native touch scrolling. Assets are local, except the original Vimeo film,
which falls back to its local poster when unavailable. CSS/PHP resets match this ZIP.

SELF-HOSTED BRAND VIDEO (1.5.76)
Theme Settings > Brand now accepts a video uploaded through the WordPress media
library. Choose an MP4 (H.264 recommended), WebM or OGV file and save settings.
The brand hero uses that attachment directly with muted, looping inline autoplay
and no visible controls. Removing the selection restores the original Vimeo
background; the media file remains in the library. Invalid selections preserve
the previous setting. The CSS/PHP reset bundles match this installed ZIP.

BRAND FILM WITHOUT TEXT OVERLAY (1.5.75)
Removed the visible Persian headline, tagline and introduction button over the
background brand film. The page keeps a visually hidden heading for accessible
navigation. Video playback, navbar, palette and all lower sections are unchanged.
The CSS/PHP original-source bundles match installed version 1.5.75.

BRAND BACKGROUND FILM AND RED PALETTE (1.5.74)
The main brand film now opens behind the same transparent banner navbar as the
homepage. It requests automatic silent looping playback without player controls,
keyboard interaction, fullscreen, picture-in-picture, or a clickable surface.
The two supporting treatment clips retain their separate viewing controls.
The hero stays independent of those clips, with a local poster if Vimeo or the
browser cannot start background playback. Browser autoplay restrictions still
apply. The rest of the brand page now uses the theme's accent/deep red tokens,
blush surfaces, pill links and rounded cards; saved palette and banner navbar
preferences apply. Original photo, diagrams and Persian copy are retained.
The CSS/PHP reset bundles match the exact installed version 1.5.74 sources.

EXTENDED BRAND STORY (1.5.73)
/brand/ now includes the remaining substantive sections from the official
http://genosys.info/en/page/11 brand introduction, with concise Persian copy:
the main brand film, complementary internal/external treatment explanations
and videos, histology, translated comparison diagrams and the closing statement.
The original user-supplied banner and banner-first order remain in place.
Source media previews and histology are bundled locally. The three original
Vimeo videos load only after the visitor clicks Play, with direct viewing links
and recoverable loading failures. Playback depends on the external provider.
Comparison graphics reproduce the brand's presentation; ambiguous numerical
labels are omitted instead of inferred. They do not assert new study results.
The source link is available below the closing statement. No source-site
scripts, trackers, navigation, or footer are imported into this theme.
The CSS and PHP original-source bundles now match installed version 1.5.73.

BRAND PAGE (1.5.72)
/brand/ now opens the Persian brand introduction directly, without requiring a
saved WordPress Page. The supplied original banner is placed below the navbar,
with overlapping treatment circles and translated text. A centered brand story
follows on a white background. The mobile layout keeps the photograph visible
above the translated treatment diagram and text. The chevron opens the story.
The primary brand navigation link points to /brand/.
Both installed-ZIP original-source bundles are regenerated from exact version
1.5.72 bytes, including the new brand PHP and CSS files. The CSS and PHP editor
tabs remain last in Theme Settings.

CONTACT PAGE (1.5.71)
/contact/ follows the supplied Persian design, with contact cards, copy actions,
message topics, a responsive contact form, office details, weekly opening hours
and expandable FAQs. Theme Settings > تماس با ما controls the page headings,
labels, contact details and links, office address/postcode, social links, form
text, privacy link, all seven days of opening hours and up to 30 FAQ pairs.
Add or remove FAQ rows there; removing all rows hides the FAQ section.
Enter the business's real contact information before sharing the page; blank
contact details do not create a working dial, chat, mail or copy action.
The open/closed badge uses the WordPress timezone and configured weekly hours.

Contact submissions are validated and stored in a private local inbox under
Theme Settings > پیام‌های تماس. Viewing messages and downloading attachments
require the manage_options capability. Optional JPG, PNG, WebP or PDF attachments
are checked by actual file size/type and stored privately in the database,
never as public Media Library uploads. The limit is 5 MB per attachment.
The form requires a name, mobile number, topic, message and privacy consent;
email and attachment are optional. Message length is limited to 500 characters.
Nonce, honeypot and rate checks protect the public submission endpoint.
Email notifications are disabled until explicitly enabled with a recipient in
the contact settings. Saving settings or opening the page sends no email.

The CSS and PHP editor tabs remain the last two tabs in Theme Settings.
Both installed-ZIP original-source bundles are regenerated from the exact
version 1.5.71 source bytes, including the new contact PHP and CSS files.

CATEGORY RAIL DESKTOP RESUME (1.5.70)
Automatic category motion resumes after pointer release while the cursor stays
over the row. Keyboard focus continues to pause the rail for accessible use.
Installed-ZIP CSS and PHP original-source bundles are rebuilt for version 1.5.70.

CATEGORY RAIL MOTION (1.5.69)
The homepage category rail moves at a slower, continuous pace and hands off
smoothly to manual scrolling and dragging. Motion pauses during interaction,
when the row is offscreen or the page is hidden, and respects reduced motion.
Installed-ZIP CSS and PHP original-source bundles are rebuilt for version 1.5.69.

PHP SOURCE EDITOR (1.5.68)
Theme Settings > ویرایشگر php lists the real PHP files in this active theme,
with search, separate drafts, syntax highlighting, line-linked diagnostics,
history, code/preview tabs, resizing, fullscreen, copy and download. Reset loads
the selected file's exact original bytes from the currently installed ZIP into
a draft. Neither typing, validation, reset nor history selection saves code.
The preview is explicitly the SAVED website; unsaved PHP is never evaluated.

Save requires administrator/theme-editing permission, enabled WordPress file
editing, writable files, a current source hash and successful server checks.
The server parses the candidate using its own PHP parser, checks changed calls
and literal parameters, and checks declarations and existing dependencies.
Unknown changed executable structures are rejected rather than marked safe.
The conservative write policy accepts HTML/comments and supported presentation
calls with literal string arguments. New logic, dynamic expressions, unsupported
function calls and signature changes need separate development/testing and are
blocked here. This is a protected source editor, not a universal PHP debugger.
The editor's own PHP engine/view files remain visible but read-only so editing
cannot remove the validation/recovery mechanism.

Before writing, signed loopback probes must reach the real admin and homepage.
WordPress then performs its native guarded PHP edit and fatal-error checks;
an additional theme-settings render must pass before the save is confirmed.
Failed or unavailable probes reject the change and recover the previous bytes.
Private bounded history, conflict checks and shutdown recovery support this.
No analyzer or finite request check can guarantee every future PHP data path:
successful execution covers the admin editor, homepage and theme settings.
Conditional pages, plugin-specific behavior and future input still need testing.
The interface states this scope and blocks unsupported changes. A host that
cannot complete these loopback checks cannot save PHP through this editor.
Both original-source bundles are regenerated for version 1.5.68.

CSS FILE PANEL AND FULL-WIDTH PREVIEW (1.5.67)
Drag the left edge of the files panel to widen or narrow the list on desktop.
Its arrow keys also adjust width. Both panel widths stay within the available
workspace so the editor remains usable. The stacked mobile layout is retained.

The preview header now includes a full-width icon matching the editor's icon.
It expands the real website preview across the window and fits the website to
the available space. Click it again or press Escape, including from inside the
website preview, to restore the previous panel layout and viewport dimensions.
Unsaved drafts stay in memory through expansion and are never saved by resizing.
The installed-ZIP CSS baseline has been regenerated for this version.

CSS PREVIEW RESIZING AND ZIP RESET (1.5.66)
Drag the preview panel's right edge on desktop to change its visible width.
The larger corner grip changes the website viewport and visibly grows or
shrinks the preview, keeping a stable scale while dragging. Exact width/height
inputs and mobile/desktop presets remain available.

Reset now restores the SELECTED file's pristine CSS from the currently installed
theme ZIP, rather than re-reading the edited live file. An immutable baseline
ships with each theme version and survives saved edits, empty CSS and history
eviction. Reset loads that original code into a draft and the live preview;
Save is still required to apply it to the public site. Other file drafts remain.
Reset followed by Save preserves the original ZIP bytes, including line endings.

REAL CSS EDITOR (1.5.65)
Theme Settings > ویرایشگر CSS lists the active theme's actual CSS files and
loads their current source into the WordPress syntax editor. Each file keeps
its own unsaved draft when switching files. Only the selected file is written
when Save or Ctrl/Cmd+S is used; previewing, typing and loading a historical
version never save automatically. Reset behavior is updated in 1.5.66 above.

The private live preview loads the real website and applies drafts at each
stylesheet's original place in the cascade, including relative image/font URLs.
Choose a page, set an exact viewport size, use mobile/desktop presets or drag
the resize handle to check real responsive breakpoints. Frontend files appear
on pages where they normally load; files marked admin are editable but are not
injected into the website preview. Preview form submissions and cart/account
actions are disabled. Normal website actions remain unchanged.

Saves require administrator theme-editing permission, enabled WordPress file
editing and writable files/directories. Only CSS inside this active theme can
be edited. Complete writes, source-version conflict checks and five private
snapshots per file protect existing work. Loading a snapshot creates a draft;
Save is still required to restore it. CSS URLs receive a content hash so a
confirmed save is served as a new stylesheet version. Keep source edits in a
backup before installing later theme updates, which replace theme files.

THEME SETTINGS UPDATE (1.5.64)
Navbar settings now control desktop links, active/hover/focus colors, icons and
backgrounds, with independent colors for the fullscreen mobile menu. A homepage
without a leading slider uses the internal header palette. Explicitly saved
colors apply even when equal to a panel default. The preview uses the same
separate link/icon palettes; blank optional backgrounds restore the native look.
Existing options are retained and no defaults are written just by opening pages.

Added clinic introduction/form text, default audience and audience-pill labels;
account heading/navigation labels; latest-products view-all text; related-product
count and a basket-animation switch. Disabling motion preserves the confirmed
cart request, cart counts and success notice. Package management and club members
are accessible from their relevant settings tabs. Club SMS uses its existing
sender option and respects wp-config.php overrides; saving settings sends no SMS.
The homepage product-card destination now honors its saved setting, defaulting
to /shop/. Existing shipping, payment, package stock and account logic remain.

MOBILE BASKET DROP (1.5.63)
The basket-drop effect also plays when the active product image has scrolled
above the purchase form, as normally happens on mobile. A small decorative copy
of that image starts inside the viewport and drops into the temporary basket.
The original image, page position, normal navbar and hidden mobile header cart
stay unchanged. With no visible cart target, the temporary basket fades away.
Reduced motion, open menus, hidden images and rejected additions still skip
motion. Scroll/resize interruption and the confirmed-purchase guards remain.

PRODUCT CART ANIMATION AND MOBILE HEADER (1.5.62)
The product-page header now follows normal page flow instead of becoming sticky.
The purchase animation no longer overrides header layout, blur, positioning or
mobile cart visibility. The desktop basket stays hidden at the mobile breakpoint;
the existing mobile menu keeps its normal full-screen layout and cart card.
Confirmed additions update counts and native WooCommerce fragments. The visual
basket flies only to an existing visible cart; otherwise it fades centrally or
uses a confirmation notice. Reduced motion uses the notice. The offscreen-image
skip introduced here is corrected in 1.5.63 as described above.
Scrolling, resizing, backgrounding or opening the mobile menu stops decorative
motion without moving the page or changing its scroll lock. A decorative or
fragment-handler error cannot turn a confirmed purchase into a failure message.
Repeated submissions are guarded while the request is pending, without changing
WooCommerce's current variation availability or native disabled state.
Cart validation, prices, product stock and the server add-to-cart logic are unchanged.

CUSTOMER CLUB MEMBERS (1.5.61)
WordPress sidebar > باشگاه مشتریان lists completed /fans-club/ enrollments.
The supplied Persian list and left details drawer include consumer/professional
type badges, real active/cancelled counts, new-member/rating/NPS metrics, search,
filters, eight-row pagination, private notes, cancel/restore and filtered CSV.
Professional records show clinic/services/client/consumption/experience answers;
age and satisfaction scores are shown only where the consumer actually answered.
New submissions are now saved privately in WordPress; preview-only completions
from older versions cannot be recovered. No sample members are installed.
Authenticated submissions belong only to the current account. Guest enrollment
does not create a login or link an account by a claimed phone number; a private
browser token permits retry/update of that browser's own unlinked registration.
Purchase history uses only the explicitly linked WooCommerce customer account.
An unlinked guest displays حساب متصل نیست instead of an invented purchase count.
Enrollment does not change verified phone credentials, roles, orders or stock.
All management/export actions require WooCommerce-manager or administrator access.
CSV keeps phone zeros and neutralizes spreadsheet formula prefixes.

SMS buttons open a recipient/text review before an explicit final send. Only
active opted-in numbers are included, deduplicated, with at most 100 recipients
per campaign (narrow filters for larger groups). Existing SMS.ir API credentials
are read on the server; a site administrator can set the sending line inside the
composer. GENOSYS_CLUB_SMS_SENDER optionally overrides this line. Without both
settings, sending is disabled with a setup hint. Expiring actor-owned recipient
snapshots and durable attempt claims prevent automatic duplicate sends after an
ambiguous timeout. Provider acceptance is distinguished from confirmed delivery.
No SMS is sent automatically on enrollment, list viewing, notes or cancellation.

CLUB PAGE NAVIGATION (1.5.60)
/fans-club/ has a top بازگشت link to the validated referring site page, with
the homepage as its fallback. The visible footer is omitted on this page only;
WordPress footer hooks still load the wizard and other required scripts.

CONSUMER AND PROFESSIONAL CLUB FLOWS (1.5.59)
/fans-club/ opens with the clinic/beauty therapist audience selected. The pill
selector switches between مصرف کننده and کلینیک ها و بیوتی تراپیست ها.
The consumer's existing questions remain available. The professional flow has
three steps: clinic/professional name and contact details (including optional
clinic landline), services and work experience, then client needs/count and
monthly product consumption. It does not ask the professional's age.
Hero and benefit copy follow the selected audience. Each questionnaire retains
its own answers and current step when switching, with keyboard-accessible tabs
and scoped validation. The existing page styling is retained and adapted for
the selector on mobile. Originally browser-only in 1.5.59; persistence is added
in 1.5.61 as described above. Existing order/account authentication and inventory
logic remain unchanged.

CUSTOMER ACCOUNT DASHBOARD (1.5.58)
The signed-in /my-account/ root matches the supplied Persian account templates:
profile and membership cards, sidebar, editable personal information and phone
card. Names/email, optional Jalali birth date, owned profile image, account age,
authorized order counts, billing/shipping addresses and customer-visible order
notes come from WordPress and WooCommerce. Profile updates are current-user-only
and nonce-protected. Avatar uploads accept validated JPEG/PNG/WebP up to 5 MB;
removal unlinks the profile image without deleting a shared media file.
Orders links to /my-account/orders/ and retains its existing access/receipt rules.
Address edit links render WooCommerce's native billing/shipping editor inside
the dashboard shell; its existing validation, nonces and save handler are used.
Other WooCommerce endpoints and logged-out sign-in retain their existing views.
Phone-number entry and the six-digit verification dialog are UI previews only:
no SMS is sent, no phone changes, and no verification is claimed. The existing
login and order-lookup SMS flows are unchanged. Club status reads only explicit
genosys_club_member and optional Unix timestamp genosys_club_joined_at metadata;
account age alone does not grant membership. New/returning labels use real age.
Saved birthday uses genosys_birth_date (Jalali YYYY/MM/DD); profile image uses
genosys_avatar_id. Account pages are private and marked to bypass public caches.

WOOCOMMERCE PACKAGE LIST (1.5.57)
WooCommerce > لیست پکیج ها appears directly below افزودن پکیج with the supplied
Persian table layout. Saved WooCommerce packages provide real status counts,
name/SKU/ID/component search, sorting and six-row pagination. Expand a package
to see its current included products, quantities, prices and shared stock.
Edit and preview links use the existing package editor and native product page.
Protected actions create a draft copy, move packages to the trash, restore them,
or move selected rows to the trash. These actions do not change component stock.
Package publication, receipt behavior and storefront design are retained.

CANCELLED ORDER RECEIPT UPLOAD GUARD (1.5.56)
Cancelled orders are no longer eligible for customer receipt upload/replacement.
The shared rule hides the account-orders picker and rejects stale upload requests.
Receipt records, admin controls, styling and all other order statuses are unchanged.

PUBLISHABLE PACKAGES AND SHARED COMPONENT INVENTORY (1.5.55)
WooCommerce > افزودن پکیج now selects its image through the native WordPress
media library and saves/publishes actual WooCommerce package products. Reopen
a package from the Products list to edit its contents, quantities, image and
price. Drafts can remain incomplete; publishing requires at least two distinct
eligible products, a name, image and price. Server-side capabilities, nonces,
SKU uniqueness, product/image validation and retry protection are enforced.
Packages are assigned only to پکیج‌های ویژه (the existing category is reused,
or created if absent). They appear in that category's homepage tab or selected
shop filter, using the same product-card template as ordinary products. The
general shop, unrelated categories, latest-products row and unfiltered search
exclude packages. The special-category archive opens the filtered shop grid.
Package stock derives from its included products: for A=10, B=5, one unit each,
the package has five available units. Buying a package consumes each included
quantity, and standalone purchases immediately reduce the derived package
availability. Any unavailable/zero-stock component makes the package unavailable.
Shared variation-parent inventory is counted as one pool. Unmanaged in-stock
components do not invent a quantity limit; packages never allow backorders.
Cart checks combine standalone items and all packages sharing a stock pool.
Native stock holds reserve the actual component quantities. Order-item snapshots
preserve the purchased composition and stock owners after later package edits.
Package/mixed-order deductions, restoration and refund ledgers commit atomically;
repeated status callbacks and refund retries do not consume or restore twice.
The card-to-card gateway confirms inventory before returning checkout success.
Ordinary orders retain native WooCommerce stock behavior. Package images and
products use native media and WooCommerce records; no new inventory tables are
introduced. Storefront card styling and existing account, receipt and SMS flows
are retained.

PACKAGE BUILDER PREVIEW (1.5.54)
WooCommerce > افزودن پکیج opens the supplied Persian package-builder layout:
name/description, searchable product rows, quantities, live stock and price
totals, pricing/SKU, categories, local image picker and live product-card preview.
The protected selectors read published simple products and selectable variations
from WooCommerce by name or SKU, with real prices and physical stock quantities.
Shared variation stock is counted once across quantities; unmanaged stock and
missing prices remain explicitly unknown. Product categories use the current
WooCommerce category list. Administrators and shop managers can use this page.
The form is temporary: publishing and draft buttons only validate/display a
preview notice. Nothing is saved or published, images stay in the browser, and
no products, attachments, categories, options or inventory are changed.
This screen's CSS and JavaScript load only on the new package-builder page.

HOMEPAGE SHOP LINKS (1.5.53)
The five requested homepage links now point directly to /shop/: the primary
Products menu item, homepage products-card title/button, latest-products View All
card, category showcase button and featured section's All Categories link.
The primary-menu link also follows this destination when an existing saved
WordPress menu still points to the old products page. Visuals, category tabs,
category-specific View All links, customer-club links and commerce are unchanged.

FEATURED PRODUCTS MOBILE RAIL AND EDITABLE CATEGORIES (1.5.52)
The homepage's featured-category product cards now use one horizontal, touch-
scrollable row up to 820px, with one full card per snap and the same mouse-drag
settling as the latest-products row. The existing desktop three-column grid
and shared product-card design are unchanged.
In Theme Settings > Featured Products (محصولات منتخب), edit the tab title and
WooCommerce category, add up to 12 categories, or remove tabs. Category/product
records in WooCommerce are never deleted by these controls. Removing all tabs
hides this section until a category is added again. An empty title uses the
selected category's name. Existing automatic category mappings are preserved
when rows are removed, and unrelated/partial saves retain the selected list.

PAYMENT RECEIPTS DASHBOARD PRESENTATION (1.5.51)
The existing payment-receipts admin page now follows the supplied Persian
dashboard reference: branded header, soft skincare decoration, real receipt
status totals, rounded white table panels, separate payment/amount columns,
image/PDF previews and clearly labeled view, download and delete actions.
The native admin navigation, exact order-ID search, 20-row pagination, review
selector/save button, current-receipt restrictions and all protected POST/file
handlers retain their existing behavior. Count tiles are read-only and follow
the listing's existing order filter. No manual receipt creation, new filters,
bulk actions, changes to order/receipt state or storefront restyling are added.
Dashboard CSS is loaded only on the payment-receipts admin screen. Review,
upload, deletion, file protection, customer account status and SMS logic remain
unchanged. The receipt table can be scrolled horizontally on narrow screens.

PRODUCT SEARCH BY NAME AND SKU (1.5.50)
The existing navbar search searches WooCommerce products by name or partial SKU.
Variation SKUs return their parent product once. Results use the existing shop
layout and shared product cards, with working filters, pagination and a clear
empty state. Search text is preserved through filtering and pagination. Only
published products visible in search are returned; WooCommerce stock visibility
continues to apply. No third-party search service or catalogue migration is needed.

SHOP CARD HOVER AND SIDE SPACING (1.5.49)
Shop product images now retain full card height under WooCommerce's image rules,
including when the hover overlay disappears. The shop page has wider desktop
side padding and consistent mobile gutters. Shared homepage card styles, shop
filters, pagination and all other website behavior are unchanged.

SECTION-BASED THEME SETTINGS (1.5.48)
The existing تنظیمات قالب sidebar page now follows the supplied Persian panel
design: fourteen section tabs, rounded white cards, crimson controls, WordPress
media pickers, live navbar preview and a persistent save bar with unsaved state.
Edit identity, logos/favicon, navbar presentation, slider images/titles/links,
homepage section order/visibility, promotional cards, category images and their
WooCommerce destinations, statistics, featured-category tabs, headings, footer
content/social links, shop page size, product tab labels, My Orders title and
customer-club copy. Products, prices, stock, categories and navigation menus
remain managed by their existing native WordPress/WooCommerce screens.
Existing payment, shipping, card details and all five SMS template IDs are
grouped in the appropriate tabs and retain their original option names. A blank
API field preserves the saved key; a wp-config.php key cannot be overwritten.
OTP digits/time and the SMS provider retain the current service behavior.
The separate SMS login page remains available for administrator phone enrollment.
Opening the panel or saving its unchanged values preserves the storefront's
existing colors, images, typography, section order, native logo and content.
Only deliberately edited presentation settings affect the public site.
Saves require administrator capability and a valid nonce; values are validated
and output is escaped. Invalid fields retain their prior values. No migration,
demo store data, new order/receipt logic or third-party panel dependencies are
installed by this release. The panel is responsive and keyboard accessible.

GUEST ORDER HISTORY (1.5.47)
My Orders merges the signed-in customer's own orders with guest orders placed
using their verified Genosys phone. Guest order ownership is not reassigned.
Historical phone formats are normalized through WooCommerce CRUD (CPT/HPOS).
The guest checkout browser can revisit its orders without signing in. On another
browser, guests verify the checkout phone with the existing SMS OTP provider;
this creates no WordPress account and does not log anyone in. Phone proof expires
after 12 hours; private browser order grants expire after 30 days. Guests can end
their viewing session from the orders page. Receipts use the same access rules.
Other customers' registered orders are never exposed through phone matching.
This update changes only order access and its verification interface. Existing
order cards, checkout, receipt review/status logic and SMS templates are retained.

SHOP CATALOG (1.5.46)
The configured WooCommerce Shop page now uses the reference RTL layout, a
three-column desktop grid and the unchanged shared homepage product-card
template/styles/favourites. Categories, product details and images come from
the database; no demo catalog data is included in the theme.
Category checkboxes (including descendants), price range, available-stock and
sale filters combine with native WooCommerce sorting and permalink pagination.
The main WooCommerce query shows 12 products per page, newest first, and keeps
catalog visibility, product types, native price/tax handling and extension hooks.
Desktop filters apply on change. Mobile filters use a keyboard-accessible drawer
and Apply button. GET forms work without JavaScript; changes start at page one
and pagination retains selections. Prices and category counts use the store data
and currency. The shop handles empty results and provides a clear-filters link.
Only the Shop page is changed. Homepage cards, other archives, header/footer,
checkout, receipts and SMS logic are unchanged.

RECEIPT APPROVAL AND REJECTION SMS (1.5.45)
Under تنظیمات قالب enter the two separate template IDs:
شناسه قالب پیامک تایید فیش and شناسه قالب پیامک رد فیش.
Both provider templates must contain exactly NAME (billing first and last name)
and reuse the existing SMS API key. A blank field disables only that notification.
An actual saved admin receipt decision queues its corresponding background SMS.
Pending, unchanged reviews, stale forms, failed saves and receipt deletion do not
send either message. Per-decision snapshots and durable attempt claims prevent
duplicate sends while allowing review cycles and replacement receipts. SMS
errors cannot fail a committed review. WooCommerce order/payment/fulfillment
statuses, totals, other SMS templates and customer-page designs are unchanged.

SHAMSI DATES AND ORDER STATUS SMS (1.5.44)
The submission SMS DATE is now Shamsi (YYYY/MM/DD HH:MM), with Persian digits
and the WordPress site's timezone. It uses the saved order creation time.
Enter شناسه قالب پیامک تغییر وضعیت سفارش under تنظیمات قالب for the new
status notification template. Its exact parameters are NAME and STATUS; STATUS
uses WooCommerce's displayed name for the new order status. It reuses the
existing SMS API key. A blank status-template field disables status SMS only.
Actual saved status changes enqueue an independent message. Initial checkout
gateway setup, unchanged saves, receipt-review metadata and page refreshes do
not send a status message. Each transition is claimed once before sending;
provider failure cannot interrupt order updates or change payment/order data.

ORDER SUBMISSION SMS (1.5.43)
Enter the new شناسه قالب پیامک ثبت سفارش field under تنظیمات قالب.
It uses the existing SMS login API key (including GENOSYS_SMSIR_API_KEY when
defined). No second key is needed. Leave the new template field blank to disable
order-submission SMS; the login template and login behaviour are unchanged.
The sms.ir template must contain these exact parameter names without the # marks:
NAME: the order's billing first and last name.
ORDERID: the visible WooCommerce order number.
DATE: the original order date/time, Shamsi YYYY/MM/DD HH:MM in the store timezone,
with Persian digits (updated in 1.5.44).
Validated classic and Block checkout submissions enqueue a background message
to the saved billing mobile. Confirmation-page visits and payment retries do
not resend an attempted order message. A durable claim prevents duplicate sends
after ambiguous provider timeouts; errors are logged without credentials or
customer/provider payloads and never fail checkout. Messages use WooCommerce's
Action Scheduler, with WordPress cron as the fallback. Only SMS metadata is saved;
order/payment statuses, totals, receipts and other page designs are unchanged.

MY ORDERS UPLOAD NOTIFICATION (1.5.42)
A successful receipt upload on My Orders shows the one-time notification:
رسید با موفقیت بارگذاری شد
The compact notification has a success check, a dismiss button, a soft entrance
transition, mobile sizing and reduced-motion support. It appears after the
existing order refresh; if session storage is unavailable it appears briefly
before that refresh. Failed uploads never display this success notification.
Receipt review, uploader persistence, payment logic and other pages are unchanged.

RECEIPT DELETION, RETRY AND ORDER TIME (1.5.41)
Each current receipt in the dashboard has a protected trash control for
administrators and store managers. Deletion removes the private receipt and
its order link; customers see card details and an available uploader again.
Rejected receipts show the previous file's rejection and allow a replacement.
Only pending and approved receipts lock customer changes; upload permission
depends on receipt review, independently of the WooCommerce order status.
New/replacement receipts always start pending. Native review/removal endpoints
validate permissions, nonces, current receipt links and per-order locks.
Customer order timestamps now emit the real UTC instant, so the store timezone
is applied once and order times match the WooCommerce administration panel.
Other layouts and WooCommerce order/payment statuses and totals are unchanged.

RECEIPT REVIEW AND PAYMENT DETAILS (1.5.40)
The receipts dashboard's order details now contain only the amount, shipping
and payment method. Product names and quantities are omitted from this table.
A separate وضعیت فیش column lets administrators and store managers save one
of three states: در حال بررسی, تایید شده or رد شده. New and replacement uploads
start as در حال بررسی; receipts saved before this feature display that default.
Customers see the saved receipt review state in their orders list and details.
Receipt review never changes the WooCommerce order status or marks it paid.
Missing eligible card-transfer receipts show the card number and holder saved
with the order. Legacy orders without a saved card pair use the configured
theme settings. Incomplete details are never replaced with sample numbers.
Receipt access, download protection, checkout and other pages are preserved.

CUSTOMER ORDERS PAGE (1.5.39)
The WooCommerce orders endpoint (/my-account/orders/) now has the supplied
Persian reference design: status filters, order cards, expandable timelines,
ordered products, delivery/contact information, receipts and amount summaries.
All orders and amounts come from the signed-in customer's WooCommerce history.
Historical product names, quantities, prices, discounts, fees, tax and currency
come from the order; deleted products retain their saved names and a placeholder.
Receipt uploads use the existing authorized receipt service and admin list.
Successful uploads reload this page with the order expanded and updated counts.
The history is paginated, empty/guest states are included, dates use the site's
timezone and the browser's Persian calendar support, and mobile layout adapts.
The six reference status filters always show real counts. Native processing is
preparation, completed is delivery, and an existing shipped status is displayed
if registered. An unpaid order with a saved receipt is shown as awaiting review.
No new WooCommerce status is registered or written by this presentation.
Other account endpoints, the header/footer, checkout, receipts administration,
payment processing and other theme pages remain unchanged.

SAVED RECEIPTS AND ADMIN PAGE (1.5.38)
Selecting a valid receipt now saves it automatically against the WooCommerce
order. The existing uploader design and envelope animation are unchanged;
success is shown only after the server confirms the upload. Reloading the
order-received page restores the saved receipt. Change/remove controls update
the same order's receipt without changing order or payment status.

WordPress admin > فیش های واریزی lists receipts newest first, with order links,
customer details, products, totals, current order status, file previews,
download links and upload dates. Administrators and WooCommerce shop managers
can view this page. The list is paginated and supports order-ID lookup.

Uploads require access to the matching order and an order-scoped nonce.
JPG, PNG, WebP and PDF are verified on the server and limited to 5 MB.
Files are stored in guarded containers under uploads/genosys-receipts,
with additional Apache/IIS access rules. Receipt previews and downloads are
served through an authorized endpoint, not public Media Library URLs.
Back up both the database and this uploads directory to preserve receipts.
Existing orders, card details, checkout, shipping and payment processing stay
unchanged. This update is provided as a ZIP and is not deployed automatically.

RECEIPT UPLOADER UI (1.5.37; persistence added in 1.5.38)
The order-received payment panel replaces the WhatsApp section with a local
receipt picker matching the supplied idle, envelope-animation and result
designs. It accepts JPG, PNG, WebP and PDF up to 5 MB, supports keyboard and
drag-and-drop, and offers change/remove controls and reduced-motion support.
Version 1.5.37 was frontend-only; version 1.5.38 adds order-linked persistence.
window.getGenosysReceipt() returns the newly selected File or null. A receipt
restored after reload is saved on the order, without manufacturing a File.
window.validateGenosysReceipt() provides required-file feedback and scrolls
to the uploader; a containing form also uses this validation on submit.
Selecting a receipt saves it without an extra submit button. Order,
payment, card-copy and WooCommerce checkout processing remain unchanged.

ORDER CONFIRMATION DESIGN (1.5.36)
The order-received confirmation, order overview, product/totals table, and
billing/shipping addresses now match the checkout's crimson and soft-white
design, with responsive layouts for smaller screens. This is a CSS-only
redesign of the default WooCommerce sections. The bank card panel and all
order, payment, copy-card and WhatsApp behavior remain unchanged.

CHECKOUT PAYMENT DISPLAY (1.5.35)
The cart summary excludes shipping cost and its tax. The chosen delivery
method and any delivery charge appear at checkout. The optional MailPoet
marketing checkbox is hidden. Checkout automatically selects the configured
card-to-card method without showing its payment panel; the saved bank card,
copy button and WhatsApp receipt instructions appear on the order-received
page after the order is submitted. Complete the card settings under تنظیمات قالب
before accepting orders through this checkout.

CHECKOUT SHIPPING AND VALIDATION (1.5.34)
On /checkout/, shipping offers Tipax first (pay its delivery charge on receipt;
no delivery charge is added to the online order) and Post second. Post uses
the saved هزینه ارسال amount in تنظیمات قالب; leave that field blank to hide
Post. These are WooCommerce shipping rates and the selected method is saved
with the order. At least one WooCommerce shipping method must be enabled for
WooCommerce to calculate shipping. The checkout country is fixed to Iran and
the province list uses Iranian provinces. Missing contact/address fields and
an invalid Iranian mobile number show inline Persian errors without moving
the checkout columns. Textareas use the same styling as the other fields.

CHECKOUT PAGE (1.5.33)
The /checkout/ page follows the supplied RTL design. It renders the live
WooCommerce cart, totals, coupon, customer contact and delivery fields, actual
available shipping methods, and the standard order submission.
Names, phone and email are prefilled from WooCommerce customer data where
available; email remains optional for ordinary checkout. The order summary
and shipping choices refresh when WooCommerce recalculates the checkout.

In WordPress admin > تنظیمات قالب, set نام بانک, شماره کارت, نام صاحب کارت,
and شماره واتساپ دریافت فیش to enable the card-to-card payment option. Until
all four are valid and saved, this method stays hidden. It creates an on-hold
order for manual receipt review; the order-received page also shows the actual
payment details and WhatsApp receipt link. Other enabled WooCommerce gateways
are not offered on this custom checkout page. The card number button copies the configured number, and the
card has a subtle hover effect. This update does not modify cart/product pages.

SINGLE-PRODUCT BASKET ANIMATION (1.5.32)
Supported WooCommerce single-product forms use a decorative woven-basket effect
and Persian cart confirmation. Version 1.5.62 moves the effect after the actual
confirmed add, removes its sticky-header/mobile-cart overrides and handles
hidden or offscreen targets without changing the page layout. Reduced-motion
users receive the confirmation without motion. Grouped products continue
through WooCommerce's original form handler. Other pages are unchanged.

NAVBAR SPACING, REVEAL, AND MOBILE MENU (1.5.31)
The navbar has wider side spacing. Account and search panels open with a short,
reduced-motion-aware reveal. On mobile, only the search control stays in the
header; account and cart move into the full-screen menu. That menu shows the
saved customer name, phone, cart count, and the existing account, orders,
login, and logout destinations as appropriate. Banner assets and links are
unchanged.

HOVER NAVBAR CONTROLS (1.5.30)
The three navbar icons have tighter spacing. On pointer-hover devices, the
account dropdown and a smaller search field open on hover and close when the
pointer leaves. Search stays visible when text has been entered. Touch and
keyboard access remain available. Banner assets and links are unchanged.

COMPACT ACCOUNT MENU (1.5.29)
The homepage cart, account, and search icons sit directly over the banner
without pale circular backgrounds. The open account control remains red.
Signed-in and guest dropdowns are narrower and shorter, with the same content
and destinations. Banner assets and behavior are unchanged.

TRANSPARENT HOMEPAGE NAVBAR (1.5.28)
The homepage navbar overlays the existing banner with a transparent background.
Its light logo and menu text stay readable over the darkened top of each slide.
Inner-page navbar styling and all banner assets, settings, and behavior stay
unchanged.

NAVBAR REDESIGN (1.5.27)
The white navbar follows the supplied desktop references with dark GENOSYS
branding, existing page links, circular cart/account/search controls, an
expandable search field, and signed-in or guest account menus. Signed-in names
and phone numbers come from saved WordPress user data; cart and order badges
use WooCommerce counts. Existing account, cart, login, and logout destinations
are preserved. The mobile menu remains functional. Banner assets and styles
were not changed.

SHIPPING SETTING FIX (1.5.26)
The saved delivery amount now creates a real WooCommerce shipping rate for
Iranian orders even when the active zone only has Free Shipping, or no rate is
available before a customer enters an address. The amount is included in the
cart and checkout totals. A coupon that explicitly grants free shipping and
local pickup keep their existing behavior. Clearing the field restores the
store's original WooCommerce shipping rates.

THEME SETTINGS AND CART ALERTS (1.5.25)
The cart's WooCommerce success, information, and error notices use the site's
red, white, and pale-pink styling. Their messages and links keep WooCommerce's
existing behavior.
Open the new WordPress admin menu تنظیمات قالب and enter هزینه ارسال in the
store's currency units. Enter 0 for free delivery or leave the field blank to
keep WooCommerce's existing shipping setup.

CART PAGE (1.5.24)
The /cart/ page displays the current WooCommerce cart using the store's real
products, images, prices, shipping and totals. Quantity changes, removal and
coupon forms use WooCommerce's own cart handling; checkout uses its configured
checkout page. Cart products and discounts are not hard-coded into the theme.
Cart quantities have plus and minus controls. Currency symbols are decoded
before visible digits are localized so encoded text displays correctly.

SMS LOGIN (1.5.22)
Guests using the header ورود button reach /login/. The page sends a six-digit
verification code through the sms.ir verification template. A matching account
signs in and returns home. A new number opens a details form after verification;
first and last names are required and email is optional. Submitting the form
creates a customer account with those details and the verified phone number,
then signs in and returns home. A verified number already enrolled on an
administrator account can sign in to that account. Codes expire after two
minutes, have five attempts, and cannot be reused. Sending is limited per phone
and per IP. Signed-in visitors to /login/ return to the homepage.

New customer usernames and nicknames use the supplied first and last names.
If the same full name is already a username, a number is appended. Earlier
genosys_ customer usernames change to the saved full name on the customer's
next successful SMS login; this does not affect administrator accounts.

After updating the theme, open WordPress Settings > ورود پیامکی ژنوسیس and
enter the sms.ir API key once. The template ID defaults to 163300 and the
parameter name defaults to CODE for the #CODE# placeholder. The key is kept on
the server and is never included in the theme package or login page. For an
administrator to use SMS login, enter that administrator's own phone number in
the same settings page while signed in to their WordPress account. The API key
may alternatively be defined as GENOSYS_SMSIR_API_KEY in wp-config.php.

SIGNED-IN HEADER (1.5.18)
Guests see the existing ورود button. Signed-in users instead see an account
icon and a cart icon on desktop and mobile. The account icon opens links to
the WooCommerce account, orders, and WordPress logout; logout returns home.

HOMEPAGE TABBED PRODUCTS (1.5.17)
Below the circular statistics and before the footer, three category tabs display
up to six published WooCommerce products from each matching product category.
The cards reuse the homepage product card design, including real images, prices,
stock state, links and favorites. A category with no products shows an empty
message instead of substituting unrelated products. The first category,
پکیج های ویژه, has zero products in the supplied WooCommerce screenshot.

MOBILE MENU ICON (1.5.15)
The mobile menu opener uses the supplied transparent red three-bar design:
rounded strokes with a shorter middle bar. Its 44px tap target and existing
menu open/close behavior remain intact across pages.

GLOBAL FOOTER (1.5.14)
The supplied footer design appears after the main content on every page. Its
Persian heading, contact buttons, page links, GENOSYS mark, and copyright are
live text and links; the abstract artwork uses the supplied image. Instagram,
Telegram, WhatsApp and LinkedIn icons are visible but intentionally unlinked.
The layout adapts to mobile without changing existing page sections. The extra
wave from the artwork is removed, the headline is smaller, and the upper button
reads دریافت کاتالوگ محصولات and is intentionally inactive for now.

HOMEPAGE CATEGORY LOOP (updated 1.5.16)
The existing category cards move continuously at a slow pace and repeat in a
seamless RTL loop. Mouse dragging and native touch, wheel and keyboard scrolling
remain available; automatic movement resumes after manual interaction. The
original WooCommerce category destinations and card design are unchanged.
The automatic pace is slightly faster for more continuous motion.

CUSTOMER CLUB PAGE (1.5.11)
The homepage customer-club title and button open /fans-club/. The dedicated
page follows the supplied three-step Persian questionnaire and benefit-card
design. The steps, choices, rating, slider and required-field feedback work
in the visitor's browser. ثبت عضویت is intentionally inactive; no membership
data is sent or saved. The homepage products card and existing shop pages are
unchanged.

HOMEPAGE CATEGORY SHOWCASE (1.5.10)
A new section below the latest products follows the supplied Persian reference:
centered heading and actions, six distinct supplied category photographs in an RTL,
staggered horizontal row, and a deep crimson band behind the cards. Mouse drag,
native touch scrolling, snapping and hidden scrollbars use the existing rail behavior.
Cards link to matching WooCommerce category archives when available, otherwise to
the configured products destination. The prior homepage sections and product page
are unchanged.
Category cards now have softly rounded corners, a subtle image zoom, slightly
smaller titles, and an animated red title and longer underline on desktop hover.
Four circular statistics now follow the category cards on the same crimson band.
Their Persian counts and white progress rings animate once when scrolled into view;
reduced-motion visitors see the completed values immediately.
Category cards are now shorter at the same width. The statistics circles are
smaller with wider side spacing, form two columns on mobile, and replay their
count and glowing white ring when hovered with a mouse.

RELATED PRODUCT CARDS (1.5.6)
The single-product related row now uses the same reusable card template, styling,
favorites and horizontal scroll behavior as the homepage row. WooCommerce still
selects the related products; their data and ordering logic are unchanged.

PRODUCT CARD HOVER TEXT (1.5.5)
On desktop hover, the title, category and price transition to near-black with a
fine light stroke and shadow, remaining readable as the glass layers fade away.
The image zoom and all product behavior remain unchanged.

PRODUCT CARD HOVER (1.5.4)
On desktop hover, the two dark/glass layers over the product image fade out smoothly
to reveal the photograph. They return on mouse-out. The existing image zoom, text,
badge, favorite control, product data and mobile behavior are unchanged.

SINGLE-PRODUCT FIXES (1.5.3)
The gallery image fills its rounded box. Simple-product quantity and Add to Cart
share one full-width row. WooCommerce's one-item hidden quantity remains submitted,
with a visible ۱ in the counter; editable quantities change only through +/-.
The native in-stock notice is a rounded badge, and every additional-information
attribute uses the same pale-pink tile. Homepage assets and behavior are unchanged.

HOMEPAGE PRODUCT CARD (1.5.2)
The existing WooCommerce product-card component now uses its real featured image
as a full-card cover photograph, with a fixed-height dark blurred information area
at the bottom. Name, category and price remain dynamic; sale and unavailable
states plus the favorite control are unchanged. Product ordering, scrolling,
drag/swipe, snap behavior and the view-all item are unchanged.

PRODUCT TABS (1.5.1)
The existing WooCommerce description, additional-information and reviews tabs use
rounded RTL navigation, unboxed description text, attribute tiles and a styled
native review form. Tab labels and review count are Persian. Attribute values,
descriptions, ratings and comments still come from WooCommerce. Missing content
continues to follow WooCommerce's normal tab visibility rules.

SINGLE PRODUCT PAGE (1.5.0)
WooCommerce product URLs use the existing permalink settings, including /product-item/.
The new responsive product layout reads the current WooCommerce product. Its gallery,
category, title, short description, formatted price, sale percentage, stock state,
SKU, quantity, Add to Cart form, variation selectors, tabs and reviews stay dynamic.
Images fill the white gallery; on mobile the gallery appears before
the information card. No product data is copied into the theme.

CONFIGURE
Appearance > Menus: create a menu and assign it to the Primary menu location.
Fallback links: Brand /brand/, Products /products/, Company /company/, Contact /contact/.
Create those pages or replace their destinations with your own menu links.
The primary menu automatically ends with a Persian login button (ورود) linking
to the standard WordPress login URL, including when a custom primary menu is assigned.
Desktop navigation uses slightly smaller text and logo, with equal, tighter gaps
between the four links and login button. Homepage lettering is a very light gray;
hover uses a subtle gray-to-rose text gradient without an underline. The login
button uses the GENOSYS red gradient. The mobile glass menu includes the same action.
Appearance > Customize > Site Identity supports an uploaded custom logo. The bundled wordmark retains the existing lettering and colors with the corrected center symbol.
ENG has been removed. The wordmark uses complete vector outlines, including the final S.
Its versioned filename prevents browsers from reusing the earlier clipped image.

TYPOGRAPHY
Vazirmatn 33.003 variable WOFF2 is bundled locally and used across the theme.
No external font requests are required. Font license is included in assets/fonts/OFL.txt.
Font project: https://github.com/rastikerdar/vazirmatn

MOBILE
Below 821px, the hamburger opens a full-width, full-height frosted glass menu.
The page is blurred underneath a translucent dark panel, with vertically arranged
Persian links, subtle separators, and a 44px close button. Safe-area padding keeps
controls clear of notches. The menu scrolls independently when content is long.
Background scrolling and focus are locked while the menu is open; banner playback pauses.
The close button, Escape, or a link selection closes the menu and restores focus.
Breakpoint changes reset the menu and restore scrolling and keyboard access.
Reduced-motion preferences disable the reveal animation. Browsers without backdrop
blur support receive a readable dark panel. Desktop navigation is unchanged.
Without JavaScript, navigation stays visible and the inactive toggle stays hidden.

HOMEPAGE BANNER
Four replacement photographs supplied on October 1, in their numbered order 1-4:
skincare collection, ECO Gel, hair products/device, and roller with skincare vials.
Each photograph has a new versioned filename to avoid stale cached images.
The banner fills the viewport, including the area behind the transparent navbar.
The full-height image overlay starts at 82% black opacity at the top, stays dark
under the navbar, and fades smoothly downward to completely clear at 96% height.
The bottom 4% retains the original photograph's color and brightness.
Homepage navigation and default logo lettering are light gray for contrast; the logo's
red mark, complete vector shapes, viewBox and final S are preserved exactly.
The 404 and other pages continue to use the original dark logo and navigation.
Photographs use cover sizing. Portrait mobile screens crop the sides with an
individual focal point for each photograph to keep the featured product visible.
Original dimensions are retained; optimized WebP images are included locally.
Automatic RTL sliding every 6 seconds, with previous/next arrows, four slide
selectors, a pause/resume control, RTL arrow-key navigation, and touch swiping.
On desktop, hold the left mouse button over the image and drag in either direction.
Images track the pointer, then settle on the next/previous slide when released.
Short drags settle back on the same slide. Autoplay pauses during mouse dragging.
Automatic playback pauses while the browser tab is hidden, a banner control has
keyboard focus, or the mobile navigation is open. It resumes with a fresh interval.
Reduced-motion preferences disable initial autoplay and slide animation.
Without JavaScript, the first image remains visible and slide controls are hidden.
Only the homepage loads the banner script. No external carousel library is used.

HOMEPAGE CARDS
Two equally sized image cards sit below the full-screen banner with white outer
padding and a clear gap. The customer club card is on the right in RTL order;
products is on the left. Mobile screens below 821px stack the cards vertically.
Each card contains one Persian title and one red-gradient button, over a soft
bottom fade that preserves readable text. Background images are lazy-loaded.
Both card backgrounds use the replacement photographs supplied on October 1:
GENOSYS gift box for the customer club, and skincare products with greenery.
The banner photographs remain unchanged.
Club title and button: open /fans-club/.
Product button: مشاهده محصولات. It follows the Products item in the assigned
primary menu; without that item it defaults to /products/.
Change the products URL in Appearance > Customize > کارت‌های صفحه اصلی.
The cards do not create a membership backend or products database.

SCOPE
The navbar, homepage image banner, two homepage cards, latest products and Persian 404 page are built.
WordPress selects 404.php and sends its normal 404 HTTP response for missing URLs.
The user's working WordPress rewrite rules on the server are preserved.
This version does not write or install hosting rules and contains no global 404 installer.
Existing pages remain intentionally empty below the header.
WooCommerce must be installed and active to display the latest-products section.
WooCommerce itself and any test catalog are not bundled in the theme ZIP.
No build process or separate product database is required.

LICENSE
Theme code: GPL v2 or later. GENOSYS branding belongs to its respective owner.
Vazirmatn: SIL Open Font License 1.1 (included).

1.3.2: Replaced only the products-card image with the latest supplied photo.
Both card buttons are centered beneath their titles; card type sizes are reduced.

LATEST PRODUCTS — 1.4.0
Homepage title: جدیدترین محصولات. The section appears directly after the two boxes.
wc_get_products() retrieves up to eight published catalog products, newest first.
Hidden/search-only products are excluded; the store's hide-out-of-stock setting is respected.
At eight or more eligible products, the row ends with مشاهده همه linking to /products.
With fewer than eight, only the available products appear. No sample data is shipped.
An active empty store displays a short Persian empty message; without WooCommerce,
the section is omitted and the rest of the theme continues to work.
Reusable templates: template-parts/home/latest-products.php and template-parts/product/card.php.
No WooCommerce templates are overridden. Product images, names, categories, permalinks
and price HTML come from WooCommerce/WordPress. Currency, taxes, variable ranges and
price extensions remain under WooCommerce's control. Do not embed the card-reference
screenshot as a product image: upload a product photograph to each product instead.
The reference design is recreated as rounded image cards with a frosted panel,
category under the title, sale badge, old/current prices and an outlined heart.
Long names clamp to two lines; categories truncate; missing images use WooCommerce's
placeholder; empty prices and unavailable products receive truthful Persian labels.
Discount percentages use active simple/external product prices; products with varying
discounts use a generic sale label rather than an inaccurate percentage.
The row never wraps. Native X scrolling works with touch, trackpads,
Shift + mouse wheel and keyboard arrow keys while the scroll region has focus.
Mobile displays one full-width card with mandatory snap points and scroll-snap-stop:
always, so ordinary swipes advance one item. No carousel JavaScript is used.
Hearts save only product IDs in the visitor's browser and show the selected state;
they are not an account-synced wishlist. If storage is disabled, they work for the visit.
Existing navbar, banner, two-box styling, images and navigation scripts are unchanged.

LATEST PRODUCTS UX — 1.4.1
The horizontal scrollbar and track are hidden with WebKit, Firefox and legacy Edge CSS.
Desktop mouse users can drag the row with grab/grabbing cursors. Small ordinary clicks
still open products or toggle hearts; the click following a drag is suppressed.
Touch/pen and wheel scrolling remain native, and vertical page scrolling is preserved.
Snap temporarily pauses during mouse dragging and resumes on release at the nearest item.
Product cards are slightly narrower and shorter, with smaller readable text, badges,
padding and text gaps. Images use contain sizing in a separate media area above the
panel, so products are neither cropped nor covered by the name/category/price panel.
The lower glass panel has a slightly deeper translucent warm-gray tint and retains
its blur, white border, rounded corners, hierarchy and RTL alignment.
WooCommerce queries/helpers, card markup, data, favorites and other sections are unchanged.

LATEST PRODUCTS REFINEMENTS — 1.4.2
The outer card, image area, image padding and gap above the lower panel are pure white.
The existing translucent gradient, 18px blur, rounded corners and typography are retained.
Every information panel has the same fixed height at its breakpoint: 170px desktop,
164px mobile and 160px on narrow phones. A two-line title area is always reserved;
long titles clamp to two lines. Category and price rows align even when a title is
short or a category is absent. Card widths, aspect ratio and image contain sizing
are unchanged. Product photographs are unchanged.
Desktop mouse dragging follows the pointer with a short animation-frame easing.
On release, a small capped momentum bias selects the nearest snap position, then
settles smoothly over 200-320ms. Snap remains paused until settling completes.
Touch, wheel and keyboard scrolling stay native. Reduced motion disables easing
and momentum. Ordinary links and hearts still work; drag-end clicks are suppressed.
The row uses the default cursor throughout; clickable controls keep normal cursors.
Hidden scrollbars and one-card mobile snapping are preserved.
No WooCommerce query, helper, reusable card markup or other homepage section changed.

PRODUCT CARD REFERENCE DESIGN — 1.4.3
Cards follow the three supplied October 3 references. The media and outer areas
remain pure white. The lower panel fades from near-white at the top to soft rose,
strongest at the bottom-left, with a thin pink border and gentle pink shadow.
Category appears above the two-line title. Sale/original price is a smaller gray
line above the red current price; both align right. Typography is restrained:
title14px (13.5px on narrow phones), category10.5px, price15px (14.5px narrow),
original price10.5px and badge11px. The existing full-size card dimensions stay.
Equal fixed-height panels reserve category, two title lines and price positions,
including when titles are short or categories are missing. Hearts appear at the
top-left and sale/stock badges at the top-right. Selected hearts are white on red.
Out-of-stock cards keep their ناموجود badge and replace all numeric price output
with تماس بگیرید. In-stock prices, sale prices and missing-price behavior remain
under the existing WooCommerce presentation. No product photos or catalog query
changed. Drag animation, native touch swipe, snap and hidden scrollbars are unchanged.

LIGHTER PANEL AND IMAGE HOVER — 1.4.4
Only the information-panel gradient has been softened to match the latest image:
a lighter blush tint concentrated at the lower-left over a nearly white base.
Border, shadow, blur, panel height, card dimensions, typography and stock text stay.
On desktop mouse hover over a card, its product image smoothly scales to1.1 over
420ms, returning on leave. Zoom is clipped inside the image area and never changes
the card layout. Touch screens retain the normal image. Reduced-motion preferences
disable the zoom and transition. No image files, card markup, price/data logic,
scrolling scripts, or other homepage sections changed.
