<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Tests\Repository;

use Doctrine\ORM\QueryBuilder;
use LibertJeremy\Doctrine\Helpers\Repository\BuilderHelper;
use LibertJeremy\Doctrine\Helpers\Tests\Fixtures\Entity\UuidIdentifiedEntity;
use LibertJeremy\Doctrine\Helpers\Tests\Support\EntityManagerFactory;
use PHPUnit\Framework\TestCase;

final class BuilderHelperTest extends TestCase
{
    public function testBuildColumnFallsBackToRootAliasWhenNoPrefixGiven(): void
    {
        self::assertSame('u.name', BuilderHelper::buildColumn($this->builder('u'), 'name'));
    }

    public function testBuildColumnUsesFirstRootAliasWhenSeveralRoots(): void
    {
        self::assertSame('u.name', BuilderHelper::buildColumn($this->builder('u', 'g'), 'name'));
    }

    public function testBuildColumnUsesExplicitPrefixOverRootAlias(): void
    {
        self::assertSame('a.city', BuilderHelper::buildColumn($this->builder('u'), 'city', 'a'));
    }

    public function testBuildColumnKeepsQualifiedColumnUntouched(): void
    {
        self::assertSame('a.city', BuilderHelper::buildColumn($this->builder('u'), 'a.city'));
    }

    public function testBuildColumnWithExplicitPrefixDoesNotNeedARootAlias(): void
    {
        self::assertSame('a.city', BuilderHelper::buildColumn($this->builder(), 'city', 'a'));
    }

    public function testBuildColumnThrowsWhenNoPrefixAndBuilderHasNoRootAlias(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No root alias found in the query builder.');

        BuilderHelper::buildColumn($this->builder(), 'name');
    }

    public function testBuildColumnNamePrefixesUnqualifiedColumn(): void
    {
        self::assertSame('u.name', BuilderHelper::buildColumnName('name', 'u'));
    }

    public function testBuildColumnNameKeepsSingleDotQualifiedColumn(): void
    {
        self::assertSame('a.name', BuilderHelper::buildColumnName('a.name', 'u'));
    }

    public function testBuildColumnNameKeepsMultiDotQualifiedColumn(): void
    {
        self::assertSame('a.b.name', BuilderHelper::buildColumnName('a.b.name', 'u'));
    }

    public function testBuildColumnNamePrefixesEmptyColumn(): void
    {
        self::assertSame('u.', BuilderHelper::buildColumnName('', 'u'));
    }

    public function testBuildColumnNamePrefixesColumnWithEmptyPrefix(): void
    {
        self::assertSame('.name', BuilderHelper::buildColumnName('name', ''));
    }

    public function testBuildJoinColumnNameReturnsColumnWhenPrefixIsNull(): void
    {
        self::assertSame('address', BuilderHelper::buildJoinColumnName('address', null));
    }

    public function testBuildJoinColumnNameReturnsColumnWhenPrefixIsEmpty(): void
    {
        self::assertSame('address', BuilderHelper::buildJoinColumnName('address', ''));
    }

    public function testBuildJoinColumnNameTreatsStringZeroPrefixAsEmpty(): void
    {
        self::assertSame('address', BuilderHelper::buildJoinColumnName('address', '0'));
    }

    public function testBuildJoinColumnNameReturnsQualifiedColumnWhenPrefixIsNull(): void
    {
        self::assertSame('address.city', BuilderHelper::buildJoinColumnName('address.city', null));
    }

    public function testBuildJoinColumnNamePrefixesPlainColumn(): void
    {
        self::assertSame('u.address', BuilderHelper::buildJoinColumnName('address', 'u'));
    }

    public function testBuildJoinColumnNameKeepsFirstSegmentOfQualifiedColumn(): void
    {
        self::assertSame('u.address', BuilderHelper::buildJoinColumnName('address.city', 'u'));
    }

    public function testBuildJoinColumnNameKeepsFirstSegmentOfMultiDotColumn(): void
    {
        self::assertSame('u.address', BuilderHelper::buildJoinColumnName('address.city.name', 'u'));
    }

    public function testBuildJoinColumnNameReturnsColumnWhenPrefixIsOmitted(): void
    {
        self::assertSame('address', BuilderHelper::buildJoinColumnName('address'));
    }

    public function testBuildPrefixForJoinColumnNameTakesLastNamespaceSegmentLowercasingFirstLetter(): void
    {
        self::assertSame('userAddress', BuilderHelper::buildPrefixForJoinColumnName('App\Entity\UserAddress'));
    }

    public function testBuildPrefixForJoinColumnNameHandlesLeadingBackslash(): void
    {
        self::assertSame('userAddress', BuilderHelper::buildPrefixForJoinColumnName('\UserAddress'));
    }

    public function testBuildPrefixForJoinColumnNameGivesBackslashBranchPriorityOverDot(): void
    {
        self::assertSame('user.address', BuilderHelper::buildPrefixForJoinColumnName('App\Entity\User.address'));
    }

    public function testBuildPrefixForJoinColumnNameTakesSegmentBeforeFirstDotLowercasingFirstLetter(): void
    {
        self::assertSame('address', BuilderHelper::buildPrefixForJoinColumnName('Address.city'));
    }

    public function testBuildPrefixForJoinColumnNameTakesSegmentBeforeFirstDotOfMultiDotColumn(): void
    {
        self::assertSame('address', BuilderHelper::buildPrefixForJoinColumnName('address.city.name'));
    }

    public function testBuildPrefixForJoinColumnNameLowercasesFirstLetterOfPlainName(): void
    {
        self::assertSame('userAddress', BuilderHelper::buildPrefixForJoinColumnName('UserAddress'));
    }

    public function testBuildPrefixForJoinColumnNameReturnsAlreadyLowercasePlainName(): void
    {
        self::assertSame('address', BuilderHelper::buildPrefixForJoinColumnName('address'));
    }

    public function testBuildPrefixForJoinColumnNameReturnsEmptyStringWhenColumnStartsWithDot(): void
    {
        self::assertSame('', BuilderHelper::buildPrefixForJoinColumnName('.city'));
    }

    public function testBuildPrefixForJoinColumnNameReturnsEmptyStringForEmptyColumn(): void
    {
        self::assertSame('', BuilderHelper::buildPrefixForJoinColumnName(''));
    }

    public function testRetrieveMainPrefixReturnsTheRootAlias(): void
    {
        self::assertSame('u', BuilderHelper::retrieveMainPrefix($this->builder('u')));
    }

    public function testRetrieveMainPrefixReturnsTheFirstRootAliasWhenSeveralRoots(): void
    {
        self::assertSame('u', BuilderHelper::retrieveMainPrefix($this->builder('u', 'g')));
    }

    public function testRetrieveMainPrefixThrowsWhenBuilderHasNoRootAlias(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No root alias found in the query builder.');

        BuilderHelper::retrieveMainPrefix($this->builder());
    }

    private function builder(string ...$aliases): QueryBuilder
    {
        $builder = EntityManagerFactory::create()->createQueryBuilder();

        foreach ($aliases as $alias) {
            $builder->from(UuidIdentifiedEntity::class, $alias);
        }

        return $builder;
    }
}
