<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\GroupAction;

use Nette\Application\UI\Form;
use Nette\Forms\Container;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form as NetteForm;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridGroupActionException;

class GroupActionCollection
{

	private const ID_ATTRIBUTE_PREFIX = '_item_';

	/**
	 * @var array<GroupAction>
	 */
	protected $groupActions = [];

	/**
	 * @var DataGrid
	 */
	protected $datagrid;


	public function __construct(DataGrid $datagrid)
	{
		$this->datagrid = $datagrid;
	}


	public function addToFormContainer(Container $container): void
	{
		/** @var Form $form */
		$form = $container->lookup(Form::class);
		$lookupPath = $container->lookupPath();
		$translator = $form->getTranslator();
		$main_options = [];

		if ($translator === null) {
			throw new \UnexpectedValueException;
		}

		/**
		 * First foreach for adding button actions
		 */
		foreach ($this->groupActions as $id => $action) {
			if ($action instanceof GroupButtonAction) {
				$control = $container->addSubmit((string) $id, $action->getTitle());

				/**
				 * User may set a class to the form control
				 */
				$control->setAttribute('class', $action->getClass());

				/**
				 * User may set additional attribtues to the form control
				 */
				foreach ($action->getAttributes() as $name => $value) {
					$control->setAttribute($name, $value);
				}
			}
		}

		/**
		 * Second foreach for filling "main" select
		 */
		foreach ($this->groupActions as $id => $action) {
			if (! $action instanceof GroupButtonAction) {
				$main_options[$id] = $action->getTitle();
			}
		}

		$groupActionSelect = $container->addSelect('group_action', '', $main_options)
			->setPrompt('ublaboo_datagrid.choose');

		/**
		 * Third for creating select for each "sub"-action
		 */
		foreach ($this->groupActions as $id => $action) {
			$control = null;

			if ($action instanceof GroupSelectAction) {
				if ($action->hasOptions()) {
					if ($action instanceof GroupMultiSelectAction) {
						$control = $container->addMultiSelect((string) $id, '', $action->getOptions());
						$control->setAttribute('data-datagrid-multiselect-id', $lookupPath . self::ID_ATTRIBUTE_PREFIX . $id);
						$control->setAttribute('data-style', 'hidden');
						$control->setAttribute('data-selected-icon-check', DataGrid::$iconPrefix . 'check');
					} else {
						$control = $container->addSelect((string) $id, '', $action->getOptions());
					}

					$control->setAttribute('id', $lookupPath . self::ID_ATTRIBUTE_PREFIX . $id);
				}
			} elseif ($action instanceof GroupTextAction) {
				$control = $container->addText((string) $id, '');

				$control->setAttribute('id', $lookupPath . self::ID_ATTRIBUTE_PREFIX . $id)
					->addConditionOn($groupActionSelect, Form::EQUAL, $id)
					->setRequired($translator->translate('ublaboo_datagrid.choose_input_required'))
					->endCondition();

			} elseif ($action instanceof GroupTextareaAction) {
				$control = $container->addTextArea((string) $id, '');

				$control->setAttribute('id', $lookupPath . self::ID_ATTRIBUTE_PREFIX . $id)
					->addConditionOn($groupActionSelect, Form::EQUAL, $id)
					->setRequired($translator->translate('ublaboo_datagrid.choose_input_required'));
			}

			if (isset($control)) {
				/**
				 * User may set a class to the form control
				 */
				$control->setAttribute('class', $action->getClass());

				/**
				 * User may set additional attribtues to the form control
				 */
				foreach ($action->getAttributes() as $name => $value) {
					$control->setAttribute($name, $value);
				}
			}
		}

		if ($main_options !== []) {
			foreach (array_keys($this->groupActions) as $id) {
				$groupActionSelect->addCondition(Form::EQUAL, $id)
					->toggle($lookupPath . self::ID_ATTRIBUTE_PREFIX . $id);
			}

			$groupActionSelect->addCondition(Form::FILLED)
				->toggle(
					strtolower($this->datagrid->getFullName()) . 'group_action_submit'
				);

			$container->addSubmit('submit', 'ublaboo_datagrid.execute')
				->setValidationScope([$container])
				->setAttribute(
					'id',
					strtolower($this->datagrid->getFullName()) . 'group_action_submit'
				);
		} else {
			unset($container['group_action']);
		}

		$form->onSubmit[] = function (NetteForm $form): void {
			$this->submitted($form);
		};
	}


