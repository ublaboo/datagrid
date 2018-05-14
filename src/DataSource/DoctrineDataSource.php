<?php declare(strict_types = 1);

namespace Ublaboo\DataGrid\DataSource;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\AggregationFunction\IAggregatable;
use Ublaboo\DataGrid\Filter;
use Ublaboo\DataGrid\Utils\DateTimeHelper;
use Ublaboo\DataGrid\Utils\Sorting;

/**
 * @method void onDataLoaded(array $result)
 */
class DoctrineDataSource extends FilterableDataSource implements IDataSource, IAggregatable
{

	/**
	 * Event called when datagrid data is loaded.
	 *
	 * @var callable[]
	 */
	public $onDataLoaded;

	/**
	 * @var QueryBuilder
	 */
	protected $data_source;

	/**
	 * @var string
	 */
	protected $primary_key;

	/**
	 * @var string
	 */
	protected $root_alias;

	/**
	 * @var int
	 */
	protected $placeholder;

	public function __construct(QueryBuilder $data_source, string $primary_key)
	{
		$this->placeholder = count($data_source->getParameters());
		$this->data_source = $data_source;
		$this->primary_key = $primary_key;
	}


	public function getQuery(): Query
	{
		return $this->data_source->getQuery();
	}


	private function checkAliases(string $column): string
	{
		if (Strings::contains($column, '.')) {
			return $column;
		}

		if (!isset($this->root_alias)) {
			$this->root_alias = $this->data_source->getRootAliases();
			$this->root_alias = current($this->root_alias);
		}

		return $this->root_alias . '.' . $column;
	}


	private function usePaginator(): bool
	{
		return $this->data_source->getDQLPart('join') || $this->data_source->getDQLPart('groupBy');
	}


	/********************************************************************************
	 *                          IDataSource implementation                          *
	 ********************************************************************************/

	/**
	 * Get count of data
	 */
	public function getCount(): int
	{
		if ($this->usePaginator()) {
			return (new Paginator($this->getQuery()))->count();
		}
		$data_source = clone $this->data_source;
		$data_source->select(sprintf('COUNT(%s)', $this->checkAliases($this->primary_key)));
		$data_source->resetDQLPart('orderBy');

		return (int) $data_source->getQuery()->getSingleScalarResult();
	}


	/**
	 * Get the data
	 *
	 * @return array
	 */
	public function getData(): array
	{
		if ($this->usePaginator()) {
			$iterator = (new Paginator($this->getQuery()))->getIterator();

			$data = iterator_to_array($iterator);
		} else {
			$data = $this->getQuery()->getResult();
		}

		$this->onDataLoaded($data);

		return $data;
	}


	/**
	 * Filter data - get one row
	 *
	 * @param  array  $condition
	 * @return static
	 */
	public function filterOne(array $condition)
	{
		$p = $this->getPlaceholder();

		foreach ($condition as $column => $value) {
			$c = $this->checkAliases($column);

			$this->data_source->andWhere("$c = :$p")
				->setParameter($p, $value);
		}

		return $this;
	}


	/**
	 * Filter by date
	 */
	public function applyFilterDate(Filter\FilterDate $filter): void
	{
		$p1 = $this->getPlaceholder();
		$p2 = $this->getPlaceholder();

		foreach ($filter->getCondition() as $column => $value) {
			$date = DateTimeHelper::tryConvertToDateTime($value, [$filter->getPhpFormat()]);
			$c = $this->checkAliases($column);

			$this->data_source->andWhere("$c >= :$p1 AND $c <= :$p2")
				->setParameter($p1, $date->format('Y-m-d 00:00:00'))
				->setParameter($p2, $date->format('Y-m-d 23:59:59'));
		}
	}


