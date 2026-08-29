# MGD AI Labelling for JTL-Shop 5

This JTL-Shop 5 plugin manages and displays transparent labels for AI-generated or AI-modified images. It performs **no automatic AI detection**; an authorised human deliberately reviews and assigns each status.

Version 1.2.1 adds a protected display page with a local live preview, bounded transparency, synchronised number and range controls, and the optional “supported by: Michael Gahn DESIGN” credit. Preview position and theme are preview-only; their real values remain stored per image. The established image features include a responsive gallery, explicit save dialog, protected single and bulk administration, direct labelling for supported local OPC image fields, and an optional fail-safe elFinder action.

The private repository does not provide an automatic updater. Install the verified `MGD_AI_Kennzeichnung-1.2.1.zip` through JTL's manual plugin upload after a backup and a development-site test. When update notices are enabled, GitHub can technically receive the server IP, time and fixed User-Agent; no images, tokens, customer or form data are sent, and positive or negative results are cached for twelve hours.

Requirements: JTL-Shop 5.7.2 or newer, PHP 8.1 or newer, and NOVA or a compatible NOVA child theme. Back up the database and plugin files before installation. The complete German wiki-style guide is available in the [versioned GitHub handbook](https://github.com/MichaelGahnDESIGN/MGD-AI-Kennzeichnung-JTL-Shop-5/blob/main/wiki/Home.md); operating, privacy and rollback documents are stored in [`Dokumentation/`](Dokumentation/README.md).

Build the installable archive with `bash scripts/build-release.sh`. This project is licensed under `GPL-3.0-or-later`.
