<?php

namespace FPDFWrapper\Core;

class Table extends Printable
{
    use ColumnsContainer;
    use HasWidth;

    /**
     * rows of this table
     * 
     * @var array
     */
    protected $rows;

    /**
     * current row state
     *
     * @var Row
     */
    protected $currentRow;


    /**
     * height that must be filled
     *
     * @var float
     */
    protected $fillHeight;

    /**
     * map for storing callbacks used to change the printing style of  columns
     * 
     * @var array
     */
    protected $columnStyles;

    /**
     * map for storing callbacks used to change the printing style of rows
     * 
     * @var array
     */
    protected $rowStyles;

    /**
     * map for storing callbacks used to change the printing style of cells
     *
     * @var array
     */
    protected $cellStyles;

    /**
     * constructs an instance
     *
     * @param $wrapper FPDFTableWrapper
     * @param array $widths
     * @param array|string $alignsArgument
     * @param array|null $headerArr
     */
    public function __construct(FPDFTableWrapper $wrapper, $widths, $alignsArgument, $headerArr = null)
    {
        // set basic properties
        parent::__construct($wrapper);
        $this->rows = [];
        $this->widths = $widths;
        
        // set align from array or string
        $this->aligns = $alignsArgument = is_string($alignsArgument)
            ? array_fill(0, count($widths), $alignsArgument)
            : $alignsArgument;
        
        // add header if exists
        if (! \is_null($headerArr)) {
            $this->rows[] = new Row($this->pdf, $headerArr, true);    
        }

        // check size of aligns and widths
        $err = 'Invalid number of columns in configuration!';
        if (count($this->widths) !== count($this->aligns)) {
            throw new \Exception($err, 1);
        }
        if (
            ! \is_null($headerArr) &&
            (count($headerArr) !== count($this->aligns))
        ) {
            throw new \Exception($err, 1);
        }

        // default initialization of maps
        $this->columnStyles = [];
        $this->rowStyles = [];
        $this->cellStyles = [];
        $this->currentRow = null;
        $this->fillHeight = null;
        $this->width = 0;
    }

    /**
     * string representation of this type of printable
     *
     * @return string
     */
    public function getType() {return 'table';}

    /**
     * returns the current row being plotted
     *
     * @return array
     */
    public function getCurrentRow() 
    {
        if (! $this->currentRow) {
            throw new \Exception("Current row has not been set", 1);
        }
        return $this->currentRow;
    }

    /**
     * stores the current row being plotted
     *
     * @param Row $rr
     * @return void
     */
    public function setCurrentRow(Row $rr) {$this->currentRow = $rr;}

    /**
     * adds a new row
     *
     * @param array $rowValues
     * @return void
     */
    public function addRow(Row $row) {$this->rows[] = $row;}

    /**
     * deletes the current row being plotted
     *
     * @return void
     */
    public function deleteCurrentRow() {$this->currentRow = null;}

    /**
     * seths the height that must be filled on this table
     *
     * @param float $h
     * @return void
     */
    public function setFillHeight($h) {$this->fillHeight = $h;}

    /**
     * prints this table on the pdf
     *
     * @return void
     */
    public function print()
    {
        $this->addStylesToCells();
        $widths = $this->widths;
        $totalWidth = $this->width;

        array_walk($widths, function(&$val, $key) use($totalWidth) {
            $val = ($val * $totalWidth) / 100;
        });
        $initialX = $this->pdf->getX();
        $cantRows = count($this->rows);
        $heightOfRows = [];
        foreach ($this->rows as $rowindex => $row) {

            // calculate lines that needs this row
            $row->setWidths($widths);
            $heightRow = $this->pdf->getCellHeight() * $row->getNbLines();
            if ($rowindex == $cantRows - 1 && $this->fillHeight) {
                $remaining = $this->fillHeight - array_sum($heightOfRows);
                assert($remaining >= $heightRow, "Bad heightRow and fillheight configuration");
                $heightRow = $remaining;
            }
            $heightOfRows[] = $heightRow;
            
            // check page break
            $this->pdf->checkPageBreak($heightRow);
            
            // print the row
            $row->setAligns($this->aligns);
            $row->setHeight($heightRow);
            $row->print();

            // restore position
            $this->pdf->ln($heightRow);
            $this->pdf->setX($initialX);
        }
    }

    /**
     * returns the amount of lines that this table occupies
     *
     * @return int
     */
    public function getNbLines()
    {
        $widths = $this->widths;
        $totalW = $this->width;
        
        array_walk($widths, function(&$val, $key) use($totalW) {
            $val = ($val * $totalW) / 100;
        });

        $nb = 0;
        foreach ($this->rows as $rowindex => $row) {
            $row->setWidths($widths);
            $nb += $row->getNbLines();
        }
        return $nb;
    }

    /**
     * adds the callback styles to each cell
     *
     * @return void
     */
    protected function addStylesToCells()
    {
        $headerCallback = function ($pdf) {$pdf->setTableHeaderStyle();};
        $bodyCallback = function ($pdf) {$pdf->setTableBodyStyle();};
        foreach ($this->rows as $index => $row) {
            $basicCallback = $row->isHeader() ? $headerCallback : $bodyCallback;
            for ($i = 0; $i < $this->countColumns(); $i++) {
                $row->getCell($i)->addCallback($basicCallback);
                $callback = null;
                if (\array_key_exists($index, $this->rowStyles)) {
                    $callback = $this->rowStyles[$index];
                }
                if (
                    ($index || ! $row->isHeader()) &&
                    \array_key_exists($i, $this->columnStyles)
                ) {
                    $callback = $this->columnStyles[$i];
                }
                if (\array_key_exists("{$index}x{$i}", $this->cellStyles)) {
                    $callback = $this->cellStyles["{$index}x{$i}"];
                }
                if ($callback) $row->getCell($i)->addCallback($callback);
            }
        }
    }

    /**
     * adds a new callback style to a row
     *
     * @param int $index
     * @param callback $callback
     * @return void
     */
    public function setRowStyle($index, $callback) {$this->rowStyles[$index] = $callback;}

    /**
     * adds a new callback style to a column
     *
     * @param int $index
     * @param callback $callback
     * @return void
     */
    public function setColumnStyle($index, $callback) {$this->columnStyles[$index] = $callback;}

    /**
     * adds a new callback style to a cell
     *
     * @param int $row
     * @param int $col
     * @param callback $callback
     * @return void
     */
    public function setCellStyle($row, $col, $callback) {$this->cellStyles["{$row}x{$col}"] = $callback;}
}