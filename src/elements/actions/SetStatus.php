<?php

namespace enupal\stripe\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;

class SetStatus extends ElementAction
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

    public $status;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getTriggerHtml(): string
    {
        return Craft::$app->view->renderTemplate('enupal-stripe/_setstatus/trigger');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $status = $this->status;

        $ids = $query->ids();

        Craft::$app->db->createCommand()->update(
            '{{%enupalstripe_orders}}',
            ['orderStatusId' => $status],
            ['in', 'id', $ids]
        )->execute();

        $this->setMessage("Status updated.");

        return true;
    }
}
