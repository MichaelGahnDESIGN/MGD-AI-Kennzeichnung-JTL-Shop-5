<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Portlets\AIPhilosophie;

use JTL\OPC\Portlet;
use JTL\Shop;
use Plugin\MGD_AI_Kennzeichnung\Infrastructure\Database\PhilosophyRepository;

/**
 * Gibt die zentral gepflegte KI-Philosophie in der aktuellen Shopsprache aus.
 *
 * Das Portlet besitzt absichtlich kein freies Textfeld. Dadurch kann der Inhalt
 * nur im geschützten Plugin-Backend gepflegt und an einer Stelle bereinigt
 * werden. Das Platzieren des Portlets veröffentlicht keine Seite automatisch.
 */
final class AIPhilosophie extends Portlet
{
    protected string $title = 'AI-Philosophie';
    protected string $group = 'content';
    protected bool $active = true;

    public function getSanitizedContent(): string
    {
        return (new PhilosophyRepository($this->db))->findForLocale(Shop::getLanguageCode());
    }
}
