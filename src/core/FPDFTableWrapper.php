<?php

namespace FPDFWrapper\Core;

class FPDFTableWrapper extends FPDF 
{
    /**
     * contexts stack
     *
     * @var \SplStack
     */
    protected $contexts;

    /**
     * map for storing style callbacks
     *
     * @var array
     */
    protected $styles;

    /**
     * current table being plotted
     *
     * @var Table
     */
    protected $currentTable;

    /**
     * initial table
     *
     * @var Table
     */
    protected $initialTable;

    /**
     * default table cell height
     *
     * @var float
     */
    protected $cellHeight = 5;

    /**
     * constructs a new instance
     *
     * @param string $orientation
     * @param string $unit
     * @param string $size
     */
    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        $this->contexts = new \SplStack();
        $this->styles = [];
    }

    /**
     * sets the cell height
     *
     * @param float $vv
     * @return void
     */
    public function setCellHeight($vv) {$this->cellHeight = $vv;}

    /**
     * gets the cell height
     *
     * @return float
     */
    public function getCellHeight() {return $this->cellHeight;}

    /**
     * configures a table's plotting
     *
     * @param array $widthsArr - array width percentage occupped by each column
     * @param array|string $alignsArgument - aligns for each column
     * @param array $headersArr - optional. Header titles
     * @return void
     */
    public function openTable(array $widthsArr, $alignsArgument, array $headersArr = null)
    {
        // validate previous context
        assert(
            $this->contexts->isEmpty() ||
            $this->_getCurrentContext() === 'cell',
            "Invalid context for openTable"
        );

        // add new table
        $this->currentTable = new Table($this, $widthsArr, $alignsArgument, $headersArr);
        if ($this->contexts->isEmpty()) $this->initialTable = $this->currentTable;

        // change current context
        $this->contexts->push($this->currentTable);
    }

    /**
     * close a table's plotting configuration
     *
     * @return void
     */
    public function closeTable()
    {
        // validate current context
        assert(
            $this->_getCurrentContext() === 'table',
            "Invalid context for closeTable"
        );

        // change current context
        $oldContext = $this->contexts->pop();
        if (! $this->contexts->isEmpty()) {
            $this->currentTable = $this->contexts->top();
        }

        // if we're in a cell context, then set it as a table in a cell
        if (!$this->contexts->isEmpty() && $this->_getCurrentContext() === 'cell') {
            $this->currentTable->getCurrentRow()->addCellValue($oldContext);
        } else {
            // print the table if there's no more contexts
            $this->initialTable->setWidth($this->w - $this->lMargin - $this->rMargin);
            $this->initialTable->print();
        }
    }

    /**
     * open a new row plotting configuration
     *
     * @return void
     */
    public function openRow()
    {
        // validate current context
        assert($this->_getCurrentContext() === 'table', "Invalid context for openRow");

        // change context
        $row = new Row($this);
        $this->contexts->push($row);
        $this->currentTable->setCurrentRow($row);
    }

    /**
     * closes a row plotting configuration
     *
     * @return void
     */
    public function closeRow()
    {
        // validate context
        assert($this->_getCurrentContext() === 'row', "Invalid context for closeRow");

        // validate row size
        $row = $this->currentTable->getCurrentRow();
        assert(
            $row->count() === $this->currentTable->countColumns(),
            'Invalid size of row'
        );
        
        // put row on table body
        $this->currentTable->addRow($row);
        $this->currentTable->deleteCurrentRow();

        // change context
        $this->contexts->pop();
    }

    /**
     * adds a row to the table
     *
     * @param array $row
     * @return void
     */
    public function printRow(array $row)
    {
        // validate context
        assert($this->_getCurrentContext() === 'table', "Invalid context for printRow");

        // verify size of row
        assert(
            count($row) === $this->currentTable->countColumns(),
            'Invalid size of row'
        );

        // add row to table body
        $this->currentTable->addRow(new Row($this, $row));
    }

    /**
     * opens a cell's plotting configuration
     *
     * @return void
     */
    public function openCell()
    {
        // validate current context
        assert($this->_getCurrentContext() === 'row', "Invalid context for openCell");

        // change context
        $this->contexts->push(new Cell($this));
    }

    /**
     * closes a cell's plotting configuration
     *
     * @return void
     */
    public function closeCell()
    {
        // validate current context
        assert($this->_getCurrentContext() === 'cell', "Invalid context for closeCell");

        // change context
        $this->contexts->pop();
    }
    
    /**
     * prints a cell's value on a row context
     *
     * @param string $content
     * @return void
     */
    public function printCell($content)
    {
        // validate context
        assert($this->_getCurrentContext() === 'row', "Invalid context for printCell {$content}");

        if (! \is_scalar($content)) {
            throw new \Exception("Content must be a string value", 1);
        }

        $this->currentTable->getCurrentRow()->addCellValue($content);
    }

    /**
     * adds a callback for styling a row in the table
     *
     * @param int $rowindex
     * @param callback $callback
     * @return void
     */
    public function setRowStyle($rowindex, $callback)
    {
        $this->currentTable->setRowStyle($rowindex, $callback);
    }

    /**
     * adds a callback for styling a column in the current table
     *
     * @param int $colindex
     * @param callback $callback
     * @return void
     */
    public function setColumnStyle($colindex, $callback)
    {
        $this->currentTable->setColumnStyle($colindex, $callback);
    }

    /**
     * adds a callback for styling a cell in the current table
     *
     * @param int $rowindex
     * @param int $colindex
     * @param callback $callback
     * @return void
     */
    public function setCellStyle($rowindex, $colindex, $callback)
    {
        $this->currentTable->setCellStyle($rowindex, $colindex, $callback);
    }

    /**
     * sets the printing style to print the table header
     *
     * @return void
     */
    public function setTableHeaderStyle()
    {
        $this->setFont('Arial', 'B', 9);
        $this->SetFillColor(233,241,219);
    }

    /**
     * sets the printing styles to print the table body
     *
     * @return void
     */
    public function setTableBodyStyle()
    {
        $this->setFont('Arial', '', 8);
        $this->SetFillColor(255, 255, 255);
    }

    /**
     * adds a new callback using an alias
     *
     * @param string $alias
     * @param callback $func
     * @return void
     */
    public function addStyle($alias, $func)
    {
        $this->styles[$alias] = $func;
    }

    /**
     * executes the callback stored with the alias
     *
     * @param string $alias
     * @return void
     */
    public function useStyle($alias)
    {
        $cc = \array_key_exists($alias, $this->styles)
            ? $this->styles[$alias] : null;
        if ($cc) $cc($this);
    }
    
    /**
     * returns the current context type
     *
     * @return string
     */
    protected function _getCurrentContext()
    {
        return $this->contexts->top()->getType();
    }

    /**
     * @Override
     */
    public function cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = 'L', $fill=false, $link='')
    {
        if (!$h) $h = $this->getCellHeight();
        parent::cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    /**
     * nblines implementation
     *
     * @see http://www.fpdf.org/en/script/script3.php
     * @param float $w
     * @param string $txt
     * @return int
     */
    public function NbLines($w, $txt)
    {
        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->CurrentFont['cw'];
        if($w==0)
            $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb)
        {
            $c=$s[$i];
            if($c=="\n")
            {
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax)
            {
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                }
                else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }

    /**
     * check page break implementation
     * 
     * @see http://www.fpdf.org/en/script/script3.php
     * @param float $h
     * @return void
     */
    public function CheckPageBreak($h)
    {
        //If the height h would cause an overflow, add a new page immediately
        if($this->GetY()+$h>$this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }
}