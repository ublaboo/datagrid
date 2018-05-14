<?php declare(strict_types = 1);

namespace Ublaboo\DataGrid\DataSource;

use Dibi\Fluent;
use Dibi\Helpers;
use Ublaboo\DataGrid\AggregationFunction\IAggregatable;
use Ublaboo\DataGrid\Filter;
use Ublaboo\DataGrid\Utils\DateTimeHelper;
use Ublaboo\DataGrid\Utils\Sorting;

class DibiFluentDataSource extends FilterableDataSource implements IDataSource, IAggregatable
{

	/**
	 * @var Fluent
	 */
	protected $data_source;

	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * @var string
	 */
	protected $primary_key;

	public function __construct(Fluent $data_source, string $primary_key)
	{
		$this->data_source = $data_source;
		$this->primary_key = $primary_key;
	}


	/********************************************************************************
	 *                          IDataSource implementation                          *
	 ********************************************************************************/

	/**
	 * Get count of data
	 */
	public function getCount(): int
	{
		return $this->data_source->count();
	}


	/**
	 * Get the data
	 *
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data ?: $this->data_source->fetchAll();
	}


	/**
	 * Filter data - get one row
	 *
	 * @param array $condition
	 * @return static
	 */
	public function filterOne(array $condition)
	{
		$this->data_source->where($condition)->limit(1);

		return $this;
	}


	/**
	 * Filter by date
	 */
	public function applyFilterDate(Filter\FilterDate $filter): void
	{
		$conditions = $filter->getCondition();

		$date = DateTimeHelper::tryConvertToDateTime($conditions[$filter->getColumn()], [$filter->getPhpFormat()]);

		$this->data_source->where('DATE(%n) = ?', $filter->getColumn(), $date->format('Y-m-d'));
	}


	/**
	 * Filter by date range
	 */
	public function applyFilterDateRange(Filter\FilterDateRange $filter): void
	{
		$conditions = $filter->getCondition();

		$value_from = $conditions[$filter->getColumn()]['from'];
		$value_to = $conditions[$filter->getColumn()]['to'];

		if ($value_from) {
			$date_from = DateTimeHelper::tryConvertToDateTime($value_from, [$filter->getPhpFormat()]);
			$date_from->setTime(0, 0, 0);

			$this->data_source->where('DATE(%n) >= ?', $filter->getColumn(), $date_from);
		}

		if ($value_to) {
			$date_to = DateTimeHelper::tryConvertToDateTime($value_to, [$filter->getPhpFormat()]);
			$date_to->setTime(23, 59, 59);

			$this->data_source->where('DATE(%n) <= ?', $filter->getColumn(), $date_to);
		}
	}


	/**
	 * Filter by range
	 */
	public function applyFilterRange(Filter\FilterRange $filter): void
	{
		$conditions = $filter->getCondition();

		$value_from = $conditions[$filter->getColumn()]['from'];
		$value_to = $conditions[$filter->getColumn()]['to'];

		if ($value_from || $value_from !== '') {
			$this->data_source->where('%n >= ?', $filter->getColumn(), $value_from);
		}

		if ($value_to || $value_to !== '') {
			$this->data_source->where('%n <= ?', $filter->getColumn(), $value_to);
		}
	}


	/**
	 * Filter by keyword
	 */
	public function applyFilterText(Filter\FilterText $filter): void
	{
		$condition = $filter->getCondition();
		$driver = $this->data_source->getConnection()->getDriver();
		$or = [];

		foreach ($condition as $column => $value) {
			if (class_exists(Helpers::class) === true) {
				$column = Helpers::escape(
					$driver,
					$column,
					\dibi::IDENTIFIER
				);
			} else {
				$column = $driver->escape(
					$column,
					\dibi::IDENTIFIER
				);
			}

			if ($filter->isExactSearch()) {
				$this->data_source->where("$column = %s", $value);
				continue;
			}

			if ($filter->hasSplitWordsSearch() === false) {
				$words = [$value];
			} else {
				$words = explode(' ', $value);
			}

			foreach ($words as $word) {
				$or[] = ["$column LIKE %~like~", $word];
			}
		}

		if (sizeof($or) > 1) {
			$this->data_source->where('(%or)', $or);
		} else {
			$this->data_source->where($or);
		}
	}


	/**
	 * Filter by multi select value
	 */
	public function applyFilterMultiSelect(Filter\FilterMultiSelect $filter): void
	{
		$condition = $filter->getCondition();
		$values = $condition[$filter->getColumn()];
		$or = [];

		if (sizeof($values) > 1) {
			$value1 = array_shift($values);
			$length = sizeof($values);
			$i = 1;

			$this->data_source->where('(%n = ?', $filter->getColumn(), $value1);

			foreach ($values as $value) {
				if ($i === $length) {
					$this->data_source->or('%n = ?)', $filter->getColumn(), $value);
				} else {
					$this->data_source->or('%n = ?', $filter->getColumn(), $value);
				}

				$i++;
			}
		} else {
			$this->data_source->where('%n = ?', $filter->getColumn(), reset($values));
		}
	}


	/**
	 * Filter by select value
	 */
	public function applyFilterSelect(Filter\FilterSelect $filter): void
	{
		$this->data_source->where($filter->getCondition());
	}


	/**
	 * Apply limit and offset on data
	 *
	 * @return static
	 */
	public function limit(int $offset, int $limit)
	{
		$this->data_source->limit($limit)->offset($offset);

		$this->data = $this->data_source->fetchAll();

		return $this;
	}


	/**
	 * Sort data
	 *
	 * @return static
	 */
	public function sort(Sorting $sorting)
	{
		if (is_callable($sorting->getSortCallback())) {
			call_user_func(
				$sorting->getSortCallback(),
				$this->data_source,
				$sorting->getSort()
			);

			return $this;
		}

		$sort = $sorting->getSort();

		if (!empty($sort)) {
			$this->data_source->removeClause('ORDER BY');
			$this->data_source->orderBy($sort);
		} else {
			/**
			 * Has the statement already a order by clause?
			 */
			$this->data_source->clause('ORDER BY');

			$reflection = new ReflectionClass(Fluent::class);
			$cursor_property = $reflection->getProperty('cursor');
			$cursor_property->setAccessible(true);
			$cursor = $cursor_property->getValue($this->data_source);

			if (!$cursor) {
				$this->data_source->orderBy($this->primary_key);
			}
		}

		return $this;
	}


	public function processAggregation(callable $aggregationCallback): void
	{
		call_user_func($aggregationCallback, clone $this->data_source);
	}

}
