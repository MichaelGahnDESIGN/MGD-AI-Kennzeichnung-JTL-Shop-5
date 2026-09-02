# MGD AI Labelling for JTL-Shop 5

This JTL-Shop 5 plugin manages and displays transparent labels for AI-generated or AI-modified images. It performs **no automatic AI detection**; an authorised human deliberately reviews and assigns each status.

Version 1.3.6 is a local development-test package, not yet publicly released or installed on a shop. Its recursive OPC scan includes supported uploads in nested folders, even if they are not used on a page. Existing labels and gallery filters are preserved. A real JTL development-site acceptance test is still required.

It also keeps the two large, stacked AI-philosophy editors from Version 1.3.0 and the local AJAX-compatible editor startup from 1.3.4. Authors can work in **Visual** mode or switch to the optional **HTML** source view. **Save both language versions** submits the German and English content together.

The editor only accepts `p`, `h2`, `h3`, `ul`, `ol`, `li`, `strong`, `em`, and `a`. Unsafe protocols, scripts, styles, images, iframes, forms, embedded objects, event handlers, and unknown attributes are removed. Client-side sanitising provides immediate feedback; the PHP sanitizer remains the authoritative security boundary on save.

All editor code, styling, icons, and dialogs are included locally. It loads no external editor library, CDN asset, font, third-party content, or telemetry. If JavaScript is unavailable, the large source textareas remain fully usable as a no-JavaScript fallback.

The public repository does not provide an automatic updater. Install the verified `MGD_AI_Kennzeichnung-1.3.6.zip` through JTL's manual plugin upload after a backup and a development-site test. When update notices are enabled, GitHub can technically receive the server IP, time and fixed User-Agent; no images, tokens, customer or form data are sent, and positive or negative results are cached for twelve hours.

Requirements: JTL-Shop 5.7.2 or newer, PHP 8.1 or newer, and NOVA or a compatible NOVA child theme. Back up the database and plugin files before installation. The complete German wiki-style guide is available in the [versioned GitHub handbook](https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/blob/main/wiki/Home.md); operating, privacy and rollback documents are stored in [`Dokumentation/`](Dokumentation/README.md).

The free Version 1.3.6 contains no licence keys, payment flow, feature locks, telemetry, or Pro activation. Sustainable service and marketplace options are documented in [`Dokumentation/Monetarisierung-und-Marketplaces.md`](Dokumentation/Monetarisierung-und-Marketplaces.md); that research is technical guidance, not legal advice.

Build the installable archive with `bash scripts/build-release.sh`. This project is licensed under `GPL-3.0-or-later`.
