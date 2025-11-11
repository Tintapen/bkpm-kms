<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Closure;

class ExcelImportService
{
    /**
     * @param string $filePath path file Excel
     * @param array $rules aturan validasi per baris
     * @param callable $onValid callback simpan data valid
     * @param array $columns nama kolom (opsional untuk header)
     * @return array [bool $hasErrors, string|null $errorFilePath]
     */
    public function process(
        string $filePath,
        ?Closure $rules,
        callable $onValid,
        string $menuName = "",
        ?Closure $onBeforeValidate = null,
        array $columns = []
    ): array {
        // Membaca data dari file Excel
        $rows = Excel::toArray([], $filePath)[0];

        $results = [];
        $hasErrors = false;
        $headers = [];

        // Periksa kolom yang diharapkan ada
        $expectedColumns = array_map('strtolower', $columns);
        $actualHeader = array_map('strtolower', $rows[0]);

        foreach ($expectedColumns as $expectedColumn) {
            if (!in_array($expectedColumn, $actualHeader)) {
                $hasErrors = true;
                $results[] = ["ERROR: Column '{$expectedColumn}' not found."];
                break; // Jika kolom tidak ditemukan, hentikan pemrosesan
            }
        }

        if ($hasErrors) {
            return $this->generateErrorLog($results, $menuName);
        }

        // Proses setiap baris data
        foreach ($rows as $index => $row) {
            // Skip header
            if ($index === 0) {
                $headers = $row;
                $row[] = '_LOG_';  // Menambahkan kolom untuk log
                $results[] = $row;
                continue;
            }

            // Map kolom ke data yang relevan
            $data = [];
            foreach ($headers as $colIndex => $key) {
                $data[$key] = $row[$colIndex] ?? null;
            }

            $data = [];
            foreach ($headers as $colIndex => $key) {
                // Periksa apakah kolom ada, jika tidak, set nilai default
                $data[$key] = $row[$colIndex] ?? null;
            }

            // Pre-processing jika ada callback
            if ($onBeforeValidate) {
                $data = $onBeforeValidate($data);
            }

            // Validasi data dengan menggunakan rules yang dihasilkan oleh Closure
            $rulesArray = $rules($data);  // Menggunakan Closure untuk menghasilkan rules

            // Validasi data
            $validator = Validator::make($data, $rulesArray);

            if ($validator->fails()) {
                $hasErrors = true;
                $row[] = implode(', ', $validator->errors()->all()); // Menambahkan error ke hasil log
            } else {
                $row[] = 'Inserted'; // Tandai sebagai berhasil
                call_user_func($onValid, $data); // Proses data valid
            }

            $results[] = $row; // Tambahkan baris yang telah diproses
        }

        // Jika ada error, simpan file log
        if ($hasErrors) {
            return $this->generateErrorLog($results, $menuName);
        }

        return [false, null]; // Tidak ada error
    }

    /**
     * Simpan hasil error dalam file log Excel dan kembalikan path-nya
     *
     * @param array $results Data hasil import
     * @param string $menuName Nama menu untuk log
     *
     * @return array [bool, string] Status dan file path log
     */
    private function generateErrorLog($results, $menuName)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Menulis hasil ke spreadsheet
        foreach ($results as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }

        $fileName = "import_{$menuName}_" . now()->format('YmdHis') . '_log.xlsx';
        $savePath = storage_path("app/public/{$fileName}");

        $writer = new Xlsx($spreadsheet);
        $writer->save($savePath);

        return [true, $savePath]; // Kembalikan path file log
    }
}
