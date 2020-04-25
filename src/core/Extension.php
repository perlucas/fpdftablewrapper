<?php
namespace FPDFWrapper\Core;

class ExtensionPdf extends PDFplataforma 
{
    /**
     * pila de contextos
     *
     * @var SplStack
     */
    protected $contexts;

    /**
     * repositorio de tablas, headers, configuraciones
     *
     * @var HashMap
     */
    protected $repo;

    /**
     * id de la tabla actualmente siendo configurada
     *
     * @var string
     */
    protected $currentTableId;

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
     * header del pdf
     *
     * @var string
     */
    protected $headerString;

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
        $this->repo = new HashMap();
        $this->headerString = '';
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
        $this->currentTable = new Table($widthsArr, $alignsArgument, $headersArr);
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
            $this->_printTable($this->initialTable, $this->w - $this->lMargin - $this->rMargin);
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
        $row = new Row();
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
        $this->currentTable->addRow(new Row($row));
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
        $this->contexts->push(new Cell());
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
        $this->repo->set("style_row@{$this->currentTableId}@{$rowindex}", $callback);
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
        $this->repo->set("style_col@{$this->currentTableId}@{$colindex}", $callback);
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
        $this->repo->set("style_cell@{$this->currentTableId}@{$rowindex}@{$colindex}", $callback);
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
        $this->repo->set("style@{$alias}", $func);
    }

    /**
     * ejecuta el callback de estilos
     *
     * @param string $alias
     * @return void
     */
    public function useStyle($alias)
    {
        $cc = $this->_getOrFail("style@{$alias}", false, '');
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

    /* Métodos internos auxiliares */

    protected function _getOrFail($key, $throwException, $exceptionMessage)
    {
        if (!$this->repo->exists($key)) {
            if ($throwException) {
                throw new Exception($exceptionMessage, 1);
            } else {
                return false;
            }
        }
        return $this->repo->get($key);
    }

    protected function _getTableWidths($tableId)
    {
        return $this->_getOrFail("widths@{$tableId}", true, "Widths not set for table {$tableId}");
    }

    protected function _getTableAligns($tableId)
    {
        return $this->_getOrFail("aligns@{$tableId}", true, "Aligns not set for table {$tableId}");
    }

    protected function _getTableHeader($tableId)
    {
        return $this->_getOrFail("header@{$tableId}", false, '');
    }
    
    protected function _getCurrentContext()
    {
        return $this->contexts->top()->getType();
    }

    protected function _setTableBody($tableId, array $body)
    {
        $this->repo->set("body@{$tableId}", $body);
    }

    protected function _getTableBody($tableId)
    {
        return $this->_getOrFail("body@{$tableId}", false, '');
    }

    protected function _printTable($tableId, $totalWidth, $fillHeight = null)
    {
        $widths = $this->_getTableWidths($tableId);
        $aligns = $this->_getTableAligns($tableId);
        $header = $this->_getTableHeader($tableId);
        $body = $this->_getTableBody($tableId);

        if ($header) {
            array_unshift($body, $header);
        }

        array_walk($widths, function(&$val, $key) use($totalWidth) {
            $val = ($val * $totalWidth) / 100;
        });
        $initialX = $this->getX();
        $cantRows = count($body);
        $heightOfRows = [];
        foreach ($body as $rowindex => $row) {

            // determinar saltos de linea que ocupa la fila
            $nbLinesRow = $this->_getNbLinesRow($row, $widths, $rowindex, $tableId, $header);
            $heightRow = $this->getCellHeight() * $nbLinesRow;
            if ($rowindex == $cantRows - 1 && $fillHeight) {
                $remaining = $fillHeight - array_sum($heightOfRows);
                assert($remaining >= $heightRow, "Bad heightRow and fillheight configuration");
                $heightRow = $remaining;
            }
            $heightOfRows[] = $heightRow;
            
            // colocar salto de pagina si es necesario
            $this->checkPageBreak($heightRow);
            
            // imprimir cada celda de la fila
            foreach ($row as $cellindex => $cell) {
                //Save the current position
                $x = $this->GetX();
                $y = $this->GetY();
                
                if (explode('@', $cell)[0] === 'table') {
                    $this->_printTable(explode('@', $cell)[1], $widths[$cellindex], $heightRow);
                    $this->SetXY($x + $widths[$cellindex],$y);
                } else {
                    $this->_setPrintingStyle($tableId, $rowindex, $cellindex, $header);

                    //Draw the border
                    $this->rect($x,$y,$widths[$cellindex],$heightRow);

                    //Print the text
                    $align = $aligns[$cellindex];
                    if ($rowindex == 0 && $header) $align = 'C';
                    $this->multiCell($widths[$cellindex],$this->getCellHeight(),$cell,0,$align);
                }
                $this->SetXY($x + $widths[$cellindex],$y);
            }
            $this->ln($heightRow);
            $this->setX($initialX);
        }
    }

    protected function _getNbLinesRow(array $row, array $widths, $rowindex, $tableId, $hasHeader)
    {
        $max = 0;
        foreach ($row as $cellindex => $cell) {
            if (explode('@', $cell)[0] === 'table') {
                $nb = $this->_getNbLinesTable(explode('@', $cell)[1], $widths[$cellindex]);
            } else {
                $this->_setPrintingStyle($tableId, $rowindex, $cellindex, $hasHeader);
                $nb = $this->nbLines($widths[$cellindex], $cell);
            }
            $max = max($max, $nb);
        }

        return $max;
    }

    protected function _getNbLinesTable($tableId, $totalW)
    {
        $widths = $this->_getTableWidths($tableId);
        $header = $this->_getTableHeader($tableId);
        $body = $this->_getTableBody($tableId);

        if ($header) array_unshift($body, $header);
        
        array_walk($widths, function(&$val, $key) use($totalW) {
            $val = ($val * $totalW) / 100;
        });

        $nb = 0;
        foreach ($body as $rowindex => $row) {
            $nb += $this->_getNbLinesRow($row, $widths, $rowindex, $tableId, $header);
        }
        return $nb;
    }

    protected function _setPrintingStyle($tableId, $rowindex, $colindex, $hasHeader)
    {
        if ($rowindex == 0 && $hasHeader) {
            $this->setTableHeaderStyle();
        } else {
            $this->setTableBodyStyle();
        }
        $cc = $this->_getOrFail("style_cell@{$tableId}@{$rowindex}@{$colindex}", false, '');
        if (!$cc && ($rowindex || !$hasHeader)) {
            $cc = $this->_getOrFail("style_col@{$tableId}@{$colindex}", false, '');
        }
        if (!$cc) $cc = $this->_getOrFail("style_row@{$tableId}@{$rowindex}", false, '');
        if ($cc) $cc($this);
    }
}