<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Tests\Support;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Types\UuidType;

/** Real EntityManager on an in-memory SQLite database, mapping the entities of tests/Fixtures/Entity. */
final class EntityManagerFactory
{
    public static function create(): EntityManager
    {
        if (!Type::hasType(UuidType::NAME)) {
            Type::addType(UuidType::NAME, UuidType::class);
        }

        $configuration = new Configuration();
        $configuration->setMetadataDriverImpl(new AttributeDriver([\dirname(__DIR__).'/Fixtures/Entity']));
        $configuration->setProxyDir(sys_get_temp_dir());
        $configuration->setProxyNamespace('LibertJeremy\Doctrine\Helpers\Tests\Proxies');

        if (\PHP_VERSION_ID >= 80400) {
            $configuration->enableNativeLazyObjects(true);
        }

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $configuration);

        return new EntityManager($connection, $configuration);
    }

    public static function createWithSchema(): EntityManager
    {
        $entityManager = self::create();
        (new SchemaTool($entityManager))->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        return $entityManager;
    }
}
