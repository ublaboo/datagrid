<?php declare(strict_types = 1);

/**
 * @copyright   Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\DataGrid\Traits;

trait TButtonClass
{

	/**
	 * @var string
	 */
	protected $class = 'btn btn-xs btn-default';

	/**
	 * Set attribute class
	 *
	 * @param string $class
	 */
	public function setClass(string $class)
	{
		$this->class = $class;

		return $this;
	}


	/**
	 * Get attribute class
	 *
	 * @return string
	 */
	public function getClass(): string
	{
		return $this->class;
	}

}
