<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Repository;

use Doctrine\ORM\QueryBuilder;

class BuilderHelper
{
    public static function buildColumn(QueryBuilder $builder, string $column, ?string $prefix = null): string
    {
        return self::buildColumnName($column, $prefix ?? self::retrieveMainPrefix($builder));
    }

    public static function buildColumnName(string $column, string $prefix): string
    {
        if (true === str_contains($column, '.')) {
            return $column;
        }

        return $prefix.'.'.$column;
    }

    public static function buildJoinColumnName(string $column, ?string $prefix = null): string
    {
        if (empty($prefix)) {
            return $column;
        }

        if (false !== str_contains($column, '.')) {
            $columnExploded = explode('.', $column);

            return $prefix.'.'.$columnExploded[0];
        }

        return $prefix.'.'.$column;
    }

    public static function buildPrefixForJoinColumnName(string $column): string
    {
        if (false !== ($lastSlashPosition = strrpos($column, '\\'))) {
            return lcfirst(substr($column, -(\strlen($column) - $lastSlashPosition) + 1));
        }

        if (false !== ($dotPosition = strpos($column, '.'))) {
            return lcfirst(substr($column, 0, $dotPosition));
        }

        return lcfirst($column);
    }

    public static function retrieveMainPrefix(QueryBuilder $builder): string
    {
        if (0 === count($rootAliases = $builder->getRootAliases())) {
            throw new \RuntimeException('No root alias found in the query builder.');
        }

        return $rootAliases[0];
    }
}
