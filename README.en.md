# MGD AI Labelling for JTL-Shop 5

This JTL-Shop 5 plugin manages and displays transparent labels for AI-generated or AI-modified images. It performs **no automatic AI detection**; an authorised human deliberately reviews and assigns each status.

Version 1.0.0 includes local image discovery, six review states, accessible German and English labels, an explicitly scoped OPC CSS/JavaScript fallback, a bilingual AI-philosophy portlet, protected bulk administration, optional footer credit and opt-in GitHub release notices.

Requirements: JTL-Shop 5.7.2 or newer, PHP 8.1 or newer, and NOVA or a compatible NOVA child theme. Back up the database and plugin files before installation. German operating, privacy and rollback documentation is available in [`Dokumentation/`](Dokumentation/README.md).

Build the installable archive with `bash scripts/build-release.sh`. This project is licensed under `GPL-3.0-or-later`.
