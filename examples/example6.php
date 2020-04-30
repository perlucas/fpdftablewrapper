<?php

use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

/**
 * setting a printing callback for specific cells
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
	$pdf->printRow(["A","B","C"]);
	$pdf->printRow(["D","E","F"]);
	$pdf->printRow(["G","H","I"]);

	$pdf->setCellStyle(2, 1, function($pdf) {
       $pdf->setFont('Times', 'I', 20);
       $pdf->setFillColor(250, 155, 135);
    }); // cell 2x1

	$pdf->setCellStyle(3, 0, function($pdf) {
       $pdf->setFont('Times', 'U', 20);
       $pdf->setFillColor(250, 250, 135);
    }); // cell 3x0

$pdf->closeTable();

$pdf->output();