<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid;

use Dibi\Row as DibiRow;
use LeanMapper\Entity;
use Nette\Database\Row as NetteRow;
use Nette\Database\Table\ActiveRow;
use Nette\MemberAccessException;
use Nette\Utils\Html;
use Nextras\Orm\Entity\Entity as NextrasEntity;
use Ublaboo\DataGrid\Column\Column;
use Ublaboo\DataGrid\Exception\DataGridException;
use Ublaboo\DataGrid\Utils\PropertyAccessHelper;

class Row
{

	/**
	 * @var DataGrid
	 */
	protected $datagrid;

	/**
	 * @var mixed
	 */
	protected $item;

	/**
	 * @var string
	 */
	protected $primaryKey;

	/**
	 * @var mixed
	 */
	protected $id;

	/**
	 * @var Html
	 */
	protected $control;

	/**
	 * @param mixed $item
	 */
	public function __construct(DataGrid $datagrid, $item, string $primaryKey)
	{
		$this->control = Html::el('tr');
		$this->datagrid = $datagrid;
		$this->item = $item;
		$this->primaryKey = $primaryKey;
		$this->id = $this->getValue($primaryKey);

		if ($datagrid->getColumnsSummary() instanceof ColumnsSummary) {
			$datagrid->getColumnsSummary()->add($this);
		}
	}


	/**
	 * @return mixed
	 */
	public function getId()
	{
		if (is_object($this->id) && method_exists($this->id, '__toString')) {
			return (string) $this->id;
		}

		return $this->id;
	}


	/**
	 * @param  mixed $key
	 * @return mixed
	 */
	public function getValue($key)
	{
		if ($this->item instanceof Entity) {
			return $this->getLeanMapperEntityProperty($this->item, $key);
		}

		if ($this->item instanceof NextrasEntity) {
			return $this->getNextrasEntityProperty($this->item, $key);
		}

		if ($this->item instanceof DibiRow) {
			return $this->item[$this->formatDibiRowKey($key)];
		}

		if ($this->item instanceof ActiveRow) {
			return $this->getActiveRowProperty($this->item, $key);
		}

		if ($this->item instanceof NetteRow) {
			return $this->item->{$key};
		}

		if (is_array($this->item)) {
			$arrayValue = $this->item[$key];

			if (is_object($arrayValue) && method_exists($arrayValue, '__toString')) {
				return (string) $arrayValue;
			}

			return $arrayValue;
		}

		return $this->getDoctrineEntityProperty($this->item, $key);
	}


	public function getControl(): Html
	{
		return $this->control;
	}


	public function getControlClass(): string
	{
		$class = $this->control->getAttribute('class');

		if ($class === null) {
			return '';
		}

		return implode(' ', array_keys($class));
	}


	/**
	 * @return mixed
	 */
	public function getActiveRowProperty(ActiveRow $item, string $key)
	{
		if (preg_match('/^:([a-zA-Z0-9_$]+)\.([a-zA-Z0-9_$]+)(:([a-zA-Z0-9_$]+))?$/', $key, $matches) === 1) {
			$relatedTable = $matches[1];
			$relatedColumn = $matches[2];
			$throughColumn = $matches[4] ?? null;

			try {
				$relatedRow = $item->related($relatedTable, $throughColumn)->fetch();
			} catch (MemberAccessException $e) {
				return null;
			}

			return $relatedRow[$relatedColumn];
		}

		if (preg_match('/^([a-zA-Z0-9_$]+)\.([a-zA-Z0-9_$]+)(:([a-zA-Z0-9_$]+))?$/', $key, $matches) === 1) {
			$referencedTable = $matches[1];
			$referencedColumn = $matches[2];
			$throughColumn = $matches[4] ?? null;

			try {
				$referencedRow = $item->ref($referencedTable, $throughColumn);
			} catch (MemberAccessException $e) {
				return null;
			}

			return $referencedRow === null
				? null
				: $referencedRow[$referencedColumn];
		}

		return $item[$key];
	}


	/**
	 * LeanMapper: Access object properties to get a item value
	 *
	 * @return mixed
	 */
	public function getLeanMapperEntityProperty(Entity $item, string $key)
	{
		$properties = explode('.', $key);
		$value = $item;

		for (;;) {
			$property = array_shift($properties);

			if ($property === null) {
				break;
			}

			if (!$value->__isset($property)) {
				if ($this->datagrid->strictEntityProperty) {
					throw new DataGridException(sprintf(
						'Target Property [%s] is not an object or is empty, trying to get [%s]',
						$value,
						str_replace('.', '->', $key)
					));
				}

				return null;
			}

			$value = $value->__get($property);
		}

		return $value;
	}


	/**
	 * Nextras: Access object properties to get a item value
	 *
	 * @return mixed
	 */
	public function getNextrasEntityProperty(NextrasEntity $item, string $key)
	{
		$properties = explode('.', $key);
		$value = $item;

		while ($property = array_shift($properties)) {
			if (!$value->__isset($property)) {
				if ($this->datagrid->strictEntityProperty) {
					throw new DataGridException(sprintf(
						'Target Property [%s] is not an object or is empty, trying to get [%s]',
						$value,
						str_replace('.', '->', $key)
					));
				}

				return null;
			}

			$value = $value->__get($property);
		}

		return $value;
	}


	/**
	 * Doctrine: Access object properties to get a item value
	 *
	 * @param  mixed $item
	 * @param  mixed $key
	 * @return mixed
	 */
	public function getDoctrineEntityProperty($item, $key)
	{
		$properties = explode('.', $key);
		$value = $item;
		$accessor = PropertyAccessHelper::getAccessor();

		while ($property = array_shift($properties)) {
			if (!is_object($value) && ! (bool) $value) {
				if ($this->datagrid->strictEntityProperty) {
					throw new DataGridException(sprintf(
						'Target Property [%s] is not an object or is empty, trying to get [%s]',
						$value,
						str_replace('.', '->', $key)
					));
				}

				return null;
			}

			$value = $accessor->getValue($value, $property);
		}

		if (is_object($value) && method_exists($value, '__toString')) {
			return (string) $value;
		}

		return $value;
	}


	/**
	 * Get original item
	 *
	 * @return mixed
	 */
	public function getItem()
	{
		return $this->item;
	}


	/**
	 * Has particular row group actions allowed?
	 */
	public function hasGroupAction(): bool
	{
		$condition = $this->datagrid->getRowCondition('group_action');

		if (is_callable($condition)) {
			return ($condition)($this->item);
		}

		return true;
	}


	/**
	 * Has particular row a action allowed?
	 *
	 * @param  mixed  $key
	 */
	public function hasAction($key): bool
	{
		$condition = $this->datagrid->getRowCondition('action', $key);

		if (is_callable($condition)) {
			return ($condition)($this->item);
		}

		return true;
	}


	/**
	 * Has particular row inlie edit allowed?
	 */
	public function hasInlineEdit(): bool
	{
		$condition = $this->datagrid->getRowCondition('inline_edit');

		if (is_callable($condition)) {
			return ($condition)($this->item);
		}

		return true;
	}


	public function applyColumnCallback(string $key, Column $column): Column
	{
		$callback = $this->datagrid->getColumnCallback($key);

		if ($callback !== null) {
			call_user_func($callback, $column, $this->getItem());
		}

		return $column;
	}


	/**
	 * Key may contain ".", get rid of it (+ the table alias)
	 */
	private function formatDibiRowKey(string $key): string
	{
		$offset = strpos($key, '.');

		if ($offset !== false) {
			return substr($key, $offset + 1);
		}

		return $key;
	}
}
