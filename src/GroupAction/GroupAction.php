<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\GroupAction;

use Nette\SmartObject;
use Ublaboo\DataGrid\Column\Action\Confirmation\CallbackConfirmation;
use Ublaboo\DataGrid\Column\Action\Confirmation\IConfirmation;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * @method void onSelect(array $ids, string $value)
 */
abstract class GroupAction
{

	use SmartObject;

	/**
	 * @var array|callable[]
	 */
	public $onSelect = [];

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $class = 'form-control input-sm form-control-sm';

	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * @var IConfirmation|null
	 */
	protected $confirmation;


	public function __construct(string $title)
	{
		$this->title = $title;
	}


	public function getTitle(): string
	{
		return $this->title;
	}


	/**
	 * @return static
	 */
	public function setClass(string $class): self
	{
		$this->class = $class;

		return $this;
	}


	public function getClass(): string
	{
		return $this->class;
	}


	/**
	 * @param mixed $value
	 * @return static
	 */
	public function setAttribute(string $key, $value): self
	{
		$this->attributes[$key] = $value;

		return $this;
	}


	public function getAttributes(): array
	{
		return $this->attributes;
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
	 * @return bool
	 */
	public function hasConfirmation(): bool
	{
		return $this->confirmation !== null;
	}

	/**
	 * @throws DataGridException
	 */
	public function getConfirmationDialog(DataGrid $grid): ?string
	{
		if ($this->confirmation === null) {
			return null;
		}

		if ($this->confirmation instanceof CallbackConfirmation) {
			return ($this->confirmation->getCallback())();
		}

		if ($this->confirmation instanceof StringConfirmation) {

			$question = $grid->getTranslator()->translate($this->confirmation->getQuestion());

			if ($this->confirmation->getPlaceholderName() === null) {
				return $question;
			}

			return str_replace(
				'%s',
				$row->getValue($this->confirmation->getPlaceholderName()),
				$question
			);
		}
	}
}
