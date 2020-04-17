{use class="\yii\helpers\Html"}
{\backend\assets\BDPAsset::register($this)|void}
<div class="widget box box-wrapp-blue widget-fixed">
    <div class="widget-header">
        <h4>{$app->controller->view->headingTitle}
            <span class="filterFormHead filterFormHeadWrapp"><span>{Html::dropDownList('platforms[]', $app->controller->view->filter->platforms_id, $platforms, ['class' => '', 'multiple' => 'multiple', 'data-role' => 'multiselect'])}</span></span>
        </h4>
    </div>
    <div class="widget-content geo-report">
{if !is_null($app->controller->view->filter->mapskey)}
{$filter}
<div class="map_dashboard">
    <div id="gmap_markers" class="gmaps"></div>
    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js">
    </script>    
    <script src="https://maps.googleapis.com/maps/api/js?key={$app->controller->view->filter->mapskey}&callback=initMap" async defer></script> 
</div>
<script type="text/javascript">

var map;
var geocoder;
var markers = new Array();
var delay = 1000;
var masSearch = new Array();
var max = 10;
var start = -1;
var tim;
var firstloaded = 0;
var moreLoaded = 0;
var markerCluster;
var _max_orders_count = 0;
var limit = 50;

function reloadClaster(){
//  if (_max_orders_count > limit){
      
      markerCluster = new MarkerClusterer(map, markers,
            {
              imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
            });
//  }
}

function loadMapData($frm, $to){

	$.get("{Yii::$app->urlManager->createUrl('geo_report/locations')}",{
        'from' : $frm,
        'to' : $to,
        'platfroms_id[]' : $('select[data-role=multiselect]').multipleSelect('getSelects'), 
    }, function(data){
		if (data.founded && data.founded.length > 0){
            _max_orders_count = data.orders_count;
			$.each(data.founded, function(i, e){
				addMarker(e, map);
			});
			reloadClaster();
		}		
	}, "json");
	
}

function clearMarkers(){
  if (markers.length > 0){
    $.each(markers, function(i, e){
      markers[i].setMap(null);
      markerCluster.markers_[i].setMap(null);
    });
    markers.length = 0;
    markerCluster.markers_.length = 0;
    markerCluster.clearMarkers();
  }
}

function initMap() { 

    map = new google.maps.Map(document.getElementById('gmap_markers'), { 
      zoom: parseFloat({$origPlace.zoom}),
      center: { lat: parseFloat({$origPlace.lat}), lng: parseFloat({$origPlace.lng}) }
    });
    geocoder = new google.maps.Geocoder();
	
	var labels = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	var labelIndex = 0;	

    var _min = new Date({$min}*1000);
    var _max = new Date({$max}*1000);
    
    $('.min-range input').val(getLongDate(_min));
    $('.max-range input').val(getLongDate(_max));
	loadMapData({$min}, {$max});
}    


function addMarker(location, map) {
  // Add the marker at the clicked location, and add the next-available label
  // from the array of alphabetical characters.
  if (_max_orders_count < limit){
	  markers.push(new google.maps.Marker({
		position:  {
			lat: parseFloat(location.lat),
			lng: parseFloat(location.lng)
		},
		label: "A",//labels[labelIndex++ % labels.length],
	    map: map,
		title: location.title
	  }));
  
  } else { // to be reloaded by Cluster
	  markers.push(new google.maps.Marker({
		position:  {
			lat: parseFloat(location.lat),
			lng: parseFloat(location.lng)
		},
		label: "A",//labels[labelIndex++ % labels.length],
		title: location.title
	  }));
  
  }
}

function getLongDate(udate){
    var u = udate.getDate() + " " + udate.toLocaleString('en-us', { month: "long" }) + " " + udate.getFullYear();
    return u.toString();
}

$(document).ready(function(){
        $("select[data-role=multiselect]").multipleSelect({
                multiple: true,
                filter: false,
                selectAll: false,
                placeholder:'{$smarty.const.BOX_PLATFORMS}',
                onClick:function(e){
                    var values = $('#slider-range').slider('values');
                    clearMarkers();
                    loadMapData(values[0], values[1]);
                },                
        });
        //$('select[data-role=multiselect]').multipleSelect('checkAll');
    })

  </script>
{/if}
    </div>
</div>    