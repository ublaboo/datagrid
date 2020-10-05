<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\AggregationFunction;

use Dibi\Fluent;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Nette\Database\Table\Selection;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\Utils\PropertyAccessHelper;

class FunctionSum implements ISingleColumnAggregationFunction
{

	/**
	 * @var string
	 */
	protected $column;

	/**
	 * @var int
	 */
	protected $result = 0;

	/**
	 * @var string
	 */
	protected $dataType;

	/**
	 * @var callable|null
	 */
	protected $renderer = null;


	public function __construct(
		string $column,
		string $dataType = IAggregationFunction::DATA_TYPE_PAGINATED
	) {
		$this->column = $column;
		$this->dataType = $dataType;
	}


	public function getFilterDataType(): string
	{
		return $this->dataType;
	}


	/**
	 * @param Fluent|QueryBuilder|Collection|Selection $dataSource
	 */
	public function processDataSource($dataSource): void
	{
		if ($dataSource instanceof Fluent) {
			$connection = $dataSource->getConnection();
			$this->result = (int) $connection->select('SUM(%n)', $this->column)
				->from($dataSource, 's')
				->fetchSingle();
		}

		if ($dataSource instanceof QueryBuilder) {
			$column = Strings::contains($this->column, '.')
				? $this->column
				: current($dataSource->getRootAliases()) . '.' . $this->column;

			$this->result = $dataSource
				->select(sprintf('SUM(%s)', $column))
				->setMaxResults(1)
				->setFirstResult(0)
				->getQuery()
				->getSingleScalarResult();
		}

		if ($dataSource instanceof Collection) {
			$dataSource->forAll(function ($key, $value) {
				$this->result += PropertyAccessHelper::getValue($value, $this->column);

				return true;
			});
		}
	}


	/**
	 * @return mixed
	 */
	public function renderResult()
	{
		$result = $this->result;

		if (isset($this->renderer)) {
			$result = call_user_func($this->renderer, $result);
		}

		return $result;
	}


	/**
	 * @return static
	 */
	public function setRenderer(?callable $callback = null): self
	{
		$this->renderer = $callback;

		return $this;
	}
}
