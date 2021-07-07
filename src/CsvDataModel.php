<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid;

use Nette\Localization\Translator;
use Ublaboo\DataGrid\Column\Column;

class CsvDataModel
{

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @var array<Column>
	 */
	protected $columns;

	/**
	 * @var Translator
	 */
	protected $translator;


	public function __construct(
		array $data,
		array $columns,
		Translator $translator
	)
	{
		$this->data = $data;
		$this->columns = $columns;
		$this->translator = $translator;
	}


	/**
	 * Get data with header and "body"
	 */
	public function getSimpleData(bool $includeHeader = true): array
	{
		$return = [];

		if ($includeHeader) {
			$return[] = $this->getHeader();
		}

		foreach ($this->data as $item) {
			$return[] = $this->getRow($item);
		}

		return $return;
	}


	public function getHeader(): array
	{
		$header = [];

		foreach ($this->columns as $column) {
			$header[] = $this->translator->translate($column->getName());
		}

		return $header;
	}


	/**
	 * Get item values saved into row
	 *
	 * @param mixed $item
	 */
	public function getRow($item): array
	{
		$row = [];

		foreach ($this->columns as $column) {
			$row[] = strip_tags((string) $column->render($item));
		}

		return $row;
	}

}
