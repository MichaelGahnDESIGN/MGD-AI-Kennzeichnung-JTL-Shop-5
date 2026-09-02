/** Zeigt die Messwerte des schmalen Test-Viewports sichtbar in dessen lokaler Hülle. */
window.addEventListener('load', () => {
    const root = document.querySelector('[data-mgd-display-preview]');
    const label = root.querySelector('[data-mgd-display-label]');
    const image = root.querySelector('img');
    const frame = root.getBoundingClientRect();
    const badge = label.getBoundingClientRect();
    const picture = image.getBoundingClientRect();
    const result = {
        viewport: window.innerWidth,
        documentWidth: document.documentElement.scrollWidth,
        previewWidth: root.clientWidth,
        previewScrollWidth: root.scrollWidth,
        labelWidth: badge.width,
        labelHeight: badge.height,
        labelInside: badge.left >= frame.left && badge.right <= frame.right,
        patternBehindLabel: badge.bottom <= picture.top || badge.top >= picture.bottom,
        fontSize: getComputedStyle(label).fontSize,
    };
    // Zugriff nur auf die gleich-originige Testhülle; keine Nachrichten oder Netzanfragen.
    const output = window.parent.document.getElementById('local-measurements');
    if (output) output.textContent = JSON.stringify(result);
}, { once: true });
