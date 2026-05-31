<?php

namespace App\Services\ReviewFilter\Contracts;

use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;

interface ReviewRule
{
    /**
     * Evaluasi satu aturan. Return RuleResult::pass() bila lolos,
     * RuleResult::reject() / ::flag() bila gagal.
     */
    public function check(ReviewContext $ctx): RuleResult;

    /** Nama rule (untuk audit log). */
    public function name(): string;
}
