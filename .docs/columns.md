Table of contents

- [Columns](#columns)
	- [Api](#api)
		- [Parameters](#parameters)
		- [Templates](#templates)
		- [Renderers](#renderers)
		- [Replacement](#replacement)
		- [Escaping values](#escaping-values)
		- [Sorting](#sorting)
		- [Resetting pagination after sorting](#resetting-pagination-after-sorting)
		- [Default sort](#default-sort)
		- [Resetting default sort](#resetting-default-sort)
		- [Multiple columns sort](#multiple-columns-sort)
		- [Default per page](#default-per-page)
		- [Custom sorting Callback](#custom-sorting-callback)
		- [Align](#align)
		- [Removing column](#removing-column)
	- [Column Text](#column-text)
	- [Column Number](#column-number)
	- [Column DateTime](#column-datetime)
	- [Column Link](#column-link)
		- [Open in new tab](#open-in-new-tab)
	- [Column Status](#column-status)
	- [Hideable columns](#hideable-columns)
		- [Default hide](#default-hide)
	- [Columns Summary](#columns-summary)
	- [Aggregation Function](#aggregation-function)
		- [Single column](#single-column)
		- [Multiple columns](#multiple-columns)
	- [Column \(th&gt;, td&gt;\) attributes](#column-th-td-attributes)
	- [Column callback](#column-callback)

# Columns

There are several column classes and they all have some common behaviour and properties.

## Api

### Parameters

Lets add a simple text column like we've done before:

```php
$grid->addColumnText('name', 'Name');
```

Parameters that the method takes are: `$key, $title, $column = $key`. By default, the column name is the same as the key. There is a key, because you can show more data grid columns that will display only one database table column:

```php
$grid->addColumnText('name', 'Name'); // Equivalent to $grid->addColumnText('name', 'Name', 'name');
$grid->addColumnText('name2', 'Name', 'name');
$grid->addColumnText('name3', 'Name', 'name');
```

### Templates

Column may have it's own template. I will add one more parameter (optional) to the method `::setTemplate()`, just for fun:

```php
$grid->addColumnText('name', 'Name')
	->setTemplate(__DIR__ . '/templates/name.latte', ['foo' => 'bar']);
```

In that template (name.latte), we will have two variables available: `$item` and `$foo`. The parameter (expanded array) is there just in case you need it sometime.


### Renderers

We can also modify data outputting via renderer callbacks:

```php
$grid->addColumnText('name', 'Name')
	->setRenderer(function($item) {
		return strtoupper($item->id . ': ' . $item->name);
	});
```

But hey, what if i want to replace **just some** rows? No problem, the second optional argument tells me (callback again) whether the datagrid should use your renderer or not. Example:

```php
$grid->addColumnText('name', 'Name')
	->setRenderer(function($item) {
		return strtoupper($item->id . ': ' . $item->name);
	}, function($item) {
		return (bool) ($item->id % 2);
	});
```

### Replacement

Outputted data could have a simple array replacement instead of renderer callback:

```php
$grid->addColumnText('name', 'Name')
	->setReplacement([
		'John' => 'Doe',
		'Hell' => 'o'
	]);
```

### Escaping values

By default, latte escapes all values from data source. You can disable that:

```php
$grid->addColumnText('link_html', 'Link')
	->setTemplateEscaping(FALSE);
```

### Sorting

You can set the column as sortable.

```php
$grid->addColumnText('name', 'Name')
	->setSortable();
```

When using doctrine as data source, you can output data the object way using dot-notaion and property accessor. But when you are using collumn of related table for sorting, you probably want to use alias for sorting:

```php
$grid->addColumnText('role', 'User role', 'role.name')
	->setSortable('r.name')
```

### Resetting pagination after sorting

```php
$grid->addColumnText('name', 'Name')
	->setSortable()
	->setSortableResetPagination();
```

### Default sort

`DataGrid` implements default sorting mechanism:

```php
$grid->setDefaultSort(['name' => 'DESC']);
```

### Resetting default sort

By default, once you reset the filter, default sort is applied. If you don't want to apply it after resetting the filter, pass FALSE as a second parameter to `DataGrid::setDefaultSort()`:

```php
$grid->setDefaultSort('id' => 'DESC', FALSE);
```

### Multiple columns sort

Sorting by multiple columns is disabled by default. But can be enaled:

```php
$grid->setMultiSortEnabled($enabled = TRUE); // Pass FALSE to disable
```

### Default per page

You can also set default "items per page" value:

```php
$grid->setDefaultPerPage(20);
```

### Custom sorting Callback

You can define your own sorting callback:

```php
$grid->addColumnText('name', 'Name')
	->setSortable()
	->setSortableCallback(function($datasource, $sort) {
		/**
		 * Apply your sorting...
		 * 	(e.g. $sort = ['name' => 'ASC'])
		 */
	});
```

### Align

Column can be aligned to one side:

```php
$grid->addColumnText('name', 'Name')
	->setAlign('center');
```

### Removing column

You can remove column from grid like so:

```php
$grid->addColumnText('foo', 'Name', 'name');
$grid->removeColumn('foo');
```

## Column Text

We have discussed this in examples above - it's pretty basic column.

## Column Number

What more can ColumnNumber offer? Number formatting:

```php
/**
 * Default format: decimals = 0, dec_point = '.', thousands_sep = ' '
 */
$grid->addColumnNumber('price', 'Price');
$grid->addColumnNumber('price2', 'Price', 'price')
	->setFormat(2, ',', '.');
```

## Column DateTime

DateTime formatting (or just date or just time):

```php
/**
 * Default format: 'j. n. Y'
 */
$grid->addColumnDateTime('created', 'Date registered')
	->setFormat('H:i:s');
```

## Column Link

We can use column link to output a&gt; element:

```php
/**
 * Parameters order: $key, $name, $href = NULL, $column = NULL, array $params = NULL
 */
$grid->addColumnLink('name', 'Name', 'edit');
```

Now, ColumnLink is pretty clever! It passes an id parameter to the "edit" destination. Well, not id, more likely `$primary_key`, which can be changed.
But that is not all. What if we want to pass some other parameters to the action method (or handler - edit!)? Use the last method parameter:

```php
$grid->addColumnLink('name', 'Name', 'edit', 'name', ['role']);
```

That is still not all. You can pass the parameters under different name:

```php
$grid->addColumnLink('name', 'Name', 'edit', 'name', ['id', 'surname' => 'name']);
```

Now, suppose the row in database with **name = John** and **id = 5**. Then the link will look something like that: `/edit?id=5&amp;surname=John`!

### Open in new tab

You can tell `ColumnLink` to open its link in new tab:

```php
$grid->addColumnLink('name', 'Name', 'edit')
	->setOpenInNewTab();
```

## Column Status

![Status 1](https://github.com/contributte/datagrid/blob/master/.docs/assets/status1.gif)
![Status 1](https://github.com/contributte/datagrid/blob/master/.docs/assets/status2.gif)

Once your item keep some "status" flag, it is appropriate to show user the status in highlighted form. Also there could be a dropdown with available statuses:

```php
$grid->addColumnStatus('status', 'Status')
	->addOption(1, 'Online')
		->endOption()
	->addOption(0, 'Offline')
		->endOption()
	->onChange[] = function($id, $new_value) { dump($id, $new_value); die; };
```

ColumnStatus has optional caret, icon, class and confirmation. By default, there is a caret visible and the main button that toggles statuses dropdown has class "btn-success". You can change all these properties:

```php
$grid->addColumnStatus('status', 'Status')
	->setCaret(FALSE)
	->addOption(1, 'Online')
		->setIcon('check')
		->setClass('btn-success')
		->setConfirmation(new StringConfirmation('Do you really want set status as Online?'))
		->endOption()
	->addOption(2, 'Standby')
		->setIcon('user')
		->setClass('btn-primary')
		->endOption()
	->addOption(0, 'Offline')
		->setIcon('close')
		->setClass('btn-danger')
		->endOption()
	->onChange[] = [$this, 'statusChange'];
```

There are 2 default class properties in `Option`. `$class = 'btn-success'` and `$classSecondary = 'ajax btn btn-default btn-xs'`. As you can see, the default request is called via ajax. You can of course change that (`$option->setClassSecondary('btn btn-default')`).

When ajax request `changeStatus!` is called, you probably want to redraw the item after changing item status:

```php
public function createComponentColumnsGrid($name)
{
	$grid->addColumnStatus('status', 'Status')
		# ...
		->onChange[] = [$this, 'statusChange'];
}


public function statusChange($id, $new_status)
{
	# ...

	if ($this->isAjax()) {
		$this['columnsGrid']->redrawItem($id);
	}
}
```

Also, option classes in dropdown can be altered:

```php
$grid->addColumnStatus('status', 'Status')
	->addOption(1, 'Online')
	->setClassInDropdown('foo');
```

Options could be also set at once:

```php
$grid->addColumnStatus('status', 'Status')
	->setOptions([1 => 'Online', 2 => 'Standby'])
```

Accessing particular option:

```php
$grid->getColumn('status')->getOption(2)
	->setClass('btn-primary'); // For example
```

## Hideable columns

![Columns Hiding](https://github.com/contributte/datagrid/blob/master/.docs/assets/hideable_columns.gif)

In example datargid above, you can hide columns and then reveal them again. This feature is disabled by default. You can enable it like this:

```php
$grid->setColumnsHideable();
```

Hidden columns are saved into session, so they will remain hidden along all next requests.

### Default hide

Columns can be hidden by default:

```php
$grid->addColumnText('name', 'Name')
	->setDefaultHide(); // Or disable default hide: ::setDefaultHide(FALSE)
```

If default hide is used, new button is shown in that settings (gear) dropdown - **Show default columns**:

<img title="Columns Hiding" src="https://github.com/contributte/datagrid/blob/master/.docs/assets/hideable_columns_reset.png" width="267" height="256">

## Columns Summary

Datagrid implements a feature that allows you to display **sum** of column of displayed items at the bootom of the grid. Try it out:

```php
$grid->addColumnNumber('in', 'Income')
	->setFormat(2, ',', '.');

$grid->addColumnNumber('out', 'Expenses')
	->setFormat(2, ',', '.');

$grid->setColumnsSummary(['in', 'out']);
```

Summary will be aligned to the same site as the column it is counting. By default, number format is taken also from it's column, but you can change the formatting (for each sum separately):

```php
$grid->setColumnsSummary(['in', 'out'])
	->setFormat('out', 0, '.', ' ');
```

Sure you can use your own callback for each row item number to be add to summary:

```php
$grid->setColumnsSummary(['id'], function($item, $column) {
	return $item->{$column} * 10;
});
```

And as many other places, you may want to use your own renderer callback to show user altered summary data:

```php
$grid->setColumnsSummary(['price', 'amount'])
	->setRenderer(function($sum, string $column): string {
		if ($column === 'price') {
			return $sum . ' $';
		}

		return $sum . ' items';
	});
```

## Aggregation Function

### Single column

Some column aggregation can be viewed either using columns summary or using Aggregation Function:

```php
$grid->addAggregationFunction('status', new FunctionSum('id'));
```

This will render a sum of ids under the `"status"` column.

As mentioned above, there is one aggregation function prepared: `Ublaboo\DataGrid\AggregationFunction\FunctionSum`. You can implement whatever function you like, it just have to implement `Ublaboo\DataGrid\AggregationFunction\ISingleColumnAggregationFunction`.

### Multiple columns

In case you want to make the aggreagation directly using SQL by your domain, you will probably use interface `IMultipleAggregationFunction` (instead of `ISingleColumnAggregationFunction`):

```php
$grid->setMultipleAggregationFunction(
	new class implements IMultipleAggregationFunction
	{

		/**
		 * @var int
		 */
		private $idsSum = 0;

		/**
		 * @var float
		 */
		private $avgAge = 0.0;


		public function getFilterDataType(): string
		{
			return IAggregationFunction::DATA_TYPE_PAGINATED;
		}


		public function processDataSource($dataSource): void
		{
			$this->idsSum = (int) $dataSource->getConnection()
				->select('SUM([id])')
				->from($dataSource, '_')
				->fetchSingle();

			$this->avgAge = round((float) $dataSource->getConnection()
				->select('AVG(YEAR([birth_date]))')
				->from($dataSource, '_')
				->fetchSingle());
		}


		public function renderResult(string $key)
		{
			if ($key === 'id') {
				return 'Ids sum: ' . $this->idsSum;
			} elseif ($key === 'age') {
				return 'Avg Age: ' . (int) (date('Y') - $this->avgAge);
			}
		}
	}
);
```

This aggregatin is used along with `Dibi` in the demo.

## Column (th&gt;, td&gt;) attributes 

Since table cell elements are rendered using `Nette\Utils\Html`, you can easily alter their html attributes (class, data-attributes etc):

```php
$th = $grid->addColumnText('name', 'Name')
    ->getElementPrototype('td'); // Or element <th> via Column::getElementPrototype('th')

$th->data('foo', 'bar');
$th->class('super-column')
```

If you want to modify attributes for both th&gt; and td&gt; at one time, use directly Column::addCellAttributes():

```php
$grid->addColumnText('name', 'Name')
    ->addCellAttributes(['class' => 'text-center']);
```

## Column callback

When you need to modify columns just before rendering (meybe remove some status options or completely change renderer for partucular items), you can create column callback, that will be called with `$column` and `$item` in parameter:

```php
$grid->addColumnLink('link', 'Link', 'this#demo', 'name', ['id', 'surname' => 'name']);

$grid->addColumnStatus('status', 'Status')
	->addOption(1, 'Online')
		->setClass('btn-success')
		->endOption()
	->addOption(2, 'Standby')
		->setClass('btn-primary')
		->endOption()
	->addOption(0, 'Offline')
		->setClass('btn-danger')
		->endOption()
	->onChange[] = [$this, 'changeStatus'];

$grid->addColumnCallback('status', function($column, $item) {
	if ($item->id == 2) {
		$column->removeOption(2);
	}
});

$grid->addColumnCallback('link', function($column, $item) {
	if ($item->id == 2) {
		$column->setRenderer(function() {
			return '';
		});
	}
});
```

That is the code of the demove shown above. As you can see, item with id == 1 does have a empty link column and only 2 options in `ColumnStatus`.


```php
$grid->addColumnCallback('status', function($column, $item) {
	if ($item->id == 2) {
		$column->setTemplate(NULL);
		$column->setRenderer(function() {
			return 'My super another status';
		});
	}
});
```
