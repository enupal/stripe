<?php
/**
 * Stripe Payments plugin for Craft CMS 3.x
 *
 * @link      https://enupal.com/
 * @copyright Copyright (c) 2018 Enupal LLC
 */

namespace enupal\stripe\services;

use Craft;
use enupal\stripe\models\Country;
use enupal\stripe\records\Country as CountryRecord;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use yii\base\Component;
use yii\base\Exception;

class Countries extends Component
{
    /**
     * @var bool
     */
    private $_fetchedAllCountries = false;

    /**
     * @var Country[]
     */
    private $_countriesById = [];

    /**
     * Returns a country by its ID.
     *
     * @param int $id the country's ID
     * @return Country|null
     */
    public function getCountryById(int $id)
    {
        if (isset($this->_countriesById[$id])) {
            return $this->_countriesById[$id];
        }

        if ($this->_fetchedAllCountries) {
            return null;
        }

        $result = $this->_createCountryQuery()
            ->where(['id' => $id])
            ->one();

        if (!$result) {
            return null;
        }

        return $this->_countriesById[$id] = new Country($result);
    }

    /**
     * Returns a country by its ISO code.
     *
     * @param string $iso the country's ISO code
     * @return Country|null
     */
    public function getCountryByIso(string $iso)
    {
        $result = $this->_createCountryQuery()
            ->where(['iso' => $iso])
            ->one();

        return $result ? new Country($result) : null;
    }

    /**
     * Returns all country names, indexed by ISO.
     *
     * @return array
     */
    public function getAllCountriesAsList(): array
    {
        $countries = $this->getAllCountries();

        return ArrayHelper::map($countries, 'iso', 'name');
    }

    /**
     * Returns an array of all countries.
     *
     * @return Country[] An array of all countries.
     */
    public function getAllCountries(): array
    {
        if (!$this->_fetchedAllCountries) {
            $this->_fetchedAllCountries = true;
            $results = $this->_createCountryQuery()->all();

            foreach ($results as $row) {
                $this->_countriesById[$row['id']] = new Country($row);
            }
        }

        return $this->_countriesById;
    }

    /**
     * Saves a country.
     *
     * @param Country $country The country to be saved.
     * @param bool $runValidation should we validate this country before saving.
     * @return bool Whether the country was saved successfully.
     * @throws Exception if the country does not exist.
     */
    public function saveCountry(Country $country, bool $runValidation = true): bool
    {
        if ($country->id) {
            $record = CountryRecord::findOne($country->id);

            if (!$record) {
                throw new Exception(Craft::t('enupal-stripe', 'No country exists with the ID “{id}”', ['id' => $country->id]));
            }
        } else {
            $record = new CountryRecord();
        }

        if ($runValidation && !$country->validate()) {
            Craft::info('Country not saved due to validation error.', __METHOD__);

            return false;
        }

        $record->name = $country->name;
        $record->iso = strtoupper($country->iso);
        $record->isStateRequired = $country->isStateRequired;

        $record->save(false);

        $country->id = $record->id;

        return true;
    }

    /**
     * Deletes a country by its ID.
     *
     * @param int $id the country's ID
     * @return bool whether the country was deleted successfully
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function deleteCountryById(int $id): bool
    {
        $record = CountryRecord::findOne($id);

        if ($record) {
            return (bool)$record->delete();
        }

        return false;
    }

    /**
     * Returns a Query object prepped for retrieving Countries.
     *
     * @return Query The query object.
     */
    private function _createCountryQuery(): Query
    {
        return (new Query())
            ->select([
                'countries.id',
                'countries.name',
                'countries.iso',
                'countries.isStateRequired'
            ])
            ->from(['{{%enupalstripe_countries}} countries'])
            ->orderBy(['name' => SORT_ASC]);
    }
}
