<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\Tests\Cases;

use Tester\Assert;
use Tester\TestCase;
use Ublaboo;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../Files/TestingDataGridFactory.php';

final class ColumnActionTest extends TestCase
{

	/**
	 * @var DataGrid
	 */
	private $grid;

	public function setUp(): void
	{
		$factory = new Ublaboo\DataGrid\Tests\Files\TestingDataGridFactory();
		$this->grid = $factory->createTestingDataGrid();
	}


	public function render($column)
	{
		$item = new Ublaboo\DataGrid\Row($this->grid, ['id' => 1, 'name' => 'John'], 'id');

		return (string) $column->render($item);
	}


	public function testActionDuplcitColumn(): void
	{
		$this->grid->addAction('action', 'Do', 'doStuff!');

		$grid = $this->grid;
		$add_action = function () use ($grid): void {
			$grid->addAction('action', 'Do', 'doStuff!');
		};

		Assert::exception($add_action, 'Ublaboo\DataGrid\Exception\DataGridException', 'There is already action at key [action] defined.');
	}


	public function testActionLink(): void
	{
		$action = $this->grid->addAction('action', 'Do', 'doStuff!');

		Assert::same(
			'<a href="doStuff!?id=1" class="btn btn-xs btn-default btn-secondary">Do</a>',
			$this->render($action)
		);

		$action = $this->grid->addAction('detail', 'Do');

		Assert::same(
			'<a href="detail?id=1" class="btn btn-xs btn-default btn-secondary">Do</a>',
			$this->render($action)
		);

		$action = $this->grid->addAction('title', 'Do', 'detail', ['id', 'name']);
		Assert::same(
			'<a href="detail?id=1&amp;name=John" class="btn btn-xs btn-default btn-secondary">Do</a>',
			$this->render($action)
		);

		$action = $this->grid->addAction('title2', 'Do', 'detail', [
			'id' => 'name',
		'name' => 'id',
		]);
		Assert::same(
			'<a href="detail?id=John&amp;name=1" class="btn btn-xs btn-default btn-secondary">Do</a>',
			$this->render($action)
		);
	}


	public function testActionIcon(): void
	{
		$action = $this->grid->addAction('action', 'Do', 'doStuff!');

		DataGrid::$iconPrefix = 'icon-';
		$action->setIcon('user');

		Assert::same(
			'<a href="doStuff!?id=1" class="btn btn-xs btn-default btn-secondary"><span class="icon-user"></span>&nbsp;Do</a>',
			$this->render($action)
		);
	}


	public function testActionClass(): void
	{
		$action = $this->grid->addAction('action', 'Do', 'doStuff!')->setClass('btn');

		Assert::same('<a href="doStuff!?id=1" class="btn">Do</a>', $this->render($action));

		$action->setClass(null);

		Assert::same('<a href="doStuff!?id=1">Do</a>', $this->render($action));
	}


	public function testActionTitle(): void
	{
		$action = $this->grid->addAction('action', 'Do', 'doStuff!')->setTitle('hello');

		Assert::same(
			'<a href="doStuff!?id=1" title="hello" class="btn btn-xs btn-default btn-secondary">Do</a>',
			$this->render($action)
		);
	}


	public function testActionCustomHref(): void
	{
		$action = $this->grid->addAction('action1', 'Do')->setCustomHref('https://www.example.com/');

		Assert::same(
			'<a href="https://www.example.com/" class="btn btn-xs btn-default btn-secondary">Do</a>',
			$this->render($action)
		);

		$action = $this->grid->addAction('action2', 'Do')->setCustomHref(function($rowItem) {
			return 'https://www.example.com/?name=' . $rowItem['name'];
		});

		Assert::same(
			'<a href="https://www.example.com/?name=John" class="btn btn-xs btn-default btn-secondary">Do</a>',
			$this->render($action)
		);

		$action = $this->grid->addAction('action3', 'Do')->setCustomHref(function($rowItem) {
			return '/preview/user/?id=' . $rowItem['id'];
		});

		Assert::same(
			'<a href="/preview/user/?id=1" class="btn btn-xs btn-default btn-secondary">Do</a>',
			$this->render($action)
		);
	}


	public function testActionConfirm(): void
	{
		$action = $this->grid->addAction('action', 'Do', 'doStuff!')
			->setConfirmation(new StringConfirmation('Really?'));

		Assert::same(
			'<a href="doStuff!?id=1" class="btn btn-xs btn-default btn-secondary" data-datagrid-confirm="Really?">Do</a>',
			$this->render($action)
		);
	}


	public function testActionRenderCondition(): void
	{
		$action = $this->grid->addAction('action1', 'Do', 'doStuff!')->setRenderCondition(function () {
			return true;
		});

		Assert::same('<a href="doStuff!?id=1" class="btn btn-xs btn-default btn-secondary">Do</a>', $this->render($action));

		$action = $this->grid->addAction('action2', 'Do', 'doStuff!')->setRenderCondition(function () {
			return false;
		});

		Assert::same('', $this->render($action));
	}

}


$test_case = new ColumnActionTest();
$test_case->run();
