# GitHub-Telefonnummernaktualisierung Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Alle eindeutigen Vorkommen der alten geschäftlichen Telefonnummer auf den Standardbranches eigener GitHub-Repositories sicher durch die neue Nummer ersetzen.

**Architecture:** Ein temporärer, flacher Klon jedes eigenen Standardbranches dient als überprüfbare Arbeitskopie. Nur Repositories mit exaktem Altwert werden verändert; jedes erhält einen isolierten Diff, einen eigenen Commit und eine Push-/Nachprüfung.

**Tech Stack:** Git, GitHub CLI/API, ripgrep, Markdown und projektspezifische Textformate

---

### Task 1: Vollständige Bestandsaufnahme

**Files:**
- Read: all default-branch text files in repositories owned by `MichaelGahnDESIGN`
- Read: repository-specific `AGENTS.md`, `GRUNDREGELN.md`, `PROJEKTREGELN.md`

- [ ] **Step 1: Create a recoverable temporary workspace**

Run: `arbeitsbereich="$(mktemp -d "${TMPDIR:-/tmp}/mgd-phone-update.XXXXXX")"`

Expected: ein neuer, eindeutig begrenzter temporärer Ordner.

- [ ] **Step 2: Enumerate owned repositories and clone only their default branch**

Die GitHub-API liefert Name und Standardbranch. Jedes Repository wird mit `--depth=1 --single-branch --filter=blob:limit=2m` in den temporären Ordner geklont; weder Arbeitsordner noch bestehende lokale Projekte werden verändert.

- [ ] **Step 3: Find exact old-number occurrences**

Run innerhalb des temporären Ordners:

```bash
rg -n --hidden --glob '!.git/**' --glob '!vendor/**' --glob '!node_modules/**' --glob '!Backups/**' --glob '!dist/**' --glob '!build/**' --fixed-strings '+49 (0) 176 557 647 48'
```

Expected: eine überprüfbare Liste betroffener Repository-/Dateipfade; bekannte Root-Vorkommen liegen in mindestens 20 Repositories.

- [ ] **Step 4: Read every applicable repository instruction file**

Vor Änderungen werden alle Regeldateien der tatsächlich betroffenen Repositories vollständig gelesen. Repositories mit abweichenden Freigabe-, Branch- oder Testregeln werden separat behandelt.

### Task 2: Exakte Ersetzung repositoryweise durchführen

**Files:**
- Modify: only files containing the exact old number

- [ ] **Step 1: Apply exact, file-scoped patches**

Jede betroffene Zeile wird mit `apply_patch` von:

```text
+49 (0) 176 557 647 48
```

auf:

```text
+49 (0) 151 59156639
```

geändert. Andere Texte und Telefonnummern bleiben bytegenau unverändert.

- [ ] **Step 2: Verify every repository diff**

Pro Repository:

```bash
git diff --check
git diff --word-diff
rg -n --fixed-strings '+49 (0) 176 557 647 48' . --hidden --glob '!.git/**'
git status --short
```

Expected: kein Altwert, ausschließlich neue Telefonnummernzeilen und keine unbezogenen Dateien.

- [ ] **Step 3: Commit one repository at a time**

```bash
git add -- <explizite-betroffene-dateien>
git commit -m "docs: aktualisiert geschäftliche Telefonnummer"
```

Expected: genau ein klar abgegrenzter Dokumentationscommit je Repository.

- [ ] **Step 4: Push without rewriting history**

Run: `git push origin HEAD:<standardbranch>`

Expected: Fast-forward-Push; bei Divergenz oder Schutzregel stoppen und Repository als blockiert dokumentieren. Kein Force-Push.

### Task 3: GitHub-Nachprüfung und Abschlussprotokoll

**Files:**
- Verify: GitHub default branches of all owned repositories

- [ ] **Step 1: Re-read affected root files through the GitHub API**

README.md und IMPRESSUM.md werden für alle eigenen Repositories erneut abgefragt. Der Altwert darf nicht mehr vorkommen, der Neuwert muss in den zuvor betroffenen Dateien vorhanden sein.

- [ ] **Step 2: Verify nested occurrences from fresh remote clones**

Ein frischer, flacher Remote-Audit prüft die zuvor betroffenen Repositories erneut mit `rg --fixed-strings`. Dadurch wird nicht nur die lokale Arbeitskopie, sondern der tatsächlich gepushte Standardbranch bestätigt.

- [ ] **Step 3: Produce the audit list**

Das Abschlussprotokoll nennt Repository, geänderte Datei(en), Commit-SHA und Push-Status. Nicht betroffene Projekte werden als geprüft, aber unverändert zusammengefasst; blockierte Projekte erhalten Ursache und sicheren nächsten Schritt.

