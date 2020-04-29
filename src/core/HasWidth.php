<?php

namespace FPDFWrapper\Core;

trait HasWidth
{
    /**
     * width of this element
     *
     * @var float
     */
    protected $width;

    /**
     * sets the width
     *
     * @param float $w
     * @return void
     */
    public function setWidth($w) {$this->width = $w;}
}