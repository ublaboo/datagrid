<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\Column;

use Nette\Utils\Html;
use Ublaboo\DataGrid\Column\Action\Confirmation\CallbackConfirmation;
use Ublaboo\DataGrid\Column\Action\Confirmation\IConfirmation;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnRendererException;
use Ublaboo\DataGrid\Exception\DataGridException;
use Ublaboo\DataGrid\Row;
use Ublaboo\DataGrid\Traits\TButtonText;
use Ublaboo\DataGrid\Traits\TButtonTryAddIcon;
use Ublaboo\DataGrid\Traits\TLink;
use Ublaboo\DataGrid\Traits\TRenderCondition;

class Action extends Column
{

	use TButtonTryAddIcon;
	use TButtonText;
	use TLink;
	use TRenderCondition;

	/**
	 * @var string
	 */
	public static $dataConfirmAttributeName = 'datagrid-confirm';

	/**
	 * @var string
	 */
	protected $href;

	/**
	 * @var array
	 */
	protected $params;

	/**
	 * @var IConfirmation|null
	 */
	protected $confirmation;

	/**
	 * @var array
	 */
	protected $dataAttributes = [];

	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * @var array
	 */
	protected $parameters = [];

	/**
	 * @var string|callable|null
	 */
	protected $icon;

	/**
	 * @var string|callable
	 */
	protected $class = 'btn btn-xs btn-default btn-secondary';

	/**
	 * @var bool
	 */
	protected $openInNewTab = false;

	/**
	 * @var string|callable
	 */
	private $title;

	/**
	 * @var string|callable
	 */
	private $customHref;


	public function __construct(
		DataGrid $grid,
		string $key,
		string $href,
		string $name,
		array $params
	)
	{
		parent::__construct($grid, $key, '', $name);

		$this->href = $href;
		$this->params = $params;
	}


	/**
	 * @return mixed
	 */
	public function render(Row $row)
	{
		if (!$this->shouldBeRendered($row)) {
			return null;
		}

		try {
			return $this->useRenderer($row);
		} catch (DataGridColumnRendererException $e) {
		}

		if (!empty($this->customHref)) {
			$link = $this->getCustomHref($row);
		} else {
			$link = $this->createLink(
				$this->grid,
				$this->href,
				$this->getItemParams($row, $this->params) + $this->parameters
			);
		}

		$a = Html::el('a')->href($link);

		$this->tryAddIcon($a, $this->getIcon($row), $this->getName());

		if ($this->dataAttributes !== []) {
			foreach ($this->dataAttributes as $key => $value) {
				$a->data((string) $key, $value);
			}
		}

		if ($this->attributes !== []) {
			$a->addAttributes($this->attributes);
		}

		$a->addText($this->translate($this->getName()));

		$title = $this->getTitle($row);

		if ($title !== null) {
			$a->setAttribute('title', $this->translate($title));
		}

		if ($this->class !== null) {
			$a->setAttribute('class', $this->getClass($row));
		}

		$confirmationDialog = $this->getConfirmationDialog($row);

		if ($confirmationDialog !== null) {
			$a->data(static::$dataConfirmAttributeName, $confirmationDialog);
		}

		if ($this->openInNewTab) {
			$a->addAttributes(['target' => '_blank']);
		}

		return $a;
	}


	/**
	 * @return static
	 */
	public function addParameters(array $parameters): self
	{
		$this->parameters = $parameters;

		return $this;
	}


	/**
	 * @param string|callable $title
	 * @return static
	 * @throws DataGridException
	 */
	public function setTitle($title): self
	{
		$this->checkPropertyStringOrCallable($title, 'title');

		$this->title = $title;

		return $this;
	}


	/**
	 * @throws DataGridException
	 */
	public function getTitle(Row $row): ?string
	{
		/**
		 * If user callback was used for setting action title, it has to return string
		 */
		return $this->getPropertyStringOrCallableGetString($row, $this->title, 'title');
	}


