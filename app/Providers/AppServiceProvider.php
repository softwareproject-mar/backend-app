<?php

namespace App\Providers;

use App\Cache\FirebirdDatabaseStore;
use App\Database\Query\Grammars\FirebirdUppercaseGrammar;
use App\Database\Query\Processors\FirebirdReturningProcessor;
use App\Models\PersonalAccessToken;
use Danidoble\Firebird\FirebirdConnection;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->booted(function () {
            Connection::resolverFor('firebird', function ($connection, $database, $tablePrefix, $config) {
                $conn = new FirebirdConnection($connection, $database, $tablePrefix, $config);
                $grammar = new FirebirdUppercaseGrammar($conn);
                if (method_exists($grammar, 'setConnection')) {
                    $grammar = $grammar->setConnection($conn);
                }
                $conn->setQueryGrammar($grammar);
                $conn->setPostProcessor(new FirebirdReturningProcessor);

                return $conn;
            });
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        $this->app->make(CacheManager::class)->extend('database', function ($app, array $config) {
            $manager = $app->make(CacheManager::class);
            $connection = $app['db']->connection($config['connection'] ?? null);
            $prefix = $config['prefix'] ?? $app['config']['cache.prefix'];

            $storeClass = $connection->getDriverName() === 'firebird'
                ? FirebirdDatabaseStore::class
                : DatabaseStore::class;

            $store = new $storeClass(
                $connection,
                $config['table'],
                $prefix,
                $config['lock_table'] ?? 'cache_locks',
                $config['lock_lottery'] ?? [2, 100],
                $config['lock_timeout'] ?? 86400,
            );

            return $manager->repository(
                $store->setLockConnection(
                    $app['db']->connection($config['lock_connection'] ?? $config['connection'] ?? null)
                ),
                $config
            );
        });
    }
}
