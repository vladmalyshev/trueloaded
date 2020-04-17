<script type="text/javascript" language="javascript"><!--
function rowOverEffect(object) {
  if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect(object) {
  if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}

function checkBox(object, index) {
  if (index != ''){
    document.account_notifications.elements[object].checked = !document.account_notifications.elements[object].checked;
  }else{
    document.account_notifications.elements[object][index].checked = !document.account_notifications.elements[object][index].checked;
  }
}
//--></script>
