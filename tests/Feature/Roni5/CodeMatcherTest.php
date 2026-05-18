<?php

namespace Tests\Feature\Roni5;

use App\Services\Roni5\CodeMatcher;
use Tests\TestCase;

class CodeMatcherTest extends TestCase
{
    private CodeMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new CodeMatcher();
    }

    public function test_extracts_caps_prefix_code(): void
    {
        $this->assertSame('NJ-012-128', $this->matcher->extract('კონვერტი NJ-012-128'));
    }

    public function test_extracts_numeric_code(): void
    {
        $this->assertSame('1505', $this->matcher->extract('მშრალი წებო 1505 15g'));
    }

    public function test_does_not_treat_size_as_code(): void
    {
        $this->assertNull($this->matcher->extract('მშრალი წებო 15g'));
        $this->assertNull($this->matcher->extract('წყალი 500ml'));
    }

    public function test_does_not_match_size_with_three_digit_value(): void
    {
        // "500ml" → 500 is a size, not a code.
        $this->assertNull($this->matcher->extract('Bottle 500ml'));
    }

    public function test_extracts_caps_code_with_slash(): void
    {
        $this->assertSame('AB/123-X', $this->matcher->extract('Item AB/123-X here'));
    }

    public function test_returns_null_when_no_code(): void
    {
        $this->assertNull($this->matcher->extract('სუფთა გადახდის ფურცელი'));
    }

    public function test_prefers_alpha_code_when_both_present(): void
    {
        // "ABC-001" wins over "12345".
        $this->assertSame('ABC-001', $this->matcher->extract('Thing ABC-001 12345 widget'));
    }
}
