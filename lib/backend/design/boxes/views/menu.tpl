{use class="Yii"}
<form action="{Yii::getAlias('@web')}/design/box-save" method="post" id="box-save">
  <input type="hidden" name="id" value="{$id}"/>
  <div class="popup-heading">
    {$smarty.const.TEXT_MENU}
  </div>
  <div class="popup-content">




    <div class="tabbable tabbable-custom">
      <ul class="nav nav-tabs">

        <li class="active"><a href="#type" data-toggle="tab">{$smarty.const.HEADING_TYPE}</a></li>
        <li><a href="#style" data-toggle="tab">{$smarty.const.HEADING_STYLE}</a></li>
        <li><a href="#align" data-toggle="tab">{$smarty.const.HEADING_WIDGET_ALIGN}</a></li>
        <li><a href="#visibility" data-toggle="tab">{$smarty.const.TEXT_VISIBILITY_ON_PAGES}</a></li>

      </ul>
      <div class="tab-content">
        <div class="tab-pane active menu-list" id="type">




          <div class="setting-row">
            <label for="">{$smarty.const.TEXT_CHOSE_MENU} {$params}</label>
            <select name="params" id="" class="form-control">
              {foreach $menus as $menu}
              <option value="{$menu.menu_name}"{if $menu.menu_name == $params} selected{/if}>{$menu.menu_name}</option>
              {/foreach}
            </select>
          </div>


          <div class="setting-row">
            <label for="">{$smarty.const.TEXT_MENU_STYLE}</label>
            <select name="setting[0][class]" id="" class="form-control">
              <option value="menu-style-1"{if $settings[0].class == 'menu-style-1'} selected{/if}>{$smarty.const.TEXT_HORIZONTAL}</option>
              <option value="menu-slider"{if $settings[0].class == 'menu-slider'} selected{/if}>{$smarty.const.TEXT_MENU_SLIDER}</option>
              <option value="menu-big"{if $settings[0].class == 'menu-big'} selected{/if}>{$smarty.const.BIG_DROPDOWN_MENU}</option>
              <option value="menu-style-2"{if $settings[0].class == 'menu-style-2'} selected{/if}>{$smarty.const.TEXT_VERTICAL}</option>
              <option value="menu-horizontal"{if $settings[0].class == 'menu-horizontal'} selected{/if}>{$smarty.const.TEXT_HORIZONTAL} (Secondary navigation)</option>
              <option value="menu-simple"{if $settings[0].class == 'menu-simple'} selected{/if}>{$smarty.const.TEXT_VERTICAL} (Secondary navigation)</option>
              <option value="mobile"{if $settings[0].class == 'mobile'} selected{/if}>mobile</option>
            </select>
          </div>

          {if $settings.media_query|@count > 0}
            <div style="margin-bottom: 20px">
            <h4>{$smarty.const.HIDE_MENU_UNDER_ICON}</h4>
            {foreach $settings.media_query as $item}
              <p><label>
                  <input type="checkbox" name="visibility[0][{$item.id}][hide_menu]"{if $visibility[0][$item.id].hide_menu} checked{/if}/>
                  {$item.setting_value}
                </label></p>
            {/foreach}
            </div>
          {/if}


          {include 'include/ajax.tpl'}



        </div>
        <div class="tab-pane" id="style">
          {include 'include/style.tpl'}
        </div>
        <div class="tab-pane" id="align">
          {include 'include/align.tpl'}
        </div>
        <div class="tab-pane" id="visibility">
          {include 'include/visibility.tpl'}
        </div>

      </div>
    </div>


  </div>
  <div class="popup-buttons">
    <button type="submit" class="btn btn-primary btn-save">{$smarty.const.IMAGE_SAVE}</button>
    <span class="btn btn-cancel">{$smarty.const.IMAGE_CANCEL}</span>
  </div>
</form>