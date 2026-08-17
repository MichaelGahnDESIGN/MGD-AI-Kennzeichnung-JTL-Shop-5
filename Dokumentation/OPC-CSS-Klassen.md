# OPC-Klassen für manuell markierte Elemente

Ein vorhandenes OPC-Element erhält genau eine Basisklasse, einen Status, eine Position und ein Theme:

```text
mgd-ai-label mgd-ai-status-generated mgd-ai-label--position-bottom-right mgd-ai-label--theme-auto
```

Status: `mgd-ai-status-generated`, `mgd-ai-status-partially-generated`, `mgd-ai-status-modified`, `mgd-ai-status-deepfake`.

Position: `mgd-ai-label--position-top-left`, `-top-right`, `-bottom-left`, `-bottom-right`.

Theme: `mgd-ai-label--theme-auto`, `-light`, `-dark`.

Der CSS-Fallback bleibt ohne JavaScript sichtbar. Der lokale Helfer verarbeitet ausschließlich `.mgd-ai-label`, ergänzt barrierearme Semantik und verwendet `textContent`; er scannt weder Bilder noch das übrige DOM.
