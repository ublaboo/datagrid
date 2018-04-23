<?php declare(strict_types = 1);

/**
 * @copyright   Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\DataGrid\Column;

use Nette\SmartObject;
use Ublaboo;
use Ublaboo\DataGrid\DataGrid;

abstract class FilterableColumn
{

	use SmartObject;

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var DataGrid
	 */
	protected $grid;

	/**
	 * @var string
	 */
	protected $column;

	/**
	 * @param DataGrid $grid
	 * @param string   $key
	 * @param string   $column
	 * @param string   $name
	 */
	public function __construct(DataGrid $grid, string $key, string $column, string $name)
	{
		$this->grid = $grid;
		$this->key = $key;
		$this->column = $column;
		$this->name = $name;
	}


	/**
	 * @param string|array|null $columns
	 * @return Ublaboo\DataGrid\Filter\FilterText
	 */
	public function setFilterText($columns = null): Ublaboo\DataGrid\Filter\FilterText
	{
		if ($columns === null) {
			$columns = [$this->column];
		} else {
			$columns = is_string($columns) ? [$columns] : $columns;
		}

		return $this->grid->addFilterText($this->key, $this->name, $columns);
	}


	/**
	 * @param array       $options
	 * @param string|null $column
	 * @return Ublaboo\DataGrid\Filter\FilterSelect
	 */
	public function setFilterSelect(array $options, ?string $column = null): Ublaboo\DataGrid\Filter\FilterSelect
	{
		$column = $column === null ? $this->column : $column;

		return $this->grid->addFilterSelect($this->key, $this->name, $options, $column);
	}


	/**
	 * @param array       $options
	 * @param string|null $column
	 * @return Ublaboo\DataGrid\Filter\FilterMultiSelect
	 */
	public function setFilterMultiSelect(array $options, ?string $column = null): Ublaboo\DataGrid\Filter\FilterMultiSelect
	{
		$column = $column === null ? $this->column : $column;

		return $this->grid->addFilterMultiSelect($this->key, $this->name, $options, $column);
	}


	/**
	 * @param string|null $column
	 * @return Ublaboo\DataGrid\Filter\FilterDate
	 */
	public function setFilterDate(?string $column = null): Ublaboo\DataGrid\Filter\FilterDate
	{
		$column = $column === null ? $this->column : $column;

		return $this->grid->addFilterDate($this->key, $this->name, $column);
	}


	/**
	 * @param string|null $column
	 * @param string|null $name_second
	 * @return Ublaboo\DataGrid\Filter\FilterRange
	 */
	public function setFilterRange(?string $column = null, ?string $name_second = '-'): Ublaboo\DataGrid\Filter\FilterRange
	{
		$column = $column === null ? $this->column : $column;

		return $this->grid->addFilterRange($this->key, $this->name, $column, $name_second);
	}


	/**
	 * @param string|null $column
	 * @param string|null $name_second
	 * @return Ublaboo\DataGrid\Filter\FilterDateRange
	 */
	public function setFilterDateRange(?string $column = null, ?string $name_second = '-'): Ublaboo\DataGrid\Filter\FilterDateRange
	{
		$column = $column === null ? $this->column : $column;

		return $this->grid->addFilterDateRange($this->key, $this->name, $column, $name_second);
	}

}
