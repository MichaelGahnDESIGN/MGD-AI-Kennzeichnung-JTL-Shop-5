/** Zeigt die Messwerte des schmalen Test-Viewports sichtbar in dessen lokaler Hülle. */
window.addEventListener('load', () => {
    const root = document.querySelector('[data-mgd-display-preview]');
    const label = root.querySelector('[data-mgd-display-label]');
    const image = root.querySelector('img');
    const frame = root.getBoundingClientRect();
    const badge = label.getBoundingClientRect();
    const picture = image.getBoundingClientRect();
    const detail = document.querySelector('[data-mgd-detail-preview]');
    const scene = detail.querySelector('.mgd-detail__scene');
    const detailLabel = detail.querySelector('[data-mgd-detail-label]');
    const detailFrame = scene.getBoundingClientRect();
    const detailBadge = detailLabel.getBoundingClientRect();
    // Ein halber Pixel Toleranz vermeidet Fehlalarme durch Subpixel-Rundung.
    const inside = (child, parent) => child.left >= parent.left - .5
        && child.right <= parent.right + .5 && child.top >= parent.top - .5 && child.bottom <= parent.bottom + .5;
    const result = {
        viewport: window.innerWidth,
        documentWidth: document.documentElement.scrollWidth,
        previewWidth: root.clientWidth,
        previewScrollWidth: root.scrollWidth,
        labelWidth: badge.width,
        labelHeight: badge.height,
        labelInside: inside(badge, frame),
        imageInside: inside(picture, frame),
        detailLabelInside: inside(detailBadge, detailFrame),
        detailZoom: getComputedStyle(scene).zoom,
        detailText: detailLabel.textContent,
        detailTransparency: detail.querySelector('[data-mgd-detail-transparency]').textContent,
        detailBlur: detail.querySelector('[data-mgd-detail-blur]').textContent,
        fontSize: getComputedStyle(label).fontSize,
    };
    // Zugriff nur auf die gleich-originige Testhülle; keine Nachrichten oder Netzanfragen.
    const output = window.parent.document.getElementById('local-measurements');
    if (output) output.textContent = JSON.stringify(result);
}, { once: true });
