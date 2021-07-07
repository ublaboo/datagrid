<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\Status;

use Ublaboo\DataGrid\Column\Action\Confirmation\CallbackConfirmation;
use Ublaboo\DataGrid\Column\Action\Confirmation\IConfirmation;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\Column\ColumnStatus;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Row;

class Option
{

	/**
	 * @var ColumnStatus
	 */
	protected $columnStatus;

	/**
	 * @var mixed
	 */
	protected $value;

	/**
	 * @var string
	 */
	protected $text;

	/**
	 * @var string|null
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $class = 'btn-success';

	/**
	 * @var string
	 */
	protected $classSecondary = 'btn btn-xs';

	/**
	 * @var string
	 */
	protected $classInDropdown = 'ajax dropdown-item';

	/**
	 * @var string|null
	 */
	protected $icon;

	/**
	 * @var string|null
	 */
	protected $iconSecondary;

	/**
	 * @var IConfirmation|null
	 */
	protected $confirmation;

	/**
	 * @var DataGrid
	 */
	private $grid;

	/**
	 * @param mixed $value
	 */
	public function __construct(DataGrid $grid, ColumnStatus $columnStatus, $value, string $text)
	{
		$this->grid = $grid;
		$this->columnStatus = $columnStatus;
		$this->value = $value;
		$this->text = $text;
	}


	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}


	public function endOption(): ColumnStatus
	{
		return $this->columnStatus;
	}


	/**
	 * @return static
	 */
	public function setTitle(string $title): self
	{
		$this->title = $title;

		return $this;
	}


	public function getTitle(): ?string
	{
		return $this->title;
	}


	/**
	 * @return static
	 */
	public function setClass(string $class, ?string $classSecondary = null): self
	{
		$this->class = $class;

		if ($classSecondary !== null) {
			$this->classSecondary = $classSecondary;
		}

		return $this;
	}


	public function getClass(): ?string
	{
		return $this->class;
	}


	/**
	 * @return static
	 */
	public function setClassSecondary(string $classSecondary): self
	{
		$this->classSecondary = $classSecondary;

		return $this;
	}


	public function getClassSecondary(): string
	{
		return $this->classSecondary;
	}


	/**
	 * @return static
	 */
	public function setClassInDropdown(string $classInDropdown): self
	{
		$this->classInDropdown = $classInDropdown;

		return $this;
	}


	public function getClassInDropdown(): string
	{
		return $this->classInDropdown;
	}


	/**
	 * @return static
	 */
	public function setIcon(string $icon): self
	{
		$this->icon = $icon;

		return $this;
	}


	public function getIcon(): ?string
	{
		return $this->icon;
	}


	/**
	 * @return static
	 */
	public function setIconSecondary(string $iconSecondary): self
	{
		$this->iconSecondary = $iconSecondary;

		return $this;
	}


	public function getIconSecondary(): ?string
	{
		return $this->iconSecondary;
	}


	public function getText(): string
	{
		return $this->text;
	}


	/**
	 * @return static
	 */
	public function setConfirmation(IConfirmation $confirmation): self
	{
		$this->confirmation = $confirmation;

		return $this;
	}


	/**
	 * @return static
	 */
	public function setCallbackConfirmation(callable $callback): self
	{
		$this->confirmation = new CallbackConfirmation($callback);

		return $this;
	}


	/**
	 * @return static
	 */
	public function setStringConfirmation(string $question, string ...$placeholders): self
	{
		$this->confirmation = new StringConfirmation($question, ...$placeholders);
		$this->confirmation->setTranslator($this->grid->getTranslator());

		return $this;
	}


	public function getConfirmationDialog(Row $row): ?string
	{
		if ($this->confirmation === null) {
			return null;
		}

		if ($this->confirmation instanceof StringConfirmation) {
			if ($this->confirmation->getTranslator() === null) {
				// Backward compatibility
				$this->confirmation->setTranslator($this->grid->getTranslator());
			}
		}

		return $this->confirmation->getMessage($row);
	}
}
