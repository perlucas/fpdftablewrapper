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

    /**
     * constructs an instance
     *
     * @param array $values
     */
    public function __construct($values = [])
    {
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

    /**
     * adds a new cell to this row
     *
     * @param mixed $val
     * @return void
     */
    public function addCellValue($val)
    {
        $this->values[] = new Cell($val);
    }
}