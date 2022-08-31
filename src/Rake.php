<?php

namespace Uocnv\Rake;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Uocnv\Rake\Exception\LangNotSupported;
use function PHPUnit\Framework\throwException;

class Rake
{
    protected $stopWords = [];
    protected $paragraph;

    public function __construct(string $paragraph, string $lang = 'vi_VN')
    {
        $this->setLang($lang);
        $this->paragraph = $paragraph;
    }

    /**
     * Set language
     *
     * @return $this
     */
    public function setLang(string $lang): Rake
    {
        // Check exit file
        if (!file_exists(__DIR__ . "/../asset/{$lang}.json")) {
            throw new LangNotSupported('Not support this lang!');
        }
        $this->stopWords = json_decode(file_get_contents(__DIR__ . "/../asset/{$lang}.json"), true);
        return $this;
    }

    public function getKeyword(int $numKeyword = null, int $minScore = 0)
    {
        $phrases_plain       = self::split_sentences($this->paragraph);
        $candidateKeyPhrases = $this->getPhrase($phrases_plain);
        $scores              = $this->getScore($candidateKeyPhrases);
        $finalScores         = [];
        foreach ($candidateKeyPhrases as $keyPhrase) {
            $words = array_filter(explode(' ', $keyPhrase), function ($word) {
                return (bool)trim($word);
            });

            $score = 0;
            foreach ($words as $w) {
                $score += $scores[$w] ?? 0;
            }

            $finalScores[$keyPhrase] = $score;
        }
        arsort($finalScores);

        $result = array_filter($finalScores, function ($value) use ($minScore) {
            return $value >= $minScore;
        });

        if ($numKeyword) {
            $result = array_slice($result, 0, $numKeyword);
        }

        return $result;
    }

    /**
     * Tách văn bản thành các câu
     *
     * @param string $text
     * @return array|false|string[]
     */
    public static function split_sentences(string $text)
    {
        return preg_split('/[.?!,;\-"\'()\n\r\t]+/u', $text);
    }

    /**
     * Lấy các cụm từ
     *
     * @param array $sentences
     * @return array
     */
    private function getPhrase(array $sentences): array
    {
        $phraseArr = [];
        $regex     = '/\b' . implode('\b|\b', $this->stopWords) . '\b/iu';
        foreach ($sentences as $sentence) {
            if (trim($sentence)) {
                $phraseTemp = preg_replace($regex, '|', trim(mb_strtolower($sentence)));
                foreach (explode('|', $phraseTemp) as $p) {
                    if (trim($p) != '') {
                        $phraseArr[] = trim($p);
                    }
                }
            }
        }
        return $phraseArr;
    }

    /**
     * Tạo bảng điểm cho các từ
     *
     * @param array $candidateKeyPhrases
     * @return array
     */
    public function getScore(array $candidateKeyPhrases): array
    {
        $frequencies = []; // Tần suất xuất hiện của từ
        $degrees     = []; // Tổng điểm của từ
        $scores      = [];

        foreach ($candidateKeyPhrases as $keyPhrase) {
            $words = array_filter(explode(' ', $keyPhrase), function ($word) {
                return (bool)trim($word);
            });
            foreach ($words as $w) {
                $frequencies[$w] = ($frequencies[$w] ?? 0) + 1;
                $degrees[$w]     = ($degrees[$w] ?? 0) + count($words) - 1; // Số từ khác trong cụm từ <=> Số lần từ đó xuất hiện cùng các từ khác
            }
        }

        // Cộng thêm tần suất xuất hiện của từ đó vào bảng điểm
        foreach ($frequencies as $key => $value) {
            $degrees[$key] += $value;
        }

        foreach ($frequencies as $key => $value) {
            $scores[$key] = round($degrees[$key] / $value, 3);
        }

        return $scores;
    }
}
