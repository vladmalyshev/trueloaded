<?php
/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace common\api\models\Soap;


use common\api\models\Soap\Store\ArrayOfGeoZones;
use common\api\models\Soap\Store\ArrayOfTaxClasses;
use common\api\models\Soap\Store\TaxClass;
use common\api\models\Soap\Store\TaxRate;
use common\models\TaxRates;
use common\models\TaxZones;

class GetTaxClassesResponse extends SoapModel
{

    /**
     * @var \common\api\models\Soap\Store\ArrayOfTaxClasses
     * @soap
     */
    public $taxClasses;

    public function build()
    {
        $this->taxClasses = new ArrayOfTaxClasses();

        $taxClasses = \common\models\TaxClass::find()->all();
        foreach ( $taxClasses as $taxClass ) {
            $taxClassData = $taxClass->getAttributes();

            $taxClassData['tax_rates'] = [
                'tax_rate' => []
            ];
            $taxRates = TaxRates::findAll(['tax_class_id'=>$taxClass->tax_class_id]);
            foreach ($taxRates as $taxRate) {
                $taxRateData = $taxRate->getAttributes();
                $taxRateData['zones'] = ArrayOfGeoZones::populateFrom(TaxZones::find()->where(['geo_zone_id'=>$taxRate->tax_zone_id]));
                $taxClassData['tax_rates']['tax_rate'][] = new TaxRate($taxRateData);
            }

            $this->taxClasses->tax_class[] = new TaxClass($taxClassData);
        }

        parent::build();
    }

}