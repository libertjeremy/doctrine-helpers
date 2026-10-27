<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Repository\Join\Exception;

/**
 * Une condition de jointure ne peut pas être ajoutée à l'alias demandé sans changer le sens de la requête :
 * l'alias est déjà joint avec un autre type de jointure, ou c'est un alias racine.
 */
final class JoinConditionNotMergeableException extends \LogicException
{
    public static function fromJoinTypeMismatch(string $alias, string $existingJoinType, string $requestedJoinType): self
    {
        return new self(\sprintf('Alias "%s" is already joined with %s JOIN: a condition requested with %s JOIN cannot be merged into it, use a distinct alias.', $alias, $existingJoinType, $requestedJoinType));
    }

    public static function fromRootAlias(string $alias): self
    {
        return new self(\sprintf('Alias "%s" is a root alias of the query: a join condition cannot be added to it, use a distinct alias.', $alias));
    }
}
