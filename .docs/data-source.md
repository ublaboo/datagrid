Table of contents

- [Data sources](#data-sources)
	- [ORM Relations](#orm-relations)
	- [ApiDataSource](#apidatasource)
	- [NextrasDataSource](#nextrasdatasource)
	- [NetteDatabaseTableDataSource](#nettedatabasetabledatasource)

# Data sources

There are these supported datasources so far:

- Doctrine (QueryBuilder)
- Doctrine (Collection)
- Nextras (Collection)
- Dibi (DibiFluent)
- Dibi (DibiFluent) for MS-SQL
- Nette\Database (Please see it's documentation [here](https://github.com/contributte/datagrid-nette-database-data-source))
- Nette\Database\Table
- Nette\Database\Table (for MS-SQL)
- Nette\Database\Table (for PostgreSQL)
- Array
- Elasticsearch (Please see the documentation [here](https://github.com/contributte/datagrid-elasticsearch-data-source))
- Remote Api
- Any other class that implements IDataSource

You can set data source like this:

```php
$grid->setDataSource($this->ndb->table('user')); // NDBT
$grid->setDataSource($this->dibi->select('*')->from('user')); // Dibi
$grid->setDataSource([['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Joe']]); // Array
$grid->setDataSource($exampleRepository->createQueryBuilder('er')); // Doctrine query builder
# ...
```

The primary key column is by default `id`. You can change that:

```php
$grid->setPrimaryKey('email');
```

Once you have set a data source, you can add columns to the datagrid.

## ORM Relations

When you are using for example Doctrine as a data source, you can easily access another related entites for rendering in column. Let's say you have an entity `User` and each instance can have a property `$name` and `$grandma`. `$grandma` is also an instance of `User` class. Displaying people and their grandmas is very simple then - just use this dot notation:

```php
$grid->addColumnText('name', 'Name', 'name');
$grid->addColumnText('grandma_name', 'Grandma', 'grandma.name');
```

## ApiDataSource

There is also datasource, that takes data from remote api. It is experimental, you can extend it and overwrite whatever you want.

Basic usage:

```php
$grid->setDataSource(
	new Ublaboo\DataGrid\DataSource\ApiDataSource('http://my.remote.api')
);
```

The idea is simply to forward filtering/sorting/limit/... to remote api. Feel free to leave me a comment if you want to add/improve something.

## NextrasDataSource

There is one specific behaviour when using Netras ORM. When custom filter conditions are used, user has to work not with given `Collection` instance, but with `Collection::getQueryBuilder()`. That snippet of code will not work correctly, because `DbalCollection` calls clone on each of it's methods:

```php
$grid->getFilter('name')
	->setCondition(function ($collection, $value) {
		$collection->limitBy(1);
	});
```

User should use collection's `QueryBuilder` instead:

```php
$grid->getFilter('name')
	->setCondition(function ($collection, $value) {
		$collection->getQueryBuilder()->andWhere('name LIKE %s', "%$value%");
	});
```

## NetteDatabaseTableDataSource

There is a special feature for `NetteDatabaseTableDataSource` and referenced/related columns. When you want to reach related column from another table, you can do that using this syntax:

```php
$grid->addColumnText('name', 'Name', ':related_table.name');
```

For referenced table column, just remove the colon:

```php
$grid->addColumnText('name', 'Name', 'referenced_table.name');
```

In case you want to specify the "through-column", use following syntax:

```php
$grid->addColumnText('name', 'Name', ':related_table.name:through_column_id');
$grid->addColumnText('name', 'Name', 'referenced_table.name:through_column_id');
```
