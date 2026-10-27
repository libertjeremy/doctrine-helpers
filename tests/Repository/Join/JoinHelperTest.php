<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Tests\Repository\Join;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use LibertJeremy\Doctrine\Helpers\Repository\Join\Exception\JoinConditionNotMergeableException;
use LibertJeremy\Doctrine\Helpers\Repository\Join\JoinDefinition;
use LibertJeremy\Doctrine\Helpers\Repository\Join\JoinHelper;
use LibertJeremy\Doctrine\Helpers\Repository\Join\JoinType;
use LibertJeremy\Doctrine\Helpers\Tests\Fixtures\Entity\UuidIdentifiedEntity;
use LibertJeremy\Doctrine\Helpers\Tests\Support\EntityManagerFactory;
use PHPUnit\Framework\TestCase;

final class JoinHelperTest extends TestCase
{
    private const ROOT = 'SELECT company FROM LibertJeremy\Doctrine\Helpers\Tests\Fixtures\Entity\UuidIdentifiedEntity company';

    public function testInnerJoinsWithConditionAndParametersWhenAliasIsFree(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference', parameters: ['reference' => 'payroll']));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions WITH missions.reference = :reference', $builder->getDQL());
        self::assertCount(1, $builder->getParameters());
        self::assertSame('payroll', $builder->getParameter('reference')?->getValue());
    }

    public function testLeftJoinsWhenAliasIsFree(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Left, new JoinDefinition('missions', alias: 'missions'));

        self::assertSame(self::ROOT.' LEFT JOIN company.missions missions', $builder->getDQL());
        self::assertCount(0, $builder->getParameters());
    }

    public function testLeftJoinsWithConditionAndParametersWhenAliasIsFree(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Left, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference', parameters: ['reference' => 'payroll']));

        self::assertSame(self::ROOT.' LEFT JOIN company.missions missions WITH missions.reference = :reference', $builder->getDQL());
        self::assertSame('payroll', $builder->getParameter('reference')?->getValue());
    }

    public function testKeepsExistingJoinWhenNoConditionIsGiven(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.active = true'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions'));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions WITH missions.active = true', $builder->getDQL());
    }

    public function testKeepsExistingLeftJoinWhenInnerJoinWithoutConditionIsRequested(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Left, new JoinDefinition('missions', alias: 'missions'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions'));

        self::assertSame(self::ROOT.' LEFT JOIN company.missions missions', $builder->getDQL());
    }

    public function testKeepsExistingJoinAndBindsNoParameterWhenAliasExistsWithoutCondition(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', parameters: ['reference' => 'payroll']));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions', $builder->getDQL());
        self::assertCount(0, $builder->getParameters());
    }

    public function testMergesConditionAndBindsItsParametersIntoSameTypeJoin(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.active = true'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference', parameters: ['reference' => 'payroll']));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions WITH missions.active = true AND missions.reference = :reference', $builder->getDQL());
        self::assertCount(1, $builder->getParameters());
        self::assertSame('payroll', $builder->getParameter('reference')?->getValue());
    }

    public function testMergesConditionIntoFirstInnerJoin(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference', parameters: ['reference' => 'payroll']));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions WITH missions.reference = :reference', $builder->getDQL());
    }

    public function testMergesConditionIntoInnerJoinWhateverItsPosition(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Left, new JoinDefinition('owner', alias: 'owner'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference', parameters: ['reference' => 'payroll']));

        self::assertSame(self::ROOT.' LEFT JOIN company.owner owner INNER JOIN company.missions missions WITH missions.reference = :reference', $builder->getDQL());
        self::assertCount(1, $builder->getParameters());
        self::assertSame('payroll', $builder->getParameter('reference')?->getValue());
    }

    public function testMergesConditionIntoLeftJoinWhateverItsPosition(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Left, new JoinDefinition('owner', alias: 'owner'));
        JoinHelper::ensureJoin($builder, JoinType::Left, new JoinDefinition('missions', alias: 'missions'));
        JoinHelper::ensureJoin($builder, JoinType::Left, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference', parameters: ['reference' => 'payroll']));

        self::assertSame(self::ROOT.' LEFT JOIN company.owner owner LEFT JOIN company.missions missions WITH missions.reference = :reference', $builder->getDQL());
        self::assertCount(1, $builder->getParameters());
        self::assertSame('payroll', $builder->getParameter('reference')?->getValue());
    }

    public function testKeepsIdenticalConditionOnceOnRepeatedCall(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.company = company.id'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.company = company.id'));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions WITH missions.company = company.id', $builder->getDQL());
    }

    public function testParenthesizesExistingDisjunctionWhenMergingCondition(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.active = true OR missions.archived = false'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference', parameters: ['reference' => 'payroll']));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions WITH (missions.active = true OR missions.archived = false) AND missions.reference = :reference', $builder->getDQL());
    }

    public function testAddsOnePartToExistingConjunctionWithSeveralParts(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.active = true'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.code = :code'));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions WITH missions.active = true AND missions.reference = :reference AND missions.code = :code', $builder->getDQL());
    }

    public function testKeepsExistingConjunctionWhenOneOfItsPartsIsRequestedAgain(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.active = true'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.active = true'));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions WITH missions.active = true AND missions.reference = :reference', $builder->getDQL());
    }

    public function testThrowsWhenAliasIsLeftJoinedAndInnerJoinConditionIsGiven(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Left, new JoinDefinition('missions', alias: 'missions'));

        $this->expectException(JoinConditionNotMergeableException::class);
        $this->expectExceptionMessage('Alias "missions" is already joined with LEFT JOIN: a condition requested with INNER JOIN cannot be merged into it, use a distinct alias.');

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference', parameters: ['reference' => 'payroll']));
    }

    public function testThrowsWhenAliasIsInnerJoinedAndLeftJoinConditionIsGiven(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions'));

        $this->expectException(JoinConditionNotMergeableException::class);
        $this->expectExceptionMessage('Alias "missions" is already joined with INNER JOIN: a condition requested with LEFT JOIN cannot be merged into it, use a distinct alias.');

        JoinHelper::ensureJoin($builder, JoinType::Left, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference', parameters: ['reference' => 'payroll']));
    }

    public function testThrowsWhenAliasIsRootAliasAndConditionIsGiven(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        $this->expectException(JoinConditionNotMergeableException::class);
        $this->expectExceptionMessage('Alias "company" is a root alias of the query: a join condition cannot be added to it, use a distinct alias.');

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'company', condition: 'company.reference = :reference', parameters: ['reference' => 'payroll']));
    }

    public function testKeepsQuerySilentlyWhenAliasIsRootAliasAndNoConditionIsGiven(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'company'));

        self::assertSame(self::ROOT, $builder->getDQL());
        self::assertCount(0, $builder->getParameters());
    }

    public function testDerivesAliasFromColumnWhenAliasIsNull(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('Missions.reference'));

        self::assertSame(self::ROOT.' INNER JOIN company.Missions missions', $builder->getDQL());
    }

    public function testDerivesAliasFromFullyQualifiedClassName(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Left, new JoinDefinition('App\Entity\Mission', condition: 'mission.company = company'));

        self::assertSame(self::ROOT.' LEFT JOIN App\Entity\Mission mission WITH mission.company = company', $builder->getDQL());
    }

    public function testJoinsEntityClassWithoutPrefixWhenColumnIsFullyQualified(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Left, new JoinDefinition('App\Entity\Mission', prefix: 'ignored', condition: 'mission.company = company'));

        self::assertSame(self::ROOT.' LEFT JOIN App\Entity\Mission mission WITH mission.company = company', $builder->getDQL());
    }

    public function testUsesGivenPrefixInsteadOfRootAlias(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('reference', alias: 'reference', prefix: 'missions'));

        self::assertSame(self::ROOT.' INNER JOIN missions.reference reference', $builder->getDQL());
    }

    public function testKeepsColumnAsIsWhenAutoPrefixIsDisabled(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('company.missions', alias: 'missions', autoPrefix: false));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions', $builder->getDQL());
    }

    public function testExplicitPrefixWinsOverDisabledAutoPrefix(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('reference', alias: 'reference', prefix: 'missions', autoPrefix: false));

        self::assertSame(self::ROOT.' INNER JOIN missions.reference reference', $builder->getDQL());
    }

    public function testTreatsZeroStringConditionAsNoCondition(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: '0'));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions', $builder->getDQL());
    }

    public function testTreatsEmptyStringConditionAsNoCondition(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: ''));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions', $builder->getDQL());
    }

    public function testDoesNotMergeZeroStringConditionIntoExistingJoin(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.active = true'));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: '0', parameters: ['reference' => 'payroll']));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions WITH missions.active = true', $builder->getDQL());
        self::assertCount(0, $builder->getParameters());
    }

    public function testKeepsConditionTypeOfExistingJoinWhenMerging(): void
    {
        $builder = $this->createCompanyQueryBuilder();
        $builder->innerJoin('company.missions', 'missions', Join::ON, 'missions.active = true');

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.code = 1'));

        self::assertSame(self::ROOT.' INNER JOIN company.missions missions ON missions.active = true AND missions.code = 1', $builder->getDQL());
    }

    public function testOverwritesParameterOfSameNameWhenMergingCondition(): void
    {
        $builder = $this->createCompanyQueryBuilder();

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.reference = :reference', parameters: ['reference' => 'payroll']));
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions', condition: 'missions.code = :reference', parameters: ['reference' => 'invoice']));

        self::assertCount(1, $builder->getParameters());
        self::assertSame('invoice', $builder->getParameter('reference')?->getValue());
    }

    public function testIsAliasUsedIsTrueForRootAliasAndJoinAlias(): void
    {
        $builder = $this->createCompanyQueryBuilder();
        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions'));

        self::assertTrue(JoinHelper::isAliasUsed($builder, 'company'));
        self::assertTrue(JoinHelper::isAliasUsed($builder, 'missions'));
    }

    public function testIsAliasUsedIsFalseForUnknownAlias(): void
    {
        self::assertFalse(JoinHelper::isAliasUsed($this->createCompanyQueryBuilder(), 'missions'));
    }

    public function testThrowsWhenBuilderHasNoRootAliasAndAutoPrefixIsEnabled(): void
    {
        $builder = EntityManagerFactory::create()->createQueryBuilder();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No root alias found in the query builder.');

        JoinHelper::ensureJoin($builder, JoinType::Inner, new JoinDefinition('missions', alias: 'missions'));
    }

    private function createCompanyQueryBuilder(): QueryBuilder
    {
        return EntityManagerFactory::create()
            ->createQueryBuilder()
            ->select('company')
            ->from(UuidIdentifiedEntity::class, 'company');
    }
}
