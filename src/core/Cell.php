<?php
namespace FPDFWrapper\Core;

class Cell extends Printable
{
    /**
     * value of this cell
     *
     * @var string|Table
     */
    protected $value;

    /**
     * constructs an instance
     *
     * @param mixed $vv
     */
    public function __construct($vv)
    {
        $this->value = $vv;
    }

    /**
     * returns this value
     *
     * @return string
     */
    public function getValue() {return $this->value;}

    /**
     * returns true if has a table inside
     *
     * @return boolean
     */
    public function isTableCell()
    {
        return $this->value instanceof FPDF\Core\Table;
    }
}