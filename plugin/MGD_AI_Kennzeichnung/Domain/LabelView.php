<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Domain;

/**
 * Unveränderliches, bereits normalisiertes Darstellungsmodell einer Kennzeichnung.
 *
 * PHP 8.1 unterstützt noch keine readonly-Klassen. Deshalb ist die Klasse final
 * und jede einzelne Eigenschaft ausdrücklich readonly. Das erreicht unter der
 * vereinbarten Mindestversion dieselbe Unveränderlichkeit. Das Modell enthält
 * weder HTML noch frei erzeugte Styles; die sichere Aufbereitung übernimmt der
 * LabelViewResolver vor dem Erzeugen dieses Objekts.
 */
final class LabelView
{
    public function __construct(
        public readonly bool $visible,
        public readonly string $visibleText,
        public readonly string $assistiveText,
        public readonly string $positionClass,
        public readonly string $themeClass,
        public readonly string $sourceClass,
        public readonly int $fontSize,
        public readonly int $outerMargin,
        public readonly int $innerPadding,
        public readonly int $borderRadius,
        public readonly int $blur,
    ) {}
}
