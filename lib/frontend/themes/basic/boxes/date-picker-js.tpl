{use class="frontend\design\Info"}
<script type="text/javascript">
    tl(['{Info::themeFile('/js/bootstrap.min.js')}',
        '{Info::themeFile('/js/bootstrap-datepicker.js')}'
    ], function () {
        $('head').prepend('<link rel="stylesheet" href="{Info::themeFile('/css/bootstrap-datepicker.css')}">');

        $.fn.datepicker.dates.current = {
            days: [
                "{$smarty.const.TEXT_SUNDAY|strip}",
                "{$smarty.const.TEXT_MONDAY|strip}",
                "{$smarty.const.TEXT_TUESDAY|strip}",
                "{$smarty.const.TEXT_WEDNESDAY|strip}",
                "{$smarty.const.TEXT_THURSDAY|strip}",
                "{$smarty.const.TEXT_FRIDAY|strip}",
                "{$smarty.const.TEXT_SATURDAY|strip}"],
            daysShort: [
                "{$smarty.const.DATEPICKER_DAY_SUN|strip}",
                "{$smarty.const.DATEPICKER_DAY_MON|strip}",
                "{$smarty.const.DATEPICKER_DAY_TUE|strip}",
                "{$smarty.const.DATEPICKER_DAY_WED|strip}",
                "{$smarty.const.DATEPICKER_DAY_THU|strip}",
                "{$smarty.const.DATEPICKER_DAY_FRI|strip}",
                "{$smarty.const.DATEPICKER_DAY_SAT|strip}"],
            daysMin: [
                "{$smarty.const.DATEPICKER_DAY_SU|strip}",
                "{$smarty.const.DATEPICKER_DAY_MO|strip}",
                "{$smarty.const.DATEPICKER_DAY_TU|strip}",
                "{$smarty.const.DATEPICKER_DAY_WE|strip}",
                "{$smarty.const.DATEPICKER_DAY_TH|strip}",
                "{$smarty.const.DATEPICKER_DAY_FR|strip}",
                "{$smarty.const.DATEPICKER_DAY_SA|strip}"],
            months: [
                "{$smarty.const.DATEPICKER_MONTH_JANUARY|strip}",
                "{$smarty.const.DATEPICKER_MONTH_FEBRUARY|strip}",
                "{$smarty.const.DATEPICKER_MONTH_MARCH|strip}",
                "{$smarty.const.DATEPICKER_MONTH_APRIL|strip}",
                "{$smarty.const.DATEPICKER_MONTH_MAY|strip}",
                "{$smarty.const.DATEPICKER_MONTH_JUNE|strip}",
                "{$smarty.const.DATEPICKER_MONTH_JULY|strip}",
                "{$smarty.const.DATEPICKER_MONTH_AUGUST|strip}",
                "{$smarty.const.DATEPICKER_MONTH_SEPTEMBER|strip}",
                "{$smarty.const.DATEPICKER_MONTH_OCTOBER|strip}",
                "{$smarty.const.DATEPICKER_MONTH_NOVEMBER|strip}",
                "{$smarty.const.DATEPICKER_MONTH_DECEMBER|strip}"],
            monthsShort: [
                "{$smarty.const.DATEPICKER_MONTH_JAN|strip}",
                "{$smarty.const.DATEPICKER_MONTH_FEB|strip}",
                "{$smarty.const.DATEPICKER_MONTH_MAR|strip}",
                "{$smarty.const.DATEPICKER_MONTH_APR|strip}",
                "{$smarty.const.DATEPICKER_MONTH_MAY|strip}",
                "{$smarty.const.DATEPICKER_MONTH_JUN|strip}",
                "{$smarty.const.DATEPICKER_MONTH_JUL|strip}",
                "{$smarty.const.DATEPICKER_MONTH_AUG|strip}",
                "{$smarty.const.DATEPICKER_MONTH_SEP|strip}",
                "{$smarty.const.DATEPICKER_MONTH_OCT|strip}",
                "{$smarty.const.DATEPICKER_MONTH_NOV|strip}",
                "{$smarty.const.DATEPICKER_MONTH_DEC|strip}"],
            today: "{$smarty.const.TEXT_TODAY|strip}",
            clear: "{$smarty.const.TEXT_CLEAR|strip}",
            weekStart: 1
        };

      {\frontend\design\Info::addBoxToCss('datepicker')}
        $('{$selector}').datepicker({
            {foreach $params as $key => $val}
            {$key}: {$val},
            {/foreach}
            format: '{$smarty.const.DATE_FORMAT_DATEPICKER|strip}',
            language: 'current'
        });
    });
</script>