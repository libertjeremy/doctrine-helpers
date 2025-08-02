<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Entity\Domain\Interfaces;

use LibertJeremy\Doctrine\Helpers\Entity\Domain\Traits\UuidIdentifierTrait;
use Symfony\Component\Uid\Uuid;

/**
 * @see UuidIdentifierTrait:class
 */
interface UuidIdentifierAwareInterface
{
    public function getId(): ?Uuid;
}
