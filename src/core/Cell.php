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

    protected $width;

    protected $pdf;

    protected $callbacks;

    /**
     * constructs an instance
     *
     * @param mixed $vv
     */
    public function __construct(FPDFTableWrapper $pdf, $vv)
    {
        $this->pdf = $pdf;
        $this->value = $vv;
        $this->callbacks = [];
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

    public function setWidth($w) {$this->width = $w;}

    public function addCallback($c) {$this->callbacks[] = $c;}

    public function getNbLines()
    {
        if ($this->isTableCell()) {
            $this->value->setWidth($this->width);
            return $this->value->getNbLines();
        }
        $this->applyStyles();
        return $this->pdf->nbLines($this->width, $this->value);
    }

    protected function applyStyles()
    {
        $reverse = \array_reverse($this->callbacks);
        foreach ($reverse as $c) {
            $c($this->pdf);
        }
    }
}