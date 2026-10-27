<?php

declare(strict_types=1);

namespace LibertJeremy\Doctrine\Helpers\Tests\Repository\Join;

use LibertJeremy\Doctrine\Helpers\Repository\Join\JoinDefinition;
use PHPUnit\Framework\TestCase;

final class JoinDefinitionTest extends TestCase
{
    public function testHasNoConditionWhenConditionIsNull(): void
    {
        self::assertFalse((new JoinDefinition('missions'))->hasCondition());
    }

    public function testHasNoConditionWhenConditionIsEmptyString(): void
    {
        self::assertFalse((new JoinDefinition('missions', condition: ''))->hasCondition());
    }

    public function testHasNoConditionWhenConditionIsZeroString(): void
    {
        self::assertFalse((new JoinDefinition('missions', condition: '0'))->hasCondition());
    }

    public function testHasConditionWhenConditionIsNotEmpty(): void
    {
        self::assertTrue((new JoinDefinition('missions', condition: 'x'))->hasCondition());
    }

    public function testDefaultsMatchTheHistoricalSignature(): void
    {
        $join = new JoinDefinition('missions');

        self::assertSame('missions', $join->column);
        self::assertNull($join->alias);
        self::assertNull($join->prefix);
        self::assertNull($join->condition);
        self::assertSame([], $join->parameters);
        self::assertTrue($join->autoPrefix);
    }
}
