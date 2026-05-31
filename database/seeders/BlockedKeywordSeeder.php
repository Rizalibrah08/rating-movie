<?php

namespace Database\Seeders;

use App\Models\BlockedKeyword;
use Illuminate\Database\Seeder;

class BlockedKeywordSeeder extends Seeder
{
    /**
     * Default blocked keywords berdasarkan konteks_project.md.
     *
     * Catatan: kata-kata kritik wajar seperti "buruk", "jelek", "tidak bagus"
     * SENGAJA tidak masuk default — itu kosakata kritik sah dalam ulasan film.
     * Admin bisa menambahkannya manual lewat UI bila platform memiliki
     * kebijakan lebih ketat.
     */
    public function run(): void
    {
        $keywords = [
            // Spam patterns
            ['keyword' => 'gaje', 'category' => BlockedKeyword::CATEGORY_SPAM],
            ['keyword' => 'gajelas', 'category' => BlockedKeyword::CATEGORY_SPAM],
            ['keyword' => 'nggak jelas', 'category' => BlockedKeyword::CATEGORY_SPAM],
            ['keyword' => 'gak jelas', 'category' => BlockedKeyword::CATEGORY_SPAM],

            // Promosi terselubung — menjurus ke film/konten lain
            ['keyword' => 'mending nonton', 'category' => BlockedKeyword::CATEGORY_PROMOSI],
            ['keyword' => 'mending film lain', 'category' => BlockedKeyword::CATEGORY_PROMOSI],
            ['keyword' => 'lebih baik nonton', 'category' => BlockedKeyword::CATEGORY_PROMOSI],
            ['keyword' => 'nonton ini lebih baik', 'category' => BlockedKeyword::CATEGORY_PROMOSI],

            // Insult berat / merendahkan
            ['keyword' => 'menjijikkan', 'category' => BlockedKeyword::CATEGORY_INSULT],
            ['keyword' => 'racun toxic', 'category' => BlockedKeyword::CATEGORY_INSULT],
            ['keyword' => 'racun banget', 'category' => BlockedKeyword::CATEGORY_INSULT],
            ['keyword' => 'sok bagus', 'category' => BlockedKeyword::CATEGORY_INSULT],
            ['keyword' => 'sok dewasa', 'category' => BlockedKeyword::CATEGORY_INSULT],
            ['keyword' => 'sok intelektual', 'category' => BlockedKeyword::CATEGORY_INSULT],
            ['keyword' => 'pretensius', 'category' => BlockedKeyword::CATEGORY_INSULT],
            ['keyword' => 'manipulatif', 'category' => BlockedKeyword::CATEGORY_INSULT],

            // Slang negatif yang merendahkan
            ['keyword' => 'receh banget', 'category' => BlockedKeyword::CATEGORY_SLANG],
            ['keyword' => 'receh parah', 'category' => BlockedKeyword::CATEGORY_SLANG],
            ['keyword' => 'receh abis', 'category' => BlockedKeyword::CATEGORY_SLANG],
            ['keyword' => 'bikin ngantuk', 'category' => BlockedKeyword::CATEGORY_SLANG],
            ['keyword' => 'bikin eneg', 'category' => BlockedKeyword::CATEGORY_SLANG],
            ['keyword' => 'bikin mual', 'category' => BlockedKeyword::CATEGORY_SLANG],
            ['keyword' => 'bikin frustrasi', 'category' => BlockedKeyword::CATEGORY_SLANG],
            ['keyword' => 'alay', 'category' => BlockedKeyword::CATEGORY_SLANG],
            ['keyword' => 'ngaco', 'category' => BlockedKeyword::CATEGORY_SLANG],
        ];

        foreach ($keywords as $kw) {
            BlockedKeyword::updateOrCreate(
                ['keyword' => $kw['keyword']],
                ['category' => $kw['category'], 'is_active' => true],
            );
        }
    }
}
