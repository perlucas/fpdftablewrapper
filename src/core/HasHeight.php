<?php

namespace FPDFWrapper\Core;

trait HasHeight
{
    /**
     * height of this element
     *
     * @var float
     */
    protected $height;

    /**
     * sets the height 
     *
     * @param float $h
     * @return void
     */
    public function setHeight($h) {$this->height = $h;}
}