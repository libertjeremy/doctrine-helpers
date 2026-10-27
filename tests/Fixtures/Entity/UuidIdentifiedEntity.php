<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use LibertJeremy\Doctrine\Helpers\Entity\Domain\Interfaces\UuidIdentifierAwareInterface;
use LibertJeremy\Doctrine\Helpers\Entity\Domain\Traits\UuidIdentifierTrait;

#[ORM\Entity]
class UuidIdentifiedEntity implements UuidIdentifierAwareInterface
{
    use UuidIdentifierTrait;
}
