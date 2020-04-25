<?php
namespace FPDFWrapper\Core;

class Table extends Printable
{
    /**
     * widths for each column
     *
     * @var array
     */
    protected $widths;

    /**
     * aligns for each row
     *
     * @var array
     */
    protected $aligns;

    /**
     * flag indicates if the first row is the header
     *
     * @var boolean
     */
    protected $hasHeader;

    /**
     * rows of this table
     * 
     * @var array
     */
    protected $rows;

    /**
     * current row state
     *
     * @var Row
     */
    protected $currentRow;

    /**
     * constructs an instance
     *
     * @param array $widths
     * @param array|float $alignsArgument
     * @param array|null $headerArr
     */
    public function __construct($widths, $alignsArgument, $headerArr = null)
    {
        $this->rows = [];
        $this->widths = $widths;
        $this->aligns = $alignsArgument = is_string($alignsArgument)
            ? array_fill(0, count($widths), $alignsArgument)
            : $alignsArgument;
        if (\is_null($headerArr)) {
            $this->hasHeader = false;
        } else {
            $this->hasHeader = true;
            $this->rows[] = new Row($headerArr);
        }

        // check size of aligns and widths
        $err = 'Invalid number of columns in configuration!';
        if (count($this->widths) !== count($this->aligns)) {
            throw new \Exception($err, 1);
        }
        if (
            $this->hasHeader && 
            (count($this->hasHeader) !== count($this->aligns))
        ) {
            throw new \Exception($err, 1);
        }

    }

    /**
     * string representation of this type of printable
     *
     * @return string
     */
    public function getType() {return 'table';}

    /**
     * returns the current row being plotted
     *
     * @return array
     */
    public function getCurrentRow() 
    {
        if (! $this->currentRow) {
            throw new \Exception("Current row has not been set", 1);
        }
        return $this->currentRow;
    }

    /**
     * stores the current row being plotted
     *
     * @param Row $rr
     * @return void
     */
    public function setCurrentRow(Row $rr) {$this->currentRow = $rr;}

    /**
     * adds a new row
     *
     * @param array $rowValues
     * @return void
     */
    public function addRow(Row $row) {$this->rows[] = $row;}

    /**
     * deletes the current row
     *
     * @return void
     */
    public function deleteCurrentRow() {$this->currentRow = null;}

    /**
     * returns the amount of columns
     *
     * @return int
     */
    public function countColumns() {return count($this->aligns);}
}