<?php

namespace z4kn4fein\SemVer\Tests;

use PHPUnit\Framework\TestCase;
use z4kn4fein\SemVer\Constraints\Condition;
use z4kn4fein\SemVer\Constraints\Constraint;
use z4kn4fein\SemVer\Constraints\Op;
use z4kn4fein\SemVer\Constraints\Range;
use z4kn4fein\SemVer\SemverException;
use z4kn4fein\SemVer\Version;

class ConstraintTest extends TestCase
{
    /**
     * @dataProvider dataInvalid
     */
    public function testInvalidConstraints(string $constraint)
    {
        $this->expectException(SemverException::class);
        Constraint::parse($constraint);
    }

    /**
     * @dataProvider dataSatisfies
     */
    public function testSatisfies(string $constraint, string $version)
    {
        $this->assertTrue(Version::satisfies($version, $constraint));
    }

    /**
     * @dataProvider dataNotSatisfies
     */
    public function testNotSatisfies(string $constraint, string $version)
    {
        $this->assertFalse(Version::satisfies($version, $constraint));
    }

    /**
     * @dataProvider dataParse
     */
    public function testParse(string $constraint, string $parsed)
    {
        $this->assertEquals($parsed, (string)Constraint::parse($constraint));
    }

    public function testConditions()
    {
        $version = Version::parse("1.0.0");
        $this->assertEquals("!=1.0.0", (new Condition(Op::EQUAL, $version))->opposite());
        $this->assertEquals("=1.0.0", (new Condition(Op::NOT_EQUAL, $version))->opposite());
        $this->assertEquals(">=1.0.0", (new Condition(Op::LESS_THAN, $version))->opposite());
        $this->assertEquals(">1.0.0", (new Condition(Op::LESS_THAN_OR_EQUAL, $version))->opposite());
        $this->assertEquals("<=1.0.0", (new Condition(Op::GREATER_THAN, $version))->opposite());
        $this->assertEquals("<1.0.0", (new Condition(Op::GREATER_THAN_OR_EQUAL, $version))->opposite());
    }

    public function testRanges()
    {
        $start = new Condition(Op::GREATER_THAN, Version::parse("1.0.0"));
        $end = new Condition(Op::LESS_THAN, Version::parse("1.1.0"));
        $this->assertEquals("<=1.0.0 || >=1.1.0", (new Range($start, $end, Op::EQUAL))->opposite());
        $this->assertEquals(">1.0.0 <1.1.0", (new Range($start, $end, Op::NOT_EQUAL))->opposite());
        $this->assertEquals(">1.0.0", (new Range($start, $end, Op::LESS_THAN))->opposite());
        $this->assertEquals(">=1.1.0", (new Range($start, $end, Op::LESS_THAN_OR_EQUAL))->opposite());
        $this->assertEquals("<1.1.0", (new Range($start, $end, Op::GREATER_THAN))->opposite());
        $this->assertEquals("<=1.0.0", (new Range($start, $end, Op::GREATER_THAN_OR_EQUAL))->opposite());
    }

    public function dataInvalid(): array
    {
        return [
            ["a"],
            ["||"],
            [">1.a"],
            ["1.1-3"],
            [">0-alpha"],
            [">=0.0-0"],
            [">=1.2a"],
        ];
    }

