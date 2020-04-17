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


use yii\data\Pagination;
use yii\db\ActiveQuery;

class Paging extends SoapModel
{

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $page = 1;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $totalPages = 0;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $perPage;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $totalRows = 0;

    /**
     * @var integer {minOccurs=0}
     * @soap
     */
    public $rowsOnPage = 0;

    public $maxPerPage = 200;

    /**
     * @param int $maxPerPage
     */
    public function setMaxPerPage($maxPerPage)
    {
        $this->maxPerPage = $maxPerPage;
    }

    public function setFoundRows($rowOnPage, $totalRows)
    {
        $this->rowsOnPage = $rowOnPage;
        $this->totalRows = $totalRows;
        $this->totalPages = ceil($totalRows/$this->getPerPage());
        $this->perPage = $this->getPerPage();
    }

    public function getPageOffset()
    {
        if ( $this->page<=1 ){
            return 0;
        }else{
            return ($this->page-1)*$this->getPerPage();
        }
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        if ( empty($this->perPage) ) $this->perPage = $this->maxPerPage;
        return ($this->maxPerPage>$this->perPage?max(1,$this->perPage):$this->maxPerPage);
    }

    public function applyActiveQuery(ActiveQuery $query)
    {
        $countQuery = clone $query;

        $pages = new Pagination([
            'totalCount' => $countQuery->count(),
            'pageSize' => $this->getPerPage(),
            'defaultPageSize' => $this->getPerPage(),
        ]);
        $pages->setPage(max(0, $this->page-1),true);

        $query->offset($pages->offset)->limit($pages->limit);

        $this->totalPages = $pages->getPageCount();
        $this->totalRows = $pages->totalCount;

        $this->page = $pages->getPage()+1;

        $this->rowsOnPage = 0;
        if ($pages->getPage()+1 == $pages->getPageCount()){
            if ( $this->getPerPage()!=0 ) {
                $this->rowsOnPage = $pages->totalCount % $this->getPerPage();
            }
        }else{
            if ($pages->totalCount>$this->getPerPage()){
                $this->rowsOnPage = $this->getPerPage();
            }else{
                $this->rowsOnPage = $pages->totalCount;
            }
        }
    }

}