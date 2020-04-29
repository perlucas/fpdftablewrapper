<?php

namespace FPDFWrapper\Core;

abstract class Printable
{
    /**
     * wrapper for print this object
     *
     * @var FPDFTableWrapper
     */
    protected $pdf;

    /**
     * constructs an instance
     *
     * @param FPDFTableWrapper $pdf
     */
    public function __construct(FPDFTableWrapper $pdf)
    {
        $this->pdf = $pdf;
    }

    /**
     * string representation of the type of printable
     *
     * @return string
     */
    public abstract function getType();

    /**
     * amount of lines needed to print this object
     *
     * @return int
     */
    public abstract function getNbLines();

    /**
     * prints this object using the pdf wrapper
     *
     * @return void
     */
    public abstract function print();
}