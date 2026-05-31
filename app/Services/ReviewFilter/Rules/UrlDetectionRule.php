<?php

namespace App\Services\ReviewFilter\Rules;

use App\Services\ReviewFilter\Contracts\ReviewRule;
use App\Services\ReviewFilter\ReviewContext;
use App\Services\ReviewFilter\RuleResult;

/**
 * UrlDetectionRule — blokir ulasan yang mengandung URL/link.
 *
 * Mengaddress PRD weakness #6: filter promosi terselubung.
 * Pattern menangkap http://, https://, www., dan domain umum (.com/.id/.co/.net/.org/etc).
 */
final class UrlDetectionRule implements ReviewRule
{
    public function name(): string
    {
        return 'url_detection';
    }

    public function check(ReviewContext $ctx): RuleResult
    {
        $body = $ctx->body;

        // Pattern 1: explicit scheme
        if (preg_match('/\b(?:https?:\/\/|www\.)\S+/iu', $body)) {
            return RuleResult::reject(
                $this->name(),
                'Ulasan tidak boleh mengandung tautan URL.',
            );
        }

        // Pattern 2: bare domain (kata.tld) — TLD umum
        $tlds = 'com|net|org|id|co|io|me|tv|app|xyz|info|biz|live|site|online|store|shop';
        if (preg_match("/\b[a-z0-9-]+\.(?:{$tlds})\b/iu", $body)) {
            return RuleResult::reject(
                $this->name(),
                'Ulasan tidak boleh mengandung nama domain.',
            );
        }

        return RuleResult::pass();
    }
}
