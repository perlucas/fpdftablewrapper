<?php

namespace FPDFWrapper\Core;

trait ColumnsContainer
{
    /**
     * widths for each column
     *
     * @var array
     */
    protected $widths;

    /**
     * aligns for each column
     *
     * @var array
     */
    protected $aligns;

    /**
     * sets the aligns
     *
     * @param array $aa
     * @return void
     */
    public function setAligns(array $aa) {$this->aligns = $aa;}

    /**
     * sets the widths
     *
     * @param array $ww
     * @return void
     */
    public function setWidths(array $ww) {$this->widths = $ww;}

    /**
     * returns the amount of columns
     *
     * @return int
     */
    public function countColumns() {return count($this->aligns);}
}