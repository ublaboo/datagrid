<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\DataSource;

use ArrayAccess;
use DateTime;
use DateTimeInterface;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\Exception\DataGridArrayDataSourceException;
use Ublaboo\DataGrid\Exception\DataGridDateTimeHelperException;
use Ublaboo\DataGrid\Filter\Filter;
use Ublaboo\DataGrid\Filter\FilterDate;
use Ublaboo\DataGrid\Filter\FilterDateRange;
use Ublaboo\DataGrid\Filter\FilterMultiSelect;
use Ublaboo\DataGrid\Filter\FilterRange;
use Ublaboo\DataGrid\Filter\FilterText;
use Ublaboo\DataGrid\Utils\DateTimeHelper;
use Ublaboo\DataGrid\Utils\Sorting;

class ArrayDataSource implements IDataSource
{

	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * @var int
	 */
	protected $count = 0;

	/**
	 * @param array $dataSource
	 */
	public function __construct(array $dataSource)
	{
		$this->setData($dataSource);
	}


	/********************************************************************************
	 *                          IDataSource implementation                          *
	 ********************************************************************************/

	public function getCount(): int
	{
		return sizeof($this->data);
	}


	/**
	 * {@inheritDoc}
	 */
	public function getData(): array
	{
		return $this->data;
	}


	/**
	 * {@inheritDoc}
	 */
	public function filter(array $filters): void
	{
		foreach ($filters as $filter) {
			if ($filter->isValueSet()) {
				if ($filter->getConditionCallback() !== null) {
					$data = (array) call_user_func_array(
						$filter->getConditionCallback(),
						[$this->data, $filter->getValue()]
					);
					$this->setData($data);
				} else {
					$data = array_filter($this->data, function ($row) use ($filter) {
						return $this->applyFilter($row, $filter);
					});
					$this->setData($data);
				}
			}
		}
	}


	/**
	 * {@inheritDoc}
	 */
	public function filterOne(array $condition): IDataSource
	{
		foreach ($this->data as $item) {
			foreach ($condition as $key => $value) {
				if ($item[$key] === $value) {
					$this->setData([$item]);

					return $this;
				}
			}
		}

		$this->setData([]);

		return $this;
	}


	public function limit(int $offset, int $limit): IDataSource
	{
		$data = array_slice($this->data, $offset, $limit);
		$this->setData($data);

		return $this;
	}


	/**
	 * @param  mixed $row
	 * @return mixed
	 */
	protected function applyFilter($row, Filter $filter)
	{
		if (is_array($row) || $row instanceof ArrayAccess) {
			if ($filter instanceof FilterDate) {
				return $this->applyFilterDate($row, $filter);
			}

			if ($filter instanceof FilterMultiSelect) {
				return $this->applyFilterMultiSelect($row, $filter);
			}

			if ($filter instanceof FilterDateRange) {
				return $this->applyFilterDateRange($row, $filter);
			}

			if ($filter instanceof FilterRange) {
				return $this->applyFilterRange($row, $filter);
			}

			$condition = $filter->getCondition();

			foreach ($condition as $column => $value) {
				$value = (string) $value;
				$rowVal = (string) $row[$column];

				if ($filter instanceof FilterText && $filter->isExactSearch()) {
					return $rowVal === $value;
				}

				$words = $filter instanceof FilterText && $filter->hasSplitWordsSearch() === false ? [$value] : explode(' ', $value);

				$row_value = strtolower(Strings::toAscii((string) $row[$column]));

				foreach ($words as $word) {
					if (strpos($row_value, strtolower(Strings::toAscii($word))) !== false) {
						return $row;
					}
				}
			}
		}

		return false;
	}


	/**
	 * @param mixed $row
	 */
	protected function applyFilterMultiSelect($row, FilterMultiSelect $filter): bool
	{
		$condition = $filter->getCondition();
		$values = $condition[$filter->getColumn()];

		return in_array($row[$filter->getColumn()], $values, true);
	}


