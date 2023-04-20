<?php

declare(strict_types=1);

namespace Zii\Integrations;

use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Yii;
use Closure;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use yii\base\Exception;
use yii\web\Response;

final class SimpleExcel
{
    public const EVENT_BEFORE_EXCEL_SAVE = 'beforeExcelSave';
    public const EVENT_AFTER_EXCEL_SAVE = 'afterExcelSave';

    /**
     * @var array 行
     */
    public array $rows = [];

    /**
     * @var int 最多列的索引
     */
    public int $maxColIndex = 1;

    /**
     * @var string 文件保存目录（绝对路径）
     */
    public string $dir;

    /**
     * @var string 下载的文件名（仅文件名）
     */
    public string $filename_for_download;

    /**
     * @var string 临时的文件名（仅文件名）
     */
    private string $_file;

    /**
     * @var bool 是否已经初始化
     */
    private bool $_prepared = false;

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function prepare(): void
    {
        if ($this->_prepared) {
            return;
        }

        if ($this->dir === null) {
            $this->dir = Yii::getAlias('@runtime/simple-excel/');
        }

        if (!(file_exists($this->dir) && is_dir($this->dir))) {
            if (!mkdir($this->dir)) {
                throw new Exception('Unable to create runtime dir');
            }
        }

        $this->_file = Yii::$app->security->generateRandomString() . '.xlsx';

        if ($this->filename_for_download === null) {
            $this->filename_for_download = date('Y-m-d.H-i-s.') . pf_mt_rand(1_000_000, 9_999_999);
        }

        $this->_excel = new Spreadsheet();
        $this->_sheet = $this->_excel->getActiveSheet();

        $this->_prepared = true;
    }

    /**
     * @var Spreadsheet
     */
    private Spreadsheet $_excel;
    /**
     * @var Worksheet
     */
    private Worksheet $_sheet;

    /**
     * @param string $title
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function createSheet(string $title = ''): void
    {
        $this->_prepared ? $this->_sheet = $this->_excel->createSheet() : $this->prepare();

        if (!empty($title)) {
            $this->_sheet->setTitle($title);
        }

        $this->rows = array_values($this->rows);

        $rowIndex = 1;
        foreach ($this->rows as $row) {
            $mergeCells = [];
            $maxRowsCount = 1;
            $colIndex = 1;
            foreach ($row as $col) {
                if (\is_array($col)) {
                    $colCount = \count($col);
                    $subcolIndex = 0;
                    foreach ($col as $subcol) {
                        $this->_sheet->setCellValueByColumnAndRow(
                            $colIndex,
                            $rowIndex + $subcolIndex,
                            $subcol
                        );
                        $subcolIndex++;
                    }
                    if ($colCount > $maxRowsCount) {
                        $maxRowsCount = $colCount;
                    }
                } else {
                    $this->setCellValueWithBackgroundColor($colIndex, $rowIndex, $col);
                    $mergeCells[] = ['r' => $rowIndex, 'c' => $colIndex];
                }
                $colIndex++;
            }
            // 合并单元格
            if ($maxRowsCount > 1) {
                foreach ($mergeCells as $mergeCell) {
                    $this->_sheet->mergeCellsByColumnAndRow(
                        $mergeCell['c'],
                        $mergeCell['r'],
                        $mergeCell['c'],
                        $mergeCell['r'] + $maxRowsCount - 1
                    );
                    $this->_sheet->getStyleByColumnAndRow($mergeCell['c'], $mergeCell['r'])
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }
            }
            // 首行加粗
            if ($rowIndex === 1) {
                $this->_sheet->getStyleByColumnAndRow(1, 1, \count($row),1)
                    ->getFont()
                    ->setBold(true);
                // 合并单元格
                if (\count($row) === 1) {
                    $this->_sheet->mergeCellsByColumnAndRow(
                        1,
                        1,
                        $this->maxColIndex,
                        1
                    );
                    $this->_sheet->getStyleByColumnAndRow(1, 1)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }

            $rowIndex += $maxRowsCount;
        }

        $this->_sheet->getStyle($this->_sheet->calculateWorksheetDimension())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => Color::COLOR_BLACK],
                ],
            ],
        ]);
    }

    private function setCellValueWithBackgroundColor(int $colIndex, int $rowIndex, $value): void
    {
        if ($value === null) {
            $value = '';
        }

        if (is_numeric($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            throw new Exception('Invalid data type: [value] must be string, ' . gettype($value) . ' given.');
        }

        $vc = explode(';;;', $value);

        if (!isset($vc[1]) || !preg_match('/[a-zA-Z\d]{8}/', $vc[1])) {
            $this->_sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, $value);
            return;
        }

        $this->_sheet->setCellValueByColumnAndRow($colIndex, $rowIndex, $vc[0]);

        $this->_sheet->getStyleByColumnAndRow(
            $colIndex,
            $rowIndex,
            $colIndex,
            $rowIndex
        )
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB($vc[1]);

        // 临时处理
        if ($vc[1] === 'ff5380c1') {
            $this->_sheet->getStyleByColumnAndRow($colIndex, $rowIndex)
                ->getFont()
                ->getColor()
                ->setARGB(Color::COLOR_WHITE);
        }
    }

    /**
     * @param \Closure|null $callback
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function createFile(?Closure $callback = null): void
    {
        (new XlsxWriter($this->_excel))->save($this->dir . '/' . $this->_file);

        $this->_excel->disconnectWorksheets();

        unset($this->_excel);

        if ($callback instanceof Closure) {
            $callback($this->dir . '/' . $this->_file);
        }
    }

    public function send(): Response
    {
        $response = Yii::$app->response;
        $response->on(
            Yii::$app->response::EVENT_AFTER_SEND,
            function (): void {
                unlink($this->dir . '/' . $this->_file);
            }
        );

        return $response->sendFile(
            $this->dir . '/' . $this->_file,
            $this->filename_for_download . '.xlsx'
        );
    }
}
