<?php

use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

/**
 * basic example
 */

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setTableHeaderStyle();
$pdf->cell(20, 8, 'New table');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], // widths of each column in %
    ['L', 'L', 'R'],  // align of each column (header is always centered)
    ['Title 1', 'Title 2', 'Title 3'] // headers
);
    // open a new row and print each cell content
	$pdf->openRow();
		$pdf->printCell("First value");
		$pdf->printCell("Second value");
		$pdf->printCell("Third value");
    $pdf->closeRow();
    
    // shorthand way of doing previous action
    $pdf->printRow(["First value 2","Second value 2","Third value 2"]);

// close table
$pdf->closeTable();

$pdf->output();
