<div class="popup-heading">{$smarty.const.TEXT_LEGEND}</div>
<div class="popup-content pop-mess-cont">

                      <div class="tabbable tabbable-custom">
                        <ul class="nav nav-tabs tab-radius-ul tab-radius-ul-white">
                          <li><a href="#marketing" data-toggle="tab"><span>{$smarty.const.TEXT_MARKETING}</span></a></li>
                          <li class="active"><a href="#info" data-toggle="tab"><span>{$smarty.const.IMAGE_DETAILS}</span></a></li>
                          <li><a href="#errors" data-toggle="tab"><span>{$smarty.const.TEXT_ERRORS}</span></a></li>
                        </ul>
                          <div class="tab-content" id="">
                            <div id="marketing" class="tab-pane">
                                <table border="0" cellspacing="0" cellpadding="2"  class="main" width="100%">
                                  <tr><td width="50%">{$smarty.const.TEXT_CUSTOMER_ORIGIN}:</td><td>{$ua->origin}</td></tr>
                                  <tr><td>{$smarty.const.TEXT_COMPAING}:</td><td>{$ua->utmccn}</td></tr>
                                  <tr><td>{$smarty.const.HEADING_TITLE_SEARCH}</td><td>{$ua->utmcmd}</td></tr>
                                  <tr><td>{$smarty.const.TEXT_SEARCH_KEY}:</td><td>{$ua->utmctr}</td></tr>
                                </table>  
                            </div>
                            <div id="info" class="tab-pane active">
                              <table border="0" cellspacing="0" cellpadding="2" class="main" width="100%">
                                <tr><td width="50%">IP:</td><td>{$ua->ip_address}</td></tr>
                                <tr><td>{$smarty.const.TEXT_BROWSER}:</td><td>{$ua->agent_name}</td></tr>
                                <tr><td>{$smarty.const.TEXT_OPERATING_SYSTEM}:</td><td>{$ua->os_name}</td></tr>
                                <tr><td>{$smarty.const.TEXT_SCREEN_RESOLUTION}:</td><td>{$ua->resolution}</td></tr>
                                <tr><td>{$smarty.const.TEXT_JAVA_SUPPORT}:</td><td>{$ua->java}</td></tr>
                              </table>
                            </div>
                            <div id="errors" class="tab-pane">
                              <table border="0" cellspacing="0" cellpadding="2" class="main" width="100%">
                               <tr>
                                <td width="15%"><b>{$smarty.const.HEADING_TYPE}</b></td>
                                <td width="25%"><b>{$smarty.const.TEXT_TITLE_}</b</td>
                                <td  width="35%"><b>{$smarty.const.TABLE_HEADING_COMMENTS}</b</td>
                                <td width="25%"><b>{$smarty.const.TEXT_DATE_ADDED}</b</td>
                               </tr>
                               {foreach $errors as $error}
                               <tr>
                                <td width="15%">{$error->error_entity}</td>
                                <td width="25%">{$error->error_title}</td>
                                <td  width="35%">{$error->error_message}</td>
                                <td width="25%">{date(DATE_FORMAT, strtotime($error->error_date))}</td>
                               </tr>
                               {/foreach}
                              </table>
                            </div>
                          </div>
                        </div>
</div>
<div class="note-block noti-btn">
  <div></div>
  <div><span class="btn btn-cancel">{$smarty.const.TEXT_BTN_OK}</span></div>
</div>
