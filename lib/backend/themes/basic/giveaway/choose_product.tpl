{*
This file is part of True Loaded.

@link http://www.holbi.co.uk
@copyright Copyright (c) 2005 Holbi Group LTD

For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
*}

{use class="common\helpers\Html"}
<div class="widget box box-wrapp-blue filter-wrapp">
  <div class="wl-td">
    <div class="customer_in auto-wrapp" style="position: relative; width: 100%;">
    <label>{$smarty.const.FIND_PRODUCTS}</label>
    {Html::textInput('product', '', ['class' => 'form-control', 'id' => 'search_text'])}
    </div>
  </div>
</div>


<script type="text/javascript">
  $(document).ready(function () {
    $('#search_text').autocomplete({
      minLength: 2,
      autoFocus: true,
      delay: 1000,
      appendTo: '.auto-wrapp',
      create: function () {
        $(this).data('ui-autocomplete')._renderItem = function (ul, item) {
          return $("<li></li>")
                  .data("item.autocomplete", item)
                  .append("<a>" +
                    (item.hasOwnProperty('image') && item.image.length > 0 ?
                      "<img src='" + item.image + "' align='left' width='25px' height='25px'>" :
                      '') +
                    "<span>" + item.label + "</span></a>")
                  .appendTo(ul);
        };
      },
      source: function (request, response) {
        if (request.term.length > 2) {
          $.get("{\Yii::$app->urlManager->createUrl('stock-manufacturer/seacrh-product')}", {
            'search': request.term,
          }, function (data) {
            response($.map(data, function (item, i) {
              return {
                values: item.text,
                label: item.text,
                id: parseInt(item.id),
              };
            }));
          }, 'json');

        }
      },
      select: function (event, ui) {
        if (ui.item.id > 0) {
          var href = window.location.href;
          window.location.href = href + ((href.indexOf('?')===-1)?'?':'&')+'products_id='+ui.item.id;
        }
      },
    }).focus(function () {
      $('#search_text').autocomplete("search");
    });
  })
</script>