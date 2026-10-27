<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Repository\Join;

/** Values are the ones returned by Doctrine\ORM\Query\Expr\Join::getJoinType(). */
enum JoinType: string
{
    case Inner = 'INNER';
    case Left = 'LEFT';
}