	/**
	 * Pass "sub"-form submission forward to custom submit function
	 */
	public function submitted(NetteForm $form): void
	{
		$submitter = $this->getFormSubmitter($form);

		if (! $submitter instanceof SubmitButton) {
			return;
		}

		$values = (array) $form->getValues();
		$values = $values['group_action'];

		if (
			($submitter->getName() === 'submit' && $submitter->isSubmittedBy())
			 && ($values->group_action === 0 || $values->group_action === null)) {
			return;
		}

		/**
		 * @todo Define items IDs
		 */
		$httpIds = $form->getHttpData(
			Form::DATA_LINE | Form::DATA_KEYS,
			strtolower($this->datagrid->getFullName()) . '_group_action_item[]'
		);

		$ids = array_keys($httpIds);

		if ($submitter->getName() === 'submit') {
			$id = $values->group_action;
			$this->groupActions[$id]->onSelect($ids, $values[$id] ?? null);

			if (!$form['group_action'] instanceof Container) {
				throw new \UnexpectedValueException;
			}

			if (isset($form['group_action']['group_action'])) {
				if (!$form['group_action']['group_action'] instanceof SelectBox) {
					throw new \UnexpectedValueException;
				}

				$form['group_action']['group_action']->setValue(null);
			}
		} else {
			$groupButtonAction = $this->groupActions[$submitter->getName()];

			if (!$groupButtonAction instanceof GroupButtonAction) {
				throw new \UnexpectedValueException(
					'This action is supposed to be a GroupButtonAction'
				);
			}

			$groupButtonAction->onClick($ids);
		}
	}


	private function getFormSubmitter(NetteForm $form): ?SubmitButton
	{
		$container = $form['group_action'];

		if (!$container instanceof Container) {
			throw new \UnexpectedValueException;
		}

		if (isset($container['submit'])) {
			if (!$container['submit'] instanceof SubmitButton) {
				throw new \UnexpectedValueException;
			}

			if ($container['submit']->isSubmittedBy()) {
				return $container['submit'];
			}
		}

		foreach ($container->getComponents() as $component) {
			if (
				$component instanceof SubmitButton
				&& $component->isSubmittedBy()
			) {
				return $component;
			}
		}

		return null;
	}


	/**
	 * Add one group button action to collection of actions
	 */
	public function addGroupButtonAction(string $title, ?string $class = null): GroupButtonAction
	{
		if (count($this->groupActions) > 0) {
			$id = count($this->groupActions) + 1;
		} else {
			$id = 1;
		}

		return $this->groupActions[$id] = new GroupButtonAction($title, $class);
	}


	/**
	 * Add one group action (select box) to collection of actions
	 */
	public function addGroupSelectAction(string $title, array $options): GroupAction
	{
		if (count($this->groupActions) > 0) {
			$id = count($this->groupActions) + 1;
		} else {
			$id = 1;
		}

		return $this->groupActions[$id] = new GroupSelectAction($title, $options);
	}


	/**
	 * Add one group action (multiselect box) to collection of actions
	 */
	public function addGroupMultiSelectAction(string $title, array $options): GroupAction
	{
		if (count($this->groupActions) > 0) {
			$id = count($this->groupActions) + 1;
		} else {
			$id = 1;
		}

		return $this->groupActions[$id] = new GroupMultiSelectAction($title, $options);
	}


	/**
	 * Add one group action (text input) to collection of actions
	 */
	public function addGroupTextAction(string $title): GroupAction
	{
		if (count($this->groupActions) > 0) {
			$id = count($this->groupActions) + 1;
		} else {
			$id = 1;
		}

		return $this->groupActions[$id] = new GroupTextAction($title);
	}


	/**
	 * Add one group action (textarea) to collection of actions
	 */
	public function addGroupTextareaAction(string $title): GroupAction
	{
		if (count($this->groupActions) > 0) {
			$id = count($this->groupActions) + 1;
		} else {
			$id = 1;
		}

		return $this->groupActions[$id] = new GroupTextareaAction($title);
	}


	public function getGroupAction(string $title): GroupAction
	{
		foreach ($this->groupActions as $action) {
			if ($action->getTitle() === $title) {
				return $action;
			}
		}

		throw new DataGridGroupActionException("Group action $title does not exist.");
	}
}
