/*
 * Aktiviert ausschließlich den Zielwert eines ausgewählten Feldes. Dadurch
 * sendet der Browser keine unmaskierten Werte; serverseitige Validierung bleibt
 * dennoch vollständig erforderlich.
 */
'use strict';

document.querySelectorAll('form').forEach((form) => {
    ['status', 'position', 'theme'].forEach((field) => {
        const checkbox = form.querySelector(`[name="mask[${field}]"]`);
        const target = form.querySelector(`[name="values[${field}]"]`);
        if (!(checkbox instanceof HTMLInputElement) || !(target instanceof HTMLSelectElement)) {
            return;
        }
        const synchronize = () => {
            target.disabled = !checkbox.checked;
        };
        checkbox.addEventListener('change', synchronize);
        synchronize();
    });
});
