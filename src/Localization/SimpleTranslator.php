<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\Localization;

use Nette\Localization\Translator;

class SimpleTranslator implements Translator
{

	/**
	 * @var array
	 */
	private $dictionary = [
		'ublaboo_datagrid.no_item_found_reset' => 'No items found. You can reset the filter',
		'ublaboo_datagrid.no_item_found' => 'No items found.',
		'ublaboo_datagrid.here' => 'here',
		'ublaboo_datagrid.items' => 'Items',
		'ublaboo_datagrid.all' => 'all',
		'ublaboo_datagrid.from' => 'from',
		'ublaboo_datagrid.reset_filter' => 'Reset filter',
		'ublaboo_datagrid.group_actions' => 'Group actions',
		'ublaboo_datagrid.show' => 'Show',
		'ublaboo_datagrid.add' => 'Add',
		'ublaboo_datagrid.edit' => 'Edit',
		'ublaboo_datagrid.show_all_columns' => 'Show all columns',
		'ublaboo_datagrid.show_default_columns' => 'Show default columns',
		'ublaboo_datagrid.hide_column' => 'Hide column',
		'ublaboo_datagrid.action' => 'Action',
		'ublaboo_datagrid.previous' => 'Previous',
		'ublaboo_datagrid.next' => 'Next',
		'ublaboo_datagrid.choose' => 'Choose',
		'ublaboo_datagrid.choose_input_required' => 'Group action text not allow empty value',
		'ublaboo_datagrid.execute' => 'Execute',
		'ublaboo_datagrid.save' => 'Save',
		'ublaboo_datagrid.cancel' => 'Cancel',
		'ublaboo_datagrid.multiselect_choose' => 'Choose',
		'ublaboo_datagrid.multiselect_selected' => '{0} selected',
		'ublaboo_datagrid.filter_submit_button' => 'Filter',
		'ublaboo_datagrid.show_filter' => 'Show filter',
		'ublaboo_datagrid.per_page_submit' => 'Change',
	];


	public function __construct(array $dictionary = [])
	{
		$this->dictionary = array_merge($this->dictionary, $dictionary);
	}


	/**
	 * @param mixed $message
	 * @param mixed ...$parameters
	 */
	public function translate($message, ...$parameters): string
	{
		return $this->dictionary[$message] ?? $message;
	}


	public function setDictionary(array $dictionary): void
	{
		$this->dictionary = $dictionary;
	}
}
