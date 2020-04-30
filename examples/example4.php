<?php

use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

/**
 * basic table with custom printing style for rows
 */

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'New table');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Title 1', 'Title 2', 'Title 3']
);
	$pdf->setRowStyle(2, function ($pdf) {
       $pdf->setFont('Times', 'U', 10) ;
    }); // applies on the row 2

	$pdf->printRow(["A","B","C"]);
	$pdf->printRow(["D","E","F"]);
	$pdf->printRow(["G","H","I"]);

	$pdf->setRowStyle(0, function ($pdf) {
    	$pdf->setFillColor(50, 50, 0);
    }); // applies on the header

$pdf->closeTable();

$pdf->output();