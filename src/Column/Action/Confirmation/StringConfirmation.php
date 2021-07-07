<?php

declare(strict_types=1);

namespace Ublaboo\DataGrid\Column\Action\Confirmation;

use Nette\Localization\ITranslator;
use Ublaboo\DataGrid\Row;

final class StringConfirmation implements IConfirmation
{

	/**
	 * @var string
	 */
	private $question;

	/**
	 * @var string[]
	 */
	private $placeholders;

	/**
	 * @var ITranslator|null
	 */
	private $translator;


	public function __construct(string $question, string ...$placeholders)
	{
		$this->question = $question;
		$this->placeholders = $placeholders;
	}


	public function getMessage(Row $row): ?string
	{
		$question = $this->translator !== null
			? $this->translator->translate($this->question)
			: $this->question;

		if (!(bool) $this->placeholders) {
			return $question;
		}

		$values = array_map(function (string $placeholder) use ($row): string {
			return (string) $row->getValue($placeholder);
		}, $this->placeholders);

		return vsprintf($question, $values);
	}


	public function getTranslator(): ?ITranslator
	{
		return $this->translator;
	}

	public function setTranslator(?ITranslator $translator): void
	{
		$this->translator = $translator;
	}
}
