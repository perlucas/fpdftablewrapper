<?php

namespace FPDFWrapper\Core;

class Row extends Printable
{
    use ColumnsContainer;
    use HasHeight;

    /**
     * row values
     *
     * @var array
     */
    protected $values;

    /**
     * true if this is a header
     *
     * @var boolean
     */
    protected $isHeader;

    /**
     * constructs a new row
     *
     * @param FPDFTableWrapper $pdf
     * @param array $values
     */
    public function __construct(FPDFTableWrapper $pdf, $values = [], $header = false)
    {
        parent::__construct($pdf);
        $this->values = $values;
        $this->isHeader = $header;
        $this->widths = null;
        $this->height = null;
        $this->aligns = null;
    }

    /**
     * returns the amount of columns
     *
     * @return int
     */
    public function count() {return count($this->values);}

    /**
     * string representation of this type of printable
     *
     * @return string
     */
    public function getType() {return 'row';}

    /**
     * returns the cell located on that index
     *
     * @param int $index
     * @return Cell
     */
    public function getCell($index) {return $this->values[$index];}

    /**
     * adds a new cell to this row
     *
     * @param mixed $val
     * @return void
     */
    public function addCellValue($val)
    {
        $this->values[] = new Cell($this->pdf, $val);
    }

    /**
     * returns true if this row is a header
     *
     * @return boolean
     */
    public function isHeader() {return $this->isHeader;}

    /**
     * implements the printing of a row
     *
     * @return void
     */
    public function print()
    {
        foreach ($this->values as $cellindex => $cell) {
            //Save the current position
            $x = $this->pdf->GetX();
            $y = $this->pdf->GetY();

            // configure cell and print
            $cell->setWidth($this->widths[$cellindex]);
            $cell->setHeight($this->height);
            $cell->setAlign($this->aligns[$cellindex]);
            $cell->print();

            // restore position
            $this->pdf->SetXY($x + $this->widths[$cellindex],$y);
        }
    }

    /**
     * calculates teh amount of lines that this rrow ocuppies
     *
     * @return int
     */
    public function getNbLines()
    {
        $max = 0;
        foreach ($this->values as $cellindex => $cell) {
            $cell->setWidth($this->widths[$cellindex]);
            $max = max($max, $cell->getNbLines($pdf));
        }
        return $max;
    }
}