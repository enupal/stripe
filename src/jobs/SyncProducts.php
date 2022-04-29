<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\jobs;


use enupal\stripe\Stripe as StripePlugin;
use craft\queue\BaseJob;

use Stripe\Product;
use yii\queue\RetryableJobInterface;
use Craft;

/**
 * Sync products and prices job
 */
class SyncProducts extends BaseJob implements RetryableJobInterface
{
    /**
     * Returns the default description for this job.
     *
     * @return string
     */
    protected function defaultDescription(): string
    {
        return StripePlugin::t('Syncing Stripe Products and Prices');
    }

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        StripePlugin::$app->settings->initializeStripe();
        $stripeProducts = Product::all();

        $step = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($stripeProducts->autoPagingIterator() as $stripeProduct) {
            $step++;
            $isSyncProduct = $stripeProduct['metadata']['enupal_sync'] ?? $stripeProduct['metadata']['enupal-sync'] ?? false;
            if (!$isSyncProduct) {
                $skipped++;
                continue;
            }

            try {
                $product = StripePlugin::$app->products->createOrUpdateProduct($stripeProduct);
                if (!is_null($product)) {
                    StripePlugin::$app->prices->syncPricesFromProduct($product);
                }
            }catch (\Exception $e) {
                $failed++;
                Craft::error('Unable to sync Order: ' . $e->getMessage(), __METHOD__);
            }
        }

        Craft::info('Product Sync process finished, Total: '.$step. ', Skipped: '.$skipped.', Failed: '.$failed, __METHOD__);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getTtr()
    {
        return 3600;
    }

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error)
    {
        return ($attempt < 5) && ($error instanceof \Exception);
    }
}