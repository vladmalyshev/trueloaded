<?php
/**
 * This file is part of True Loaded.
 * 
 * @link http://www.holbi.co.uk
 * @copyright Copyright (c) 2005 Holbi Group LTD
 * 
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

namespace backend\controllers;

use Yii;
use backend\models\Recovery;
define('BASE_MINUTES', 20);

class Recover_cart_salesController extends Sceleton {
    
  public $acl = ['BOX_HEADING_MARKETING_TOOLS', 'BOX_TOOLS_RECOVER_CART'];
    public $tdate = RCS_BASE_DAYS;
    public $sdate = RCS_SKIP_DAYS;
    public $exact_date;
    public $type_list;
    public $grand_total = 0;
    public $grand_tax = 0;
    public $grand_total_minus = 0;
    public $opened_count = RCS_OPENED_COUNT;
    public $filters;
    public $platform_id;
    public $_search;
    private $rows = null;

    public function actionIndex() {
        $this->selectedMenu = array('marketing', 'recover_cart_sales');
        $this->navigation[] = array('link' => Yii::$app->urlManager->createUrl('recover_cart_sales/index'), 'title' => HEADING_TITLE);
        $this->view->headingTitle = HEADING_TITLE;
        
        $platforms = \common\classes\platform::getList(false);

        $this->view->recoveryTable = array(
            array(
                'title' => '',
                'not_important' => 0
            ),		  
        );
		
        if ($ext = \common\helpers\Acl::checkExtension('RecoverShoppingCart', 'adminCart')) {
            return $ext::adminCart();
        }
        return $this->render('index');
    }


  function seadate($day)
  {
    $rawtime = strtotime("-" . $day . " days");
    $ndate = date("Ymd", $rawtime);
    return $ndate;
  }

  function cart_date_short($raw_date) {
    if ( ($raw_date == '00000000') || ($raw_date == '') ) return false;

    $year = substr($raw_date, 0, 4);
    $month = (int)substr($raw_date, 4, 2);
    $day = (int)substr($raw_date, 6, 2);

    if (@date('Y', mktime(0, 0, 0, $month, $day, $year)) == $year) {
      return date(DATE_FORMAT, mktime(0, 0, 0, $month, $day, $year));
    } else {
      return preg_replace('/2037' . '$/', $year, date(DATE_FORMAT, mktime(0, 0, 0, $month, $day, 2037)));
    }
  }

  // This will return a list of customers with sessions. Handles either the mysql or file case
  // Returns an empty array if the check sessions flag is not true (empty array means same SQL statement can be used)
  function _GetCustomerSessions()
  {
    $cust_ses_ids = array();

    if( RCS_CHECK_SESSIONS == 'true' )
    {
      if (STORE_SESSIONS == 'mysql')
      {
        // --- DB RECORDS --- 
        $sesquery = tep_db_query("select value from " . TABLE_SESSIONS . " where 1");
        while ($ses = tep_db_fetch_array($sesquery))
        {
          if ( preg_match("/customer_id[^\"]*\"([0-9]*)\"/", $ses['value'], $custval ) )
            $cust_ses_ids[] = $custval[1];
        }
      }
      else  // --- FILES ---
      {
        if( $handle = opendir( tep_session_save_path() ) )
        {
          while (false !== ($file = readdir( $handle )) )
          {
            if ($file != "." && $file != "..")
            {
              $file = tep_session_save_path() . '/' . $file;  // create full path to file!
              if( $fp = fopen( $file, 'r' ) )
              {
                $val = fread( $fp, filesize( $file ) );
                fclose( $fp );

                if ( preg_match("/customer_id[^\"]*\"([0-9]*)\"/", $val, $custval ) )
                  $cust_ses_ids[] = $custval[1];
              }
            }
          }
          closedir( $handle );
        }
      }
    }
    return $cust_ses_ids;
  }	
  
  
  public function actionList(){
        $languages_id = \Yii::$app->settings->get('languages_id');
	$currencies = Yii::$container->get('currencies');
    
    $draw = Yii::$app->request->get('draw', 1);
    $start = Yii::$app->request->get('start', 0);
    $length = Yii::$app->request->get('length');
    if( $length == -1 ) $length = 10000;
    
    $this->type_list = Yii::$app->request->get('type_list', 0);;
    
    $this->sdate = Yii::$app->request->get('sdate', $this->sdate);
    $this->tdate = Yii::$app->request->get('tdate', $this->tdate);
    
    $this->platform_id = Yii::$app->request->get('platform_id', []);	 
    $used_search = false;
    
    $filter = Yii::$app->request->get('filter', '');
    $_search = '';
    $this->_search = [];
    if (tep_not_null($filter)){
      $filter = parse_str($filter, $output);
      if (isset($output['tdate']) && tep_not_null($output['tdate'])){
        $this->tdate = $output['tdate'];
      }
      if (isset($output['sdate']) && tep_not_null($output['sdate'])){
        $this->sdate = $output['sdate'];
      }
      if (isset($output['type_list']) && $output['type_list'] > 0){
        $this->type_list = $output['type_list'];
        $used_search = true;
      }
      if (isset($output['platform_id'])){
        $this->platform_id = $output['platform_id'];
      }
      if (isset($output['by']) && isset($output['search']) && tep_not_null($output['search'])){
        $keywords = tep_db_prepare_input($output['search']);
        switch ($output['by']){
          case 'product':
            $this->_search['product'] = $keywords;
            $this->_search['customer'] = false;            
            break;
          case 'name':
            $_search .= " and (cus.customers_firstname like '%" . tep_db_input($keywords) . "%' or cus.customers_lastname like '%" . tep_db_input($keywords) . "%' or cus.customers_telephone like '%" . tep_db_input($keywords) . "%' or cus.customers_fax like '%" . tep_db_input($keywords) . "%' or cus.customers_email_address like '%" . tep_db_input($keywords) . "%') ";
            $this->_search['customer_search'] = $keywords;
            $this->_search['customer'] = true;
            break;
          default:
           // $_search .= " and (cus.customers_firstname like '%" . tep_db_input($keywords) . "%' or cus.customers_lastname like '%" . tep_db_input($keywords) . "%' or cus.customers_telephone like '%" . tep_db_input($keywords) . "%' or cus.customers_fax like '%" . tep_db_input($keywords) . "%' or cus.customers_email_address like '%" . tep_db_input($keywords) . "%') ";
            $this->_search['customer'] = false;
            $this->_search['customer_search'] = $keywords;
            $this->_search['product'] = $keywords;
            break;
        }
        $used_search = true;
      }
    }  
	 
    \common\helpers\Translation::init('admin/recover_cart_sales');
	    	 
	  $cust_ses_ids = $this->_GetCustomerSessions();
	 
    if (isset($_GET['search']['value']) && tep_not_null($_GET['search']['value'])) {
      $keywords = tep_db_prepare_input($_GET['search']['value']);
      $_search .= " and (cus.customers_firstname like '%" . tep_db_input($keywords) . "%' or cus.customers_lastname like '%" . tep_db_input($keywords) . "%')";
      $this->_search['customer'] = true;
      $used_search = true;
    } 

     $orderBy = "ci.time_long desc, cb.customers_id";
   
     $customers_query_raw = $customers_query_raw1 = "select cb.customers_id cid,
                    cb.products_id pid,
                    cb.customers_basket_quantity qty,
                    cb.customers_basket_date_added bdate,
                    cus.customers_firstname fname,
                    cus.customers_lastname lname,
                    cus.customers_telephone phone,
                    cus.customers_fax fax,
                    cus.customers_email_address email,
                    cb.basket_id, cb.platform_id, 
                    cus.customers_gender, ci.time_long,
                    datediff( now(), ci.time_long) as daysago, cb.language_id
                    from   " . TABLE_CUSTOMERS_BASKET . " cb,
                    " . TABLE_CUSTOMERS . " cus left join " . TABLE_CUSTOMERS_INFO. " ci on ci.customers_info_id = cus.customers_id
                    where /*cb.customers_basket_date_added >= '" . $this->seadate($this->sdate) . "' and
                     cb.customers_basket_date_added <= '" . $this->seadate($this->tdate) . "' and*/
                     date_format(ci.time_long, '%Y%m%d') >= '" . $this->seadate($this->sdate) . "' and
                     date_format(ci.time_long, '%Y%m%d') <= '" . $this->seadate($this->tdate) . "' and " .
                    (is_array($this->platform_id) && count($this->platform_id) ? " cb.platform_id in (" . implode(',', $this->platform_id) . ") and " : "")
                    . " cus.customers_id not in ('" . implode("', '", $cust_ses_ids) . "') and
                    cb.customers_id = cus.customers_id " . $_search . " group by cb.customers_id order by ". $orderBy;

                    //in => NOT in 

    $current_page_number = ($start / $length) + 1;
    $customers_split = new \splitPageResults($current_page_number, $length, $customers_query_raw, $customers_query_numrows, 'cb.customers_id');
    $query1 = tep_db_query(($used_search ? $customers_query_raw1 : $customers_query_raw));
  
    $this->rows = [];
     $results = 0;
     $curcus = "";
     $first_line = true;
     $skip = false;
     $responseList = array();	 
     
     $products = array();
     
     $knt = tep_db_num_rows($query1);    

     $opened = 0;
     $already_found = 0;
	 $ids = [];
     for ($i = 0; $i <= $knt; $i++)
     {

       $inrec = tep_db_fetch_array($query1);
       
       $inrec['already_found'] = false;
      // If this is a new customer, create the appropriate HTML
      if ($curcus != $inrec['cid'])
      {
        // set new cline and curcus
        $curcus = $inrec['cid'];        
		$ids[] = $curcus;

        if ($curcus != "") {
          $tprice = 0;
        
          // change the color on those we have contacted add customer tag to customers
          $checked = 1;  // assume we'll send an email
          $new = 1;
          $skip = false;
          $sentdate = "";
          $beforeDate = RCS_CARTS_MATCH_ALL_DATES == 'true' ? '0' : preg_replace("/([\d]{4})([\d]{2})([\d]{2})/", "$1-$2-$3", $inrec['bdate']);

          $status = "";
          $inrec['status'] = "";
          $inrec['contact_status'] = false;
          $inrec['worked_out'] = false;
          
          $donequery = tep_db_query("select * from " . TABLE_SCART . " where customers_id = '" . (int)$curcus . "' and basket_id = '" . (int)$inrec['basket_id'] . "'");
          $emailttl = $this->seadate(RCS_EMAIL_TTL);

          if (tep_db_num_rows($donequery) > 0) {
            $ttl = tep_db_fetch_array($donequery);
            if( $ttl ) {
              if( tep_not_null($ttl['datemodified']) )  // allow for older scarts that have no datemodified field data
              $ttldate = $ttl['datemodified'];
              else
              $ttldate = $ttl['dateadded'];

              if ($emailttl <= $ttldate) {
              $sentdate = $ttldate;
              $checked = 0;
              $new = 0;
              }
              
              if ($ttl['contacted']){
                $inrec['contact_status'] = true;
              }
              if ($ttl['workedout']){
                $inrec['worked_out'] = true;
              }
            }
          }

          if ($this->type_list){
            if ( ($this->type_list == 1 && tep_db_num_rows($donequery) == 0) || ($this->type_list == 2 && tep_db_num_rows($donequery) > 0) || ($this->type_list == 3 && (tep_db_num_rows($donequery) == 0 || (isset($ttl['workedout']) && !$ttl['workedout']))) ) {
              continue;
              if ($opened) 
                $opened--;
              else 
                $opened = 0;
            }
          }        
          
          // See if the customer has purchased from us before
          // Customers are identified by either their customer ID or name or email address
          // If the customer has an order with items that match the current order, assume order completed, bail on this entry!
          $ccquery = tep_db_query('select orders_id, orders_status from ' . TABLE_ORDERS . ' where (customers_id = ' . (int)$curcus . ' OR customers_email_address like "' . $inrec['email'] .'" or customers_name like "' . $inrec['fname'] . ' ' . $inrec['lname'] . '") and date_purchased >= "' . $beforeDate . '" - interval 20 day' );

          if (tep_db_num_rows($ccquery) > 0)
          {
          // We have a matching order; assume current customer but not for this order	  
          // Now, look to see if one of the orders matches this current order's items
            while( $orec = tep_db_fetch_array( $ccquery ) ) {
              $ccquery = tep_db_query( 'select products_id from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = ' . (int)$orec['orders_id'] . ' AND products_id = ' . (int)$inrec['pid'] );

              if( tep_db_num_rows( $ccquery ) > 0 )
              {
                if( $orec['orders_status'] > RCS_PENDING_SALE_STATUS ) $checked = 0;
            
                // OK, we have a matching order; see if we should just skip this or show the status
                if( RCS_SKIP_MATCHED_CARTS == 'true' && !$checked ) {
                  $skip = true;  // reset flag & break us out of the while loop!
                  break;
                } else {
                  // It's rare for the same customer to order the same item twice, so we probably have a matching order, show it
                  $ccquery = tep_db_query("select orders_status_name from " . TABLE_ORDERS_STATUS . " where language_id = " . (int)$languages_id . " AND orders_status_id = " . (int)$orec['orders_status'] );
              
                  if( $srec = tep_db_fetch_array( $ccquery ) )
                    $status = ' [<a href="' . \yii\helpers\Url::to(['orders/process-order', 'orders_id' => $orec['orders_id']]) . '" target="_blank">' . $srec['orders_status_name'] . '</a>] <div class="ord-location"><div class="ord-total-info">' . TEXT_PROBABLY_BOUGHT . '</div></div>';
                  else
                    $status = ' ['. TEXT_CURRENT_CUSTOMER . ']';
                  
                  $inrec['status'] = $status;
                }
              }
            }
          
          //  if( $skip ) continue;  // got a matched cart, skip to next one
          }

          $sentInfo = TEXT_NOT_CONTACTED;          

          if ($sentdate != '') $sentInfo = TEXT_CONTACTED;//$this->cart_date_short($sentdate);
          
          $class_contacted = $sentInfo;
          
          $inrec['changed_email'] = $inrec['email'];
          if (isset($this->_search['customer_search'])){
            $found = false;
            $inrec['found'] = $found;
            $inrec['changed_email'] = $this->hilite_function($this->_search['customer_search'],  $inrec['email'], $found);
            $inrec['found'] = $inrec['found'] || $found;
            $inrec['fname'] = $this->hilite_function($this->_search['customer_search'],  $inrec['fname'], $found);
            $inrec['found'] = $inrec['found'] || $found;
            $inrec['lname'] = $this->hilite_function($this->_search['customer_search'],  $inrec['lname'], $found);
            $inrec['found'] = $inrec['found'] || $found;
          }         
          
          $inrec['expanded'] = ($opened < $this->opened_count || $used_search);
    
          $params = ['sentInfo' => strtoupper($sentInfo), 'inrec' => $inrec, 'class_contacted' => strtolower($class_contacted) ];
    
          if (!array_key_exists($curcus, $this->rows)){
            $this->rows[$curcus] = $params;
          }
          
          if (tep_not_null($inrec['status']) && !tep_not_null($this->rows[$curcus]['order_status']))  $this->rows[$curcus]['order_status'] = $inrec['status'];
          $_sent_list = '';

        }
        $opened++;
      }
      
    }
    
    $this->grand_total = 0;
	
	if (is_array($ids) && count($ids)){
		$pos_from = strpos($customers_query_raw1, ' from', 0);
		$str = "select sum(cb.final_price*cb.customers_basket_quantity) as total " . substr($customers_query_raw1, $pos_from);
		if (strpos($str, ' group')!== false){
			$str = substr($str, 0, strpos($str, ' group'));
		}
		$total = tep_db_fetch_array(tep_db_query($str));
		$this->grand_total = (float)$total['total'];
		
	}
    if (is_array($this->rows)){
      
      $prepared = [];
     // echo 'start render:' . time(). ' ';
      foreach($this->rows as $_c => $params){
        if (is_array($params)){

          $params['sentInfo'] .= $params['order_status'];
//          echo ' ' .time(). '-';
          $params['details'] = $this->getDetails($params['inrec']);          
//          echo time(). ' ';
          
          if ($used_search && $this->rows[$_c]['inrec']['found']){
            if ($ext = \common\helpers\Acl::checkExtension('RecoverShoppingCart', 'adminContacts')) {
                $response = array(
                    $ext::adminContacts($params),
                );
            } else {
                $response = array(
                  $this->renderAjax('contacts', $params),
                );
            }
            
            $prepared[] = $response;
            //$this->grand_total += $this->rows[$_c]['inrec']['total'];
          }
          if (!$used_search){
            if ($ext = \common\helpers\Acl::checkExtension('RecoverShoppingCart', 'adminContacts')) {
                $response = array(
                    $ext::adminContacts($params),
                );
            } else {
                $response = array(
                  $this->renderAjax('contacts', $params),
                );
            }
            
            $prepared[] = $response;
            //$this->grand_total += $this->rows[$_c]['inrec']['total'];
            $start = 0;
          }
        }
      }
   //   echo 'end render:' . time(). ' ';
      unset($response);
      if (count($prepared)){
        for($i=$start;$i<$start+$length;$i++){
          if (isset($prepared[$i])){
            $responseList[] = $prepared[$i];
          }          
        }
      }   
		if( RCS_INCLUDE_TAX_IN_PRICES  == 'true' )  {
			$this->grand_total += ($this->grand_total * $this->grand_tax / 100);			
		}
	  
    
    }   

    $response = array(
            'draw' => $draw,
            'recordsTotal' => ($used_search? count($prepared) : $customers_query_numrows),//count($responseList),
            'recordsFiltered' => ($used_search? count($prepared) : $customers_query_numrows),//count($responseList),
            'data' => $responseList,
            'e'=> $this->rows,
			'tax' => $this->grand_tax,
            'grandTotal' => $currencies->format($this->grand_total),
          );
    echo json_encode($response);	
  }
  
  public function hilite_function($search, $text, &$replaced = false) {
    $w = preg_quote(trim($search),'/');
    $regexp = "/($w)(?![^<]+>)/i";
    $replacement = '<b style="color:#ff0000">\\1</b>';
    $new = preg_replace($regexp, $replacement, $text);
    if($new != $text) $replaced = true;
    return $new;
  }
  public function renderDetailsAjax($details){
        $this->view->detailTable = array(
            array(
                'title' => '',
                'not_important' => 0,
                'width' => '1%',
            ),		
            array(
                'title' => TABLE_HEADING_CONTACT,
                'not_important' => 0,
                'width' => '10%',
            ),
            array(
                'title' => TABLE_HEADING_DATE,
                'not_important' => 0,
                'width' => '11%',
            ),
            array(
                  'title' => TABLE_HEADING_PLATFORM,
                  'not_important' => 0,
                  'width' => '11%',
                  ),
            array(
                  'title' => TABLE_HEADING_QUANTY,
                  'not_important' => 0,
                  'width' => '5%',
                  ),
            array(
                  'title' => TABLE_HEADING_PRODUCTS,
                  'not_important' => 0,
                  'width' => '30%',
                  ),
            array(
                  'title' => TABLE_HEADING_MODEL,
                  'not_important' => 0,
                  'width' => '10%',
                  ),
            array(
                  'title' => TABLE_HEADING_PRICE,
                  'not_important' => 0,
                  'width' => '10%',
                  ),
            array(
                  'title' => TABLE_HEADING_TOTAL,
                  'not_important' => 0,
                  'width' => '10%',
                  ),
            array(
                  'title' => '',
                  'not_important' => 0,
                  'width' => '3%',
                  ),		  
        );    
        
      if ($ext = \common\helpers\Acl::checkExtension('RecoverShoppingCart', 'adminDetails')) {
          return $ext::adminDetails($details);
      }
      return $this->renderAjax('details',['details' => $details]);        
  }
  
  public function getDetails(&$inrec){
    $languages_id = \Yii::$app->settings->get('languages_id');
    $curcus = $inrec['cid'];
    
    \common\helpers\Translation::init('admin/recover_cart_sales');
    $currencies = Yii::$container->get('currencies');

        $details = [];
        $details['status'] = '';
        $contacted_class = 'btn-defualt';
        $disabled = '';
        if ($inrec['contact_status']){
          $contacted_class = 'btn-cancel';
          $disabled = 'disabled';
        }
        $details['status'] = '<a onClick="contacted('.$inrec['cid'].', ' . $inrec['basket_id'] . ')" href="javascript:void(0);' . '" class="btn ' . $contacted_class . ' btn-contacted" ' . $disabled . '>' . TEXT_AS_CONTACTED . '</a>';
        $contacted_class = 'btn-defualt';
        $disabled = '';
        if ($inrec['worked_out']){
          $contacted_class = 'btn-cancel';
          $disabled = 'disabled';          
        }
        $details['status'] .= '<a onClick="workedout('.$inrec['cid'].', ' . $inrec['basket_id'] . ')" href="javascript:void(0);' . '" class="btn ' . $contacted_class . ' btn-workedout" ' . $disabled . '>' . TEXT_AS_WORKEDOUT . '</a>';
        
        $details['date'] = '<div class="date-box"><b>' . strftime(DATE_TIME_FORMAT, strtotime($inrec['time_long'])) . '</b><br>'. ($inrec['daysago'] > 0? $inrec['daysago'] . '&nbsp;' . DAYS_FIELD_POSTFIX . '&nbsp;' . TEXT_AGO_COMMON : TEXT_TODAY) . '</div>'. (($ip = Recovery::is_online($curcus)) !== false ? '<div class="status-line on-line">' . TEXT_ONLINE . '</div>IP:' . $ip : '<div class="status-line off-line">' . TEXT_OFFLINE . '</div>');
        $details['platform'] = \common\classes\platform::name($inrec['platform_id']);
        $details['platform_id'] = $inrec['platform_id'];
        
        // We only have something to do for the product if the quantity selected was not zero!
        if ($inrec['qty'] != 0){
          $details['products'] = [];
          $ii = 0;
          $tprice = 0;
          //$currency_id = $currencies->currencies[$inrec['currency']]['id'];
          //$customer_groups_id = \common\helpers\Customer::get_customers_group($inrec['cid']);
          $bInfo_query = tep_db_query("select cb.products_id, cb.customers_basket_quantity as qty, cb.is_giveaway, cb.final_price, cb.platform_id from " . TABLE_CUSTOMERS_BASKET . " cb where cb.customers_id = '" . (int)$inrec['cid'] . "'");
          if (tep_db_num_rows($bInfo_query)){
            while ($bInfo = tep_db_fetch_array($bInfo_query) ){
              $query2 = tep_db_query("select p.products_id, p.products_price price,
                        p.products_tax_class_id taxclass,
                        p.products_model model,
                        pd.products_name name
                        from    " . TABLE_PRODUCTS . " p,
                        " . TABLE_PRODUCTS_DESCRIPTION . " pd
                        where p.products_id = '" . (int)$bInfo['products_id'] . "' and
                        pd.products_id = p.products_id and
                        pd.platform_id = '".intval($bInfo['platform_id'])."' and 
                        pd.language_id = " . (int)$languages_id);
              $inrec2 = tep_db_fetch_array($query2);
              
              if ($inrec2){
                // Check to see if the product is on special, and if so use that pricing
                $ProductsTax = \common\helpers\Tax::get_tax_rate($inrec2['taxclass']);//get default
				if ($ProductsTax) $this->grand_tax = $ProductsTax;
				$sprice = $bInfo['final_price'];
                /*if ($sprice = \common\helpers\Product::get_products_special_price_edit_order($inrec2['products_id'], $currency_id, $customer_groups_id)){
                  $sprice = $sprice;
                }else{
                  $sprice = \common\helpers\Product::get_products_price_edit_order($inrec2['products_id'], $currency_id, $customer_groups_id, $bInfo['qty'], true);
                }*/
                $sprice = (float)$sprice;
                // Some users may want to include taxes in the pricing, allow that. NOTE HOWEVER that we don't have a good way to get individual tax rates based on customer location yet!
                if( RCS_INCLUDE_TAX_IN_PRICES  == 'true' )
                  $sprice += ($sprice * \common\helpers\Tax::get_tax_rate( $inrec2['taxclass'] ) / 100);
                else if( RCS_USE_FIXED_TAX_IN_PRICES  == 'true' && RCS_FIXED_TAX_RATE > 0 )
                  $sprice += ($sprice * RCS_FIXED_TAX_RATE / 100);                
                // BEGIN OF ATTRIBUTE DB CODE
                $prodAttribs = ''; // DO NOT DELETE

                if (RCS_SHOW_ATTRIBUTES == 'true') {
                  $attribquery = tep_db_query("select  cba.products_id pid,
                               po.products_options_name poname,
                               pov.products_options_values_name povname
                               from    " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " cba,
                               " . TABLE_PRODUCTS_OPTIONS . " po,
                               " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
                                 where   cba.products_id ='" . $bInfo['products_id'] . "' and
                              cba.customers_id = " . $curcus . " and
                              po.products_options_id = cba.products_options_id and
                               pov.products_options_values_id = cba.products_options_value_id and
                               po.language_id = " . (int)$languages_id . " and
                               pov.language_id = " . (int)$languages_id
                          );                        
                  $hasAttributes = false;

                  if (tep_db_num_rows($attribquery))
                  {
                    $hasAttributes = true;
                    $prodAttribs = '<br>';

                    while ($attribrecs = tep_db_fetch_array($attribquery))
                     $prodAttribs .= '<small><i> - ' . $attribrecs['poname'] . ' ' . $attribrecs['povname'] . '</i></small><br>';
                  }
                }
                // END OF ATTRIBUTE DB CODE
                
                $tprice = $tprice + ($bInfo['qty'] * $sprice);
                $pprice_formated  = $currencies->format($sprice);
                $tpprice_formated = $currencies->format(($bInfo['qty'] * $sprice));
                
              
                $details['products'][$ii]['qty'] = $bInfo['qty'];
                $details['found_here'] = false;
                $details['products'][$ii]['name'] = "<a href='" . tep_href_link('categories/productedit', 'read=only&pID=' . (int)$inrec['pid'] . '&origin=' . FILENAME_RECOVER_CART_SALES, 'NONSSL') . "' class='prod-name'>" . (is_array($this->_search) && isset($this->_search['product'])? $this->hilite_function($this->_search['product'], $inrec2['name'], $found_here) : $inrec2['name']) . "</a>" . $prodAttribs;
                // {{ stock indicator
                $_uprid = \common\helpers\Inventory::normalize_id($bInfo['products_id']);
                $_current_qty = 0;
                $_get_stock_r = tep_db_query(
                  "SELECT IFNULL(i.products_quantity,p.products_quantity) AS products_quantity ".
                  "FROM ".TABLE_PRODUCTS." p ".
                  " LEFT JOIN ".TABLE_INVENTORY." i ON i.prid=p.products_id AND i.products_id='".tep_db_input($_uprid)."' ".
                  "WHERE p.products_id='".(int)$_uprid."' "
                );
                if ( tep_db_num_rows($_get_stock_r)>0 ) {
                  $_get_stock = tep_db_fetch_array($_get_stock_r);
                  $_current_qty = $_get_stock['products_quantity'];
                }

                $_stock_info = \common\classes\StockIndication::product_info(array(
                  'products_id' => $_uprid,
                  'products_quantity' => $_current_qty-$bInfo['qty']+1
                ));
                $details['products'][$ii]['name'] .= '<div class="'.$_stock_info['text_stock_code']. ($_stock_info['allow_out_of_stock_checkout']?'':' stock_not_allow_checkout'). '" '.
                  'title="'.\common\helpers\Output::output_string(sprintf(TEXT_CURRENT_STOCK_QUANTITY,$_current_qty)).($_stock_info['allow_out_of_stock_checkout']?'':' - '.\common\helpers\Output::output_string(TEXT_CHECKOUT_NOT_ALLOWED)).'">'.
                  '<span class="'.$_stock_info['stock_code'].'">&nbsp;</span>' . 
                  $_stock_info['stock_indicator_text'].
                  '</div>';
                $details['products'][$ii]['name'] .= '<br>'.sprintf(TEXT_CURRENT_STOCK_QUANTITY,$_current_qty);
                // }} stock indicator
                $details['found_here'] = $details['found_here'] || $found_here;
                $details['products'][$ii]['model'] = (is_array($this->_search) && isset($this->_search['product'])? $this->hilite_function($this->_search['product'], $inrec2['model'], $found_here) : $inrec2['model']);
                $details['found_here'] = $details['found_here'] || $found_here;
                if (!is_array($this->_search) || !isset($this->_search['product']) || (isset($this->_search['customer']) && $this->_search['customer']) || $inrec['already_found']){
                  $details['found_here'] = true;
                }

                $details['products'][$ii]['price'] = $pprice_formated;
                $details['products'][$ii]['stotal'] = $tpprice_formated;
                
                $ii++;                
              }
            }
            
            if ($details['found_here']) {
              $this->rows[$curcus]['inrec']['found'] = true;
            }
          }
          $details['total'] = $currencies->format($tprice);
          $this->rows[$curcus]['inrec']['total'] = $tprice;
          $details['rows'] = $ii;
          $details['cid'] = $curcus;
          $details['bid'] = $inrec['basket_id'];
		  $details['batch'] = tep_draw_checkbox_field('batch','1',false,'', ' data-cid="'.$details['cid'].'" data-bid="'.$details['bid'].'" class="batchbox uniform"');
          $details['expanded'] = $inrec['expanded'];
        }
      return $details;

  }
  
  public function actionDelete(){
	  
   \common\helpers\Translation::init('admin/recover_cart_sales');
	 
	 $customer_id = Yii::$app->request->post('customer_id', 0);
   $basket_id = Yii::$app->request->post('basket_id', 0);

	 $response = array();
	 if ($customer_id){
		 $reset_query_raw = "delete from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$customer_id ."'"; 
		 tep_db_query($reset_query_raw); 

		 $reset_query_raw2 = "delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = '" . (int)$customer_id ."'"; 
		 tep_db_query($reset_query_raw2);
     
     if ($basket_id){
       tep_db_query("delete from " . TABLE_SCART . " where customers_id = '" . (int)$customer_id . "' and basket_id = '" . (int)$basket_id . "'");
     }
     
		 $response = array('message' => MESSAGE_STACK_CUSTOMER_ID . $customer_id . MESSAGE_STACK_DELETE_SUCCESS, 'messageType' => 'success');
	 } else {
		 $response = array('message' => MESSAGE_STACK_CUSTOMER_ID . $customer_id . MESSAGE_STACK_DELETE_ERROR, 'messageType' => 'error');
	 }

	
   echo json_encode($response);
   exit();
  }
  
  public function actionContacted(){
    $cid = Yii::$app->request->post('customer_id', 0);
    $bid = Yii::$app->request->post('basket_id', 0);
    $answer = [];
    if ($cid && $bid){
      $donequery = tep_db_query("select * from ". TABLE_SCART ." where customers_id = '" . (int)$cid . "' and basket_id= '" . (int)$bid . "'");
      if (tep_db_num_rows($donequery) == 0){
        tep_db_query("insert into " . TABLE_SCART . " (customers_id, basket_id, dateadded, datemodified, contacted ) values ('" . (int)$cid . "', '" . (int)$bid . "','" . $this->seadate('0') . "', '" . $this->seadate('0') . "' , 1)");
      } else {
        tep_db_query("update " . TABLE_SCART . " set datemodified = '" . $this->seadate('0') . "', contacted = 1 where customers_id = '" . (int)$cid . "' and basket_id= '" . (int)$bid . "'");	 
      }
      $answer['message'] = TEXT_MESSEAGE_SUCCESS;
    } else {
      $answer['message'] = TEXT_MESS_WRONG_DATA;
    }
    return json_encode($answer);
    exit();
  }
  
  public function actionWorkedout(){
    $cid = Yii::$app->request->post('customer_id', 0);
    $bid = Yii::$app->request->post('basket_id', 0);
    $answer = [];
    if ($cid && $bid){
      $donequery = tep_db_query("select * from ". TABLE_SCART ." where customers_id = '" . (int)$cid . "' and basket_id= '" . (int)$bid . "'");
      if (tep_db_num_rows($donequery) == 0){
        tep_db_query("insert into " . TABLE_SCART . " (customers_id, basket_id, dateadded, datemodified, contacted, workedout ) values ('" . (int)$cid . "', '" . (int)$bid . "','" . $this->seadate('0') . "', '" . $this->seadate('0') . "' , 1, 1)");
      } else {
        tep_db_query("update " . TABLE_SCART . " set datemodified = '" . $this->seadate('0') . "', workedout = 1, contacted = 1 where customers_id = '" . (int)$cid . "' and basket_id= '" . (int)$bid . "'");	 
      }
      $answer['message'] = TEXT_MESSEAGE_SUCCESS;
    } else {
      $answer['message'] = TEXT_MESS_WRONG_DATA;
    }
    return json_encode($answer);
    exit();
  }
  
  
  public function actionMail(){

        \common\helpers\Translation::init('admin/recover_cart_sales');
          
    if(Yii::$app->request->isGet || (Yii::$app->request->isPost && Yii::$app->request->post('batch'))){
	 
      $currencies = Yii::$container->get('currencies');
      
      $curcus = Yii::$app->request->get('cid', 0);
      $basket_id = Yii::$app->request->get('basket_id', 0);
      $type = Yii::$app->request->get('type', 'm');
	  $batch = Yii::$app->request->post('batch', 0);
      
      $currencies_list = '';
      
      if (($curcus && $basket_id) || $batch){
        $_cc = tep_db_fetch_array(tep_db_query("select currency, language_id from " . TABLE_CUSTOMERS_BASKET . " where customers_id = '" . (int)$curcus . "' and basket_id = '" . (int)$basket_id . "'"));
        if ($type == 'm'){ // just message
          //
        } else if ($type == 'c'){
          $platform_id = Yii::$app->request->get('platform_id', 0);
          $coupons = Recovery::getRecoveryCoupons();
          $coupon_list = '';
          if (is_array($coupons)){
            $_customer_coupons = Recovery::getCustomerEmailCouponsNotSended($curcus, $coupons, $basket_id);
            $_customer_coupons_sep = ['v' => [], 'c' => []];
            if (is_array($_customer_coupons) && count($_customer_coupons)){//echo '<pre>';print_r($_customer_coupons);
              $coupon_list = '';
              foreach($_customer_coupons as $_k => $_c){
                if ($_c['coupon_type'] == 'G'){//vouchers
                  $_customer_coupons_sep['v'][$_k] = $_c;
                  $_customer_coupons_sep['v'][$_k]['coupon_amount'] = $currencies->format($_c['coupon_amount'], false, $_c['coupon_currency']);
                } else { //coupons
                  $_customer_coupons_sep['c'][$_k] = $_c;
                  $_customer_coupons_sep['c'][$_k]['coupon_amount'] = ($_c['coupon_type'] == 'F' ? $currencies->format($_c['coupon_amount'], false, $_c['coupon_currency']) : ($_c['coupon_type'] == 'P' ? round($_c['coupon_amount'],2) . '%' : /*maybe for free shipping S*/'')) . ($_c['expired']? ' (' . TEXT_EXPIRED . ')' : "");
                }                
              }
              $__customer_coupons = $_customer_coupons_sep['c'];
              $expired = [];
              if (is_array($__customer_coupons)){
                foreach($__customer_coupons as $_k=>$v){
                  if ($v['expired']) {
                    $expired[$_k] = $__customer_coupons[$_k];
                    unset($__customer_coupons[$_k]);
                  }
                }        
              }
              
              if (count($__customer_coupons)){
                $fk = key($__customer_coupons);
                $fv = current($__customer_coupons);
                reset($__customer_coupons);
                $coupon_list .= TEXT_NOT_USED_COUPONS . \yii\helpers\Html::radioList('coupon[' . $curcus . ']', $fk, \yii\helpers\ArrayHelper::map($__customer_coupons, 'coupon_id', 'coupon_amount'), ['class' => 'coupon-list']);
                if (count($expired)){
                  $coupon_list .= \yii\helpers\Html::radioList('coupon_expired[' . $curcus . ']', '', \yii\helpers\ArrayHelper::map($expired, 'coupon_id', 'coupon_amount'), ['itemOptions' => ['disabled' => 'disabled']]);
                }                
              } else {
                $coupon_list .= TEXT_EXPIRED_COUPONS;
              }
              
            }
            //get sent coupns
            $_sent = array_diff_key($coupons, $_customer_coupons_sep['c']);
            $_sent_list = '';
            if (is_array($_sent) && count($_sent)){
              $_sent_list = TEXT_USED_COUPONS . '<br>';
              foreach($_sent as $_id => $_c){
                $_sent_list .= $_c['coupon_code'] . ' (' . ($_c['coupon_type'] == 'F' ? $currencies->format($_c['coupon_amount'], false, $_c['coupon_currency']) : ($_c['coupon_type'] == 'P' ? round($_c['coupon_amount'],2) . '%' : /*maybe for free shipping S*/'')) . "), ";
              }
            }          
            //get vouchers
            $sent_vouchers = '';
            $chec_query = tep_db_query("select cp.coupon_code, cp.coupon_amount, coupon_currency, coupon_active from " . TABLE_COUPON_EMAIL_TRACK . " et, " . TABLE_COUPONS . " cp where cp.coupon_id = et.coupon_id and et.customer_id_sent = '" . (int)$curcus . "' and basket_id = '" . (int)$basket_id . "' and cp.coupon_type='G'");
            if (tep_db_num_rows($chec_query)){
              $sent_vouchers .= "Customer has vouchers:<br>";
              while ($row = tep_db_fetch_array($chec_query)){
                $sent_vouchers .= $row['coupon_code'] . '(' . $currencies->format($row['coupon_amount'], false, $row['coupon_currency']) . ')' . ($row['coupon_active'] == 'N'? '&nbsp;' . TEXT_USED :'') . '<br>';
              }
            }            
          }       

          $platform_query = tep_db_fetch_array(tep_db_query("select defined_currencies from " . TABLE_PLATFORMS . " where platform_id = '" . (int)$platform_id . "'"));
          
          $_curr = 0;
          if ($platform_query){
            $_curr = explode(",", $platform_query['defined_currencies']);
          }
          if (!$_curr || !count($_curr)) $_curr = [DEFAULT_CURRENCY];
          $cpd = [];
          foreach($_curr as $__c){
            $cpd[]= ['id' => strtoupper($__c), 'text' => strtoupper($__c)];
          }
          $_customer_curr = DEFAULT_CURRENCY;
		  		  
          if ($_cc){
            $_customer_curr = $_cc['currency'];
            if (!in_array($_customer_curr, $_curr)) $_customer_curr = DEFAULT_CURRENCY;			
          }
          
          $currencies_list = tep_draw_pull_down_menu('coupon_currency', $cpd, $_customer_curr, 'class="form-control"');
        }
		global $lng;
		$his_language = '';
		$map = \yii\helpers\ArrayHelper::map($lng->catalog_languages, 'id', 'name');
		if ($_cc && isset($map[$_cc['language_id']])){
			$his_language = '(' . $map[$_cc['language_id']] . ')';
		}	

		if ($batch){
			$curcus = implode('-', Yii::$app->request->post('batch_cids'));
			$basket_id = implode('-', Yii::$app->request->post('batch_bids'));
		}
		
      }
      
      return $this->renderAjax('mail', ['coupon_list' => $coupon_list . $_sent_list , 'cid' => $curcus, 'bid' => $basket_id,  'type' => $type, 'currencies_list' => $currencies_list, 'sent_vouchers' => $sent_vouchers, 'his_language'=> $his_language]);
      
    } else {
      $data = Yii::$app->request->post('data');
      $data = parse_str($data, $output);      
      $bid = 0;
      if (isset($output['cid'])) $custid = explode("-", $output['cid']);
      if (isset($output['bid'])) $bid = explode("-", $output['bid']);
      $use_method = $output['use_method'];

      $currencies = Yii::$container->get('currencies');
      $admin = new \backend\models\Admin();
      
      $_platfomrs = [];
     
      if (is_array($custid) && count($custid)) {
        
        foreach ($custid as $ckey=>$cid) {
          unset($email);
          $url = '';
          $query1 = tep_db_query("select cb.products_id pid,
                          cb.customers_basket_quantity qty,
                          cb.customers_basket_date_added bdate,
                          cus.customers_firstname fname,
                          cus.customers_lastname lname,
                          cus.customers_email_address email,
                          ci.token, cb.basket_id, cb.platform_id, cb.currency, cb.language_id
                      from      " . TABLE_CUSTOMERS_BASKET . " cb,
                          " . TABLE_CUSTOMERS . " cus
                          left join " . TABLE_CUSTOMERS_INFO. " ci on ci.customers_info_id = cus.customers_id
                      where     cb.customers_id = cus.customers_id  and
                          cus.customers_id = '" . (int)$cid . "'
                      order by cb.customers_basket_date_added desc ");

          $knt = tep_db_num_rows($query1);
          $url = '';
          for ($i = 0; $i < $knt; $i++)
          {
            $inrec = tep_db_fetch_array($query1);
            
            if (!isset($_platfomrs[$cid])){
              $_platfomrs[$cid] = new \common\classes\platform_config($inrec['platform_id']);
            }
            if (!tep_not_null($url)) $url = $_platfomrs[$cid]->getCatalogBaseUrl(true);
            
            $query2 = tep_db_query("select   p.products_price price,
                    p.products_tax_class_id taxclass,
                    p.products_model model,
                          pd.products_name name
                      from " . TABLE_PRODUCTS . " p,
                          " . TABLE_PRODUCTS_DESCRIPTION . " pd
                      where p.products_id = '" . $inrec['pid'] . "' and
                          pd.products_id = p.products_id and
                          pd.language_id = " . (int)$inrec['language_id'] );

            $inrec2 = tep_db_fetch_array($query2);
            $sprice = \common\helpers\Product::get_products_special_price( $inrec['pid'] );
            if( $sprice < 1 )
              $sprice = $inrec2['price'];
            // Some users may want to include taxes in the pricing, allow that. NOTE HOWEVER that we don't have a good way to get individual tax rates based on customer location yet!
            if( RCS_INCLUDE_TAX_IN_PRICES  == 'true' )
              $sprice += ($sprice * \common\helpers\Tax::get_tax_rate( $inrec2['taxclass'] ) / 100);
            else if( RCS_USE_FIXED_TAX_IN_PRICES  == 'true' && RCS_FIXED_TAX_RATE > 0 )
              $sprice += ($sprice * RCS_FIXED_TAX_RATE / 100);

            $tprice = $tprice + ($inrec['qty'] * $sprice);
            $pprice_formated  = $currencies->format($sprice);
            $tpprice_formated = $currencies->format(($inrec['qty'] * $sprice));
            $image = '';
            if( EMAIL_USE_HTML == 'true' ){
              $image = \common\classes\Images::getImage($inrec['pid']);
              $mline .= '   <blockquote valign="middle">' . ($image ? $image . '&nbsp;': '') . $inrec['qty'] . ' x ' . '<a href="' . $url . FILENAME_CATALOG_PRODUCT_INFO .'?'. http_build_query(['products_id' => $inrec['pid']]) . '">' . $inrec2['name'] . "</a></blockquote>";
            }          
            else
              $mline .= $inrec['qty'] . ' x  ' . $inrec2['name'] .'(' . $url . FILENAME_CATALOG_PRODUCT_INFO . '?'. http_build_query(['products_id' => $inrec['pid']]) .")\n";
          }

            $custname = $inrec['fname']." ".$inrec['lname'];

          $outEmailAddr = '"' . $custname . '" <' . $inrec['email'] . '>';
          if( tep_not_null(RCS_EMAIL_COPIES_TO) ) $outEmailAddr .= ', ' . RCS_EMAIL_COPIES_TO;

          $email_params = array();
          $email_params['STORE_NAME'] = $_platfomrs[$cid]->const_value('STORE_NAME');
          $email_params['USER_GREETING'] = trim(\common\helpers\Translation::getTranslationValue('EMAIL_TEXT_SALUTATION', 'admin/recover_cart_sales', $inrec['language_id']) . $custname);
          $email_params['CUSTOMER_FIRSTNAME'] = $inrec['fname'];
          $email_params['STORE_OWNER_EMAIL_ADDRESS'] = $_platfomrs[$cid]->const_value('STORE_OWNER_EMAIL_ADDRESS');
          $email_params['PRODUCTS_ORDERED'] = $mline;
          $email_params['ORDER_COMMENTS'] = (tep_not_null($output['message']) ? stripcslashes (urldecode($output['message'])) : '');
            
          if (isset($output['coupon'][$cid]) && $use_method == 'c'){
              $_c = Recovery::getRecoveryCoupons($output['coupon'][$cid]);
              $_c = $_c[$output['coupon'][$cid]];
              $email_params['COUPON_CODE'] = nl2br(sprintf(\common\helpers\Translation::getTranslationValue('TEXT_COUPON_OFFER', 'admin/recover_cart_sales', $inrec['language_id']), $_c['coupon_code'], ($_c['coupon_type'] == 'F' ? $currencies->format($_c['coupon_amount'], false, $_c['coupon_currency']) : ($_c['coupon_type'] == 'P' ? round($_c['coupon_amount'],2) . '%' : /*maybe for free shipping S*/''))));
              if( EMAIL_USE_HTML == 'true' ){
                $email_params['COUPON_CODE'] .= '<br><a href="' . $url . FILENAME_SHOPPING_CART . '?' . http_build_query(['action' => 'recovery_restore', 'email_address' => $inrec['email'], 'credit_apply[coupon][gv_redeem_code]' => $_c['coupon_code'], 'utmgclid' => 'recoveryemail', 'currency'=>$inrec['currency'], 'token' => $inrec['token']]) .'">' . \common\helpers\Translation::getTranslationValue('TEXT_RETURN', 'admin/recover_cart_sales', $inrec['language_id']) . '</a>';//tep_catalog_href_link('shopping-cart', 'action=recovery_restore&email_address='.$inrec['email'].'&credit_apply[coupon][gv_redeem_code]='.$_c['coupon_code'].'&utmgclid=recoveryemail&token=' . $inrec['token']
              } else {
                $email_params['COUPON_CODE'] .= $__c . "\n" . $url . FILENAME_SHOPPING_CART . '?' . http_build_query(['action' => 'recovery_restore', 'email_address' => $inrec['email'], 'credit_apply[coupon][gv_redeem_code]' => $_c['coupon_code'], 'utmgclid' => 'recoveryemail', 'currency'=>$inrec['currency'], 'token' => $inrec['token']]); //tep_catalog_href_link('shopping-cart', 'action=recovery_restore&email_address='.$inrec['email'].'&credit_apply[coupon][gv_redeem_code]='.$_c['coupon_code'].'&utmgclid=recoveryemail&token=' . $inrec['token']);
              }
              tep_db_query("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, basket_id, sent_firstname, sent_lastname, emailed_to, date_sent) values ('" . $output['coupon'][$cid] ."', '" . (int)$cid ."', '" . (int)$inrec['basket_id'] . "', '" . $admin->getInfo('admin_firstname') . "', '" . $admin->getInfo('admin_lastname') . "', '" . $inrec['email'] . "', now() )"); 
          } elseif(isset($output['amount']) && $use_method == 'v'){
            $output['amount'] = (float)$output['amount'];
            $data = \common\helpers\Coupon::generate_customer_gvcc($coupon_id, $inrec['email'], $output['amount'], $output['coupon_currency'], $cid, $bid[$ckey]);
            $email_params['COUPON_CODE'] = nl2br(sprintf(\common\helpers\Translation::getTranslationValue('TEXT_COUPON_OFFER', 'admin/recover_cart_sales', $inrec['language_id']), $data['id1'], $data['amount']));
            if( EMAIL_USE_HTML == 'true' ){
              $email_params['COUPON_CODE'] .= '<br><a href="' . $url . FILENAME_SHOPPING_CART . '?' . http_build_query(['action' => 'recovery_restore', 'email_address' => $inrec['email'], 'credit_apply[gv][gv_redeem_code]' => $data['id1'], 'utmgclid' => 'recoveryemail', 'currency'=>$inrec['currency'], 'token' => $inrec['token']]) .'">' . \common\helpers\Translation::getTranslationValue('TEXT_RETURN', 'admin/recover_cart_sales', $inrec['language_id']) . '</a>';//tep_catalog_href_link('shopping-cart', 'action=recovery_restore&email_address='.$inrec['email'].'&credit_apply[coupon][gv_redeem_code]='.$_c['coupon_code'].'&utmgclid=recoveryemail&token=' . $inrec['token']
            } else {
              $email_params['COUPON_CODE'] .= $__c . "\n" . $url . FILENAME_SHOPPING_CART . '?' . http_build_query(['action' => 'recovery_restore', 'email_address' => $inrec['email'], 'credit_apply[gv][gv_redeem_code]' => $data['id1'], 'utmgclid' => 'recoveryemail', 'currency'=>$inrec['currency'], 'token' => $inrec['token']]); //tep_catalog_href_link('shopping-cart', 'action=recovery_restore&email_address='.$inrec['email'].'&credit_apply[coupon][gv_redeem_code]='.$_c['coupon_code'].'&utmgclid=recoveryemail&token=' . $inrec['token']);
            }            
          } else {
            if( EMAIL_USE_HTML == 'true' ){
              $email_params['COUPON_CODE'] = '<br><a href="' . $url . FILENAME_SHOPPING_CART . '?' . http_build_query(['action' => 'recovery_restore', 'email_address' => $inrec['email'], 'utmgclid' => 'recoveryemail', 'currency'=>$inrec['currency'], 'token' => $inrec['token']]) .'">' . \common\helpers\Translation::getTranslationValue('TEXT_RETURN', 'admin/recover_cart_sales', $inrec['language_id']) . '</a>';;
            } else {
              $email_params['COUPON_CODE'] = $url . FILENAME_SHOPPING_CART . '?' . http_build_query(['action' => 'recovery_restore', 'email_address' => $inrec['email'], 'utmgclid' => 'recoveryemail', 'currency'=>$inrec['currency'], 'token' => $inrec['token']]);
            }
          }
            
          list($email_subject, $email_text) = \common\helpers\Mail::get_parsed_email_template('Recovery Cart', $email_params, $inrec['language_id'], $inrec['platform_id']);
              
          \common\helpers\Mail::send('', $outEmailAddr, $email_subject, $email_text, $_platfomrs[$cid]->const_value('STORE_OWNER'), $_platfomrs[$cid]->const_value('STORE_OWNER_EMAIL_ADDRESS'));

          $mline = "";

          // See if a record for this customer already exists; if not create one and if so update it
          $donequery = tep_db_query("select * from ". TABLE_SCART ." where customers_id = '" . (int)$cid . "' and basket_id= '" . (int)$inrec['basket_id'] . "'");
          if (tep_db_num_rows($donequery) == 0){
            tep_db_query("insert into " . TABLE_SCART . " (customers_id, basket_id, dateadded, datemodified, contacted ) values ('" . (int)$cid . "', '" . (int)$inrec['basket_id'] . "','" . $this->seadate('0') . "', '" . $this->seadate('0') . "' , 1)");
          }
          else{
            tep_db_query("update " . TABLE_SCART . " set datemodified = '" . $this->seadate('0') . "', contacted = 1 where customers_id = '" . (int)$cid . "' and basket_id= '" . (int)$inrec['basket_id'] . "'");	 
          }
        //}	 
      
        $response = array('message' => NOTICE_EMAILS_SENT, 'messageType' => 'success');		
	    }
      } else {
        $response = array('message' => NOTICE_EMAILS_NOT_SENT, 'messageType' => 'warning');
      }
      echo json_encode($response);      
    }

  }
  
  public function actionPhoned(){
    $_phoned = Yii::$app->request->post('phoned', 0);
    $cid = Yii::$app->request->post('custid', 0);
    $basket_id = Yii::$app->request->post('basket_id', 0);
    if ($cid){
        $donequery = tep_db_query("select * from ". TABLE_SCART ." where customers_id = '" . (int)$cid . "' and basket_id= '" . (int)$basket_id . "'");
        if (tep_db_num_rows($donequery) == 0){
          tep_db_query("insert into " . TABLE_SCART . " (customers_id, basket_id, dateadded, datemodified, contacted ) values ('" . (int)$cid . "', '" . (int)$basket_id . "','" . $this->seadate('0') . "', '" . $this->seadate('0') . "', '" . (int)$_phoned . "')");
        }
        else{
          tep_db_query("update " . TABLE_SCART . " set datemodified = '" . $this->seadate('0') . "', contacted = '" . (int)$_phoned . "' where customers_id = '" . (int)$cid . "' and basket_id= '" . (int)$basket_id . "'");	 
        }
      
    }
  }
  
  public function actionLegend(){
    $cid = Yii::$app->request->get('cid', 0);
    $basket_id = Yii::$app->request->get('basket_id', 0);
    \common\helpers\Translation::init('admin/orders');
    \common\helpers\Translation::init('admin/recover_cart_sales');
    $uaData = \common\helpers\System::get_ga_basket_detection($cid, $basket_id);
    $errors = \common\models\CustomersErrors::find()->where(['customers_id' => $cid, 'basket_id' => $basket_id])->all();
    return $this->renderAjax('legend', ['ua' => $uaData, 'errors' => $errors]);
  }

  public function actionNoteedit(){
    
    $admin = new \backend\models\Admin();
    
    if (Yii::$app->request->isPost){
      $cid = Yii::$app->request->post('cid', 0);
      $comments = urldecode(Yii::$app->request->post('comments', ''));
      $donequery = tep_db_query("select * from ". TABLE_SCART ." where customers_id = '" . (int)$cid . "'");
      if (tep_db_num_rows($donequery) == 0){
        tep_db_query("insert into " . TABLE_SCART . " (customers_id, dateadded, datemodified, note ) values ('" . (int)$cid . "', '" . $this->seadate('0') . "', '" . $this->seadate('0') . "', '" . tep_db_input($comments) . "')");
      }
      else{
        tep_db_query("update " . TABLE_SCART . " set datemodified = '" . $this->seadate('0') . "', note = '" . tep_db_input($comments) . "' where customers_id = " . (int)$cid );	 
      }      
      echo 'ok';
      exit();
    } else {
      $cid = Yii::$app->request->get('cid', 0);
      $note = '';
      if ($cid){     
       
        $note_query = tep_db_fetch_array(tep_db_query("select note from ". TABLE_SCART . " where customers_id = '" . (int)$cid . "'"));
        if ($note_query){
          $note = $note_query['note'] . "\n";
        }
        $note .= $admin->getInfo('admin_firstname') . ' '. $admin->getInfo('admin_lastname')  . " (" . strftime (DATE_TIME_FORMAT_LONG) . ")\n";
        
      }
      return $this->renderAjax('note.tpl', ['note' => $note, 'cid' => $cid]);      
    }
  }
	
}