    public function dataSatisfies(): array
    {
        return [
            ["<\t1.0.0", "0.1.2"],
            ["1.2.3", "1.2.3"],
            ["=1.2.3", "1.2.3"],
            ["!=1.2.3", "1.2.4"],
            ["1.0.0 - 2.0.0", "1.2.3"],
            ["^1.2.3+build", "1.2.3"],
            ["^1.2.3+build", "1.3.0"],
            ["x - 1.0.0", "0.9.7"],
            ["x - 1.x", "0.9.7"],
            ["1.0.0 - x", "1.9.7"],
            ["1.x - x", "1.9.7"],
            ["1.1 - 2", "1.1.1"],
            ["1 - 2", "2.0.0-alpha"],
            ["1 - 2", "1.0.0"],
            ["1.0 - 2", "1.0.0"],
            ["1.2.3-alpha+beta - 2.4.5-alpha+beta", "1.2.3"],
            ["1.2.3-alpha+beta - 2.4.5-alpha+beta", "1.2.3-alpha.2"],
            ["1.2.3-alpha+beta - 2.4.5-alpha+beta", "2.4.5-alpha"],
            ["1.2.3+beta - 2.4.3+beta", "1.2.3"],
            ["1.0.0", "1.0.0"],
            [">=1.0.0", "1.0.0"],
            [">=1.0.0", "1.0.1"],
            [">=1.0.0", "1.1.0"],
            [">1.0.0", "1.0.1"],
            [">1.0.0", "1.1.0"],
            ["<=2.0.0", "2.0.0"],
            ["<=2.0.0", "1.9.9"],
            ["<=2.0.0", "0.1.2"],
            ["<2.0.0", "1.9.9"],
            ["<2.0.0", "0.1.2"],
            [">= 1.0.0", "1.0.0"],
            [">=  1.0.0", "1.0.1"],
            [">=   1.0.0", "1.1.0"],
            ["> 1.0.0", "1.0.1"],
            [">  1.0.0", "1.1.0"],
            ["<=   2.0.0", "2.0.0"],
            ["<= 2.0.0", "1.9.9"],
            ["<=  2.0.0", "0.1.2"],
            ["<    2.0.0", "1.9.9"],
            [">=0.1.2", "0.1.2"],
            [">1.1 <2", "1.2.1"],
            ["0.1.2 || 1.2.4", "1.2.4"],
            [">=0.1.2 || <0.0.1", "0.0.0"],
            [">=0.1.2 || <0.0.1", "0.1.2"],
            [">=0.1.2 || <0.0.1", "0.1.3"],
            [">=1.1 <2 !=1.2.3 || > 3", "4.1.2"],
            [">=1.1 <2 !=1.2.3 || >= 3", "3.0.0"],
            [">=1", "1.0.0"],
            [">= 1", "1.0.0"],
            ["<1.2", "1.1.1"],
            ["< 1.2", "1.1.1"],
            ["=0.7.x", "0.7.2"],
            ["<=0.7.x", "0.7.2"],
            [">=0.7.x", "0.7.2"],
            ["<=0.7.x", "0.6.2"],
            ["2.x.x", "2.1.3"],
            ["1.2.x", "1.2.3"],
            ["1.2.x || 2.x", "2.1.3"],
            ["1.2.x || 2.x", "1.2.3"],
            ["4.1", "4.1.0"],
            ["4.1.x", "4.1.3"],
            ["1.x", "1.4.0"],
            ["x", "1.2.3"],
            ["2.*.*", "2.1.3"],
            ["1.2.*", "1.2.3"],
            ["1.2.* || 2.*", "2.1.3"],
            ["1.2.* || 2.*", "1.2.3"],
            ["*", "1.2.3"],
            [">=*", "0.2.4"],
            ["*", "1.0.0-beta"],
            ["2", "2.1.2"],
            ["2.3", "2.3.1"],
            ["~0.0.1", "0.0.1"],
            ["~0.0.1", "0.0.2"],
            ["~x", "0.0.9"],
            ["~2", "2.0.9"],
            ["~2.4", "2.4.0"],
            ["~2.4", "2.4.5"],
            ["~>3.2.1", "3.2.2"],
            ["~1", "1.2.3"],
            ["~>1", "1.2.3"],
            ["~> 1", "1.2.3"],
            ["~1.0", "1.0.2"],
            ["~ 1.0", "1.0.2"],
            ["~ 1.0.3", "1.0.12"],
            ["~ 1.0.3-alpha", "1.0.12"],
            ["~0.5.4-alpha", "0.5.5"],
            ["~0.5.4-alpha", "0.5.4"],
            ["~1.2.1 >=1.2.3", "1.2.3"],
            ["~1.2.1 =1.2.3", "1.2.3"],
            ["~1.2.1 1.2.3", "1.2.3"],
            ["~1.2.1 >=1.2.3 1.2.3", "1.2.3"],
            ["~1.2.1 1.2.3 >=1.2.3", "1.2.3"],
            ["~1.2.1 1.2.3", "1.2.3"],
            ["~*", "2.1.1"],
            ["~1", "1.3.5"],
            ["~1.x", "1.3.5"],
            ["~1.3.5-alpha", "1.3.5-beta"],
            ["~1.x", "1.2.3"],
            ["~1.1", "1.1.1"],
            ["~1.2.3", "1.2.5"],
            ["~0.0.0", "0.0.1"],
            ["~1.2.3-beta.2", "1.2.4-beta.2"],
            [">=1.2.1 1.2.3", "1.2.3"],
            ["1.2.3 >=1.2.1", "1.2.3"],
            [">=1.2.3 >=1.2.1", "1.2.3"],
            [">=1.2.1 >=1.2.3", "1.2.3"],
            [">=1.2", "1.2.8"],
            ["^1.2.3", "1.8.1"],
            ["^0.1.2", "0.1.2"],
            ["^0.1", "0.1.2"],
            ["^0.0.1", "0.0.1"],
            ["^1.2", "1.4.2"],
            ["^1.2 ^1", "1.4.2"],
            ["^1.2.3-alpha", "1.2.3-alpha"],
            ["^1.2.0-alpha", "1.2.0-alpha"],
            ["^0.0.1-alpha", "0.0.1-beta"],
            ["^0.0.1-alpha", "0.0.1"],
            ["^0.1.1-alpha", "0.1.1-beta"],
            ["^x", "1.2.3"],
            ["<=7.x", "7.9.9"],
            ["2.x", "2.0.0"],
            ["2.x", "2.1.0-alpha.0"],
            ["1.1.x", "1.1.0"],
            ["1.1.x", "1.1.1-a"],
            ["^1.0.0-0", "1.0.1-beta"],
            ["^1.0.0-beta", "1.0.1-beta"],
            ["^1.0.0", "1.0.1-beta"],
            ["^1.0.0", "1.1.0-beta"],
            ["^1.2.3", "1.8.9"],
            ["^1.2.0-alpha.0", "1.2.1-alpha.0"],
            ["^1.2.0-alpha.0", "1.2.1-alpha.1"],
            ["^1.2", "1.8.9"],
            ["^1", "1.8.9"],
            ["^0.2.3", "0.2.5"],
            ["^0.2", "0.2.5"],
            ["^0.0.3", "0.0.3"],
            ["^0.0", "0.0.3"],
            ["^0", "0.2.3"],
            ["^0.2.3-beta.2", "0.2.3-beta.4"],
            ["^1.1", "1.1.1"],
            ["^1.x", "1.1.1"],
            ["^1.1.0", "1.1.1-alpha.1"],
            ["^1.1.1-alpha", "1.1.1-beta"],
            ["^0.1.2-alpha.1", "0.1.2-alpha.1"],
            ["^0.1.2-alpha.1", "0.1.3-alpha.1"],
            ["^0.0.1", "0.0.1"],
            ["=0.7.x", "0.7.0"],
            [">=0.7.x", "0.7.0"],
            ["<=0.7.x", "0.7.0"],
            [">=1.0.0 <=1.1.0", "1.1.0-alpha"],
            ["= 2.0", "2.0.0"],
            ["!=1.1", "1.0.0"],
            ["!=1.1", "1.2.3"],
            ["!=1.x", "2.1.0"],
            ["!=1.x", "1.0.0-alpha"],
            ["!=1.1.x", "1.0.0"],
            ["!=1.1.x", "1.2.3"],
            [">=1.1", "4.1.0"],
            ["<=1.1", "1.1.0"],
            ["<=1.1", "0.1.0"],
            [">=1.1", "1.1.0"],
            ["<=1.1", "1.1.1"],
            ["<=1.x", "1.1.0"],
            [">1.1", "4.1.0"],
            ["<1.1", "0.1.0"],
            ["<2.x", "1.1.1"],
            ["<1.2.x", "1.1.1"],
        ];
    }

