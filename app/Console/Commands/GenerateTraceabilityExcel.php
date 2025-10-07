<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GenerateTraceabilityExcel extends Command
{
    protected $signature = 'traceability:excel '
        . '{csv=docs/traceability/PRD_Traceability_Matrix.csv : Source CSV path}'
        . ' {output=docs/traceability/PRD_Traceability_Matrix.xlsx : Output XLSX path}';

    protected $description = 'Generate PRD Traceability Matrix Excel with formulas and summary sheet';

    public function handle(): int
    {
        $csvPath = base_path($this->argument('csv'));
        $xlsxPath = base_path($this->argument('output'));

        if (!file_exists($csvPath)) {
            $this->error('CSV not found: ' . $csvPath);
            return self::FAILURE;
        }

        $matrix = array_map('str_getcsv', file($csvPath));
        if (empty($matrix)) {
            $this->error('CSV is empty');
            return self::FAILURE;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        // Matrix sheet
        $sheet = new Worksheet($spreadsheet, 'Matrix');
        $spreadsheet->addSheet($sheet, 0);

        // Extend header with computed columns
        $header = $matrix[0];
        $header[] = 'StatusScore';
        $header[] = 'ItemWeightedScore';
        $sheet->fromArray($header, null, 'A1');

        // Data rows
        $row = 2;
        for ($i = 1; $i < count($matrix); $i++) {
            $sheet->fromArray($matrix[$i], null, 'A' . $row);
            $row++;
        }
        $lastRow = $row - 1;

        // Auto-fit basic width
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add formulas for StatusScore and ItemWeightedScore
        for ($r = 2; $r <= $lastRow; $r++) {
            // Columns: A Category, B Module, C Requirement, D CategoryWeightPct, E ItemWeightPct, F Status, G Notes
            $sheet->setCellValue("H{$r}", "=IF(F{$r}=\"Done\",1,IF(F{$r}=\"In Progress\",0.5,0))");
            $sheet->setCellValue("I{$r}", "=IFERROR((E{$r}/100)*H{$r},H{$r})");
        }

        // Summary sheet
        $summary = new Worksheet($spreadsheet, 'Summary');
        $spreadsheet->addSheet($summary, 1);

        $summary->fromArray([
            ['Category', 'CategoryWeightPct', 'CategoryProgress', 'WeightedContribution'],
            ['Client', '=MAX(Matrix!D2:D9999)', "=IFERROR(AVERAGEIF(Matrix!A2:A9999,\"Client\",Matrix!I2:I9999),0)", "=B2*C2"],
            ['Provider', '=MAX(Matrix!D2:D9999)', "=IFERROR(AVERAGEIF(Matrix!A2:A9999,\"Provider\",Matrix!I2:I9999),0)", "=B3*C3"],
            ['Admin', '=MAX(Matrix!D2:D9999)', "=IFERROR(AVERAGEIF(Matrix!A2:A9999,\"Admin\",Matrix!I2:I9999),0)", "=B4*C4"],
            ['NFRs', '=MAX(Matrix!D2:D9999)', "=IFERROR(AVERAGEIF(Matrix!A2:A9999,\"NFRs\",Matrix!I2:I9999),0)", "=B5*C5"],
            ['Total', '', '', '=SUM(D2:D5)']
        ], null, 'A1');

        // Center headers
        foreach (['A1:I1', 'A1:D1'] as $range) {
            $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $summary->getStyle('A1:D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Save
        @mkdir(dirname($xlsxPath), 0777, true);
        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxPath);

        $this->info('Excel generated: ' . $xlsxPath);
        return self::SUCCESS;
    }
}

