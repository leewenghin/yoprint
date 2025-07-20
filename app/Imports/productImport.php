<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Import;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Illuminate\Support\Facades\Log;

class productImport implements ToModel, WithHeadingRow, ShouldQueue, WithChunkReading, WithEvents
{
    private $import;
    private $processedRows = 0;
    private $failedRows = 0;
    private $totalRows = 0;
    private $filePath;

    /**
     * Constructor to accept file path
     */
    public function __construct($filePath = null)
    {
        $this->filePath = $filePath;
    }

    /**
     * Set the import model instance
     */
    public function setImport(Import $import): void
    {
        $this->import = $import;
    }

    /**
     * Set the file path
     */
    public function setFilePath($filePath): void
    {
        $this->filePath = $filePath;
    }

    /**
     * Get the number of processed rows
     */
    public function getProcessedRows(): int
    {
        return $this->processedRows;
    }

    /**
     * Get the number of failed rows
     */
    public function getFailedRows(): int
    {
        return $this->failedRows;
    }

    /**
     * Get the total number of rows
     */
    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    /**
     * Clean non-UTF-8 characters from a string or array
     */
    private function cleanUtf8($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'cleanUtf8'], $data);
        }

        if (is_string($data)) {
            // Remove or replace non-UTF-8 characters
            $cleaned = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            // Remove any remaining invalid UTF-8 sequences
            $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleaned);
            // Remove null bytes and other control characters that might cause issues
            $cleaned = str_replace(["\0", "\x0B"], '', $cleaned);
            return trim($cleaned);
        }

        return $data;
    }

    /**
     * Register events for the import
     */
    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                // Count the lines in the CSV file using the file path
                try {
                    Log::info('BeforeImport - counting rows', [
                        'file_path' => $this->filePath,
                        'file_exists' => $this->filePath ? file_exists($this->filePath) : false
                    ]);

                    if ($this->filePath && file_exists($this->filePath)) {
                        // Count lines in CSV file, subtract 1 for header
                        $lineCount = max(0, count(file($this->filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) - 1);
                        $this->totalRows = $lineCount;

                        Log::info('Total rows calculated', [
                            'file_path' => $this->filePath,
                            'total_rows' => $this->totalRows
                        ]);

                        // Update import record with total rows
                        if ($this->import) {
                            $this->import->update(['total_rows' => $this->totalRows]);
                        }
                    } else {
                        Log::warning('File path not provided or file does not exist', [
                            'file_path' => $this->filePath
                        ]);
                        $this->totalRows = 0;
                        if ($this->import) {
                            $this->import->update(['total_rows' => 0]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error counting rows in BeforeImport', [
                        'error' => $e->getMessage(),
                        'file_path' => $this->filePath
                    ]);

                    // If we can't get the count, set to 0
                    $this->totalRows = 0;
                    if ($this->import) {
                        $this->import->update(['total_rows' => 0]);
                    }
                }
            },

        ];
    }

    public function model(array $row)
    {
        try {
            // Remove this incorrect line: $this->totalRows++;

            $data = [
                'unique_key'                     => $row['unique_key'] ?? null,
                'product_title'                  => $row['product_title'] ?? null,
                'product_description'            => $row['product_description'] ?? null,
                'style'                          => $row['style'] ?? null,
                'available_sizes'                => $row['available_sizes'] ?? null,
                'brand_logo_image'               => $row['brand_logo_image'] ?? null,
                'thumbnail_image'                => $row['thumbnail_image'] ?? null,
                'color_swatch_image'             => $row['color_swatch_image'] ?? null,
                'product_image'                  => $row['product_image'] ?? null,
                'spec_sheet'                     => $row['spec_sheet'] ?? null,
                'price_text'                     => $row['price_text'] ?? null,
                'suggested_price'                => $row['suggested_price'] ?? null,
                'category_name'                  => $row['category_name'] ?? null,
                'subcategory_name'               => $row['subcategory_name'] ?? null,
                'color_name'                     => $row['color_name'] ?? null,
                'color_square_image'             => $row['color_square_image'] ?? null,
                'color_product_image'            => $row['color_product_image'] ?? null,
                'color_product_image_thumbnail'  => $row['color_product_image_thumbnail'] ?? null,
                'size'                           => $row['size'] ?? null,
                'qty'                            => $row['qty'] ?? null,
                'piece_weight'                   => $row['piece_weight'] ?? null,
                'piece_price'                    => $row['piece_price'] ?? null,
                'dozens_price'                   => $row['dozens_price'] ?? null,
                'case_price'                     => $row['case_price'] ?? null,
                'price_group'                    => $row['price_group'] ?? null,
                'case_size'                      => $row['case_size'] ?? null,
                'inventory_key'                  => $row['inventory_key'] ?? null,
                'size_index'                     => $row['size_index'] ?? null,
                'sanmar_mainframe_color'         => $row['sanmar_mainframe_color'] ?? null,
                'mill'                           => $row['mill'] ?? null,
                'product_status'                 => $row['product_status'] ?? null,
                'companion_styles'               => $row['companion_styles'] ?? null,
                'msrp'                           => $row['msrp'] ?? null,
                'map_pricing'                    => $row['map_pricing'] ?? null,
                'front_model_image_url'          => $row['front_model_image_url'] ?? null,
                'back_model_image'               => $row['back_model_image'] ?? null,
                'front_flat_image'               => $row['front_flat_image'] ?? null,
                'back_flat_image'                => $row['back_flat_image'] ?? null,
                'product_measurements'           => $row['product_measurements'] ?? null,
                'pms_color'                      => $row['pms_color'] ?? null,
                'gtin'                           => $row['gtin'] ?? null,
            ];

            // Clean all string data to ensure UTF-8 compatibility
            $data = $this->cleanUtf8($data);

            // Skip rows without unique_key
            if (empty($data['unique_key'])) {
                $this->incrementFailedRows();
                return null;
            }

            Product::upsert(
                [$data],
                ['unique_key'],
                ['product_title', 'product_description', 'style', 'available_sizes', 'brand_logo_image', 'thumbnail_image', 'color_swatch_image', 'product_image', 'spec_sheet', 'price_text', 'suggested_price', 'category_name', 'subcategory_name', 'color_name', 'color_square_image', 'color_product_image', 'color_product_image_thumbnail', 'size', 'qty', 'piece_weight', 'piece_price', 'dozens_price', 'case_price', 'price_group', 'case_size', 'inventory_key', 'size_index', 'sanmar_mainframe_color', 'mill', 'product_status', 'companion_styles', 'msrp', 'map_pricing', 'front_model_image_url', 'back_model_image', 'front_flat_image', 'back_flat_image', 'product_measurements', 'pms_color', 'gtin']
            );

            $this->incrementProcessedRows();

            return null; // We're handling the insert manually with upsert
        } catch (\Exception $e) {
            $this->incrementFailedRows();

            Log::error('Failed to import product row', [
                'row' => $row,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Increment processed rows and update progress
     */
    private function incrementProcessedRows(): void
    {
        if (!$this->import) {
            return;
        }

        // Atomic increment in database
        $this->import->increment('processed_rows', 1);
        $this->checkCompletion();
    }

    /**
     * Increment failed rows and update progress
     */
    private function incrementFailedRows(): void
    {
        if (!$this->import) {
            return;
        }

        // Atomic increment in database
        $this->import->increment('failed_rows', 1);
        $this->checkCompletion();
    }

    /**
     * Check if import is completed and update status
     */
    private function checkCompletion(): void
    {
        if (!$this->import) {
            return;
        }

        // Refresh to get latest counts
        $this->import->refresh();

        $totalProcessed = $this->import->processed_rows + $this->import->failed_rows;

        // Check if we've processed all rows and mark as completed
        if ($this->import->total_rows > 0 && $totalProcessed >= $this->import->total_rows) {
            // Only mark as completed if not already completed (prevent race conditions)
            if (!$this->import->isCompleted()) {
                $this->import->markAsCompleted($this->import->processed_rows, $this->import->failed_rows);

                Log::info('Product import completed', [
                    'import_id' => $this->import->id,
                    'total_rows' => $this->import->total_rows,
                    'processed_rows' => $this->import->processed_rows,
                    'failed_rows' => $this->import->failed_rows
                ]);
            }
        }
    }

    /**
     * Update import progress and mark as completed when done
     */
    private function updateProgress(): void
    {
        if (!$this->import) {
            return;
        }

        // Use atomic database operations to properly track progress across chunks
        // Only update if we have actual progress to report
        if ($this->processedRows > 0 || $this->failedRows > 0) {
            // Update the database with current chunk progress
            $this->import->increment('processed_rows', $this->processedRows);
            $this->import->increment('failed_rows', $this->failedRows);

            // Reset local counters after database update
            $this->processedRows = 0;
            $this->failedRows = 0;
        }

        // Refresh the model to get latest counts
        $this->import->refresh();

        $totalProcessed = $this->import->processed_rows + $this->import->failed_rows;

        Log::info('Progress update', [
            'import_id' => $this->import->id,
            'total_rows' => $this->import->total_rows,
            'processed_rows' => $this->import->processed_rows,
            'failed_rows' => $this->import->failed_rows,
            'total_processed' => $totalProcessed,
            'percentage' => $this->import->total_rows > 0 ? round(($totalProcessed / $this->import->total_rows) * 100, 2) : 0
        ]);

        // Check if we've processed all rows and mark as completed
        if ($this->import->total_rows > 0 && $totalProcessed >= $this->import->total_rows) {
            // Only mark as completed if not already completed (prevent race conditions)
            if (!$this->import->isCompleted()) {
                $this->import->markAsCompleted($this->import->processed_rows, $this->import->failed_rows);

                Log::info('Product import completed', [
                    'import_id' => $this->import->id,
                    'total_rows' => $this->import->total_rows,
                    'processed_rows' => $this->import->processed_rows,
                    'failed_rows' => $this->import->failed_rows
                ]);
            }
        }
    }

    public function chunkSize(): int
    {
        return 5000;
    }
}
