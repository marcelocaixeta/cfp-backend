<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->preventTestingAgainstApplicationDatabase();
    }

    private function preventTestingAgainstApplicationDatabase(): void
    {
        if (! $this->app->runningUnitTests()) {
            throw new RuntimeException('Esta protecao deve rodar apenas durante a suite de testes.');
        }

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $host = (string) config("database.connections.{$connection}.host");

        if ($connection !== 'pgsql' || $host !== 'postgres_test' || ! str_ends_with($database, '_testing')) {
            throw new RuntimeException(
                "Banco de testes inseguro: conexao={$connection}, host={$host}, database={$database}. ".
                'Use o banco isolado cfp_backend_testing no servico postgres_test.'
            );
        }
    }
}
