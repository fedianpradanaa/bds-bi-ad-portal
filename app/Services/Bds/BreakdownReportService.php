<?php

namespace App\Services\Bds;

use App\Models\BreakdownReport;
use Illuminate\Support\Collection;

class BreakdownReportService
{
    public function getReportsByDataRange(?string $startDate, ?string $endDate): Collection
    {
        $query = BreakdownReport::query();

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        return $query->orderBy('event_start', 'asc')->get();
    }

    public function getReportsByDateRange(?string $startDate, ?string $endDate): Collection
    {
        return $this->getReportsByDataRange($startDate, $endDate);
    }

    public function parseDescription(string $description, string $lang = 'en'): array
    {
        $details = '-';
        $followUp = '-';

        if (empty($description)) {
            return compact('details', 'followUp');
        }

        if (stripos($description, 'Triggered by') !== false || stripos($description, 'Dipicu oleh') !== false) {
            $parts = preg_split('/(Remedy|Solusi):/i', $description);
            $details = trim(str_replace(['Triggered by:', 'Dipicu oleh:'], '', $parts[0] ?? ''));
            $followUp = trim($parts[1] ?? '-');
        } elseif (stripos($description, '->') !== false) {
            $parts = explode('->', $description);
            $details = trim($parts[0]);
            $followUp = trim($parts[1] ?? '-');
        } else {
            $details = trim($description);
        }

        $details = strip_tags(html_entity_decode($details));
        $followUp = strip_tags(html_entity_decode($followUp));

        return [
            'details' => $details,
            'follow_up' => $followUp,
        ];
    }
}