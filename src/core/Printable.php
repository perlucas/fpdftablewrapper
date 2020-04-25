<?php
namespace FPDFWrapper\Core;

abstract class Printable
{
    /**
     * string representation of the type of printable
     *
     * @return string
     */
    public abstract function getType();
}