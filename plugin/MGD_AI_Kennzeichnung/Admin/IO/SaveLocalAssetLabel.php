<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\IO;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Service\LocalAssetLabelService;
use Throwable;

/** Eng begrenzte Admin-IO-Schreibaktion mit fünf festen Positionsparametern. */
final class SaveLocalAssetLabel
{
    public function __construct(private readonly LocalAssetLabelService $service) {}

    public function __invoke(mixed ...$params): AdminIoResponse
    {
        if (count($params) !== 5) {
            return AdminIoResponse::error('invalid_request', 'Die Anfrage besitzt nicht die erwartete Form.');
        }

        try {
            return AdminIoResponse::fromLabel(
                $this->service->save($params[0], $params[1], $params[2], $params[3], $params[4]),
                'Die Bildkennzeichnung wurde gespeichert.',
            );
        } catch (AccessDeniedException) {
            return AdminIoResponse::error('access_denied', 'Keine Berechtigung für die Bildkennzeichnung.');
        } catch (ValidationException) {
            return AdminIoResponse::error('validation_failed', 'Die Kennzeichnungswerte sind nicht freigegeben.');
        } catch (Throwable) {
            return AdminIoResponse::error('save_failed', 'Die Bildkennzeichnung konnte nicht gespeichert werden.');
        }
    }
}
