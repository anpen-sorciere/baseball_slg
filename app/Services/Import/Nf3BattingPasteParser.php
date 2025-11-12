<?php

namespace App\Services\Import;

class Nf3BattingPasteParser
{
    /**
     * @param string $rawText nf3打撃成績ページからのコピペテキスト
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $rawText): array
    {
        $lines = preg_split('/\R/u', trim($rawText));
        $rows = [];
        $inTable = false;
        $section = 'batters';
        $rowIndex = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (!$inTable && mb_strpos($line, '背番') === 0) {
                $inTable = true;
                continue;
            }

            if (!$inTable) {
                continue;
            }

            if (mb_strpos($line, 'データ集計参考サイト') === 0) {
                break;
            }

            if (mb_strpos($line, '投手打撃成績') === 0) {
                $section = 'pitchers';
                $rowIndex = 0;
                continue;
            }

            if (mb_strpos($line, '背番') === 0) {
                continue;
            }

            if (mb_strpos($line, '合計') === 0) {
                continue;
            }

            $cols = preg_split('/\s+/', $line);
            if (!$cols || count($cols) === 0) {
                continue;
            }

            $first = $cols[0];
            if (!ctype_digit($first) && $first !== '00') {
                continue;
            }

            $rowIndex++;
            $rows[] = [
                'section' => $section,
                'row_index' => $rowIndex,
                'number' => $cols[0] ?? null,
                'name' => $cols[1] ?? null,
                'columns' => $cols,
                'raw_line' => $line,
            ];
        }

        return $rows;
    }
}


