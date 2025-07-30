<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Entity\Domain\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

trait UuidIdentifierTrait
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, length: 36, unique: true, options: ["default" => "UUID()"])]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }
}
