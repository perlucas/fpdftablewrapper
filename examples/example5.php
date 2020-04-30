<?php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

/**
 * simple example showing styling of columns
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

	$pdf->setColumnStyle(1, function ($pdf) {
    	$pdf->setFontSize(15);
        // applies to the column 1
    });

	$pdf->setColumnStyle(2, function($pdf) {
       $pdf->setFont('Times', 'I', 20);
       $pdf->setFillColor(250, 155, 135);
    }); // applies to the column 2

$pdf->closeTable();

$pdf->output();