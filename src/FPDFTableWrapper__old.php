<?php

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
     * establece el valor del header
     *
     * @param string $val
     * @return void
     */
    public function setHeader($val) {$this->headerString = $val;}

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
     * configura la impresión de una tabla
     *
     * @param array $widthsArr - array con el ancho de cada columna en términos de %
     * @param array|string $alignsArgument - array con el valor de alineación de cada columna o string 
     * indicando el valor de alineación para todas
     * @param array $headersArr - opcional. Array con los titulos de cada columna
     * @return void
     */
    public function openTable(array $widthsArr, $alignsArgument, array $headersArr = [])
    {
        // validar contexto previo
        assert(
            $this->contexts->isEmpty() ||
            $this->_getCurrentContext() === 'cell',
            "Invalid context for openTable"
        );

        $this->currentTableId = AbstractWidget::generateId();
        if ($this->contexts->isEmpty()) $this->initialTable = $this->currentTableId;

        // agregar anchos
        $this->_setTableWidths($this->currentTableId, $widthsArr);

        // agregar alineaciones
        $alignsArr = is_string($alignsArgument)
            ? array_fill(0, count($widthsArr), $alignsArgument)
            : $alignsArgument;
        $this->_setTableAligns($this->currentTableId, $alignsArr);

        // agregar header si viene
        if (!empty($headersArr)) $this->_setTableHeader($this->currentTableId, $headersArr);

        // cambiar contexto actual
        $this->contexts->push("table@{$this->currentTableId}");
    }

    /**
     * cierra la configuración de una tabla
     *
     * @return void
     */
    public function closeTable()
    {
        // validar contexto actual
        assert(
            $this->_getCurrentContext() === 'table',
            "Invalid context for closeTable"
        );

        // cambiar el contexto actual
        $oldContext = $this->contexts->pop();
        if (! $this->contexts->isEmpty()) {
            $this->currentTableId = explode('@', $this->contexts->top())[1];
        }

        // si estoy en contexto de una celda, entonces colocar el valor table@<id> en esa celda
        if (!$this->contexts->isEmpty() && $this->_getCurrentContext() === 'cell') {
            $row = $this->_getRowCache($this->currentTableId);
            $row[] = $oldContext;
            $this->_setRowCache($this->currentTableId, $row);

        } else {
            // si al cambiar el contexto no hay más elementos contextuales, entonces imprimir
            $this->_printTable($this->initialTable, $this->w - $this->lMargin - $this->rMargin);
        }
    }

    /**
     * abre la configuración de una fila en la tabla
     *
     * @return void
     */
    public function openRow()
    {
        // validar contexto actual
        assert($this->_getCurrentContext() === 'table', "Invalid context for openRow");

        // cambiar contexto
        $this->contexts->push("row@{$this->currentTableId}");
        $this->_setRowCache($this->currentTableId, []);
    }

    /**
     * cierra la configuración de una fila en la tabla
     *
     * @return void
     */
    public function closeRow()
    {
        // validar contexto
        assert($this->_getCurrentContext() === 'row', "Invalid context for closeRow");

        // verificar tamaño de la fila
        $row = $this->_getRowCache($this->currentTableId);
        assert(
            count($row) === count($this->_getTableAligns($this->currentTableId)),
            'Invalid size of row'
        );
        
        // colocar la fila en el cuerpo de la tabla
        $this->_addTableRow($this->currentTableId, $row);
        $this->_deleteRowCache($this->currentTableId);

        // cambiar el contexto
        $this->contexts->pop();
    }

    /**
     * agrega una fila a la tabla
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
            count($row) === count($this->_getTableAligns($this->currentTableId)),
            'Invalid size of row'
        );

        // coloca una nueva fila en el cuerpo de la tabla
        $this->_addTableRow($this->currentTableId, $row);
    }

    /**
     * abre la configuracion de una celda
     *
     * @return void
     */
    public function openCell()
    {
        // validar contexto actual
        assert($this->_getCurrentContext() === 'row', "Invalid context for openCell");

        // cambiar contexto
        $this->contexts->push("cell@{$this->currentTableId}");
    }

    /**
     * cierra la configuración de una celda
     *
     * @return void
     */
    public function closeCell()
    {
        // validar contexto actual
        assert($this->_getCurrentContext() === 'cell', "Invalid context for closeCell");

        // cambiar contexto
        $this->contexts->pop();
    }
    
    /**
     * imprime el valor de una celda en el contexto de una fila
     *
     * @param string $content
     * @return void
     */
    public function printCell($content)
    {
        // validar contexto actual
        assert($this->_getCurrentContext() === 'row', "Invalid context for printCell {$content}");

        if (is_array($content) || is_object($content)) {
            throw new Exception("Content must be a string value", 1);
        }

        $row = $this->_getRowCache($this->currentTableId);
        $row[] = $content;
        $this->_setRowCache($this->currentTableId, $row);
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
     * crea un espacio en blanco que ocupa el porcentaje indicado del ancho
     * 
     * @param float $percent - porcentaje del ancho total que debe ocupar
     * @param float $height - alto de la linea. opcional
     * @return void
     */
    public function makeSpace($percent, $height = null) {
        if (!$height) $height = $this->getCellHeight();
        $total = $this->w - $this->lMargin - $this->rMargin;
        $width = ($total * $percent) / 100;
        $this->cell($width, $height);
    }

    /**
     * @Override
     */
    public function rect($x, $y, $w, $h, $style = 'DF') {parent::rect($x, $y, $w, $h, $style);}
    
    /**
     * @Override
     */
    public function header()
    {
        $this->cabeceraReportes($this->headerString);
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

    protected function _setRowCache($tableId, array $row)
    {
        $this->repo->set("row_cache@{$tableId}", $row);
    }

    protected function _getRowCache($tableId)
    {
        return $this->_getOrFail("row_cache@{$tableId}", false, '');
    }

    protected function _deleteRowCache($tableId)
    {
        $this->repo->remove("row_cache@{$tableId}");
    }

    protected function _setTableWidths($tableId, array $widths) 
    {
        $this->repo->set("widths@{$tableId}", $widths);
    }

    protected function _getTableWidths($tableId)
    {
        return $this->_getOrFail("widths@{$tableId}", true, "Widths not set for table {$tableId}");
    }

    protected function _setTableAligns($tableId, array $aligns)
    {
        $this->repo->set("aligns@{$tableId}", $aligns);
    }

    protected function _getTableAligns($tableId)
    {
        return $this->_getOrFail("aligns@{$tableId}", true, "Aligns not set for table {$tableId}");
    }

    protected function _setTableHeader($tableId, array $header)
    {
        $this->repo->set("header@{$tableId}", $header);
    }

    protected function _getTableHeader($tableId)
    {
        return $this->_getOrFail("header@{$tableId}", false, '');
    }
    
    protected function _getCurrentContext()
    {
        return explode('@', $this->contexts->top())[0];
    }

    protected function _setTableBody($tableId, array $body)
    {
        $this->repo->set("body@{$tableId}", $body);
    }

    protected function _addTableRow($tableId, array $row)
    {
        $body = $this->_getTableBody($tableId);
        if (!$body) $body = [];
        $body[] = $row;
        $this->_setTableBody($tableId, $body);
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