	/**
	 * Set customHref
	 * @param string|callable $customHref
	 * @return static
	 * @throws DataGridException
	 */
	public function setCustomHref($customHref): self
	{
		$this->checkPropertyStringOrCallable($customHref, 'customHref');

		$this->customHref = $customHref;

		return $this;
	}


	/**
	 * Get customHref
	 * @param Row $row
	 * @return string
	 * @throws DataGridException
	 */
	public function getCustomHref(Row $row)
	{
		/**
		 * If user callback was used for setting action customHref, it has to return string
		 */
		return $this->getPropertyStringOrCallableGetString($row, $this->customHref, 'customHref');
	}


	/**
	 * Set attribute class
	 * @param string|callable $class
	 * @return static
	 * @throws DataGridException
	 */
	public function setClass($class): self
	{
		$this->checkPropertyStringOrCallable($class, 'class');

		$this->class = $class;

		return $this;
	}


	/**
	 * @throws DataGridException
	 */
	public function getClass(Row $row): ?string
	{
		/**
		 * If user callback was used for setting action class, it has to return string
		 */
		return $this->getPropertyStringOrCallableGetString($row, $this->class, 'class');
	}


	/**
	 * @param string|callable $icon
	 * @return static
	 * @throws DataGridException
	 */
	public function setIcon($icon): self
	{
		$this->checkPropertyStringOrCallable($icon, 'icon');

		$this->icon = $icon;

		return $this;
	}


	/**
	 * @throws DataGridException
	 */
	public function getIcon(Row $row): ?string
	{
		/**
		 * If user callback was used for setting action icon, it has to return string
		 */
		return $this->getPropertyStringOrCallableGetString($row, $this->icon, 'icon');
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
	 * @throws DataGridException
	 */
	public function getConfirmationDialog(Row $row): ?string
	{
		if ($this->confirmation === null) {
			return null;
		}

		if ($this->confirmation instanceof CallbackConfirmation) {
			return ($this->confirmation->getCallback())($row->getItem());
		}

		if ($this->confirmation instanceof StringConfirmation) {
			$question = $this->translate($this->confirmation->getQuestion());

			if ($this->confirmation->getPlaceholderName() === null) {
				return $question;
			}

			return str_replace(
				'%s',
				(string) $row->getValue($this->confirmation->getPlaceholderName()),
				$question
			);
		}

		throw new DataGridException('Unsupported confirmation');
	}


	/**
	 * @param mixed $value
	 * @return static
	 */
	public function setDataAttribute(string $key, $value): self
	{
		$this->dataAttributes[$key] = $value;

		return $this;
	}


	/**
	 * @return static
	 */
	public function addAttributes(array $attrs): self
	{
		$this->attributes += $attrs;

		return $this;
	}


	/**
	 * @param string|callable|null $property
	 * @throws DataGridException
	 */
	public function getPropertyStringOrCallableGetString(
		Row $row,
		$property,
		string $name
	): ?string
	{
		if (is_string($property)) {
			return $property;
		}

		if (is_callable($property)) {
			$value = call_user_func($property, $row->getItem());

			if (!is_string($value)) {
				throw new DataGridException("Action {$name} callback has to return a string");
			}

			return $value;
		}

		return $property;
	}


	public function isOpenInNewTab(): bool
	{
		return $this->openInNewTab;
	}


	/**
	 * @return static
	 */
	public function setOpenInNewTab(bool $openInNewTab = true): self
	{
		$this->openInNewTab = $openInNewTab;

		return $this;
	}


	/**
	 * @param mixed $property
	 * @throws DataGridException
	 */
	protected function checkPropertyStringOrCallable($property, string $name): void
	{
		if (!is_string($property) && !is_callable($property) && $property !== null) {
			throw new DataGridException(
				sprintf('Action %s has to be either string or a callback returning string', $name)
			);
		}
	}


	protected function translate(string $message): string
	{
		return $this->grid->getTranslator()->translate($message);
	}
}
