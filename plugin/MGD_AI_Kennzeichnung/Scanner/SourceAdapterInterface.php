<?php

declare(strict_types=1);

namespace Plugin\MGD_AI_Kennzeichnung\Scanner;

use Plugin\MGD_AI_Kennzeichnung\Domain\AssetSource;

/** Vertrag einer begrenzt und deterministisch lesenden JTL-Bildquelle. */
interface SourceAdapterInterface
{
    /** @return iterable<mixed> Der Service validiert jede Adaptergrenze erneut. */
    public function scan(int $offset, int $limit): iterable;

    public function source(): AssetSource;
}
