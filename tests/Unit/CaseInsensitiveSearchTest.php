<?php

namespace Tests\Unit;

use App\Support\CaseInsensitiveSearch;
use Tests\TestCase;

class CaseInsensitiveSearchTest extends TestCase
{
    public function test_like_pattern_uppercases_term(): void
    {
        $this->assertSame('%FIDELIS%', CaseInsensitiveSearch::likePattern('fidelis'));
    }

    public function test_like_pattern_escapes_wildcards(): void
    {
        $this->assertSame('%100\%%', CaseInsensitiveSearch::likePattern('100%'));
        $this->assertSame('%A\_B%', CaseInsensitiveSearch::likePattern('a_b'));
    }

    public function test_like_pattern_trims_whitespace(): void
    {
        $this->assertSame('%FIDELIS VIN CORO%', CaseInsensitiveSearch::likePattern('  Fidelis Vin Coro  '));
    }

    public function test_escape_like_empty_after_trim(): void
    {
        $this->assertSame('', CaseInsensitiveSearch::escapeLike('   '));
    }

    public function test_normalize_collapses_whitespace(): void
    {
        $this->assertSame('fidelis vin coro', CaseInsensitiveSearch::normalizeTerm("fidelis   vin\ncoro"));
    }

    public function test_tokenize_splits_on_spaces(): void
    {
        $this->assertSame(['fidelis', 'vin', 'coro'], CaseInsensitiveSearch::tokenize('  fidelis   vin coro  '));
    }

    public function test_tokenize_empty_for_blank(): void
    {
        $this->assertSame([], CaseInsensitiveSearch::tokenize('   '));
    }

    public function test_firebird_no_agt_search_sql_uses_each_token(): void
    {
        [$sql, $params] = CaseInsensitiveSearch::firebirdNoAgtSearchSql('016005 0099');

        $this->assertStringContainsString('UPPER("NO_AGT") LIKE ?', $sql);
        $this->assertCount(2, $params);
        $this->assertSame(['%016005%', '%0099%'], $params);
    }
}
