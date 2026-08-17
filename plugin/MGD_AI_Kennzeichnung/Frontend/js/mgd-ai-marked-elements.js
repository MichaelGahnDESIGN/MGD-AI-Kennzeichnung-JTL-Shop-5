/**
 * Ergänzt Screenreader-Semantik ausschließlich an bewusst markierten Elementen.
 * Es werden weder Bilder noch andere Bestandteile der Seite durchsucht.
 */
(function () {
    'use strict';

    const texte = {
        de: {
            'mgd-ai-status-generated': ['KI-GENERIERT', 'Dieser Inhalt wurde vollständig mit künstlicher Intelligenz erzeugt.'],
            'mgd-ai-status-partially-generated': ['TEILWEISE KI-GENERIERT', 'Dieser Inhalt wurde teilweise mit künstlicher Intelligenz erzeugt.'],
            'mgd-ai-status-modified': ['MIT KI BEARBEITET', 'Dieser Inhalt wurde mit künstlicher Intelligenz bearbeitet.'],
            'mgd-ai-status-deepfake': ['KI-DEEPFAKE', 'Dieser Inhalt ist ein mit künstlicher Intelligenz erzeugter oder manipulierter Deepfake.']
        },
        en: {
            'mgd-ai-status-generated': ['AI-GENERATED', 'This content was generated entirely using artificial intelligence.'],
            'mgd-ai-status-partially-generated': ['PARTIALLY AI-GENERATED', 'This content was partially generated using artificial intelligence.'],
            'mgd-ai-status-modified': ['AI-MODIFIED', 'This content was modified using artificial intelligence.'],
            'mgd-ai-status-deepfake': ['AI DEEPFAKE', 'This content is a deepfake generated or manipulated using artificial intelligence.']
        }
    };

    const sprache = document.documentElement.lang.toLowerCase().startsWith('de') ? 'de' : 'en';
    const markierteElemente = document.querySelectorAll('.mgd-ai-label');

    markierteElemente.forEach(function (element) {
        if (element.getAttribute('role') === 'note' || element.querySelector('.mgd-ai-label__badge')) {
            return;
        }

        const statusKlasse = Object.keys(texte[sprache]).find(function (klasse) {
            return element.classList.contains(klasse);
        });
        if (!statusKlasse) {
            return;
        }

        const label = document.createElement('span');
        label.className = 'mgd-ai-label__badge';
        label.setAttribute('role', 'note');
        label.setAttribute('aria-label', texte[sprache][statusKlasse][1]);
        label.textContent = texte[sprache][statusKlasse][0];
        element.appendChild(label);
        element.classList.add('mgd-ai-label--enhanced');
    });
}());