    public function dataNotSatisfies(): array
    {
        return [
            ["~1.2.3-alpha.2", "1.3.4-alpha.2"],
            ["^1.2.3", "2.8.9"],
            ["^1.2.3", "1.2.1"],
            ["^1.1.0", "2.1.0"],
            ["^1.2.0", "2.2.1"],
            ["^1.2.0-alpha.2", "1.2.0-alpha.1"],
            ["^1.2", "2.8.9"],
            ["^1", "2.8.9"],
            ["^0.2.3", "0.5.6"],
            ["^0.2", "0.5.6"],
            ["^0.0.3", "0.0.4"],
            ["^0.0", "0.1.4"],
            ["^0.0", "1.0.4"],
            ["^0", "1.1.4"],
            ["^0.0.1", "0.0.2-alpha"],
            ["^0.0.1", "0.0.2"],
            ["^1.2.3", "2.0.0-alpha"],
            ["^1.2.3", "1.2.2"],
            ["^1.2", "1.1.9"],
            ["^1.0.0", "1.0.0-alpha"],
            ["^1.0.0", "2.0.0-alpha"],
            ["^1.2.3-beta", "2.0.0"],
            ["^1.0.0", "2.0.0-alpha"],
            ["^1.0.0", "2.0.0-alpha"],
            ["^1.2.3+build", "2.0.0"],
            ["^1.2.3+build", "1.2.0"],
            ["^1.2.3", "1.2.3-beta"],
            ["^1.2", "1.2.0-beta"],
            ["^1.1", "1.1.0-alpha"],
            ["^1.1.1-beta", "1.1.1-alpha"],
            ["^1.1", "3.0.0"],
            ["^2.x", "1.1.1"],
            ["^1.x", "2.1.1"],
            ["^0.0.1", "0.1.3"],
            ["1 - 2", "3.0.0-alpha"],
            ["1 - 2", "1.0.0-alpha"],
            ["1.0 - 2", "1.0.0-alpha"],
            ["1.0.0 - 2.0.0", "2.2.3"],
            ["1.2.3+alpha - 2.4.3+alpha", "1.2.3-alpha.1"],
            ["1.2.3+alpha - 2.4.3-alpha", "2.4.3-alpha.1"],
            ["1.1.x", "1.0.0-alpha"],
            ["1.1.x", "1.1.0-alpha"],
            ["1.1.x", "1.2.0-alpha"],
            ["1.1.x", "1.2.0-alpha"],
            ["1.1.x", "1.0.0-alpha"],
            ["1.x", "1.0.0-alpha"],
            ["1.x", "0.0.0-alpha"],
            ["1.x", "2.0.0-alpha"],
            [">1.1", "1.1.0"],
            ["<1.1", "1.1.0"],
            ["<1.1", "1.1.1"],
            ["<1.x", "1.1.1"],
            ["<1.x", "2.1.1"],
            ["<1.1.x", "1.2.1"],
            ["<1.1.x", "1.1.1"],
            [">=1.1", "0.0.9"],
            ["<=2.x", "3.1.0"],
            ["<=1.1.x", "1.2.1"],
            [">1.1 <2", "1.1.1"],
            [">1.1 <3", "4.3.2"],
            [">=1.1 <2 !=1.1.1", "1.1.1"],
            [">=1.1 <2 !=1.1.1 || > 3", "1.1.1"],
            [">=1.1 <2 !=1.1.1 || > 3", "3.1.2"],
            [">=1.1 <2 !=1.1.1 || > 3", "3.0.0"],
            ["~1", "2.1.1"],
            ["~1", "2.0.0-alpha"],
            ["~1.x", "2.1.1"],
            ["~1.x", "2.0.0-alpha"],
            ["~1.3.6-alpha", "1.3.5-beta"],
            ["~1.3.5-beta", "1.3.5-alpha"],
            ["~1.2.3", "1.2.2"],
            ["~1.2.3", "1.3.2"],
            ["~1.1", "1.2.3"],
            ["~1.3", "2.4.5"],
            [">1.2", "1.2.0"],
            ["<=1.2.3", "1.2.4-beta"],
            ["^1.2.3", "1.2.3-beta"],
            ["=0.7.x", "0.7.0-alpha"],
            [">=0.7.x", "0.7.0-alpha"],
            ["<=0.7.x", "0.8.0-alpha"],
            ["1", "1.0.0-beta"],
            ["<1", "1.0.1"],
            ["< 1", "1.0.1-beta"],
            ["1.0.0", "1.0.1"],
            [">=1.0.0", "0.0.0"],
            [">=1.0.0", "0.0.1"],
            [">=1.0.0", "0.1.0"],
            [">1.0.0", "0.0.1"],
            [">1.0.0", "0.1.0"],
            ["<=2.0.0", "3.0.0"],
            ["=<2.0.0", "2.1.0"],
            ["=<2.0.0", "2.0.1"],
            ["<2.0.0", "2.0.0"],
            ["<2.0.0", "2.0.1"],
            [">=0.1.2", "0.1.1"],
            ["0.1.1 || 1.2.4", "1.2.3"],
            [">=0.1.0 || <0.0.1", "0.0.1"],
            [">=0.1.1 || <0.0.1", "0.1.0"],
            ["2.x.x", "1.1.3"],
            ["2.x.x", "3.1.3"],
            ["1.2.X", "1.3.3"],
            ["1.2.X || 2.x", "3.1.3"],
            ["1.2.X || 2.x", "1.1.3"],
            ["2.*.*", "1.1.3"],
            ["2.*.*", "3.1.3"],
            ["1.2.*", "1.3.3"],
            ["2", "1.1.3"],
            ["2", "3.1.3"],
            ["1.2", "1.3.3"],
            ["1.2.* || 2.*", "3.1.3"],
            ["1.2.* || 2.*", "1.1.3"],
            ["2", "1.1.2"],
            ["2.3", "2.4.1"],
            ["<1", "1.0.0"],
            ["=>1.2", "1.1.1"],
            ["1", "2.0.0-beta"],
            ["~0.1.1-alpha.2", "0.1.1-alpha.1"],
            ["=0.1.x", "0.2.0"],
            [">=0.1.x", "0.0.1"],
            ["<0.1.x", "0.1.1"],
            ["<1.2.3", "1.2.4-beta"],
            ["=1.2.3", "1.2.3-beta"],
            [">1.2", "1.2.8"],
            ["2.x", "3.0.0-beta.0"],
            [">=1.0.0 <1.1.0", "1.1.0"],
            [">=1.0.0 <1.1.0", "1.1.0"],
            [">=1.0.0 <1.1.0-beta", "1.1.0-beta"],
            ["=2.0.0", "1.2.3"],
            ["=2.0", "1.2.3"],
            ["= 2.0", "1.2.3"],
            ["!=4.1", "4.1.0"],
            ["!=4.x", "4.1.0"],
            ["!=4.2.x", "4.2.3"],
            ["!=1.1.0", "1.1.0"],
            ["!=1.1", "1.1.0"],
            ["!=1.1", "1.1.1"],
            ["!=1.1", "1.1.1-alpha"],
            ["<1", "1.1.0"],
            ["<1.1", "1.1.0"],
            ["<1.1", "1.1.1"],
            ["<=1", "2.0.0"],
            ["<=1.1", "1.2.3"],
            [">1.1", "1.1.0"],
            [">0", "0.0.0"],
            [">0", "0.0.1-alpha"],
            [">0.0", "0.0.1-alpha"],
            [">0", "0.0.0-alpha"],
            [">1", "1.1.0"],
            [">1.1", "1.1.0"],
            [">1.1", "1.1.1"],
            [">=1.1", "1.0.2"],
            [">=1.1", "0.0.9"],
            [">=0", "0.0.0-alpha"],
            [">=0.0", "0.0.0-alpha"],
            ["<0", "0.0.0"],
            ["=0", "1.0.0"],
            ["2.*", "3.0.0"],
            ["2", "2.0.0-alpha"],
            ["2.1.*", "2.2.1"],
            ["2", "3.0.0"],
            ["2.1", "2.2.1"],
            ["~1.2.3", "1.3.0"],
            ["~1.2", "1.3.0"],
            ["~1", "2.0.0"],
            ["~0.1.2", "0.2.0"],
            ["~0.0.1", "0.1.0-alpha"],
            ["~0.0.1", "0.1.0"],
            ["~2.4", "2.5.0"],
            ["~2.4", "2.3.9"],
            ["~>3.2.1", "3.3.2"],
            ["~>3.2.1", "3.2.0"],
            ["~1", "0.2.3"],
            ["~>1", "2.2.3"],
            ["~1.0", "1.1.0"],
        ];
    }
    
