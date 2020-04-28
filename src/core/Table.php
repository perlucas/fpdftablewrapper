<?php
namespace FPDFWrapper\Core;

class Table extends Printable
{
    /**
     * widths for each column
     *
     * @var array
     */
    protected $widths;

    /**
     * aligns for each row
     *
     * @var array
     */
    protected $aligns;

    /**
     * flag indicates if the first row is the header
     *
     * @var boolean
     */
    protected $hasHeader;

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
     * pdf wrapper
     *
     * @var FPDFTableWrapper
     */
    protected $pdf;

    /**
     * total width
     *
     * @var float
     */
    protected $width;

    protected $columnStyles;

    protected $rowStyles;

    protected $cellStyles;

    /**
     * constructs an instance
     *
     * @param $wrapper FPDFTableWrapper
     * @param array $widths
     * @param array|float $alignsArgument
     * @param array|null $headerArr
     */
    public function __construct(FPDFTableWrapper $wrapper, $widths, $alignsArgument, $headerArr = null)
    {
        $this->pdf = $wrapper;
        $this->rows = [];
        $this->widths = $widths;
        $this->aligns = $alignsArgument = is_string($alignsArgument)
            ? array_fill(0, count($widths), $alignsArgument)
            : $alignsArgument;
        
        // add header
        if (\is_null($headerArr)) {
            $this->hasHeader = false;
        } else {
            $this->hasHeader = true;
            $this->rows[] = new Row($this->pdf, $headerArr);
        }

        // check size of aligns and widths
        $err = 'Invalid number of columns in configuration!';
        if (count($this->widths) !== count($this->aligns)) {
            throw new \Exception($err, 1);
        }
        if (
            $this->hasHeader && 
            (count($this->hasHeader) !== count($this->aligns))
        ) {
            throw new \Exception($err, 1);
        }

        // default initialization
        $this->columnStyles = [];
        $this->rowStyles = [];
        $this->cellStyles = [];
    }

    public function setWidth($w) {$this->width = $w;}

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
     * deletes the current row
     *
     * @return void
     */
    public function deleteCurrentRow() {$this->currentRow = null;}

    /**
     * returns the amount of columns
     *
     * @return int
     */
    public function countColumns() {return count($this->aligns);}

    /**
     * prints this table
     *
     * @param null|float $fillHeight
     * @return void
     */
    public function print($fillHeight = null)
    {
        $this->addStylesToCells();
        $widths = $this->widths;
        $aligns = $this->aligns;
        $body = $this->rows;
        $totalWidth = $this->width;

        array_walk($widths, function(&$val, $key) use($totalWidth) {
            $val = ($val * $totalWidth) / 100;
        });
        $initialX = $this->pdf->getX();
        $cantRows = count($body);
        $heightOfRows = [];
        foreach ($body as $rowindex => $row) {

            // calculate lines that needs this row
            $row->setWidths($widths);
            $nbLinesRow = $row->getNbLines();
            $heightRow = $this->pdf->getCellHeight() * $nbLinesRow;
            if ($rowindex == $cantRows - 1 && $fillHeight) {
                $remaining = $fillHeight - array_sum($heightOfRows);
                assert($remaining >= $heightRow, "Bad heightRow and fillheight configuration");
                $heightRow = $remaining;
            }
            $heightOfRows[] = $heightRow;
            
            // check page break
            $this->pdf->checkPageBreak($heightRow);
            
            // print each cell
            foreach ($row as $cellindex => $cell) {
                //Save the current position
                $x = $this->pdf->GetX();
                $y = $this->pdf->GetY();
                if ($cell->isTableCell()) {
                    $table = $cell->getValue();
                    $table->setWidth($widths[$cellindex]);
                    $table->print($heightRow);
                    $this->pdf->SetXY($x + $widths[$cellindex],$y);
                } else {
                    $cell->applyStyles();

                    //Draw the border
                    $this->pdf->rect($x,$y,$widths[$cellindex],$heightRow);

                    //Print the text
                    $align = $aligns[$cellindex];
                    if ($rowindex == 0 && $this->hasHeader) $align = 'C';
                    $this->pdf->multiCell(
                        $widths[$cellindex],
                        $this->pdf->getCellHeight(),
                        $cell->getValue(),
                        0,
                        $align
                    );
                }
                $this->pdf->SetXY($x + $widths[$cellindex],$y);
            }
            $this->pdf->ln($heightRow);
            $this->pdf->setX($initialX);
        }
    }

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

    protected function addStylesToCells()
    {
        $headerCallback = function ($pdf) {$pdf->setTableHeaderStyle();};
        $bodyCallback = function ($pdf) {$pdf->setTableBodyStyle();};
        foreach ($this->rows as $index => $row) {
            $basicCallback = $index === 0 && $this->hasHeader
                ? $headerCallback
                : $bodyCallback;
            for ($i = 0; $i < $this->countColumns(); $i++) {
                $row->getCell($i)->addCallback($basicCallback);
                $callback = null;
                if (\array_key_exists($index, $this->rowStyles)) {
                    $callback = $this->rowStyles[$index];
                }
                if (
                    ($index || !$this->hasHeader) &&
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

    public function setRowStyle($index, $callback) {$this->rowStyles[$index] = $callback;}

    public function setColumnStyle($index, $callback) {$this->columnStyles[$index] = $callback;}

    public function setCellStyle($row, $col, $callback) {$this->cellStyles["{$row}x{$col}"] = $callback;}
}