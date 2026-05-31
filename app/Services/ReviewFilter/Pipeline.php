<?php

namespace App\Services\ReviewFilter;

use App\Services\ReviewFilter\Contracts\ReviewRule;

/**
 * Orchestrator yang menjalankan rules secara sekuensial.
 * First-failure-wins: rule pertama yang fail langsung di-return.
 */
final class Pipeline
{
    /** @var ReviewRule[] */
    private array $rules;

    /**
     * @param  iterable<ReviewRule>  $rules
     */
    public function __construct(iterable $rules = [])
    {
        $this->rules = is_array($rules) ? $rules : iterator_to_array($rules);
    }

    /**
     * Tambah rule ke akhir pipeline.
     */
    public function pipe(ReviewRule $rule): self
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * Jalankan seluruh rule. Return RuleResult dari pelanggaran pertama, atau pass() jika semua lolos.
     */
    public function run(ReviewContext $ctx): RuleResult
    {
        foreach ($this->rules as $rule) {
            $result = $rule->check($ctx);
            if (! $result->passed) {
                return $result;
            }
        }

        return RuleResult::pass();
    }

    /** @return ReviewRule[] */
    public function rules(): array
    {
        return $this->rules;
    }
}
