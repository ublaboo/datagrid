Table of contents

- [Installation](#installation)
	- [Composer](#composer)
	- [Features](#features)

# Installation

## Composer

Download this package using composer:

```
composer require ublaboo/datagrid
```

## Features

- Pagination
- Sorting by columns
- Sortable (reorderable)
- Items per page - configurable
- Acceptable data sources:
	- Doctrine (QueryBuilder)
	- Doctrine (Collection)
	- Nextras (Collection)
	- Dibi (DibiFluent)
	- Dibi (DibiFluent) for MS-SQL
	- Nette\Database (Please see it's documentation [here](https://github.com/contributte/datagrid-nette-database-data-source))
	- Nette\Database\Table
	- Nette\Database\Table (for MS-SQL)
	- Nette\Database\Table (for PostreSQL)
	- Array
	- Elasticsearch (Please see the documentation [here](https://github.com/contributte/datagrid-elasticsearch-data-source))
	- Remote Api
	- Any other class that implements IDataSource
- Columns (custom templates, custom, renderer, replacement, inline editing):
	- ColumnText
	- ColumnNumber
	- ColumnLink
	- ColumnDateTime
	- ColumnStatus
- Filtering: (custom templates, remembering state):
	- FilterText (multiple columns search)
	- FilterSelect
	- FilterRange
	- FilterDateRange
	- FilterDate
	- FilterMultiSelect
- Actions
- Item detail
- Extendable datagrid template
- Group actions (one/two level select)
- Exports (filtered or not-filtered)
- CSV Export ready
- Tree view (children loaded via ajax)
- Localization
- Row conditions
- Hideable columns
- Ajax spinners

DataGrid can do some really useful stuff.

Let's create a datagrid component!
We will demonstrate our examples in Presenters.

```php
use Ublaboo\DataGrid\DataGrid;

class SimplePresenter extends BasePresenter
{

	public function createComponentSimpleGrid($name)
	{
		$grid = new DataGrid($this, $name);

		$grid->setDataSource($this->db->select('*')->from('ublaboo_example'));
		$grid->addColumnText('name', 'Name');
	}

}
```

And that's it. Go check the app. :)

When you don't line pagination, you can disable it:

```php
$grid->setPagination(false);
```

But that would be a long dump - there are about a thousand rows in database. To change the items per page, select options you will do that via:

```php
$grid->setItemsPerPageList([1, 100, 9090, 2]);
```
