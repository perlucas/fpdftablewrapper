<?php

use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

/**
 * basic 1-level nested table
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
	
	// use  open row for access each cell
	$pdf->openRow();
		$pdf->printCell("First value 3");
		$pdf->printCell("Second value 3");
		
		// open a new cell
		$pdf->openCell();
			$pdf->openTable(
                [40, 40, 20],
                "R", // short way of doing ["R", "R", "R"]
                ['col1', 'col2', 'col3']
            );
				$pdf->printRow(['A', 'B', 'C']);
				$pdf->printRow(['D', 'E', 'F']);
			$pdf->closeTable(); // close the nested table
		$pdf->closeCell(); // close cell
	$pdf->closeRow();

$pdf->closeTable(); // close parent table
$pdf->output();