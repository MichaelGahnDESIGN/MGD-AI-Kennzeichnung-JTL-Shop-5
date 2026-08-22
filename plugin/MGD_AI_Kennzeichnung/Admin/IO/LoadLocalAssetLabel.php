<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\IO;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\AccessDeniedException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Service\LocalAssetLabelService;
use Throwable;

/** Eng begrenzte Admin-IO-Leseaktion mit genau einem Positionsparameter. */
final class LoadLocalAssetLabel
{
    public function __construct(private readonly LocalAssetLabelService $service) {}

    public function __invoke(mixed ...$params): AdminIoResponse
    {
        if (count($params) !== 1) {
            return AdminIoResponse::error('invalid_request', 'Die Anfrage besitzt nicht die erwartete Form.');
        }

        try {
            return AdminIoResponse::fromLabel(
                $this->service->load($params[0]),
                'Die Bildkennzeichnung wurde geladen.',
            );
        } catch (AccessDeniedException) {
            return AdminIoResponse::error('access_denied', 'Keine Berechtigung für die Bildkennzeichnung.');
        } catch (ValidationException) {
            return AdminIoResponse::error('validation_failed', 'Der lokale Bildpfad ist nicht freigegeben.');
        } catch (Throwable) {
            return AdminIoResponse::error('load_failed', 'Die Bildkennzeichnung konnte nicht geladen werden.');
        }
    }
}
