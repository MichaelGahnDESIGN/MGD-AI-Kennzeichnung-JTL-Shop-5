function assertResponse(response) {
    if (!response || typeof response !== 'object' || typeof response.ok !== 'boolean') {
        throw new Error('JTL hat keine gültige Antwort geliefert.');
    }
    if (!response.ok) {
        throw new Error(typeof response.message === 'string' ? response.message : 'Die Anfrage wurde abgelehnt.');
    }

    return response.data;
}

function io() {
    const instance = window.opc?.io;
    if (!instance || typeof instance.ioCall !== 'function') {
        throw new Error('Die geschützte JTL-Verbindung ist noch nicht verfügbar.');
    }

    return instance;
}

/** Verwendet JTLs bestehende Admin-Sitzung und deren bereits geprüfte IO-Strecke. */
export function createAdminIoClient() {
    return Object.freeze({
        async load(localPath) {
            return assertResponse(await io().ioCall('mgd_ai_label_load', localPath));
        },
        async save(localPath, source, status, position, theme) {
            return assertResponse(await io().ioCall(
                'mgd_ai_label_save',
                localPath,
                source,
                status,
                position,
                theme,
            ));
        },
    });
}
