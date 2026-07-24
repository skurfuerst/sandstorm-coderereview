<?php

namespace App\Reporting;

class LegacyReportRenderer
{
    /**
     * @param array $options
     */
    public function render(mixed $report, bool $useLegacyLayout = false, $options = []): string
    {
        if ($useLegacyLayout) {
            // old layout — kept around in case someone still needs it
            $out = '';
            foreach ($report['rows'] as $r) {
                $out .= $r['label'] . "\t" . $r['value'] . "\n";
            }
            return $out;
        }

        $format = $options['format'] ?? 'html';
        if ($format === 'html') {
            return $this->renderHtml($report);
        } elseif ($format === 'csv') {
            return $this->renderCsv($report);
        } else {
            return $this->renderHtml($report);
        }
    }

    private function renderHtml(mixed $report): string
    {
        $rows = '';
        foreach ($report['rows'] as $r) {
            $rows .= '<tr><td>' . $r['label'] . '</td><td>' . $r['value'] . '</td></tr>';
        }
        return '<table>' . $rows . '</table>';
    }

    private function renderCsv(mixed $report): string
    {
        $out = '';
        foreach ($report['rows'] as $r) {
            $out .= $r['label'] . ',' . $r['value'] . "\n";
        }
        return $out;
    }
}
