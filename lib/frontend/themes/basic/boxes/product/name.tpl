<table class="wrapper"><tr><td>
      {if $h1}
          {\frontend\design\Info::addBoxToCss('page-name')}
          {if !$settings[0].show_heading}
              <div class="page-name" {*itemprop="name"*}>{$name}</div>
              <h1>{$h1}</h1>
          {elseif $settings[0].show_heading == 'h1_name'}
              <h1>{$h1}</h1>
              <div class="page-name" {*itemprop="name"*}>{$name}</div>
          {elseif $settings[0].show_heading == 'h1'}
              <h1 {*itemprop="name"*}>{$h1}</h1>
          {elseif $settings[0].show_heading == 'name_in_div'}
              <div {*itemprop="name"*}>{$name}</div>
          {elseif $settings[0].show_heading == 'name_in_h1'}
              <h1 {*itemprop="name"*}>{$name}</h1>
          {elseif $settings[0].show_heading == 'name_in_h2'}
              <h2 {*itemprop="name"*}>{$name}</h2>
          {elseif $settings[0].show_heading == 'name_in_h3'}
              <h3 {*itemprop="name"*}>{$name}</h3>
          {elseif $settings[0].show_heading == 'name_in_h4'}
              <h4 {*itemprop="name"*}>{$name}</h4>
          {/if}
      {else}
        <h1 {*itemprop="name"*}>{$name}</h1>
      {/if}
</td></tr></table>
{if $params.message}
  {\frontend\design\Info::addBoxToCss('info')}
  <div class="info">{$params.message}</div>
{/if}
<link {*itemprop="url"*} href="{$productUrl}" />