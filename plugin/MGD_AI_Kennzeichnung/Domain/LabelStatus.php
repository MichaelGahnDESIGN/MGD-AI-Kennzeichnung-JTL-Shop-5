<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Domain;

/**
 * Beschreibt den fachlichen Prüf- und Kennzeichnungsstatus eines Inhalts.
 *
 * Das Enum ist eine geschlossene Positivliste. Unbekannte, manipulierte oder
 * technisch unerwartete Eingaben werden nicht sichtbar, sondern bewusst als
 * ungeprüft behandelt. So kann keine freie Eingabe eine Kennzeichnung erzwingen.
 */
enum LabelStatus: string
{
    case Unreviewed = 'unreviewed';
    case None = 'none';
    case Generated = 'generated';
    case PartiallyGenerated = 'partially-generated';
    case Modified = 'modified';
    case Deepfake = 'deepfake';

    /**
     * Überführt eine beliebige Einstellungs- oder Datenbankeingabe sicher in
     * einen bekannten Status. Nur Strings können als Status anerkannt werden.
     */
    public static function fromInput(mixed $input): self
    {
        if ($input instanceof self) {
            return $input;
        }

        if (!is_string($input)) {
            return self::Unreviewed;
        }

        return self::tryFrom(strtolower(trim($input))) ?? self::Unreviewed;
    }

    /**
     * Gibt an, ob der Status tatsächlich eine Kennzeichnung auslösen darf.
     */
    public function isVisible(): bool
    {
        return match ($this) {
            self::Generated, self::PartiallyGenerated, self::Modified, self::Deepfake => true,
            self::Unreviewed, self::None => false,
        };
    }

    /**
     * Liefert ausschließlich eine fest definierte Statusklasse für die Ausgabe.
     * Unsichtbare Zustände besitzen keine Klasse und können dadurch auch nicht
     * versehentlich über CSS sichtbar gemacht werden.
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Generated => 'mgd-ai-status-generated',
            self::PartiallyGenerated => 'mgd-ai-status-partially-generated',
            self::Modified => 'mgd-ai-status-modified',
            self::Deepfake => 'mgd-ai-status-deepfake',
            self::Unreviewed, self::None => '',
        };
    }
}
