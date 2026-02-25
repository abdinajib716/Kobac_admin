<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ArrayReportExport implements FromArray, WithHeadings, ShouldAutoSize
{
    /**
     * @param array<string, string> $columns
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(
        private readonly array $columns,
        private readonly array $rows,
    ) {
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function array(): array
    {
        $keys = array_keys($this->columns);

        return Collection::make($this->rows)
            ->map(function (array $row) use ($keys) {
                return Collection::make($keys)->map(function (string $key) use ($row) {
                    return $row[$key] ?? null;
                })->all();
            })
            ->all();
    }
}

