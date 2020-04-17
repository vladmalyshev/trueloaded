<?php

/**
 * This file is part of True Loaded.
 *
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\models\EP\Datasource;

use backend\models\EP\DatasourceBase;

class PdfCatalogues extends DatasourceBase
{

    public function getName()
    {
        return BOX_CATALOG_PDF_CATALOGUES;
    }

    public function prepareConfigForView($configArray)
    {   
        return parent::prepareConfigForView($configArray);
    }

    public function getViewTemplate()
    {
        return '';//'datasource/.tpl';
    }

}
