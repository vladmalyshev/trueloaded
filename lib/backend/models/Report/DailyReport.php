<?php

namespace backend\models\Report;

use Yii;

class DailyReport extends BasicReport implements ReportInterface {

    CONST DELIMETER = "/";
    CONST SHOW_ROWS = -1;

    protected $start_day = '01';
    protected $end_day;
    protected $start_month;
    protected $end_month;
    protected $start_year;
    protected $end_year;
    protected $month_year;
    protected $start_custom;
    protected $end_custom;
    protected $all_params = [];
    private $name = 'daily';
    protected $sql_params = [
        'group' => ['dayofmonth', 'month', 'year'],
        'select_period' => 'date',
    ];
    protected $range = [
        'month' => TEXT_MONTH_COMMON, 'year' => TITLE_YEAR, 'custom' => TEXT_CUSTOM,
    ];
    protected $current_range;

    public function __construct($data) {
        switch ($data['range']) {
            case 'month':
                if (isset($data['month_year']) && !empty($data['month_year'])) {
                    $this->predefineMonthYear($data['month_year']);
                }
                break;
            case 'year':
                $this->start_month = 1;
                $this->end_month = 12;
                $this->start_year = $data['year'];
                $this->end_year = $data['year'];
                break;
            case 'custom':
                if (isset($data['start_custom']) && !empty($data['start_custom'])) {
                    $this->predefineStartCustomMonthYear($data['start_custom']);
                }
                if (isset($data['end_custom']) && !empty($data['end_custom'])) {
                    $this->predefineEndCustomMonthYear($data['end_custom']);
                }
                break;
        }
        if (isset($data['range']))
            $this->current_range = $data['range'];

        if (empty($this->start_month))
            $this->start_month = date("m");
        if (empty($this->end_month))
            $this->end_month = date("m");
        if (empty($this->start_year))
            $this->start_year = date("Y");
        if (empty($this->end_year))
            $this->end_year = date("Y");
        if (empty($this->end_day))
            $this->end_day = date("t");

        if (empty($this->start_custom)) {
            $this->month_year = $this->start_month . self::DELIMETER . $this->start_year;
        } else {
            $this->month_year = '';
        }

        $this->checkMonthDayYear();
        $this->start_day = '01';
        $this->end_day = date('t', mktime(0, 0, 0, $this->end_month, 1, $this->end_year));

        parent::__construct($data);
    }

    public function getOptions($range) {
        switch ($range) {
            case 'month' :
                return Yii::$app->controller->renderAjax('month_year', [
                            'month_year' => $this->month_year,
                ]);
                break;
            case 'year' :
                return Yii::$app->controller->renderAjax('year', [
                            'year' => $this->start_year,
                            'years' => $this->getYearsList(),
                ]);
                break;
            case 'custom':
                return Yii::$app->controller->renderAjax('custom_month_year', [
                            'start_custom' => $this->start_custom,
                            'end_custom' => $this->end_custom,
                ]);
                break;
        }
    }

    public function loadPurchases($for_map = false) {
        $where = " ( o.date_purchased between '" . $this->start_year . "-" . $this->start_month . "-01 00:00:00' and '" . $this->end_year . "-" . $this->end_month . "-31 23:59:59' ) ";
        $data = $this->getRawData($where, $for_map);
        if ($for_map) return $data;
        if (is_array($data)) {
            $filled = false;
            $new_data = [];
            foreach ($data as $k => $v) {
                if (!$filled) {
                    $template = $v;
                    foreach ($template as $key => $value) {
                        if ($key != 'period_full') {
                            $template[$key] = '';
                        }
                    }
                    $new_data = $this->prepareDaysRange($template, "d M Y", $this->class_range);
                    //if (count($class_range)) $this->setClassRange($class_range);
                    //echo '<pre>';print_r($new_data);die;
                    $filled = true;
                }
                if (!empty($v['period'])) {
                    $data[$k]['period'] = date("d M Y", strtotime($v['period']));
                    $data[$k]['period_full'] = date("m/d/Y H:00:00", strtotime($v['period']));
                    $data[$k]['cur_row'] = date('Y-m-d') == date('Y-m-d', strtotime($v['period']) );
                    $new_data[date("d-m-Y", strtotime($v['period']))] = $data[$k];
                }
            }

            $_temp = [];
            foreach ($new_data as $kday => $vday) {
                $_temp[] = $vday;
            }
            $data = $_temp;
        }
        return $data;
    }

    public function getRange() {
        if ($this->start_month == $this->end_month && $this->start_year == $this->end_year) {
            return date("M, Y", mktime(0, 0, 0, $this->start_month, 1, $this->start_year));
        }
        return date("M, Y", mktime(0, 0, 0, $this->start_month, 1, $this->start_year)) . ' - ' . date("M, Y", mktime(0, 0, 0, $this->end_month, 1, $this->end_year));
    }

    public function getTableTitle() {
        return TEXT_SALES_DAILY_STATISTICS;
    }

    public function convertColumnTitle($value) {
        if ($value == 'period') {
            return TEXT_DAY;
        }
        return parent::convertColumnTitle($value);
    }

    public function getRowsCount() {
        if (($this->start_month != $this->end_month &&
                $this->start_year == $this->end_year) ||
                ($this->start_month == $this->end_month &&
                $this->start_year != $this->end_year) ||
                ($this->start_month != $this->end_month &&
                $this->start_year != $this->end_year)
        ) {
            return 25;
        }
        return self::SHOW_ROWS;
    }
    
    public function hasDailyItems(){
        return true;
    }

}
