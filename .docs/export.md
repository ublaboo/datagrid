Table of contents

- [Exports](#exports)
	- [ExportCallback](#exportcallback)
	- [CSV export](#csv-export)
		- [\(Not\) Using templates in CSV export](#not-using-templates-in-csv-export)
	- [Export columns](#export-columns)
	- [Export encoding, delimiter](#export-encoding-delimiter)

# Exports

## ExportCallback

DataGrid allows you to export the data via `$grid->addExportCallback()`. The parameters are:

```php
/**
 * $text = Button text
 * $callback = your export callback
 * $filtered = $should datagrid pass a filtered data to your callback, or all?
 */
$grid->addExportCallback($text, $callback, $filtered = false);
```

You can tell whether to use ajax or not (`->setAjax()`). Or a button title (`->setTitle('Title')`). Or target (`->setTarget('_blank')`).

## CSV export

There is already a CSV export implemented (filtered and not filtered):

```php
/**
 * Or $grid->addExportCsvFiltered();
 */
$grid->addExportCsv('Csv export (filtered)', 'examples.csv')
	->setTitle('Csv export (filtered)');
```

### (Not) Using templates in CSV export

ExportCsv ignores column template, because i don't like the idea Latte (tempalting engine for HTML) exporting data for CSV format. Using custom renderer sounds better to me in that case.

## Export columns

When you exporting the data, you can have different columns in export and in the datagrid. Or differently rendered. So there is another method `Ublaboo\DataGrid\Export\Export::setColumns()`. You can create instances of another columns and pass them in array to this method. These will be rendered in export:

```php
$column_name = new Ublaboo\DataGrid\Column\ColumnText($grid, 'name', 'name', 'Name');
$column_even = (new Ublaboo\DataGrid\Column\ColumnText($grid, 'name', 'even', 'Even ID (yes/no)'))
	->setRenderer(function(array $item): string {
		return $item['id'] % 2 ? 'No' : 'Yes';
	});

$grid->addExportCsv('Csv export', 'examples_all.csv')
	->setTitle('Csv export')
	->setColumns([
		$column_name,
		$column_even
	]);
```

## Export encoding, delimiter

By default, DataGrid exports data in `utf-8` with semicolon delimiter `;`. This can be changed:

```php
/**
 * Defaults:
 * $output_encoding = 'utf-8'
 * $delimiter       = ';'
 */
$grid->addExportCsvFiltered('Csv export (filtered)', 'examples.csv');

/**
 * Changed
 */
$grid->addExportCsv( 'Csv export', 'examples_all.csv', 'windows-1250', ',');
```