	/**
	 * Filter by date range
	 */
	public function applyFilterDateRange(Filter\FilterDateRange $filter): void
	{
		$conditions = $filter->getCondition();
		$c = $this->checkAliases($filter->getColumn());

		$value_from = $conditions[$filter->getColumn()]['from'];
		$value_to = $conditions[$filter->getColumn()]['to'];

		if ($value_from) {
			$date_from = DateTimeHelper::tryConvertToDate($value_from, [$filter->getPhpFormat()]);
			$date_from->setTime(0, 0, 0);

			$p = $this->getPlaceholder();

			$this->data_source->andWhere("$c >= :$p")->setParameter($p, $date_from->format('Y-m-d H:i:s'));
		}

		if ($value_to) {
			$date_to = DateTimeHelper::tryConvertToDate($value_to, [$filter->getPhpFormat()]);
			$date_to->setTime(23, 59, 59);

			$p = $this->getPlaceholder();

			$this->data_source->andWhere("$c <= :$p")->setParameter($p, $date_to->format('Y-m-d H:i:s'));
		}
	}


	/**
	 * Filter by range
	 */
	public function applyFilterRange(Filter\FilterRange $filter): void
	{
		$conditions = $filter->getCondition();
		$c = $this->checkAliases($filter->getColumn());

		$value_from = $conditions[$filter->getColumn()]['from'];
		$value_to = $conditions[$filter->getColumn()]['to'];

		if ($value_from) {
			$p = $this->getPlaceholder();
			$this->data_source->andWhere("$c >= :$p")->setParameter($p, $value_from);
		}

		if ($value_to) {
			$p = $this->getPlaceholder();
			$this->data_source->andWhere("$c <= :$p")->setParameter($p, $value_to);
		}
	}


	/**
	 * Filter by keyword
	 */
	public function applyFilterText(Filter\FilterText $filter): void
	{
		$condition = $filter->getCondition();
		$exprs = [];

		foreach ($condition as $column => $value) {
			$c = $this->checkAliases($column);

			if ($filter->isExactSearch()) {
				$exprs[] = $this->data_source->expr()->eq($c, $this->data_source->expr()->literal($value));
				continue;
			}

			if ($filter->hasSplitWordsSearch() === false) {
				$words = [$value];
			} else {
				$words = explode(' ', $value);
			}

			foreach ($words as $word) {
				$exprs[] = $this->data_source->expr()->like($c, $this->data_source->expr()->literal("%$word%"));
			}
		}

		$or = call_user_func_array([$this->data_source->expr(), 'orX'], $exprs);

		$this->data_source->andWhere($or);
	}


	/**
	 * Filter by multi select value
	 */
	public function applyFilterMultiSelect(Filter\FilterMultiSelect $filter): void
	{
		$c = $this->checkAliases($filter->getColumn());
		$p = $this->getPlaceholder();

		$values = $filter->getCondition()[$filter->getColumn()];
		$expr = $this->data_source->expr()->in($c, ':' . $p);

		$this->data_source->andWhere($expr)->setParameter($p, $values);
	}


	/**
	 * Filter by select value
	 */
	public function applyFilterSelect(Filter\FilterSelect $filter): void
	{
		$p = $this->getPlaceholder();

		foreach ($filter->getCondition() as $column => $value) {
			$c = $this->checkAliases($column);

			$this->data_source->andWhere("$c = :$p")
				->setParameter($p, $value);
		}
	}


	/**
	 * Apply limit and offset on data
	 *
	 * @return static
	 */
	public function limit(int $offset, int $limit)
	{
		$this->data_source->setFirstResult($offset)->setMaxResults($limit);

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
			foreach ($sort as $column => $order) {
				$this->data_source->addOrderBy($this->checkAliases($column), $order);
			}
		} else {
			/**
			 * Has the statement already a order by clause?
			 */
			if (!$this->data_source->getDQLPart('orderBy')) {
				$this->data_source->orderBy($this->checkAliases($this->primary_key));
			}
		}

		return $this;
	}


	/**
	 * Get unique int value for each instance class (self)
	 */
	public function getPlaceholder(): int
	{
		return 'param' . ($this->placeholder++);
	}


	public function processAggregation(callable $aggregationCallback): void
	{
		call_user_func($aggregationCallback, clone $this->data_source);
	}

}
