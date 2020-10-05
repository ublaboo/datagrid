<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\Column;

use Nette\SmartObject;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Row;
use Ublaboo\DataGrid\Status\Option;
use Ublaboo\DataGrid\Traits\TButtonCaret;
use Ublaboo\DataGrid\Traits\TRenderCondition;

/**
 * @method onChange(string $id, string $value)
 */
class ColumnStatus extends Column
{

	use TButtonCaret;
	use SmartObject;
	use TRenderCondition;

	/**
	 * @var array|callable[]
	 */
	public $onChange = [];

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var array
	 */
	protected $options = [];


	public function __construct(
		DataGrid $grid,
		string $key,
		string $column,
		string $name
	)
	{
		parent::__construct($grid, $key, $column, $name);

		$this->key = $key;

		$this->setTemplate(__DIR__ . '/../templates/column_status.latte');
	}


	public function getKey(): string
	{
		return $this->key;
	}


	public function getOptions(): array
	{
		return $this->options;
	}


	/**
	 * @param  mixed $value
	 * @throws DataGridColumnStatusException
	 */
	public function getOption($value): Option
	{
		foreach ($this->options as $option) {
			if ($option->getValue() === $value) {
				return $option;
			}
		}

		throw new DataGridColumnStatusException("Option [$value] does not exist");
	}


	public function getColumn(): string
	{
		return $this->column;
	}


	/**
	 * Find selected option for current item/row
	 */
	public function getCurrentOption(Row $row): ?Option
	{
		foreach ($this->getOptions() as $option) {
			if ($option->getValue() === $row->getValue($this->getColumn())) {
				return $option;
			}
		}

		return null;
	}


	/**
	 * @param mixed $value
	 * @throws DataGridColumnStatusException
	 */
	public function addOption($value, string $text): Option
	{
		if (!is_scalar($value)) {
			throw new DataGridColumnStatusException('Option value has to be scalar');
		}

		$option = new Option($this->grid, $this, $value, $text);

		$this->options[] = $option;

		return $option;
	}


	/**
	 * Set all options at once
	 *
	 * @return static
	 */
	public function setOptions(array $options): self
	{
		foreach ($options as $value => $text) {
			$this->addOption($value, $text);
		}

		return $this;
	}


	/**
	 * @param  mixed $value
	 */
	public function removeOption($value): void
	{
		foreach ($this->options as $key => $option) {
			if ($option->getValue() === $value) {
				unset($this->options[$key]);
			}
		}
	}


	/**
	 * Column can have variables that will be passed to custom template scope
	 */
	public function getTemplateVariables(): array
	{
		return array_merge($this->templateVariables, [
			'options' => $this->getOptions(),
			'column' => $this->getColumn(),
			'key' => $this->getKey(),
			'status' => $this,
		]);
	}


	public function setReplacement(array $replacements): Column
	{
		throw new DataGridColumnStatusException(
			'Cannot set replacement for Column Status. For status texts replacement use ->setOptions($replacements)'
		);
	}
}
