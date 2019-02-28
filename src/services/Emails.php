<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use yii\base\Component;
use enupal\stripe\elements\Order;
use enupal\stripe\Stripe as StripePlugin;
use enupal\stripe\events\NotificationEvent;
use craft\mail\Message;

class Emails extends Component
{
    /**
     * @event NotificationEvent The event that is triggered before a notification is send
     *
     * Plugins can get notified before a notification email is send
     *
     * ```php
     * use enupal\stripe\events\NotificationEvent;
     * use enupal\stripe\services\Emails;
     * use yii\base\Event;
     *
     * Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_NOTIFICATION_EMAIL, function(NotificationEvent $e) {
     *      $message = $e->message;
     *     // Do something
     * });
     * ```
     */
    const EVENT_BEFORE_SEND_NOTIFICATION_EMAIL = 'beforeSendNotificationEmail';

    const ADMIN_TYPE = 'admin';
    const CUSTOMER_TYPE = 'customer';

    /**
     * Send admin and customer emails
     *
     * @param Order $order
     * @return mixed
     * @throws \yii\base\Exception
     */
    public function sendNotificationEmails(Order $order)
    {
        $this->sendNotificationEmail($order, self::ADMIN_TYPE);
        $this->sendNotificationEmail($order, self::CUSTOMER_TYPE);
    }

    /**
     * @param Order $order
     * @param $type
     * @return bool
     * @throws \yii\base\Exception
     */
    public function sendNotificationEmail(Order $order, $type = self::ADMIN_TYPE)
    {
        $message = $this->getAdminMessage($order);

        if ($type == self::CUSTOMER_TYPE){
            $message = $this->getCustomerMessage($order);
        }

        $mailer = Craft::$app->getMailer();
        $result = false;

        $event = new NotificationEvent([
            'message' => $message,
            'type' => $type,
            'order' => $order
        ]);

        $this->trigger(self::EVENT_BEFORE_SEND_NOTIFICATION_EMAIL, $event);

        try {
            $result = $mailer->send($message);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
        }

        if ($result) {
            Craft::info($type.' email sent successfully', __METHOD__);
        }else{
            Craft::error('Unable to send '.$type.' email', __METHOD__);
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getEmailsPath()
    {
        $defaultTemplate = Craft::getAlias('@enupal/stripe/templates/_emails/');

        return $defaultTemplate;
    }

    /**
     * @param Order $order
     * @return bool|Message
     * @throws \yii\base\Exception
     */
    private function getCustomerMessage(Order $order)
    {
        $settings = StripePlugin::$app->settings->getSettings();

        if (!$settings->enableCustomerNotification) {
            return false;
        }

        $variables = [];
        $view = Craft::$app->getView();
        $message = new Message();
        $message->setFrom([$settings->customerNotificationSenderEmail => $settings->customerNotificationSenderName]);
        $variables['order'] = $order;
        $subject = $view->renderString($settings->customerNotificationSubject, $variables);
        $textBody = $view->renderString("Thank you! your order number is: {{order.number}}", $variables);

        $originalPath = $view->getTemplatesPath();

        $template = 'customer';
        $templateOverride = null;
        $extensions = ['.html', '.twig'];

        if ($settings->customerTemplateOverride){
            // let's check if the file exists
            $overridePath = $originalPath.DIRECTORY_SEPARATOR.$settings->customerTemplateOverride;
            foreach ($extensions as $extension) {
                if (file_exists($overridePath.$extension)){
                    $templateOverride = $settings->customerTemplateOverride;
                    $template = $templateOverride;
                }
            }
        }

        if (is_null($templateOverride)){
            $view->setTemplatesPath($this->getEmailsPath());
        }

        $htmlBody = $view->renderTemplate($template, $variables);

        $view->setTemplatesPath($originalPath);

        $message->setSubject($subject);
        $message->setHtmlBody($htmlBody);
        $message->setTextBody($textBody);
        $message->setReplyTo($settings->customerNotificationReplyToEmail);
        $emails = [$order->email];
        $message->setTo($emails);

        return $message;
    }

    /**
     * @param Order $order
     * @return bool|Message
     * @throws \yii\base\Exception
     */
    private function getAdminMessage(Order $order)
    {
        $settings = StripePlugin::$app->settings->getSettings();

        if (!$settings->enableAdminNotification) {
            return false;
        }

        $variables = [];
        $view = Craft::$app->getView();
        $message = new Message();
        $message->setFrom([$settings->adminNotificationSenderEmail => $settings->adminNotificationSenderName]);
        $variables['order'] = $order;
        $subject = $view->renderString($settings->adminNotificationSubject, $variables);
        $textBody = $view->renderString("Congratulations! you have received a payment, total: {{ order.totalPrice }} order number: {{order.number}}", $variables);

        $originalPath = $view->getTemplatesPath();
        $template = 'admin';
        $templateOverride = null;
        $extensions = ['.html', '.twig'];

        if ($settings->adminTemplateOverride){
            // let's check if the file exists
            $overridePath = $originalPath.DIRECTORY_SEPARATOR.$settings->adminTemplateOverride;
            foreach ($extensions as $extension) {
                if (file_exists($overridePath.$extension)){
                    $templateOverride = $settings->adminTemplateOverride;
                    $template = $templateOverride;
                }
            }
        }

        if (is_null($templateOverride)){
            $view->setTemplatesPath($this->getEmailsPath());
        }

        $htmlBody = $view->renderTemplate($template, $variables);

        $view->setTemplatesPath($originalPath);

        $message->setSubject($subject);
        $message->setHtmlBody($htmlBody);
        $message->setTextBody($textBody);
        $message->setReplyTo($settings->adminNotificationReplyToEmail);

        $emails = explode(",", $settings->adminNotificationRecipients);
        $message->setTo($emails);

        return $message;
    }
}