	/**
	 * @param mixed $row
	 */
	protected function applyFilterRange($row, FilterRange $filter): bool
	{
		$condition = $filter->getCondition();
		$values = $condition[$filter->getColumn()];

		if ($values['from'] !== null && $values['from'] !== '') {
			if ($values['from'] > $row[$filter->getColumn()]) {
				return false;
			}
		}

		if ($values['to'] !== null && $values['to'] !== '') {
			if ($values['to'] < $row[$filter->getColumn()]) {
				return false;
			}
		}

		return true;
	}


	/**
	 * @param mixed $row
	 */
	protected function applyFilterDateRange($row, FilterDateRange $filter): bool
	{
		$format = $filter->getPhpFormat();
		$condition = $filter->getCondition();
		$values = $condition[$filter->getColumn()];
		$row_value = $row[$filter->getColumn()];

		if ($values['from'] !== null && $values['from'] !== '') {
			$date_from = DateTimeHelper::tryConvertToDate($values['from'], [$format]);
			$date_from->setTime(0, 0, 0);

			if (!($row_value instanceof DateTime)) {
				/**
				 * Try to convert string to DateTime object
				 */
				try {
					$row_value = DateTimeHelper::tryConvertToDate($row_value);
				} catch (DataGridDateTimeHelperException $e) {
					/**
					 * Otherwise just return raw string
					 */
					return false;
				}
			}

			if ($row_value->getTimestamp() < $date_from->getTimestamp()) {
				return false;
			}
		}

		if ($values['to'] !== null && $values['to'] !== '') {
			$date_to = DateTimeHelper::tryConvertToDate($values['to'], [$format]);
			$date_to->setTime(23, 59, 59);

			if (!($row_value instanceof DateTime)) {
				/**
				 * Try to convert string to DateTime object
				 */
				try {
					$row_value = DateTimeHelper::tryConvertToDate($row_value);
				} catch (DataGridDateTimeHelperException $e) {
					/**
					 * Otherwise just return raw string
					 */
					return false;
				}
			}

			if ($row_value->getTimestamp() > $date_to->getTimestamp()) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Apply fitler date and tell whether row value matches or not
	 *
	 * @param  mixed  $row
	 */
	protected function applyFilterDate($row, FilterDate $filter): bool
	{
		$format = $filter->getPhpFormat();
		$condition = $filter->getCondition();

		foreach ($condition as $column => $value) {
			$row_value = $row[$column];

			$date = DateTimeHelper::tryConvertToDateTime($value, [$format]);

			if (!($row_value instanceof DateTime)) {
				/**
				 * Try to convert string to DateTime object
				 */
				try {
					$row_value = DateTimeHelper::tryConvertToDateTime($row_value);
				} catch (DataGridDateTimeHelperException $e) {
					/**
					 * Otherwise just return raw string
					 */
					return false;
				}
			}

			return $row_value->format($format) === $date->format($format);
		}
	}


	public function sort(Sorting $sorting): IDataSource
	{
		if (is_callable($sorting->getSortCallback())) {
			$data = call_user_func(
				$sorting->getSortCallback(),
				$this->data,
				$sorting->getSort()
			);

			if (!is_array($data)) {
				throw new DataGridArrayDataSourceException('Sorting callback has to return array');
			}

			$this->setData($data);

			return $this;
		}

		$sort = $sorting->getSort();

		foreach ($sort as $column => $order) {
			$data = [];

			foreach ($this->data as $item) {
				$sort_by = is_object($item[$column]) && $item[$column] instanceof DateTimeInterface
					? $item[$column]->format('Y-m-d H:i:s')
					: (string) $item[$column];

				$data[$sort_by][] = $item;
			}

			if ($order === 'ASC') {
				ksort($data);
			} else {
				krsort($data);
			}

			$dataSource = [];

			foreach ($data as $i) {
				foreach ($i as $item) {
					$dataSource[] = $item;
				}
			}

			$this->setData($dataSource);
		}

		return $this;
	}


	/**
	 * Set the data
	 */
	private function setData(array $dataSource): IDataSource
	{
		$this->data = $dataSource;

		return $this;
	}
}
