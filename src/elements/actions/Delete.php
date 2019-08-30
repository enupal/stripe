<?php

namespace enupal\stripe\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;

use enupal\stripe\Stripe;
use Throwable;

/**
 *
 * @property string $triggerLabel
 */
class Delete extends ElementAction
{
	// Properties
	// =========================================================================

	/**
	 * @var string|null The confirmation message that should be shown before the elements get deleted
	 */
	public $confirmationMessage;

	/**
	 * @var string|null The message that should be shown after the elements get deleted
	 */
	public $successMessage;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getTriggerLabel(): string
	{
		return Craft::t('enupal-stripe', 'Delete…');
	}

	/**
	 * @inheritdoc
	 */
	public static function isDestructive(): bool
	{
		return true;
	}

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getConfirmationMessage()
	{
		return Craft::t('enupal-stripe', "Are you sure you want to delete the selected payment forms, and all of it's orders?");
	}

	/**
	 * @inheritdoc
	 * @throws Throwable
	 */
	public function performAction(ElementQueryInterface $query): bool
	{
		$message = null;

		$response = Stripe::$app->paymentForms->deleteForms($query->all());

		if ($response) {
			$message = Craft::t('enupal-stripe', 'Payment Forms Deleted.');
		} else {
			$message = Craft::t('enupal-stripe', 'Failed to delete payment forms.');
		}

		$this->setMessage($message);

		return $response;
	}
}
