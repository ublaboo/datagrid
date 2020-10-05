<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\Export;

use Nette\Application\UI\Link;
use Nette\Utils\Html;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Traits\TButtonClass;
use Ublaboo\DataGrid\Traits\TButtonIcon;
use Ublaboo\DataGrid\Traits\TButtonText;
use Ublaboo\DataGrid\Traits\TButtonTitle;
use Ublaboo\DataGrid\Traits\TButtonTryAddIcon;

class Export
{

	use TButtonTryAddIcon;
	use TButtonIcon;
	use TButtonClass;
	use TButtonTitle;
	use TButtonText;

	/**
	 * @var callable
	 */
	protected $callback;

	/**
	 * @var bool
	 */
	protected $ajax = false;

	/**
	 * @var bool
	 */
	protected $filtered;

	/**
	 * @var Link|null
	 */
	protected $link;

	/**
	 * @var array
	 */
	protected $columns = [];

	/**
	 * @var DataGrid
	 */
	protected $grid;

	/**
	 * @var string|null
	 */
	protected $confirmDialog = null;


	/**
	 * @var string|null
	 */
	protected $target = null;


	public function __construct(
		DataGrid $grid,
		string $text,
		callable $callback,
		bool $filtered
	)
	{
		$this->grid = $grid;
		$this->text = $text;
		$this->callback = $callback;
		$this->filtered = $filtered;
		$this->title = $text;
	}


	public function render(): Html
	{
		$a = Html::el('a', [
			'class' => [$this->class],
			'title' => $this->grid->getTranslator()->translate($this->getTitle()),
			'href' => $this->link,
			'target' => $this->target,
		]);

		$this->tryAddIcon(
			$a,
			$this->getIcon(),
			$this->grid->getTranslator()->translate($this->getTitle())
		);

		$a->addText($this->grid->getTranslator()->translate($this->text));

		if ($this->isAjax()) {
			$a->appendAttribute('class', 'ajax');
		}

		if ($this->confirmDialog !== null) {
			$a->setAttribute('data-datagrid-confirm', $this->confirmDialog);
		}

		return $a;
	}


	/**
	 * @return static
	 */
	public function setConfirmDialog(string $confirmDialog): self
	{
		$this->confirmDialog = $confirmDialog;

		return $this;
	}


	/**
	 * Tell export which columns to use when exporting data
	 *
	 * @return static
	 */
	public function setColumns(array $columns): self
	{
		$this->columns = $columns;

		return $this;
	}


	/**
	 * Get columns for export
	 */
	public function getColumns(): array
	{
		return $this->columns;
	}


	/**
	 * Export signal url
	 *
	 * @return static
	 */
	public function setLink(Link $link): self
	{
		$this->link = $link;

		return $this;
	}


	/**
	 * Tell export whether to be called via ajax or not
	 *
	 * @return static
	 */
	public function setAjax(bool $ajax = true): self
	{
		$this->ajax = $ajax;

		return $this;
	}


	public function isAjax(): bool
	{
		return $this->ajax;
	}


	/**
	 * Is export filtered?
	 */
	public function isFiltered(): bool
	{
		return $this->filtered;
	}


	/**
	 * Call export callback
	 */
	public function invoke(array $data): void
	{
		($this->callback)($data, $this->grid);
	}


	/**
	 * Adds target to html:a [_blank, _self, _parent, _top]
	 * @param string|null $target
	 */
	public function setTarget($target = null): void
	{
		if (in_array($target, ['_blank', '_self', '_parent', '_top'], true)) {
			$this->target = $target;
		}
	}
}
