GENOSYS — header, banner, image cards and WooCommerce products theme, version 1.5.16
Built on the user's selected 1.4.4 baseline.

INSTALL OR UPDATE
Upload genosys.zip in Appearance > Themes > Add New > Upload Theme.
When updating, select Replace current with uploaded. The theme folder remains genosys.
Activate if needed. Set Settings > General > Site Language to Persian.

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
Appearance > Customize > Site Identity: upload the official logo to replace the approximate vector wordmark.
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
