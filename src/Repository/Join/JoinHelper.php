<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Repository\Join;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use LibertJeremy\Doctrine\Helpers\Repository\BuilderHelper;
use LibertJeremy\Doctrine\Helpers\Repository\Join\Exception\JoinConditionNotMergeableException;

final class JoinHelper
{
    /** Adds the join when its alias is free, otherwise merges its condition into the existing join of the same type. */
    public static function ensureJoin(QueryBuilder $builder, JoinType $type, JoinDefinition $join): void
    {
        $alias = self::resolveAlias($join);

        if (self::isAliasUsed($builder, $alias)) {
            if ($join->hasCondition()) {
                self::mergeConditionIntoExistingJoin($builder, $type, $alias, $join);
            }

            return;
        }

        self::addJoin($builder, $type, $alias, $join);
        self::bindParameters($builder, $join->parameters);
    }

    public static function isAliasUsed(QueryBuilder $builder, string $alias): bool
    {
        return \in_array($alias, $builder->getAllAliases(), true);
    }

    private static function resolveAlias(JoinDefinition $join): string
    {
        return $join->alias ?? BuilderHelper::buildPrefixForJoinColumnName($join->column);
    }

    private static function resolveJoinPath(QueryBuilder $builder, JoinDefinition $join): string
    {
        if (str_contains($join->column, '\\')) {
            return $join->column;
        }

        $prefix = $join->prefix ?? ($join->autoPrefix ? BuilderHelper::retrieveMainPrefix($builder) : null);

        return BuilderHelper::buildJoinColumnName($join->column, $prefix);
    }

    private static function addJoin(QueryBuilder $builder, JoinType $type, string $alias, JoinDefinition $join): void
    {
        $path = self::resolveJoinPath($builder, $join);
        $condition = $join->hasCondition() ? $join->condition : null;
        $conditionType = null === $condition ? null : Join::WITH;

        match ($type) {
            JoinType::Inner => $builder->innerJoin($path, $alias, $conditionType, $condition),
            JoinType::Left => $builder->leftJoin($path, $alias, $conditionType, $condition),
        };
    }

    private static function mergeConditionIntoExistingJoin(QueryBuilder $builder, JoinType $type, string $alias, JoinDefinition $join): void
    {
        /** @var array<string, list<Join>> $joinParts */
        $joinParts = $builder->getDQLPart('join');
        $existing = self::findJoin($joinParts, $alias) ?? throw JoinConditionNotMergeableException::fromRootAlias($alias);

        if ($type->value !== $existing->getJoinType()) {
            throw JoinConditionNotMergeableException::fromJoinTypeMismatch($alias, $existing->getJoinType(), $type->value);
        }

        $merged = new Join(
            $existing->getJoinType(),
            $existing->getJoin(),
            $alias,
            $existing->getConditionType() ?? Join::WITH,
            self::mergeConditions($existing->getCondition(), (string) $join->condition),
            $existing->getIndexBy()
        );

        self::replaceJoin($builder, $joinParts, $existing, $merged);
        self::bindParameters($builder, $join->parameters);
    }

    /**
     * @param array<string, list<Join>> $joinParts
     */
    private static function findJoin(array $joinParts, string $alias): ?Join
    {
        foreach ($joinParts as $joins) {
            foreach ($joins as $existing) {
                if ($alias === $existing->getAlias()) {
                    return $existing;
                }
            }
        }

        return null;
    }

    /**
     * Rebuilds the join part in the same order, under the same root aliases, with $replaced swapped for $replacement.
     *
     * @param array<string, list<Join>> $joinParts
     */
    private static function replaceJoin(QueryBuilder $builder, array $joinParts, Join $replaced, Join $replacement): void
    {
        $builder->resetDQLPart('join');

        foreach ($joinParts as $rootAlias => $joins) {
            foreach ($joins as $existing) {
                // Same call shape as QueryBuilder::innerJoin(); the ORM stub types add()'s array argument too narrowly.
                $builder->add('join', [$rootAlias => $existing === $replaced ? $replacement : $existing], true); // @phpstan-ignore argument.type
            }
        }
    }

    private static function mergeConditions(string|Comparison|Composite|Func|null $existingCondition, string $condition): string|Comparison|Composite|Func
    {
        if (null === $existingCondition || '' === (string) $existingCondition) {
            return $condition;
        }

        $existingParts = $existingCondition instanceof Andx ? $existingCondition->getParts() : [$existingCondition];

        foreach ($existingParts as $existingPart) {
            if ($condition === (string) $existingPart) {
                return $existingCondition;
            }
        }

        return new Andx([...$existingParts, $condition]);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private static function bindParameters(QueryBuilder $builder, array $parameters): void
    {
        foreach ($parameters as $name => $value) {
            $builder->setParameter($name, $value);
        }
    }
}