    public function dataParse(): array
    {
        return [
            ["1.2.3 - 2.3.4", ">=1.2.3 <=2.3.4"],
            ["1.2.3 - 2.3.4 || 3.0.0 - 4.0.0", ">=1.2.3 <=2.3.4 || >=3.0.0 <=4.0.0"],
            ["1.2 - 2.3.4", ">=1.2.0 <=2.3.4"],
            ["1.2.3 - 2.3", ">=1.2.3 <2.4.0-0"],
            ["1.2.3 - 2", ">=1.2.3 <3.0.0-0"],
            ["~1.2.3", ">=1.2.3 <1.3.0-0"],
            ["~1.2", ">=1.2.0 <1.3.0-0"],
            ["~1", ">=1.0.0 <2.0.0-0"],
            ["~0.2.3", ">=0.2.3 <0.3.0-0"],
            ["~0.2", ">=0.2.0 <0.3.0-0"],
            ["~0", ">=0.0.0 <1.0.0-0"],
            ["~0.0.0", ">=0.0.0 <0.1.0-0"],
            ["~0.0", ">=0.0.0 <0.1.0-0"],
            ["~1.2.3-alpha.1", ">=1.2.3-alpha.1 <1.3.0-0"],
            ["", ">=0.0.0"],
            ["*", ">=0.0.0"],
            ["x", ">=0.0.0"],
            ["X", ">=0.0.0"],
            ["1.x", ">=1.0.0 <2.0.0-0"],
            ["1.2.x", ">=1.2.0 <1.3.0-0"],
            ["1", ">=1.0.0 <2.0.0-0"],
            ["1.*", ">=1.0.0 <2.0.0-0"],
            ["1.*.*", ">=1.0.0 <2.0.0-0"],
            ["1.x", ">=1.0.0 <2.0.0-0"],
            ["1.x.x", ">=1.0.0 <2.0.0-0"],
            ["1.X", ">=1.0.0 <2.0.0-0"],
            ["1.X.X", ">=1.0.0 <2.0.0-0"],
            ["1.2", ">=1.2.0 <1.3.0-0"],
            ["1.2.*", ">=1.2.0 <1.3.0-0"],
            ["1.2.x", ">=1.2.0 <1.3.0-0"],
            ["1.2.X", ">=1.2.0 <1.3.0-0"],
            ["^1.2.3", ">=1.2.3 <2.0.0-0"],
            ["^0.2.3", ">=0.2.3 <0.3.0-0"],
            ["^0.0.3", ">=0.0.3 <0.0.4-0"],
            ["^0", ">=0.0.0 <1.0.0-0"],
            ["^0.0", ">=0.0.0 <0.1.0-0"],
            ["^0.0.0", ">=0.0.0 <0.0.1-0"],
            ["^1.2.3-alpha.1", ">=1.2.3-alpha.1 <2.0.0-0"],
            ["^0.0.1-alpha", ">=0.0.1-alpha <0.0.2-0"],
            ["^0.0.*", ">=0.0.0 <0.1.0-0"],
            ["^1.2.*", ">=1.2.0 <2.0.0-0"],
            ["^1.*", ">=1.0.0 <2.0.0-0"],
            ["^0.*", ">=0.0.0 <1.0.0-0"],
            ["1.0.0 - 2.0.0", ">=1.0.0 <=2.0.0"],
            ["1 - 2", ">=1.0.0 <3.0.0-0"],
            ["1.0 - 2.0", ">=1.0.0 <2.1.0-0"],
            ["1.0.0", "=1.0.0"],
            [">=*", ">=0.0.0"],
            [">=1.0.0", ">=1.0.0"],
            [">1.0.0", ">1.0.0"],
            ["<=2.0.0", "<=2.0.0"],
            ["<=2.0.0", "<=2.0.0"],
            ["<2.0.0", "<2.0.0"],
            ["<\t2.0.0", "<2.0.0"],
            ["<= 2.0.0", "<=2.0.0"],
            ["<=  2.0.0", "<=2.0.0"],
            ["<    2.0.0", "<2.0.0"],
            ["<    2.0", "<2.0.0"],
            ["<=    2.0", "<2.1.0-0"],
            [">= 1.0.0", ">=1.0.0"],
            [">=  1.0.0", ">=1.0.0"],
            [">=   1.0.0", ">=1.0.0"],
            ["> 1.0.0", ">1.0.0"],
            [">  1.0.0", ">1.0.0"],
            ["<=   2.0.0", "<=2.0.0"],
            ["0.1.0 || 1.2.3", "=0.1.0 || =1.2.3"],
            [">=0.1.0 || <0.0.1", ">=0.1.0 || <0.0.1"],
            ["2.x.x", ">=2.0.0 <3.0.0-0"],
            ["1.2.x", ">=1.2.0 <1.3.0-0"],
            ["1.2.x || 2.x", ">=1.2.0 <1.3.0-0 || >=2.0.0 <3.0.0-0"],
            ["1.2.x || 2.x", ">=1.2.0 <1.3.0-0 || >=2.0.0 <3.0.0-0"],
            ["x", ">=0.0.0"],
            ["2.*.*", ">=2.0.0 <3.0.0-0"],
            ["1.2.*", ">=1.2.0 <1.3.0-0"],
            ["1.2.* || 2.*", ">=1.2.0 <1.3.0-0 || >=2.0.0 <3.0.0-0"],
            ["*", ">=0.0.0"],
            ["2", ">=2.0.0 <3.0.0-0"],
            ["2.3", ">=2.3.0 <2.4.0-0"],
            ["~2.4", ">=2.4.0 <2.5.0-0"],
            ["~2.4", ">=2.4.0 <2.5.0-0"],
            ["~>3.2.1", ">=3.2.1 <3.3.0-0"],
            ["~1", ">=1.0.0 <2.0.0-0"],
            ["~>1", ">=1.0.0 <2.0.0-0"],
            ["~> 1", ">=1.0.0 <2.0.0-0"],
            ["~1.0", ">=1.0.0 <1.1.0-0"],
            ["~ 1.0", ">=1.0.0 <1.1.0-0"],
            ["^0", ">=0.0.0 <1.0.0-0"],
            ["^ 1", ">=1.0.0 <2.0.0-0"],
            ["^0.1", ">=0.1.0 <0.2.0-0"],
            ["^1.0", ">=1.0.0 <2.0.0-0"],
            ["^1.2", ">=1.2.0 <2.0.0-0"],
            ["^0.0.1", ">=0.0.1 <0.0.2-0"],
            ["^0.0.1-beta", ">=0.0.1-beta <0.0.2-0"],
            ["^0.1.2", ">=0.1.2 <0.2.0-0"],
            ["^1.2.3", ">=1.2.3 <2.0.0-0"],
            ["^1.2.3-beta.4", ">=1.2.3-beta.4 <2.0.0-0"],
            ["<1", "<1.0.0"],
            ["< 1", "<1.0.0"],
            ["= 1", ">=1.0.0 <2.0.0-0"],
            ["!= 1", "<1.0.0 || >=2.0.0-0"],
            [">=1", ">=1.0.0"],
            [">= 1", ">=1.0.0"],
            ["<1.2", "<1.2.0"],
            ["< 1.2", "<1.2.0"],
            ["1", ">=1.0.0 <2.0.0-0"],
            ["^ 1.2 ^ 1", ">=1.2.0 <2.0.0-0 >=1.0.0 <2.0.0-0"],
            ["1.2 - 3.4.5", ">=1.2.0 <=3.4.5"],
            ["1.2.3 - 3.4", ">=1.2.3 <3.5.0-0"],
            ["1.2 - 3.4", ">=1.2.0 <3.5.0-0"],
            [">1", ">=2.0.0-0"],
            [">1.2", ">=1.3.0-0"],
            ["<*", "<0.0.0-0"],
            [">*", "<0.0.0-0"],
            ["!=*", "<0.0.0-0"],
            [">=*", ">=0.0.0"],
            ["<=*", ">=0.0.0"],
            ["=*", ">=0.0.0"],

            ["v1.2.3 - v2.3.4", ">=1.2.3 <=2.3.4"],
            ["v1.2.3 - v2.3.4 || 3.0.0 - 4.0.0", ">=1.2.3 <=2.3.4 || >=3.0.0 <=4.0.0"],
            ["v1.2 - v2.3.4", ">=1.2.0 <=2.3.4"],
            ["v1.2.3 - v2.3", ">=1.2.3 <2.4.0-0"],
            ["v1.2.3 - v2", ">=1.2.3 <3.0.0-0"],
            ["~v1.2.3", ">=1.2.3 <1.3.0-0"],
            ["~v1.2", ">=1.2.0 <1.3.0-0"],
            ["~v1", ">=1.0.0 <2.0.0-0"],
            ["~v0.2.3", ">=0.2.3 <0.3.0-0"],
            ["~v0.2", ">=0.2.0 <0.3.0-0"],
            ["~v0", ">=0.0.0 <1.0.0-0"],
            ["~v0.0.0", ">=0.0.0 <0.1.0-0"],
            ["~v0.0", ">=0.0.0 <0.1.0-0"],
            ["~v1.2.3-alpha.1", ">=1.2.3-alpha.1 <1.3.0-0"],
            ["v*", ">=0.0.0"],
            ["vx", ">=0.0.0"],
            ["vX", ">=0.0.0"],
            ["v1.x", ">=1.0.0 <2.0.0-0"],
            ["v1.2.x", ">=1.2.0 <1.3.0-0"],
            ["v1", ">=1.0.0 <2.0.0-0"],
            ["v1.*", ">=1.0.0 <2.0.0-0"],
            ["v1.*.*", ">=1.0.0 <2.0.0-0"],
            ["v1.x", ">=1.0.0 <2.0.0-0"],
            ["v1.x.x", ">=1.0.0 <2.0.0-0"],
            ["v1.X", ">=1.0.0 <2.0.0-0"],
            ["v1.X.X", ">=1.0.0 <2.0.0-0"],
            ["v1.2", ">=1.2.0 <1.3.0-0"],
            ["v1.2.*", ">=1.2.0 <1.3.0-0"],
            ["v1.2.x", ">=1.2.0 <1.3.0-0"],
            ["v1.2.X", ">=1.2.0 <1.3.0-0"],
            ["^v1.2.3", ">=1.2.3 <2.0.0-0"],
            ["^v0.2.3", ">=0.2.3 <0.3.0-0"],
            ["^v0.0.3", ">=0.0.3 <0.0.4-0"],
            ["^v0", ">=0.0.0 <1.0.0-0"],
            ["^v0.0", ">=0.0.0 <0.1.0-0"],
            ["^v0.0.0", ">=0.0.0 <0.0.1-0"],
            ["^v1.2.3-alpha.1", ">=1.2.3-alpha.1 <2.0.0-0"],
            ["^v0.0.1-alpha", ">=0.0.1-alpha <0.0.2-0"],
            ["^v0.0.*", ">=0.0.0 <0.1.0-0"],
            ["^v1.2.*", ">=1.2.0 <2.0.0-0"],
            ["^v1.*", ">=1.0.0 <2.0.0-0"],
            ["^v0.*", ">=0.0.0 <1.0.0-0"],
            ["v1.0.0 - 2.0.0", ">=1.0.0 <=2.0.0"],
            ["v1 - v2", ">=1.0.0 <3.0.0-0"],
            ["v1.0 - v2.0", ">=1.0.0 <2.1.0-0"],
            ["v1.0.0", "=1.0.0"],
            [">=v*", ">=0.0.0"],
            [">=v1.0.0", ">=1.0.0"],
            [">v1.0.0", ">1.0.0"],
            ["<=v2.0.0", "<=2.0.0"],
            ["<=v2.0.0", "<=2.0.0"],
            ["<v2.0.0", "<2.0.0"],
            ["<\tv2.0.0", "<2.0.0"],
            ["<= v2.0.0", "<=2.0.0"],
            ["<=  v2.0.0", "<=2.0.0"],
            ["<    v2.0.0", "<2.0.0"],
            ["<    v2.0", "<2.0.0"],
            ["<=    v2.0", "<2.1.0-0"],
            [">= v1.0.0", ">=1.0.0"],
            [">=  v1.0.0", ">=1.0.0"],
            [">=   v1.0.0", ">=1.0.0"],
            ["> v1.0.0", ">1.0.0"],
            [">  v1.0.0", ">1.0.0"],
            ["<=   v2.0.0", "<=2.0.0"],
            ["v0.1.0 || v1.2.3", "=0.1.0 || =1.2.3"],
            [">=v0.1.0 || <v0.0.1", ">=0.1.0 || <0.0.1"],
            ["v2.x.x", ">=2.0.0 <3.0.0-0"],
            ["v1.2.x", ">=1.2.0 <1.3.0-0"],
            ["v1.2.x || v2.x", ">=1.2.0 <1.3.0-0 || >=2.0.0 <3.0.0-0"],
            ["v1.2.x || v2.x", ">=1.2.0 <1.3.0-0 || >=2.0.0 <3.0.0-0"],
            ["vx", ">=0.0.0"],
            ["v2.*.*", ">=2.0.0 <3.0.0-0"],
            ["v1.2.*", ">=1.2.0 <1.3.0-0"],
            ["v1.2.* || 2.*", ">=1.2.0 <1.3.0-0 || >=2.0.0 <3.0.0-0"],
            ["v*", ">=0.0.0"],
            ["v2", ">=2.0.0 <3.0.0-0"],
            ["v2.3", ">=2.3.0 <2.4.0-0"],
            ["~v2.4", ">=2.4.0 <2.5.0-0"],
            ["~v2.4", ">=2.4.0 <2.5.0-0"],
            ["~>v3.2.1", ">=3.2.1 <3.3.0-0"],
            ["~v1", ">=1.0.0 <2.0.0-0"],
            ["~>v1", ">=1.0.0 <2.0.0-0"],
            ["~> v1", ">=1.0.0 <2.0.0-0"],
            ["~v1.0", ">=1.0.0 <1.1.0-0"],
            ["~ v1.0", ">=1.0.0 <1.1.0-0"],
            ["^v0", ">=0.0.0 <1.0.0-0"],
            ["^ v1", ">=1.0.0 <2.0.0-0"],
            ["^v0.1", ">=0.1.0 <0.2.0-0"],
            ["^v1.0", ">=1.0.0 <2.0.0-0"],
            ["^v1.2", ">=1.2.0 <2.0.0-0"],
            ["^v0.0.1", ">=0.0.1 <0.0.2-0"],
            ["^v0.0.1-beta", ">=0.0.1-beta <0.0.2-0"],
            ["^v0.1.2", ">=0.1.2 <0.2.0-0"],
            ["^v1.2.3", ">=1.2.3 <2.0.0-0"],
            ["^v1.2.3-beta.4", ">=1.2.3-beta.4 <2.0.0-0"],
            ["<v1", "<1.0.0"],
            ["< v1", "<1.0.0"],
            ["= v1", ">=1.0.0 <2.0.0-0"],
            ["!= v1", "<1.0.0 || >=2.0.0-0"],
            [">=v1", ">=1.0.0"],
            [">= v1", ">=1.0.0"],
            ["<v1.2", "<1.2.0"],
            ["< v1.2", "<1.2.0"],
            ["v1", ">=1.0.0 <2.0.0-0"],
            ["^ v1.2 ^ v1", ">=1.2.0 <2.0.0-0 >=1.0.0 <2.0.0-0"],
            ["v1.2 - v3.4.5", ">=1.2.0 <=3.4.5"],
            ["v1.2.3 - v3.4", ">=1.2.3 <3.5.0-0"],
            ["v1.2 - v3.4", ">=1.2.0 <3.5.0-0"],
            [">v1", ">=2.0.0-0"],
            [">v1.2", ">=1.3.0-0"],
            ["<v*", "<0.0.0-0"],
            [">v*", "<0.0.0-0"],
            ["!=v*", "<0.0.0-0"],
            [">=v*", ">=0.0.0"],
            ["<=v*", ">=0.0.0"],
            ["=v*", ">=0.0.0"],
        ];
    }
}