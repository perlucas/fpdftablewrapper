<?php
namespace FPDFWrapper\Core;

class FPDFTableWrapper extends FPDF 
{
    /**
     * pila de contextos
     *
     * @var SplStack
     */
    protected $contexts;

    protected $styles;

    /**
     * current table being plotted
     *
     * @var core\Table
     */
    protected $currentTable;

    /**
     * tabla inicial
     *
     * @var string
     */
    protected $initialTable;

    /**
     * altura de cada celda por defecto
     *
     * @var float
     */
    protected $cellHeight;

    /**
     * construye e inicializa una instancia
     */
    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4');
        $this->B=0;
		$this->I=0;
		$this->U=0;
        $this->HREF='';
        $this->contexts = new \SplStack();
        $this->styles = [];
        $this->cellHeight = 5;
    }

    /**
     * establece la altura de cada celda por defecto
     *
     * @param float $vv
     * @return void
     */
    public function setCellHeight($vv) {$this->cellHeight = $vv;}

    /**
     * devuelve la altura de cada celda
     *
     * @return float
     */
    public function getCellHeight() {return $this->cellHeight;}

    /**
     * configures a table's plotting
     *
     * @param array $widthsArr - array with percentage occupped for each column
     * @param array|string $alignsArgument - aligns for each column
     * @param array $headersArr - optional. Header strings
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
        // validar contexto
        assert($this->_getCurrentContext() === 'table', "Invalid context for printRow");

        // verificar tamaño de la fila
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

        if (is_array($content) || is_object($content)) {
            throw new \Exception("Content must be a string value", 1);
        }

        $this->currentTable->getCurrentRow()->addCellValue($content);
    }

    /**
     * agrega un callback de estilos para una fila de la tabla actual
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
     * agrega un callback de estilos para una columna de la tabla actual
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
     * agrega un callback de estilos para una celda de la tabla actual
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
     * activa los estilos utilizados para imprimir un header de tabla
     *
     * @return void
     */
    public function setTableHeaderStyle()
    {
        $this->setFont('Arial', 'B', 9);
        $this->SetFillColor(233,241,219);
    }

    /**
     * activa los estilos utilizados para imprimir un cuerpo de tabla
     *
     * @return void
     */
    public function setTableBodyStyle()
    {
        $this->setFont('Arial', '', 8);
        $this->SetFillColor(255, 255, 255);
    }

    /**
     * agrega un nuevo callback de estilos
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
     * ejecuta el callback de estilos
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
     * @Override
     */
    public function cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = 'L', $fill=false, $link='')
    {
        if (!$h) $h = $this->getCellHeight();
        parent::cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }


    
    
    protected function _getCurrentContext()
    {
        return $this->contexts->top()->getType();
    }

}