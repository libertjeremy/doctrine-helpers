<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Repository\Join;

final readonly class JoinDefinition
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public string $column,
        public ?string $alias = null,
        public ?string $prefix = null,
        public ?string $condition = null,
        public array $parameters = [],
        public bool $autoPrefix = true,
    ) {
    }

    /** Same semantics as empty(): null, '' and '0' mean "no condition". */
    public function hasCondition(): bool
    {
        return !empty($this->condition);
    }
}
