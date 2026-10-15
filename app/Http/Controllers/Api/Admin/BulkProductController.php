<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductLaptopModel;
use App\Models\ProductPartNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BulkProductController extends Controller
{
    /**
     * Convert 1-based column index into Excel column letter (1 -> A, 26 -> Z, 27 -> AA).
     */
    private function columnLetter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex > 0) {
            $mod = ($colIndex - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $colIndex = intval(($colIndex - $mod) / 26);
        }
        return $letter ?: 'A';
    }

    /**
     * Generate and download a formatted sample template.
     * Uses PhpSpreadsheet (.xlsx) when available with auto column widths and styled headers.
     * Gracefully falls back to formatted UTF-8 CSV if PhpSpreadsheet is not yet installed on server.
     */
    public function downloadSample()
    {
        // Define Column Headers and Sample Data
        $columns = [
            ['key' => 'name', 'label' => 'Product Name *', 'group' => 'required', 'example' => '15.6" FHD IPS 30-Pin Matte Laptop LED Panel'],
            ['key' => 'sku', 'label' => 'SKU *', 'group' => 'required', 'example' => 'LP-156-FHD-30-IPS'],
            ['key' => 'selling_price', 'label' => 'Selling Price (LKR) *', 'group' => 'required', 'example' => 18500],
            ['key' => 'cost_price', 'label' => 'Cost Price (LKR)', 'group' => 'required', 'example' => 14000],
            ['key' => 'stock_quantity', 'label' => 'Stock Quantity *', 'group' => 'required', 'example' => 20],
            ['key' => 'min_stock', 'label' => 'Low Stock Alert Qty', 'group' => 'required', 'example' => 3],
            ['key' => 'category', 'label' => 'Category', 'group' => 'spec', 'example' => 'Laptop Displays'],
            ['key' => 'brand', 'label' => 'Brand', 'group' => 'spec', 'example' => 'HP'],
            ['key' => 'product_type', 'label' => 'Product Type', 'group' => 'spec', 'example' => 'Laptop Display'],
            ['key' => 'display_size', 'label' => 'Screen Size', 'group' => 'spec', 'example' => '15.6 inch'],
            ['key' => 'resolution', 'label' => 'Resolution', 'group' => 'spec', 'example' => '1920x1080 FHD'],
            ['key' => 'pin_type', 'label' => 'Pin Type', 'group' => 'spec', 'example' => '30-Pin'],
            ['key' => 'display_type', 'label' => 'Display Type', 'group' => 'spec', 'example' => 'IPS'],
            ['key' => 'touch_type', 'label' => 'Touch Type', 'group' => 'spec', 'example' => 'Non-Touch'],
            ['key' => 'refresh_rate', 'label' => 'Refresh Rate', 'group' => 'spec', 'example' => '60Hz'],
            ['key' => 'warranty', 'label' => 'Warranty', 'group' => 'spec', 'example' => '6 Months'],
            ['key' => 'condition', 'label' => 'Condition', 'group' => 'spec', 'example' => 'Brand New Genuine'],
            ['key' => 'panel_numbers', 'label' => 'Panel / Part Numbers (comma-separated)', 'group' => 'info', 'example' => 'NV156FHM-N48, LP156WF9-SPK1, B156HAN02.1'],
            ['key' => 'laptop_models', 'label' => 'Compatible Laptop Models (comma or / separated)', 'group' => 'info', 'example' => 'HP Pavilion 15-cs, HP 15-dw, Dell Inspiron 15 3511, Acer Aspire 5 A515'],
            ['key' => 'description', 'label' => 'Product Description', 'group' => 'info', 'example' => 'Original Grade A+ matte laptop display panel with vibrant colors and wide viewing angles.'],
            ['key' => 'short_description', 'label' => 'Short Description', 'group' => 'info', 'example' => '15.6 FHD 30-Pin IPS Slim Display Panel'],
            ['key' => 'main_image', 'label' => 'Main Image URL', 'group' => 'info', 'example' => 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=800'],
            ['key' => 'status', 'label' => 'Status (active/draft)', 'group' => 'info', 'example' => 'active'],
        ];

        $row2Data = [
            '14.0" HD LED 30-Pin Slim Screen Panel',
            'LP-140-HD-30-SLIM',
            14500,
            11000,
            15,
            2,
            'Laptop Displays',
            'Dell',
            'Laptop Display',
            '14.0 inch',
            '1366x768 HD',
            '30-Pin',
            'TN',
            'Non-Touch',
            '60Hz',
            '6 Months',
            'Brand New Genuine',
            'B140XTN07.2, N140BGA-EA4, NT140WHM-N44',
            'Dell Latitude 3400, Dell Inspiron 14 3480, Lenovo V14-ADA',
            'Crisp HD resolution standard 30-pin eDP connector screen.',
            '14.0 HD 30-Pin Slim LED Screen',
            '',
            'active',
        ];

        $row3Data = [
            '15.6" FHD 144Hz IPS 40-Pin Gaming Display',
            'LP-156-FHD-144-40',
            26500,
            21000,
            8,
            2,
            'Laptop Displays',
            'ASUS',
            'Laptop Display',
            '15.6 inch',
            '1920x1080 FHD',
            '40-Pin',
            'IPS',
            'Non-Touch',
            '144Hz',
            '6 Months',
            'Brand New Genuine',
            'NV156FHM-NX4, B156HAN13.0, LM156LF2F01',
            'ASUS TUF Gaming A15, Acer Nitro 5 AN515, HP Victus 15, Lenovo Legion 5',
            'High refresh rate 144Hz gaming panel for ultra-smooth gameplay.',
            '15.6 FHD 144Hz Gaming Screen',
            '',
            'active',
        ];

        $sampleRows = [
            array_column($columns, 'example'),
            $row2Data,
            $row3Data,
        ];

        // 1. If PhpSpreadsheet is installed, build rich formatted .xlsx workbook
        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            // SHEET 1: Products Template
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Products');

            $headerStyles = [
                'required' => ['fill' => '1E3A8A', 'color' => 'FFFFFF'],
                'spec' => ['fill' => '0F766E', 'color' => 'FFFFFF'],
                'info' => ['fill' => '334155', 'color' => 'FFFFFF'],
            ];

            $sheet->getRowDimension(1)->setRowHeight(32);

            foreach ($columns as $idx => $col) {
                $colLetter = $this->columnLetter($idx + 1);
                $cellCoord = "{$colLetter}1";

                $sheet->setCellValue($cellCoord, $col['label']);
                $styleConfig = $headerStyles[$col['group']] ?? $headerStyles['info'];

                $sheet->getStyle($cellCoord)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => $styleConfig['color']],
                        'size' => 11,
                        'name' => 'Segoe UI',
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $styleConfig['fill']],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => false,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                    ],
                ]);
            }

            $currentDataRow = 2;
            foreach ($sampleRows as $rowData) {
                $sheet->getRowDimension($currentDataRow)->setRowHeight(22);
                foreach ($rowData as $idx => $val) {
                    $colLetter = $this->columnLetter($idx + 1);
                    $cellCoord = "{$colLetter}{$currentDataRow}";
                    $sheet->setCellValue($cellCoord, $val);

                    $bgColor = ($currentDataRow % 2 === 0) ? 'F8FAFC' : 'FFFFFF';

                    $sheet->getStyle($cellCoord)->applyFromArray([
                        'font' => ['size' => 10, 'name' => 'Segoe UI'],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $bgColor],
                        ],
                        'alignment' => [
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            'horizontal' => is_numeric($val) ? \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT : \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'E2E8F0'],
                            ],
                        ],
                    ]);
                }
                $currentDataRow++;
            }

            // Auto-width adjustment on all columns
            for ($colIdx = 1; $colIdx <= count($columns); $colIdx++) {
                $sheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
            }

            // SHEET 2: Categories & Brands Reference Guide
            $refSheet = $spreadsheet->createSheet();
            $refSheet->setTitle('Reference & Instructions');

            $refSheet->setCellValue('A1', 'PANELOOK BULK PRODUCT UPLOAD INSTRUCTIONS');
            $refSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E3A8A'));
            $refSheet->getRowDimension(1)->setRowHeight(28);

            $instructions = [
                '1. Fields marked with an asterisk (*) are strictly required (Product Name, SKU, Selling Price, Stock Quantity).',
                '2. The SKU must be unique for each product. If a SKU already exists, your upload can update the existing product.',
                '3. You can provide multiple compatible laptop models separated by commas (,) or slashes (/).',
                '4. You can provide multiple alternate panel / part numbers separated by commas (,).',
                '5. Existing categories and brands in your system are listed below for accurate spelling.',
                '6. If a category or brand is typed that does not exist yet, the system will automatically create it for you.',
            ];

            $insRow = 3;
            foreach ($instructions as $ins) {
                $refSheet->setCellValue("A{$insRow}", $ins);
                $refSheet->getStyle("A{$insRow}")->getFont()->setSize(10);
                $insRow++;
            }

            $categories = Category::pluck('name')->toArray();
            $refSheet->setCellValue('A11', 'Available Categories');
            $refSheet->getStyle('A11')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
            $refSheet->getStyle('A11')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
            $catRow = 12;
            foreach ($categories as $c) {
                $refSheet->setCellValue("A{$catRow}", $c);
                $catRow++;
            }

            $brands = Brand::pluck('name')->toArray();
            $refSheet->setCellValue('C11', 'Available Brands');
            $refSheet->getStyle('C11')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
            $refSheet->getStyle('C11')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A');
            $bRow = 12;
            foreach ($brands as $b) {
                $refSheet->setCellValue("C{$bRow}", $b);
                $bRow++;
            }

            $types = DB::table('product_types')->pluck('name')->toArray();
            $refSheet->setCellValue('E11', 'Available Product Types');
            $refSheet->getStyle('E11')->getFont()->setBold(true)->setSize(11)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
            $refSheet->getStyle('E11')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4338CA');
            $tRow = 12;
            foreach ($types as $t) {
                $refSheet->setCellValue("E{$tRow}", $t);
                $tRow++;
            }

            for ($colIdx = 1; $colIdx <= 6; $colIdx++) {
                $refSheet->getColumnDimensionByColumn($colIdx)->setAutoSize(true);
            }

            $spreadsheet->setActiveSheetIndex(0);

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'panelook_sample_');
            $writer->save($tempFile);

            return response()->download($tempFile, 'panelook_product_upload_sample.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // 2. Native CSV Fallback: Never blocks the user even if PhpSpreadsheet is not yet installed on server!
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="panelook_product_upload_sample.csv"',
        ];

        return response()->stream(function () use ($columns, $sampleRows) {
            $out = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens it with perfect unicode character encoding
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, array_column($columns, 'label'));
            foreach ($sampleRows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 200, $headers);
    }

    /**
     * Process bulk product upload from Excel (.xlsx, .xls) or CSV.
     * Supports both PhpSpreadsheet and native PHP CSV parsing.
     */
    public function uploadBulk(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // up to 20MB
            'update_existing' => 'nullable',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['xlsx', 'xls', 'csv', 'txt'];

        if (!in_array($ext, $allowed)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file format. Please upload an Excel (.xlsx, .xls) or CSV file.',
            ], 422);
        }

        $updateExisting = filter_var($request->input('update_existing', true), FILTER_VALIDATE_BOOLEAN);

        $realPath = $file->getRealPath();
        if (!$realPath || !file_exists($realPath) || filesize($realPath) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded file is empty or cannot be read.',
            ], 422);
        }

        // Sniff the file magic bytes (first 8 bytes) to detect genuine file structure
        $magic = @file_get_contents($realPath, false, null, 0, 8);
        $isZip = ($magic !== false && (str_starts_with($magic, "PK\x03\x04") || str_starts_with($magic, "\x04\x03\x4b\x50")));
        $isOldXls = ($magic !== false && str_starts_with($magic, "\xd0\xcf\x11\xe0"));

        $rows = [];

        // 1. Genuine OOXML ZIP Archive (.xlsx)
        if ($isZip && class_exists(\PhpOffice\PhpSpreadsheet\Reader\Xlsx::class)) {
            try {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($realPath);
                $sheet = $spreadsheet->getSheetByName('Products') ?: $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray(null, true, true, true);
            } catch (\Throwable $e) {
                // If PhpSpreadsheet Xlsx reader fails (e.g. zip member error), gracefully try CSV parsing
                $rows = $this->parseCsvFile($realPath);
                if (empty($rows)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to read the uploaded Excel file: ' . $e->getMessage(),
                    ], 422);
                }
            }
        } elseif ($isOldXls && class_exists(\PhpOffice\PhpSpreadsheet\Reader\Xls::class)) {
            // 2. Binary Excel 97-2004 (.xls)
            try {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($realPath);
                $sheet = $spreadsheet->getSheetByName('Products') ?: $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray(null, true, true, true);
            } catch (\Throwable $e) {
                $rows = $this->parseCsvFile($realPath);
                if (empty($rows)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unable to read the uploaded Excel file: ' . $e->getMessage(),
                    ], 422);
                }
            }
        } elseif ($isZip && !class_exists(\PhpOffice\PhpSpreadsheet\Reader\Xlsx::class)) {
            return response()->json([
                'success' => false,
                'message' => 'Excel (.xlsx) reader package is not installed on the server. Please save your spreadsheet as a CSV (.csv) file and upload it, or extract vendor_phpspreadsheet_addon.zip on the server.',
            ], 422);
        } else {
            // 3. Plain Text / CSV / TSV file (even if uploaded with .xlsx extension)
            try {
                $rows = $this->parseCsvFile($realPath);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to read the uploaded CSV/Excel file: ' . $e->getMessage(),
                ], 422);
            }
        }

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded file contains no data rows.',
            ], 422);
        }

        // 1. Locate and normalize header row (find first row with content)
        $headerRow = null;
        while (!empty($rows)) {
            $candidate = array_shift($rows);
            $hasContent = false;
            foreach ($candidate as $val) {
                if (trim((string)$val) !== '') {
                    $hasContent = true;
                    break;
                }
            }
            if ($hasContent) {
                $headerRow = $candidate;
                break;
            }
        }

        if (!$headerRow) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded file does not contain a header row.',
            ], 422);
        }

        $headerMap = [];
        foreach ($headerRow as $colLetter => $rawHeader) {
            if ($rawHeader === null || trim((string)$rawHeader) === '') {
                continue;
            }
            $cleanKey = $this->normalizeHeader(trim((string)$rawHeader));
            if ($cleanKey) {
                $headerMap[$cleanKey] = $colLetter;
            }
        }

        if (!isset($headerMap['name']) || !isset($headerMap['sku'])) {
            return response()->json([
                'success' => false,
                'message' => 'Spreadsheet is missing required headers ("Product Name" and "SKU"). Please use the sample template.',
            ], 422);
        }

        $categoriesCache = Category::all()->keyBy(fn($c) => strtolower(trim($c->name)));
        $brandsCache = Brand::all()->keyBy(fn($b) => strtolower(trim($b->name)));
        $productTypesCache = DB::table('product_types')->get()->keyBy(fn($pt) => strtolower(trim($pt->name)));
        $defaultProductTypeId = DB::table('product_types')->where('slug', 'laptop-display')->value('id') ?? 1;

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $processedSkus = [];
        $totalDataRows = 0;
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;

            $isEmptyRow = true;
            foreach ($row as $val) {
                if ($val !== null && trim((string)$val) !== '') {
                    $isEmptyRow = false;
                    break;
                }
            }
            if ($isEmptyRow) {
                continue;
            }

            $totalDataRows++;

            $name = trim((string)($row[$headerMap['name']] ?? ''));
            $sku = trim((string)($row[$headerMap['sku']] ?? ''));
            $rawSellingPrice = trim((string)($row[$headerMap['selling_price']] ?? ''));
            $rawCostPrice = isset($headerMap['cost_price']) ? trim((string)($row[$headerMap['cost_price']] ?? '')) : '';
            $rawStock = isset($headerMap['stock_quantity']) ? trim((string)($row[$headerMap['stock_quantity']] ?? '')) : '';
            $rawMinStock = isset($headerMap['min_stock']) ? trim((string)($row[$headerMap['min_stock']] ?? '')) : '';
            $categoryName = isset($headerMap['category']) ? trim((string)($row[$headerMap['category']] ?? '')) : '';
            $brandName = isset($headerMap['brand']) ? trim((string)($row[$headerMap['brand']] ?? '')) : '';
            $productTypeName = isset($headerMap['product_type']) ? trim((string)($row[$headerMap['product_type']] ?? '')) : '';
            $displaySize = isset($headerMap['display_size']) ? trim((string)($row[$headerMap['display_size']] ?? '')) : null;
            $resolution = isset($headerMap['resolution']) ? trim((string)($row[$headerMap['resolution']] ?? '')) : null;
            $pinType = isset($headerMap['pin_type']) ? trim((string)($row[$headerMap['pin_type']] ?? '')) : null;
            $displayType = isset($headerMap['display_type']) ? trim((string)($row[$headerMap['display_type']] ?? '')) : 'IPS';
            $touchType = isset($headerMap['touch_type']) ? trim((string)($row[$headerMap['touch_type']] ?? '')) : 'Non-Touch';
            $refreshRate = isset($headerMap['refresh_rate']) ? trim((string)($row[$headerMap['refresh_rate']] ?? '')) : null;
            $warranty = isset($headerMap['warranty']) ? trim((string)($row[$headerMap['warranty']] ?? '')) : '3-12 Months';
            $condition = isset($headerMap['condition']) ? trim((string)($row[$headerMap['condition']] ?? '')) : 'Brand New Genuine';
            $rawPanelNumbers = isset($headerMap['panel_numbers']) ? trim((string)($row[$headerMap['panel_numbers']] ?? '')) : '';
            $rawLaptopModels = isset($headerMap['laptop_models']) ? trim((string)($row[$headerMap['laptop_models']] ?? '')) : '';
            $description = isset($headerMap['description']) ? trim((string)($row[$headerMap['description']] ?? '')) : null;
            $shortDescription = isset($headerMap['short_description']) ? trim((string)($row[$headerMap['short_description']] ?? '')) : null;
            $mainImage = isset($headerMap['main_image']) ? trim((string)($row[$headerMap['main_image']] ?? '')) : null;
            $status = isset($headerMap['status']) ? strtolower(trim((string)($row[$headerMap['status']] ?? 'active'))) : 'active';

            if ($name === '') {
                $skippedCount++;
                $errors[] = ['row' => $rowNumber, 'sku' => $sku ?: 'N/A', 'name' => 'N/A', 'field' => 'Product Name', 'message' => 'Product Name is missing.'];
                continue;
            }

            if ($sku === '') {
                $skippedCount++;
                $errors[] = ['row' => $rowNumber, 'sku' => 'N/A', 'name' => $name, 'field' => 'SKU', 'message' => 'SKU is missing and is required.'];
                continue;
            }

            if (in_array(strtolower($sku), $processedSkus)) {
                $skippedCount++;
                $errors[] = ['row' => $rowNumber, 'sku' => $sku, 'name' => $name, 'field' => 'SKU', 'message' => "Duplicate SKU '{$sku}' found multiple times in this spreadsheet."];
                continue;
            }

            $sellingPriceClean = str_replace([',', ' '], '', $rawSellingPrice);
            if (!is_numeric($sellingPriceClean) || (float)$sellingPriceClean < 0) {
                $skippedCount++;
                $errors[] = ['row' => $rowNumber, 'sku' => $sku, 'name' => $name, 'field' => 'Selling Price', 'message' => "Selling price '{$rawSellingPrice}' is invalid. Must be a number >= 0."];
                continue;
            }
            $sellingPrice = (float)$sellingPriceClean;

            $costPrice = 0.0;
            if ($rawCostPrice !== '') {
                $costPriceClean = str_replace([',', ' '], '', $rawCostPrice);
                if (is_numeric($costPriceClean)) {
                    $costPrice = max(0, (float)$costPriceClean);
                }
            }

            $stockQuantity = 0;
            if ($rawStock !== '') {
                $stockClean = str_replace([',', ' '], '', $rawStock);
                if (is_numeric($stockClean)) {
                    $stockQuantity = max(0, (int)$stockClean);
                } else {
                    $skippedCount++;
                    $errors[] = ['row' => $rowNumber, 'sku' => $sku, 'name' => $name, 'field' => 'Stock Quantity', 'message' => "Stock quantity '{$rawStock}' is invalid. Must be an integer >= 0."];
                    continue;
                }
            }

            $minStock = 2;
            if ($rawMinStock !== '' && is_numeric($rawMinStock)) {
                $minStock = max(0, (int)$rawMinStock);
            }

            if (!in_array($status, ['active', 'draft', 'archived'])) {
                $status = 'active';
            }

            $categoryId = null;
            if ($categoryName !== '') {
                $catLower = strtolower($categoryName);
                if (isset($categoriesCache[$catLower])) {
                    $categoryId = $categoriesCache[$catLower]->id;
                } else {
                    $newCat = Category::create([
                        'name' => $categoryName,
                        'slug' => Str::slug($categoryName) . '-' . rand(10, 99),
                        'status' => true,
                    ]);
                    $categoriesCache[$catLower] = $newCat;
                    $categoryId = $newCat->id;
                }
            }

            $brandId = null;
            if ($brandName !== '') {
                $brandLower = strtolower($brandName);
                if (isset($brandsCache[$brandLower])) {
                    $brandId = $brandsCache[$brandLower]->id;
                } else {
                    $newBrand = Brand::create([
                        'name' => $brandName,
                        'slug' => Str::slug($brandName) . '-' . rand(10, 99),
                        'status' => true,
                    ]);
                    $brandsCache[$brandLower] = $newBrand;
                    $brandId = $newBrand->id;
                }
            }

            $productTypeId = $defaultProductTypeId;
            if ($productTypeName !== '') {
                $typeLower = strtolower($productTypeName);
                if (isset($productTypesCache[$typeLower])) {
                    $productTypeId = $productTypesCache[$typeLower]->id;
                }
            }

            $modelsList = [];
            if ($rawLaptopModels !== '') {
                $splitModels = preg_split('/[\\/,;|\\n]+/', $rawLaptopModels);
                foreach ($splitModels as $sm) {
                    $smTrim = trim($sm);
                    if ($smTrim !== '' && !in_array($smTrim, $modelsList)) {
                        $modelsList[] = $smTrim;
                    }
                }
            }

            $partsList = [];
            if ($rawPanelNumbers !== '') {
                $splitParts = preg_split('/[,;|\\n]+/', $rawPanelNumbers);
                foreach ($splitParts as $sp) {
                    $spTrim = trim($sp);
                    if ($spTrim !== '' && !in_array($spTrim, $partsList)) {
                        $partsList[] = $spTrim;
                    }
                }
            }

            $existingProduct = Product::where('sku', $sku)->first();

            if ($existingProduct && !$updateExisting) {
                $skippedCount++;
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'name' => $name,
                    'field' => 'SKU',
                    'message' => "Product with SKU '{$sku}' already exists. Enable 'Update existing' to overwrite.",
                ];
                continue;
            }

            try {
                DB::transaction(function () use (
                    $existingProduct,
                    $name,
                    $sku,
                    $sellingPrice,
                    $costPrice,
                    $stockQuantity,
                    $minStock,
                    $status,
                    $categoryId,
                    $brandId,
                    $productTypeId,
                    $displaySize,
                    $resolution,
                    $pinType,
                    $displayType,
                    $touchType,
                    $refreshRate,
                    $warranty,
                    $condition,
                    $modelsList,
                    $partsList,
                    $rawLaptopModels,
                    $rawPanelNumbers,
                    $description,
                    $shortDescription,
                    $mainImage,
                    &$createdCount,
                    &$updatedCount
                ) {
                    $laptopModelString = !empty($modelsList) ? implode(' / ', $modelsList) : ($rawLaptopModels ?: null);
                    $panelNumberString = !empty($partsList) ? implode(', ', $partsList) : ($rawPanelNumbers ?: null);

                    $payload = [
                        'name' => $name,
                        'sku' => $sku,
                        'selling_price' => $sellingPrice,
                        'cost_price' => $costPrice,
                        'stock_quantity' => $stockQuantity,
                        'min_stock' => $minStock,
                        'status' => $status,
                        'category_id' => $categoryId,
                        'brand_id' => $brandId,
                        'product_type_id' => $productTypeId,
                        'display_size' => $displaySize,
                        'resolution' => $resolution,
                        'pin_type' => $pinType,
                        'display_type' => $displayType,
                        'touch_type' => $touchType,
                        'refresh_rate' => $refreshRate,
                        'warranty' => $warranty,
                        'condition' => $condition,
                        'laptop_model' => $laptopModelString,
                        'panel_number' => $panelNumberString,
                        'description' => $description,
                        'short_description' => $shortDescription,
                    ];

                    if ($mainImage) {
                        $payload['main_image'] = $mainImage;
                    }

                    if ($existingProduct) {
                        $existingProduct->update($payload);
                        $product = $existingProduct;
                        $updatedCount++;
                    } else {
                        $slugBase = Str::slug($name);
                        $slug = $slugBase;
                        $counter = 1;
                        while (Product::where('slug', $slug)->exists()) {
                            $slug = "{$slugBase}-{$counter}";
                            $counter++;
                        }
                        $payload['slug'] = $slug;
                        $product = Product::create($payload);
                        $createdCount++;
                    }

                    if (!empty($modelsList)) {
                        $product->laptopModels()->delete();
                        foreach ($modelsList as $m) {
                            ProductLaptopModel::create([
                                'product_id' => $product->id,
                                'model_name' => mb_substr($m, 0, 255),
                            ]);
                        }
                    }

                    if (!empty($partsList)) {
                        $product->partNumbers()->delete();
                        foreach ($partsList as $p) {
                            ProductPartNumber::create([
                                'product_id' => $product->id,
                                'part_number' => mb_substr($p, 0, 255),
                            ]);
                        }
                    }
                });

                $processedSkus[] = strtolower($sku);

            } catch (\Throwable $e) {
                $skippedCount++;
                $errors[] = [
                    'row' => $rowNumber,
                    'sku' => $sku,
                    'name' => $name,
                    'field' => 'Database',
                    'message' => 'Error saving product: ' . $e->getMessage(),
                ];
            }
        }

        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => sprintf('Upload finished: %d created, %d updated, %d skipped.', $createdCount, $updatedCount, $skippedCount),
            'data' => [
                'total_rows' => $totalDataRows,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors,
            ],
        ]);
    }

    /**
     * Map arbitrary Excel / CSV header strings into standardized field keys.
     */
    private function normalizeHeader(string $header): ?string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $h = strtolower(trim($h));
        $h = preg_replace('/[\*\(\)\:\?]/', '', $h);
        $h = str_replace(['_', '-', '/'], ' ', $h);
        $h = trim(preg_replace('/\s+/', ' ', $h));

        return match ($h) {
            'product name', 'name', 'product', 'title', 'item name' => 'name',
            'sku', 'product sku', 'item sku', 'code', 'product code' => 'sku',
            'selling price lkr', 'selling price', 'price lkr', 'price', 'unit price' => 'selling_price',
            'cost price lkr', 'cost price', 'cost' => 'cost_price',
            'stock quantity', 'stock', 'qty', 'quantity', 'inventory' => 'stock_quantity',
            'low stock alert qty', 'low stock qty', 'min stock', 'alert qty', 'minimum stock' => 'min_stock',
            'category', 'category name', 'cat' => 'category',
            'brand', 'brand name', 'make', 'manufacturer' => 'brand',
            'product type', 'type' => 'product_type',
            'screen size', 'display size', 'size' => 'display_size',
            'resolution', 'res', 'screen resolution' => 'resolution',
            'pin type', 'pins', 'pin', 'connector pins' => 'pin_type',
            'display type', 'panel type' => 'display_type',
            'touch type', 'touch' => 'touch_type',
            'refresh rate', 'hz' => 'refresh_rate',
            'warranty' => 'warranty',
            'condition' => 'condition',
            'panel part numbers comma separated', 'panel numbers', 'part numbers', 'panel number', 'part number', 'panel code' => 'panel_numbers',
            'compatible laptop models comma or separated', 'compatible laptop models', 'laptop models', 'laptop model', 'compatibility', 'models' => 'laptop_models',
            'product description', 'description', 'full description' => 'description',
            'short description' => 'short_description',
            'main image url', 'main image', 'image url', 'image' => 'main_image',
            'status active draft', 'status' => 'status',
            default => null,
        };
    }

    /**
     * Resilient CSV and plain-text table parser with auto-delimiter detection and BOM removal.
     */
    private function parseCsvFile(string $filePath): array
    {
        $rows = [];
        $handle = @fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }

        // Detect delimiter by sampling the first line
        $firstLine = fgets($handle);
        rewind($handle);

        $sample = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine ?? '');
        $delimiters = [',', ';', "\t", '|'];
        $chosenDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $count = substr_count($sample, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $chosenDelimiter = $delim;
            }
        }

        $rowIndex = 1;
        while (($data = fgetcsv($handle, 0, $chosenDelimiter)) !== false) {
            if ($rowIndex === 1 && isset($data[0])) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$data[0]);
            }

            // Check if row has any non-empty cell
            $hasData = false;
            foreach ($data as $val) {
                if (trim((string)$val) !== '') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData && $rowIndex > 1) {
                continue;
            }

            $rowAssoc = [];
            foreach ($data as $colIdx => $val) {
                $colLetter = $this->columnLetter($colIdx + 1);
                $rowAssoc[$colLetter] = $val;
            }
            $rows[$rowIndex] = $rowAssoc;
            $rowIndex++;
        }
        fclose($handle);

        return $rows;
    }
}
