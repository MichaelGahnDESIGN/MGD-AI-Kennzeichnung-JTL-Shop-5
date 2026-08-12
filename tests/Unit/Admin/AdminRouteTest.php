<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Plugin\MGD_AI_Kennzeichnung\Admin\ViewModel\AdminRoute;
use Plugin\MGD_AI_Kennzeichnung\Admin\Exception\ValidationException;

final class AdminRouteTest extends TestCase
{
    #[Test]
    public function fachparameter_koennen_die_vertrauenswuerdige_jtl_route_nicht_ueberschreiben(): void
    {
        $query = (new AdminRoute(17, 9))->query([
            'kPlugin' => '666',
            'kPluginAdminMenu' => '777',
            'view' => 'list',
        ]);

        self::assertSame('?kPlugin=17&kPluginAdminMenu=9&view=list', $query);
    }

    #[Test]
    public function unbekannte_query_parameter_werden_nicht_in_adminlinks_uebernommen(): void
    {
        $this->expectException(ValidationException::class);
        (new AdminRoute(17, 9))->query(['redirect' => 'https://fremd.example']);
    }
}
