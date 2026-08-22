import {
    FILE_MANAGER_SELECTORS,
    inspectFileManagerWindow,
    resolveSelectedLocalImage,
} from './file-manager-compatibility.mjs';

const INSTALLATIONEN = new WeakMap();

function defaultObserverFactory(windowRef, callback) {
    return new windowRef.MutationObserver(callback);
}

function defaultIntervalFactory(windowRef, callback) {
    const id = window.setInterval(callback, 500);

    return () => window.clearInterval(id);
}

/**
 * Ergänzt nach vollständiger Kompatibilitätsprüfung genau einen Menüpunkt.
 * Es werden weder elFinder-Kommandos ersetzt noch JTL-Dateien verändert.
 */
export function installFileManagerIntegration(windowRef, options) {
    if (INSTALLATIONEN.has(windowRef)) {
        return INSTALLATIONEN.get(windowRef);
    }
    const inspected = inspectFileManagerWindow(windowRef, options.shopOrigin);
    if (inspected === null) {
        return false;
    }

    const observerFactory = options.observerFactory
        ?? ((callback) => defaultObserverFactory(windowRef, callback));
    const intervalFactory = options.intervalFactory
        ?? ((callback) => defaultIntervalFactory(windowRef, callback));
    let active = true;
    let observer;
    let stopCloseCheck = () => {};

    const addMenuItem = (menu) => {
        const selected = resolveSelectedLocalImage(inspected.manager, options.shopOrigin);
        const existing = menu.querySelector(FILE_MANAGER_SELECTORS.ownItem);
        if (existing) {
            existing.hidden = selected === null;
            return;
        }
        if (selected === null) {
            return;
        }
        const item = inspected.document.createElement('button');
        item.type = 'button';
        item.className = 'elfinder-contextmenu-item mgd-elfinder-label-item';
        item.dataset.mgdAiFileLabel = 'true';
        item.textContent = 'KI-Kennzeichnung bearbeiten';
        item.addEventListener('click', (event) => {
            event?.preventDefault?.();
            event?.stopPropagation?.();
            const selected = resolveSelectedLocalImage(inspected.manager, options.shopOrigin);
            if (selected !== null) {
                options.openLabelDialog(selected.localPath, item);
            }
        });
        menu.append(item);
    };
    const scan = () => {
        if (!active) {
            return;
        }
        inspected.document.querySelectorAll(FILE_MANAGER_SELECTORS.contextMenus).forEach(addMenuItem);
    };
    const cleanup = () => {
        if (!active) {
            return;
        }
        active = false;
        observer?.disconnect();
        stopCloseCheck();
        INSTALLATIONEN.delete(windowRef);
    };

    observer = observerFactory(scan);
    observer.observe(inspected.document.body, { attributes: true, childList: true, subtree: true });
    stopCloseCheck = intervalFactory(() => {
        if (windowRef.closed === true) {
            cleanup();
        }
    });
    const installation = Object.freeze({ cleanup, scan });
    INSTALLATIONEN.set(windowRef, installation);
    scan();

    return installation;
}
