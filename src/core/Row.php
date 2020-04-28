<?php
namespace FPDFWrapper\Core;

class Row extends Printable
{
    /**
     * row's values
     *
     * @var array
     */
    protected $values;

    protected $widths;

    protected $pdf;

    /**
     * constructs an instance
     *
     * @param array $values
     */
    public function __construct(FPDFTableWrapper $pdf, $values = [])
    {
        $this->pdf = $pdf;
        $this->values = $values;
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

    public function setWidths(array $ww) {$this->widths = $ww;}

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