<?php declare(strict_types = 1);

namespace Ublaboo\DataGrid\DataSource;

use Dibi;
use DibiFluent;
use Ublaboo\DataGrid\Filter;
use Ublaboo\DataGrid\Utils\DateTimeHelper;

class DibiFluentMssqlDataSource extends DibiFluentDataSource
{

	/**
	 * @var DibiFluent
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

	/**
	 * @param DibiFluent $data_source
	 * @param string $primary_key
	 */
	public function __construct(DibiFluent $data_source, $primary_key)
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
		$clone = clone $this->data_source;
		$clone->removeClause('ORDER BY');

		return $clone->count();
	}


	/**
	 * Get the data
	 *
	 * @param array $condition
	 * @return static
	 */
	public function filterOne(array $condition)
	{
		$this->data_source->where($condition);

		return $this;
	}


	/**
	 * Filter by date
	 */
	public function applyFilterDate(Filter\FilterDate $filter): void
	{
		$conditions = $filter->getCondition();

		$date = DateTimeHelper::tryConvertToDateTime($conditions[$filter->getColumn()], [$filter->getPhpFormat()]);

		$this->data_source->where('CONVERT(varchar(10), %n, 112) = ?', $filter->getColumn(), $date->format('Ymd'));
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
			$this->data_source->where('CONVERT(varchar(10), %n, 112) >= ?', $filter->getColumn(), $value_from);
		}

		if ($value_to) {
			$this->data_source->where('CONVERT(varchar(10), %n, 112) <= ?', $filter->getColumn(), $value_to);
		}
	}


	/**
	 * Filter by date
	 */
	public function applyFilterText(Filter\FilterText $filter): void
	{
		$condition = $filter->getCondition();
		$driver = $this->data_source->getConnection()->getDriver();
		$or = [];

		foreach ($condition as $column => $value) {
			if (class_exists(Dibi\Helpers::class) === true) {
				$column = Dibi\Helpers::escape(
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

			$or[] = "$column LIKE \"%$value%\"";
		}

		if (sizeof($or) > 1) {
			$this->data_source->where('(%or)', $or);
		} else {
			$this->data_source->where($or);
		}
	}


	/**
	 * Apply limit and offset on data
	 *
	 * @return static
	 */
	public function limit(int $offset, int $limit)
	{
		$sql = (string) $this->data_source;

		$result = $this->data_source->getConnection()
			->query('%sql OFFSET ? ROWS FETCH NEXT ? ROWS ONLY', $sql, $offset, $limit);

		$this->data = $result->fetchAll();

		return $this;
	}

}
