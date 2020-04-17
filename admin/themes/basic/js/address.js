    $.fn.setStateCountryDependency = function(options){
        var _options = {
            'appendto':'.stateWrap'
        }
        var _options = $.extend({
            'country' : options.country,
            'url': options.url,
            'appendto':options.appendto
        }, options);
        return this.each(function() {
            var that = this;            
            $(that).autocomplete({
              source: function(request, response) {
                $.getJSON(_options.url, { term : request.term, country: $(_options.country).val() }, response);
              },
              minLength: 0,
              autoFocus: true,
              appendTo:_options.appendto,
              delay: 0,
              open: function (e, ui) {
                if ($(this).val().length > 0) {
                  var acData = $(this).data('ui-autocomplete');
                  acData.menu.element.find('a').each(function () {
                    var me = $(this);
                    var keywords = acData.term.split(' ').join('|');
                    me.html(me.text().replace(new RegExp("(" + keywords + ")", "gi"), '<b>$1</b>'));
                  });
                }
              },
              response: function( event, ui ) {
                $(that).attr('autocomplete', (ui.content.length > 0? 'nope': 'off'));
              },
              select: function( event, ui ) {
                setTimeout(function(){
                  $(that).trigger('change');
                }, 200)
              }
            }).focus(function () {
              $(that).autocomplete("search");              
            });
            
        });
        
    }
    
    $.fn.getCityList = function(options){
        var _options = {
            'appendto':''
        }
        var _options = $.extend({
            'country' : options.country,
            'url': options.url,/*'account/address-city'*/
            'appendto':options.appendto
        }, options);
        return this.each(function() {
            var that = this;            
            $(that).autocomplete({
              source: function(request, response) {
                $.getJSON(_options.url, { term : request.term, country: $(_options.country).val() }, response);
              },
              minLength: 0,
              autoFocus: true,
              appendTo:_options.appendto,
              delay: 0,
              open: function (e, ui) {
                if ($(this).val().length > 0) {
                  var acData = $(this).data('ui-autocomplete');                  
                  acData.menu.element.find('a').each(function () {
                    var me = $(this);
                    var keywords = acData.term.split(' ').join('|');
                    me.html(me.text().replace(new RegExp("(" + keywords + ")", "gi"), '<b>$1</b>'));
                  });
                }
              },
              response: function( event, ui ) {
                $(that).attr('autocomplete', (ui.content.length > 0? 'nope': 'off'));
              },
              select: function( event, ui ) {
                setTimeout(function(){
                  $(that).trigger('change');
                }, 200)
              }
            }).focus(function () {
              $(that).autocomplete("search");              
            });
            
        });        
    }
