{function name=renderCategoriesTree collapsed=0 level=0}
  {if $level==0}
    <div class="dd3-content" id="cat-main-box-cat-0"><span class="cat_li"><span id="0" class="cat_text" onClick="changeCategory(this)">{$smarty.const.TEXT_TOP}</span></span></div>
  {/if}
  <ol class="categories_ul dd-list"{if {$level>0 && ($collapsed || in_array($item.id, $app->controller->view->categoriesClosedTree)) && !in_array($item.id, $app->controller->view->categoriesOpenedTree) }} style="display:none"{/if} id="ol-sub-cat-{$item.id}">
  {foreach $items as $item}
    <li class="dd-item dd3-item" data-id="{$item.id}">
      <div class="tl-wrap-li-left-cat">
        <div class="dd-handle handle">
            <i class="icon-hand-paper-o"></i>
        </div>
        <div class="dd3-content{if $item.id == $app->controller->view->filters->category_id || $item.id == $app->controller->view->category_id} selected{/if}" id="cat-main-box-cat-{$item.id}">
            <span class="cat_li"><span id="{$item.id}" class="cat_text" onClick="changeCategory(this)">{$item.text}</span>
                <a href="{Yii::$app->urlManager->createUrl(['categories/categoryedit', 'categories_id' => $item.id])}" class="edit_cat"><i class="icon-pencil"></i></a>
                <a class="delete_cat" href="{Yii::$app->urlManager->createUrl(['categories/confirmcategorydelete', 'popup' => 1,'categories_id' => $item.id])}"><i class="icon-trash"></i></a>
                {if count($item.child) > 0}<span data-id-suffix="{$item.id}" class="collapse_span{if ($collapsed || in_array($item.id, $app->controller->view->categoriesClosedTree)) && !in_array($item.id, $app->controller->view->categoriesOpenedTree)} c_up{/if}"></span>{/if}
            </span>
        </div>
      </div>
    {if count($item.child) > 0}
    {call name=renderCategoriesTree items=$item.child collapsed=$collapsed level=$level+1}
    {/if}
    </li>
  {/foreach}
  </ol>
{/function}

{if $directOutput}
  {call renderCategoriesTree items=$app->controller->view->categoriesTree collapsed=$collapsed level=0}
{/if}
