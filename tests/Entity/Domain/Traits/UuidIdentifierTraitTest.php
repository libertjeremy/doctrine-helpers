<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Tests\Entity\Domain\Traits;

use Doctrine\ORM\Mapping\ClassMetadata;
use LibertJeremy\Doctrine\Helpers\Tests\Fixtures\Entity\UuidIdentifiedEntity;
use LibertJeremy\Doctrine\Helpers\Tests\Support\EntityManagerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

final class UuidIdentifierTraitTest extends TestCase
{
    public function testNewEntityHasNoIdentifierBeforePersist(): void
    {
        self::assertNull((new UuidIdentifiedEntity())->getId());
    }

    public function testGetIdReturnsTheAssignedUuid(): void
    {
        $uuid = Uuid::fromString('0192d0f4-5b7a-7c3e-9f10-2a6b8c4d1e3f');
        $entity = new UuidIdentifiedEntity();

        (new \ReflectionProperty($entity, 'id'))->setValue($entity, $uuid);

        self::assertSame($uuid, $entity->getId());
    }

    public function testIdIsTheOnlyIdentifierField(): void
    {
        $metadata = EntityManagerFactory::create()->getClassMetadata(UuidIdentifiedEntity::class);

        self::assertSame(['id'], $metadata->getIdentifierFieldNames());
    }

    public function testIdIsGeneratedBySymfonyUuidGenerator(): void
    {
        $metadata = EntityManagerFactory::create()->getClassMetadata(UuidIdentifiedEntity::class);

        self::assertSame(ClassMetadata::GENERATOR_TYPE_CUSTOM, $metadata->generatorType);
        self::assertSame(['class' => UuidGenerator::class], $metadata->customGeneratorDefinition);
        self::assertInstanceOf(UuidGenerator::class, $metadata->idGenerator);
    }

    public function testIdColumnIsAUniqueUuidColumnDefaultingToDatabaseUuid(): void
    {
        $fieldMapping = EntityManagerFactory::create()->getClassMetadata(UuidIdentifiedEntity::class)->getFieldMapping('id');

        self::assertSame(UuidType::NAME, $fieldMapping->type);
        self::assertSame(36, $fieldMapping->length);
        self::assertTrue($fieldMapping->unique);
        self::assertSame(['default' => 'UUID()'], $fieldMapping->options);
    }

    public function testPersistedEntityReceivesAUuid(): void
    {
        $entityManager = EntityManagerFactory::createWithSchema();
        $entity = new UuidIdentifiedEntity();

        $entityManager->persist($entity);
        $entityManager->flush();

        self::assertInstanceOf(Uuid::class, $entity->getId());
    }

    public function testPersistedEntityIsFoundBackByItsUuid(): void
    {
        $entityManager = EntityManagerFactory::createWithSchema();
        $entity = new UuidIdentifiedEntity();

        $entityManager->persist($entity);
        $entityManager->flush();
        $uuid = $entity->getId();
        self::assertInstanceOf(Uuid::class, $uuid);
        $entityManager->clear();

        $reloaded = $entityManager->find(UuidIdentifiedEntity::class, $uuid);

        self::assertInstanceOf(UuidIdentifiedEntity::class, $reloaded);
        self::assertNotSame($entity, $reloaded);
        self::assertTrue($uuid->equals($reloaded->getId()));
    }

    public function testTwoPersistedEntitiesReceiveDistinctUuids(): void
    {
        $entityManager = EntityManagerFactory::createWithSchema();
        $entityOne = new UuidIdentifiedEntity();
        $entityTwo = new UuidIdentifiedEntity();

        $entityManager->persist($entityOne);
        $entityManager->persist($entityTwo);
        $entityManager->flush();
        $uuidOne = $entityOne->getId();

        self::assertInstanceOf(Uuid::class, $uuidOne);
        self::assertInstanceOf(Uuid::class, $entityTwo->getId());
        self::assertFalse($uuidOne->equals($entityTwo->getId()));
    }
}
