{use class="common\helpers\Acl"}
{\backend\widgets\SalesSummary::widget()}
<div class="statistic-bottom">
<div class="row">
  <div class="col-md-6">
    {\backend\widgets\NewOrders::widget()}
  </div>
  <div class="col-md-6">
    {\backend\widgets\SalesGraph::widget()}
  </div>
</div>
</div>
{\backend\widgets\GoogleMaps::widget()}
