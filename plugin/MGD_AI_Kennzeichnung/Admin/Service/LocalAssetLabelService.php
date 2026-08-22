<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Admin\Service;

use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\AuthorizationPortInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Port\LocalAssetLabelRepositoryInterface;
use Plugin\MGD_AI_Kennzeichnung\Admin\Presentation\LocalPreviewUrlResolver;
use Plugin\MGD_AI_Kennzeichnung\Admin\Value\LocalAssetLabel;
use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelPosition;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelStatus;
use Plugin\MGD_AI_Kennzeichnung\Domain\LabelTheme;
use Plugin\MGD_AI_Kennzeichnung\Scanner\LocalPathNormalizer;

/**
 * Gemeinsame fachliche Grenze für Galerie, OPC und Dateimanager.
 *
 * Berechtigung und Pfad werden vor jedem Repositoryzugriff geprüft. Das Laden
 * eines unbekannten Bildes bleibt rein lesend; erst die bewusste Speicherung
 * darf einen Datensatz erzeugen.
 */
final class LocalAssetLabelService
{
    public function __construct(
        private readonly AuthorizationPortInterface $authorization,
        private readonly LocalAssetLabelRepositoryInterface $repository,
        private readonly LocalPathNormalizer $paths,
        private readonly LocalPreviewUrlResolver $previewUrls,
    ) {}

    public function load(mixed $localPath): LocalAssetLabel
    {
        $this->authorization->assertCanManageAssets();
        $normalizedPath = $this->validatedPath($localPath);
        $stored = $this->repository->findByLocalPath($normalizedPath);
        if ($stored !== null) {
            return $stored;
        }

        return new LocalAssetLabel(
            id: null,
            localPath: $normalizedPath,
            status: LabelStatus::Unreviewed,
            position: LabelPosition::BottomRight,
            theme: LabelTheme::Auto,
            source: AssetSource::Unknown,
            persisted: false,
        );
    }

    public function save(
        mixed $localPath,
        mixed $source,
        mixed $status,
        mixed $position,
        mixed $theme,
    ): LocalAssetLabel {
        $this->authorization->assertCanManageAssets();
        $normalizedPath = $this->validatedPath($localPath);
        $normalSource = is_string($source) ? AssetSource::tryFrom($source) : null;
        $normalStatus = is_string($status) ? LabelStatus::tryFrom($status) : null;
        $normalPosition = is_string($position) ? LabelPosition::tryFrom($position) : null;
        $normalTheme = is_string($theme) ? LabelTheme::tryFrom($theme) : null;

        if (!in_array($normalSource, [AssetSource::Opc, AssetSource::CustomLocalManual], true)
            || $normalStatus === null
            || $normalPosition === null
            || $normalTheme === null
        ) {
            throw new ValidationException('Die Kennzeichnungswerte gehören nicht zur erlaubten Auswahl.');
        }

        return $this->repository->save(
            $normalizedPath,
            $normalSource,
            $normalStatus,
            $normalPosition,
            $normalTheme,
        );
    }

    private function validatedPath(mixed $localPath): string
    {
        $normalized = $this->paths->normalize($localPath);
        if ($normalized === null || !$this->previewUrls->accepts($normalized)) {
            throw new ValidationException('Der lokale Bildpfad ist nicht freigegeben.');
        }

        return $normalized;
    }
}
