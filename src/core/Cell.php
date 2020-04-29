<?php

namespace FPDFWrapper\Core;

class Cell extends Printable
{
    use HasWidth;
    use HasHeight;

    /**
     * value of this cell
     *
     * @var string|Table
     */
    protected $value;

    /**
     * alignment of this cell
     *
     * @var string
     */
    protected $align;

    /**
     * style callbacks that will be applied to this cell before being printed
     *
     * @var array
     */
    protected $callbacks;

    /**
     * constructs a new cell
     *
     * @param FPDFTableWrapper $pdf
     * @param mixed $vv
     */
    public function __construct(FPDFTableWrapper $pdf, $vv)
    {
        parent::__construct($pdf);
        $this->value = $vv;
        $this->callbacks = [];
        $this->width = null;
        $this->height = null;
        $this->align = null;
    }

    /**
     * returns this cell's value
     *
     * @return mixed
     */
    public function getValue() {return $this->value;}

    /**
     * returns true if has a table inside
     *
     * @return boolean
     */
    public function isTableCell()
    {
        return $this->value instanceof Table;
    }

    /**
     * sets the align of this cell
     *
     * @param string $a
     * @return void
     */
    public function setAlign($a) {$this->align = $a;}

    /**
     * adds a new callback for stylish
     *
     * @param callback $c
     * @return void
     */
    public function addCallback($c) {$this->callbacks[] = $c;}

    /**
     * implements the printing for a cell
     *
     * @return void
     */
    public function print()
    {
        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY();
        if ($this->isTableCell()) {
            $table = $this->getValue();
            $table->setWidth($this->width);
            $table->setFillHeight($this->height);
            $table->print();
            $this->pdf->SetXY($x + $this->width,$y);
        } else {
            $this->applyStyles();

            //Draw the border
            $this->pdf->rect($x,$y,$this->width,$this->height);

            //Print the text
            $this->pdf->multiCell(
                $this->width,
                $this->pdf->getCellHeight(),
                $this->getValue(),
                0,
                $this->align
            );
        }
    }

    /**
     * computes the number of lines needed for this cell
     *
     * @return int
     */
    public function getNbLines()
    {
        if ($this->isTableCell()) {
            $this->value->setWidth($this->width);
            return $this->value->getNbLines();
        }
        $this->applyStyles();
        return $this->pdf->nbLines($this->width, $this->value);
    }

    /**
     * applies styles to this cell
     *
     * @return void
     */
    protected function applyStyles()
    {
        $reverse = \array_reverse($this->callbacks);
        foreach ($reverse as $c) {
            $c($this->pdf);
        }
    }
}