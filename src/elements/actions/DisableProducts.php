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
class DisableProducts extends ElementAction
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
        return Craft::t('enupal-stripe', 'Disable…');
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
    public function getConfirmationMessage(): ?string
    {
        return Craft::t('enupal-stripe', "Are you sure you want to disable the selected products?");
    }

    /**
     * @inheritdoc
     * @throws Throwable
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $message = Craft::t('enupal-stripe', 'Products disabled.');

        $products = $query->all();

        $response =Stripe::$app->products->disableProducts($products);

        if (!$response) {
            $message = Craft::t('enupal-stripe', 'Failed to disabled products.');
        }

        $this->setMessage($message);

        sleep(3);

        return $response;
    }
}
