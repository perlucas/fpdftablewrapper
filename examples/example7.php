<?php

use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

/**
 * using style callbacks in a nested table
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
	$pdf->printRow(["First value","Second value","Third value"]);
	$pdf->printRow(["First value 2","Second value 2","Third value 2"]);
	$pdf->openRow();
		$pdf->printCell("First value 3");
		$pdf->printCell("Second value 3");		
		$pdf->openCell();
			$pdf->openTable([40, 40, 20],"R");

				$pdf->printRow(['A', 'B', 'C']);
				$pdf->printRow(['D', 'E', 'F']);
				$pdf->printRow(['G', 'H', 'I']);
				
				$pdf->setColumnStyle(0, function($pdf) {
                    $pdf->setFillColor(250, 250, 135);
                });
				$pdf->setCellStyle(1, 2, function($pdf) {
                    $pdf->setFillColor(250, 155, 135);
                });
                   
			$pdf->closeTable();
		$pdf->closeCell();
	$pdf->closeRow();
$pdf->closeTable();
$pdf->output();