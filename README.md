# FPDFTableWrapper: a table-oriented wrapper for FPDF

`FPDFTableWrapper` is an extension of the well-known `FPDF` class that introduces methods and utilities for making table printing an easy and agile activity. This extension will be helpfull while working on PDF development using FPDF. If your document prints tables that can be difficult to construct using the ordinary  FPDF methods, then this extension is what you need to make the table-designing and printing an easy task.

- [FPDFTableWrapper: a table-oriented wrapper for FPDF](#fpdftablewrapper--a-table-oriented-wrapper-for-fpdf)
  * [Basic usage](#basic-usage)
    + [Nested tables](#nested-tables)
    + [Table merging and cell divisions](#table-merging-and-cell-divisions)
    + [Table styles](#table-styles)
      - [Cells height](#cells-height)
    + [Row styles](#row-styles)
      - [Column styles](#column-styles)
      - [Cell styles](#cell-styles)
      - [Styles in nested tables](#styles-in-nested-tables)
      - [Priority of styles](#priority-of-styles)
    + [Additional methods](#additional-methods)
  * [Additional resources](#additional-resources)


## Basic usage

`FPDFTableWrapper` is an extension of `FPDF` class. You can use the traditional `FPDF` methods for printing and laying out your document. The wrapper only must be used when printing table-oriented structures (i.e: basic tables, nested tables, etc.). Let's see a basic example:

```php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'Nueva tabla');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Titulo 1', 'Titulo 2', 'Titulo 3']
);
	$pdf->openRow();
		$pdf->printCell("Primer valor");
		$pdf->printCell("Segundo valor");
		$pdf->printCell("Tercer valor");
	$pdf->closeRow();
$pdf->closeTable();

$pdf->output();
```

We've created a new instance of the wrapper by using the default constructor (which uses the default params defined in _FPDF()_). Then we added a new page, choosed a font, and printed a line break. What's next? the table definition. The `openTable` method let us open a new table that will be printed in the PDF. `openTable` must be invoked with 3 params:

1. An array of `float` that indicates the width of each column of the table in %.
2. An array of `strings` that indicates the alignment of the text of each column of the table. It must be "C" for centered, "L" for left or "R" for right.
3. Optionally, you can use a third parameter indicating the header of the table. This parameter must be an array of titles for each column.

Once we opened the table, we must indicate the table content by means of table rows. The `openRow` method is used for signaling the start of a new row in the table, while the `closeRow` method indicates the end of the row. You can think of the _open_ and _close_ methods as they where the  `<table></table>`, `<tr></tr>` and `<td></td>` tags you use when making a HTML-based table.

With `printCell` we can introduce a new cell into a row. Note that the amount of cells we can introduce in a row must be the same for each row in the same parent table.

Also note that each _open_ method must have its corresponding _close_ method. The resulting PDF document is:![image-20200407162656090](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200407162656090.png)

Note that the header titles are always centered.

We've just created our first table. We will use the wrapper for printing tables using `FPDF` in this declarative way. If you go back and take a quick view on the code, you'll notice that we indicate the layout of the table, the width of each column, the amount of rows and the content of each row. This declarative syntax allows a better understandability to the final structure of the table.

We can use the `openRow` methods as many times as we want to add more rows:

```php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'Nueva tabla');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Titulo 1', 'Titulo 2', 'Titulo 3']
);
	$pdf->openRow();
		$pdf->printCell("Primer valor");
		$pdf->printCell("Segundo valor");
		$pdf->printCell("Tercer valor");
	$pdf->closeRow();
    $pdf->openRow();
        $pdf->printCell("Primer valor 2");
        $pdf->printCell("Segundo valor 2");
        $pdf->printCell("Tercer valor 2");
    $pdf->closeRow();
	$pdf->openRow();
		$pdf->printCell("Primer valor 3");
		$pdf->printCell("Segundo valor 3");
		$pdf->printCell("Tercer valor 3");
	$pdf->closeRow();
$pdf->closeTable();

$pdf->output();
```

![image-20200407163106254](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200407163106254.png)

If we know that the cells of a row contains just plain text, then we can use the shorthand way for add new rows to a table. The `printRow` is the method we're looking for. (Yes, I know what you're thinking, and that's correct: the `printCell` is the shorthand method for oppening-printing-closing a cell in a row):

```php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'Nueva tabla');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Titulo 1', 'Titulo 2', 'Titulo 3']
);
	$pdf->printRow(["Primer valor","Segundo valor","Tercer valor"]);
	$pdf->printRow(["Primer valor 2","Segundo valor 2","Tercer valor 2"]);
	$pdf->printRow(["Primer valor 3","Segundo valor 3","Tercer valor 3"]);
$pdf->closeTable();

$pdf->output();
```

The `printRow` receives an array with the text that goes on each cell of the row.

### Nested tables

We define a nested table as a table that acts as the cell of another parent table. That meaning indicates us that we need to open a new table inside of a cell for supporting nested table with the wrapper:

```php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'Nueva tabla');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Titulo 1', 'Titulo 2', 'Titulo 3']
);
	$pdf->printRow(["Primer valor","Segundo valor","Tercer valor"]);
	$pdf->printRow(["Primer valor 2","Segundo valor 2","Tercer valor 2"]);
	
	// use  open row for access each cell
	$pdf->openRow();
		$pdf->printCell("Primer valor 3");
		$pdf->printCell("Segundo valor 3");
		
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
```

The resulting PDF is:

![image-20200407171321864](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200407171321864.png)

Even wi can add another nesting level:

```php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'Nueva tabla');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Titulo 1', 'Titulo 2', 'Titulo 3']
);
	$pdf->printRow(["Primer valor","Segundo valor","Tercer valor"]);
	$pdf->printRow(["Primer valor 2","Segundo valor 2","Tercer valor 2"]);
	$pdf->openRow();
		$pdf->printCell("Primer valor 3");
		$pdf->printCell("Segundo valor 3");
		$pdf->openCell();
			$pdf->openTable(
                [40, 40, 20],
                "R", // todas las columnas a la derecha
                ['col1', 'col2', 'col3']
            );
				$pdf->printRow(['A', 'B', 'C']);
				$pdf->openRow();
					$pdf->printCell('D');
					$pdf->printCell('E');
					$pdf->openCell();
						$pdf->openTable([50, 50], ['R', 'L'], ['F1', 'F2']);
							$pdf->printRow(["f11", "f22"]);
							$pdf->printRow(["f33", "f44"]);
						$pdf->closeTable();
					$pdf->closeCell();
				$pdf->closeRow();
			$pdf->closeTable();
		$pdf->closeCell();
	$pdf->closeRow();
$pdf->closeTable();
$pdf->output();
```

![image-20200407180529739](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200407180529739.png)

### Table merging and cell divisions

The third argument of the `openTable` indicates the header titles for each column of the table. This is an optional argument. We can omit it in order to make a new table partition. That allows us to make divisions in cells:

```php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'Nueva tabla');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Titulo 1', 'Titulo 2', 'Titulo 3']
);
	$pdf->printRow(["Primer valor","Segundo valor","Tercer valor"]);
	$pdf->printRow(["Primer valor 2","Segundo valor 2","Tercer valor 2"]);
	$pdf->openRow();
		$pdf->printCell("Primer valor 3");
		$pdf->printCell("Segundo valor 3");		
		$pdf->openCell();
			$pdf->openTable([40, 40, 20],"R"); // no header
				$pdf->printRow(['A', 'B', 'C']);
				$pdf->printRow(['D', 'E', 'F']);
			$pdf->closeTable();
		$pdf->closeCell();
	$pdf->closeRow();
$pdf->closeTable();
$pdf->output();
```

We get the following PDF:

![image-20200408084636831](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200408084636831.png)

Some of the examples we can build using this feature:

![image-20200408085354110](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200408085354110.png)

![image-20200408111423568](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200408111423568.png)

![image-20200408114049159](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200408114049159.png)

### Table styles

The `FPDFTableWrapper` uses a mechanism that let us define the visual style of a table according to our custom requirements. We can customize the visual style of a particular row in a table, a column or even a specific cell. When we talk about styles, we refer to font size, font wheight, fill color and other printing configurations that we can set using the traditional `FPDF` methods.

#### Cells height

The `setCellHeight` method let us modify the height of each cell in a table, using a value that is different of the default `5`. The `setCellHeight` method alters the height of all the rows in a table:

```php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'Nueva tabla');
$pdf->ln();
$pdf->setCellHeight(15); // change cell height
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Titulo 1', 'Titulo 2', 'Titulo 3']
);
	$pdf->printRow(["A","B","C"]);
	$pdf->printRow(["D","E","F"]);
	$pdf->printRow(["G","H","I"]);
$pdf->closeTable();

$pdf->output();
```

![image-20200408114716203](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200408114716203.png)

### Row styles

The `setRowStyle` method can be used to associate a style callback to a row in a table. The first argument must indicate the index of a row. The 0 value indicates the first row of the table or the header if the table has one. The second argument must be a callback that uses the wrapper for configuring the printing style of the values in that row. The callback will be applied for each cell in the row before printing its value:

```php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'Nueva tabla');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Titulo 1', 'Titulo 2', 'Titulo 3']
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
```

![image-20200408115530966](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200408115530966.png)

Before printing a specific cell, by default, the wrapper executes the `setTableBodyStyle` method for preparing the printing configuration of normal rows of the table, and the `setTableHeaderStyle` method for preparing the printing styles of the header. You cand extend the wrapper and define your own row and header styles.

#### Column styles

Alike the row styles, we have the `setColumnStyle` method for attaching callbacks for each specific column in a table. The 0 column will be the first. The styles are applied to each cell that belongs to that number of column with the exception of the header cells (the wrapper understands that the header cell styles are defined on the 0-row callbacks or the `setTableHeaderStyle` method):

```php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'Nueva tabla');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Titulo 1', 'Titulo 2', 'Titulo 3']
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
```

![image-20200408120544339](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200408120544339.png)

#### Cell styles

Finally, we can define a callback that affects a specific cell on a table. The `setCellStyle` method provides a way of declare specific cell styles. The first argument must indicate the row, the second is the column and the third is the callback:

```php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'Nueva tabla');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Titulo 1', 'Titulo 2', 'Titulo 3']
);
	$pdf->printRow(["A","B","C"]);
	$pdf->printRow(["D","E","F"]);
	$pdf->printRow(["G","H","I"]);

	$pdf->setCellStyle(2, 1, function($pdf) {
       $pdf->setFont('Times', 'I', 20);
       $pdf->setFillColor(250, 155, 135);
    });

	$pdf->setCellStyle(3, 0, function($pdf) {
       $pdf->setFont('Times', 'U', 20);
       $pdf->setFillColor(250, 250, 135);
    });

$pdf->closeTable();

$pdf->output();
```

![image-20200408121012874](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200408121012874.png)

#### Styles in nested tables

Obviously we can use the callback methods in a nested table. You can put wherewer you want the callback methods, just remember to use them inside a `openTable` and a `closeTable` call. The method will affect the row/column/cell of the specific table that contains it:

```php
use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->cell(20, 8, 'Nueva tabla');
$pdf->ln();
$pdf->openTable(
    [30, 30, 40], 
    ['L', 'L', 'R'], 
    ['Titulo 1', 'Titulo 2', 'Titulo 3']
);
	$pdf->printRow(["Primer valor","Segundo valor","Tercer valor"]);
	$pdf->printRow(["Primer valor 2","Segundo valor 2","Tercer valor 2"]);
	$pdf->openRow();
		$pdf->printCell("Primer valor 3");
		$pdf->printCell("Segundo valor 3");		
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
```

![image-20200408123243207](C:\Users\lucas\AppData\Roaming\Typora\typora-user-images\image-20200408123243207.png)

#### Priority of styles

If a specific cell is affected by a callback defined to its row, another one applied to its column and a third one defined over that specific cell, then the priority order followed to determine which callback will be applied is:

1. Firstly, the wrapper will search a callback defined for that specific cell. If it find one, then it will be applied instead of others.
2. If there's no callback defined for that cell, then the wrapper will look for a callback defined for the row containing that cell. If found, then it will be used insted of others.
3. If there's no row callback defined for that cell, then the wraper will attempt to find a column callback defined for that cell.

The wrapper always executes the `setTableBodyStyle` or `setTableHeaderStyle` on a row before executing the specific style callbacks defined.

### Additional methods

The `addStyle` method defined on the wrapper lets us define a callback that contains printing configurations using an alias. We can execute the callback later with the `useStyle` method:

```php
<?php

use FPDFWrapper\Core\FPDFTableWrapper as Wrapper;

include '../vendor/autoload.php';

$pdf = new Wrapper();
$pdf->addPage();
$pdf->setFont('Arial', 'B', 9);
$pdf->addPage();
$pdf->addStyle('titleStyle', function($pdf) {
    $pdf->setFont('Arial', 'B', 15);
});
// ...later
$pdf->useStyle('titleStyle');
$pdf->cell(30, 'This is a title');
```

## Additional resources

You can visit the [examples](./examples) folder for inspiring yourself about how to use this tool.