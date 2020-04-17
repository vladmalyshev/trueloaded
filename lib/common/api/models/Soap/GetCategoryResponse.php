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

use common\api\models\Soap\Categories\Category;
use common\api\SoapServer\SoapHelper;

class GetCategoryResponse extends SoapModel
{

    /**
     * @var string
     * @soap
     */
    public $status = 'OK';

    /**
     * @var \common\api\models\Soap\ArrayOfMessages Array of Messages {nillable = 0, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $messages = [];

    /**
     * @var \common\api\models\Soap\Categories\Category Category {nillable = 1, minOccurs=1, maxOccurs = 1}
     * @soap
     */
    public $category;

    public $departmentId = 0;

    protected $categoryId = 0;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    public function build()
    {

        if ( SoapHelper::hasCategory($this->categoryId) ) {
            $categoryData = [];
            $category = \common\api\models\AR\Categories::findOne(['categories_id' => $this->categoryId]);
            if ($category) {
                $categoryData = $category->exportArray([]);
                $this->category = new Category($categoryData);
            }
        }
        if ( !is_object($this->category) ) {
            $this->error('Category not found','ERROR_CATEGORY_NOT_FOUND');
            $this->status = 'ERROR';
        }

        parent::build();
    }
}