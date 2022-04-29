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
class EnableProducts extends ElementAction
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
        return Craft::t('enupal-stripe', 'Enable…');
    }

    /**
     * @inheritdoc
     */
    public static function isDestructive(): bool
    {
        return false;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage()
    {
        return Craft::t('enupal-stripe', "Are you sure you want to enable the selected products?");
    }

    /**
     * @inheritdoc
     * @throws Throwable
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $message = Craft::t('enupal-stripe', 'Products enabled.');

        $products = $query->all();

        $response =Stripe::$app->products->enableProducts($products);

        if (!$response) {
            $message = Craft::t('enupal-stripe', 'Failed to enabled products.');
        }

        $this->setMessage($message);

        sleep(3);

        return $response;
    }
}
