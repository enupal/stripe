<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use enupal\stripe\elements\Commission;
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
    const VENDOR_TYPE = 'vendor';

    /**
     * Send admin and customer emails
     *
     * @param Order $order
     * @throws \yii\base\Exception
     */
    public function sendNotificationEmails(Order $order)
    {
        $settings = StripePlugin::getInstance()->getSettings();

        if ($settings->enableCustomerNotification){
            $this->sendNotificationEmail($order, self::CUSTOMER_TYPE);
        }

        if ($settings->enableAdminNotification){
            $this->sendNotificationEmail($order, self::ADMIN_TYPE);
        }
    }

    /**
     * @param Order $order
     * @param string $type
     *
     * @return bool
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    public function sendNotificationEmail(Order $order, $type = self::ADMIN_TYPE)
    {
        $message = $this->getAdminMessage($order);

        if ($type == self::CUSTOMER_TYPE){
            $message = $this->getCustomerMessage($order);
        }

        $event = new NotificationEvent([
            'message' => $message,
            'type' => $type,
            'order' => $order
        ]);

        $this->trigger(self::EVENT_BEFORE_SEND_NOTIFICATION_EMAIL, $event);

        return $this->sendEmail($message, $type);
    }

    /**
     * @param Commission $commission
     *
     * @return bool
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    public function sendVendorNotificationEmail(Commission $commission)
    {
        $settings = StripePlugin::getInstance()->getSettings();

        if (!$settings->enableVendorNotification){
            return false;
        }

        $message = $this->getVendorMessage($commission);

        return $this->sendEmail($message, self::VENDOR_TYPE);
    }

    private function sendEmail(Message $message, $type = self::ADMIN_TYPE)
    {
        $mailer = Craft::$app->getMailer();
        $result = false;

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
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
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
            $customerTemplateOverride = Craft::parseEnv($settings->customerTemplateOverride);
            // let's check if the file exists
            $overridePath = $originalPath.DIRECTORY_SEPARATOR.$customerTemplateOverride;
            foreach ($extensions as $extension) {
                if (file_exists($overridePath.$extension)){
                    $templateOverride = $customerTemplateOverride;
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
        $emails = [trim($order->email)];
        $message->setTo($emails);

        return $message;
    }

    /**
     * @param Commission $commission
     * @return bool|Message
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    private function getVendorMessage(Commission $commission)
    {
        $settings = StripePlugin::$app->settings->getSettings();

        if (!$settings->enableVendorNotification) {
            return false;
        }

        $variables = [];
        $view = Craft::$app->getView();
        $message = new Message();
        $message->setFrom([$settings->vendorNotificationSenderEmail => $settings->vendorNotificationSenderName]);
        $variables['commission'] = $commission;
        $subject = $view->renderString($settings->vendorNotificationSubject, $variables);
        $textBody = $view->renderString("Congratulations, {{commission.getVendor().getUser().firstName}}! Looks like someone just purchased one of your products", $variables);

        $originalPath = $view->getTemplatesPath();

        $template = 'vendor';
        $templateOverride = null;
        $extensions = ['.html', '.twig'];

        if ($settings->vendorTemplateOverride){
            $vendorTemplateOverride = Craft::parseEnv($settings->vendorTemplateOverride);
            // let's check if the file exists
            $overridePath = $originalPath.DIRECTORY_SEPARATOR.$vendorTemplateOverride;
            foreach ($extensions as $extension) {
                if (file_exists($overridePath.$extension)){
                    $templateOverride = $vendorTemplateOverride;
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
        $message->setReplyTo($settings->vendorNotificationReplyToEmail);
        $emails = [trim($commission->getVendor()->getUser()->email)];
        $message->setTo($emails);

        return $message;
    }

    /**
     * @param Order $order
     * @return bool|Message
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
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
            $adminTemplateOverride = Craft::parseEnv($settings->adminTemplateOverride);
            $overridePath = $originalPath.DIRECTORY_SEPARATOR.$adminTemplateOverride;
            foreach ($extensions as $extension) {
                if (file_exists($overridePath.$extension)){
                    $templateOverride = $adminTemplateOverride;
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
        // Get emails without blank spaces
        $emails = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $settings->adminNotificationRecipients);
        $message->setTo($emails);

        return $message;
    }
}
