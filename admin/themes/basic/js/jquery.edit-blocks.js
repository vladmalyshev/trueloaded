(function(){
  var frame_height = 1000;
  var history = [];
  var history_i = 0;
  var newWin = false;
  var widgetsList = {};

  var popUpPosition = function(){
    var d = ($(window).height() - $('.popup-box').height()) / 2;
    if (d < 50) d = 50;
    $('.popup-box-wrap').css('top', $(window).scrollTop() + d)
  };

  var saveSettings = function() {

    var boxSave = $('#box-save');


    window.boxInputChanges = {};

    boxSave.on('change', 'input, select, textarea', function(){
      if ($(this).attr('type') == 'checkbox' && !$(this).is(':checked')) {
        window.boxInputChanges[$(this).attr('name')] = '';
      } else if ($(this).attr('type') == 'checkbox' && $(this).is(':checked')) {
        window.boxInputChanges[$(this).attr('name')] = 1;
      }else {
        window.boxInputChanges[$(this).attr('name')] = $(this).val();
      }
    });



    boxSave.on('submit', function(){

      window.boxInputChanges['id'] = $('input[name="id"]', this).val();
      var params = $('input[name="params"], select[name="params"]', this).val();
      if (params) {
        window.boxInputChanges['params'] = params;
      }

      var values = [];
      $.each( window.boxInputChanges, function(name, value) {
        values = values.concat({ "name": name, "value": value});
      });

      values = values.concat(
        $('.visibility input[disabled]', this).map(function() {
          return { "name": this.name, "value": 1}
        }).get()
      );

      $('.check_on_off').each(function(){
          values = values.concat({ "name": $(this).attr('name'), "value": $(this).prop( "checked" )});
      });

      var data = values.reduce(function(obj, item) {
        obj[item.name] = item.value;
        return obj;
      }, {});

      $.post('design/box-save', {'values': JSON.stringify(data)}, function(){ });
      setTimeout(function(){
        $(window).trigger('reload-frame')
      }, 300);
      return false
    });

  };

  $.fn.infoView = function(options){
    var op = jQuery.extend({
      page_url: '',
      page_id: '288',
      na: '',
      remove_class: '',
      theme_name: 'theme-1',
      clear_url: false
    },options);

    var applyBlocks = function(){

      var _frame = $('#info-view');
      var frame = _frame.contents();

      $('a', frame).removeAttr('href');
      $('form', frame).removeAttr('action').on('submit', function(){return false});

      if (op.remove_class.length > 0) {
        $('.' + op.remove_class, frame).each(function () {
          $(this).removeClass(op.remove_class)
        });
      }


      $('*[data-block]', frame).each(function(){
        if ($(this).html() === '') {
          $(this).html('<span class="iv-editing">empty field</span>')
        }
      });

      $('.block', frame).append('<span class="add-box add-box-single">Add Widget</span>');
      $('.block .block > .add-box', frame).remove();

      $('.block[data-cols]', frame).append('<span class="add-box add-box-single">Add Widget</span>');

      $('.box-block', frame).append('<span class="menu-widget">' +
        '  <span class="add-box">Add Widget</span>' +
        '  <span class="edit-box" title="Edit Block"></span>' +
        '  <span class="handle" title="Move block"></span>' +
        '  <span class="export" title="Export Block"></span>' +
        '  <span class="remove-box" title="Remove Widget"></span>' +
        '</span>');
      $('.box-block > .menu-widget', frame).each(function(){
        var box = $(this).parent();
        $(this).css({
          'margin-left': box.css('padding-left'),
          'bottom': $(this).css('bottom').replace("px", "") * 1 + box.css('padding-bottom').replace("px", "")*1
        })
      });
      $('.box-block.type-1 > .menu-widget', frame).each(function(){
        var box = $(this).parent();
        $(this).css({
          'margin-left': 0,
          'left': (box.width() - $('> .block', box).width())/2 - 12
        })
      });

      $('.box, .box-block', frame).on('mouseleave mouseenter', function(e){
        $('.box-active', frame).removeClass('box-active');
        if (e.type == 'mouseleave') {
          $(this).parent().closest('.box, .box-block').addClass('box-active')
        } else if (e.type == 'mouseenter') {
          $(this).addClass('box-active')
        }
      });

      $('.block:nth-child(1)', frame).append('<span class="move-block move-block-1 handle"></span>' +
        '<span class="move-block move-block-2 handle"></span>' +
        '<span class="move-block move-block-3 handle"></span>' +
        '<span class="move-block move-block-4 handle"></span>');

      $('.box', frame).append('<span class="menu-widget">' +
        '<span class="edit-box" title="Edit Widget"></span>' +
        '<span class="edit-css" title="Edit widget styles (global for all same widgets)"></span>' +
        '<span class="handle" title="Move block"></span>' +
        '<span class="export" title="Export Block"></span>' +
        '<span class="remove-box" title="Remove Widget"></span>' +
        '</span>' +
        '<span class="move-block move-block-1 handle"></span>' +
        '<span class="move-block move-block-2 handle"></span>' +
        '<span class="move-block move-block-3 handle"></span>' +
        '<span class="move-block move-block-4 handle"></span>');

      $('.menu-widget', frame).each(function(){
        if ($(this).parent('.box').css('float') == 'right') {
          $(this).css({
              left: 'auto',
              right: 0
          })
        }
      });

      $('.block .remove-box', frame).on('click', function(){
        var blocks = {};
        var _this = $(this).closest('div[id]');
        blocks['name'] =  _this.data('name');
        blocks['theme_name'] =  op.theme_name;
        blocks['id'] = _this.attr('id');
        $.post('design/box-delete', blocks, function(){
          _this.remove();
          if (newWin && typeof newWin.location.reload == 'function') newWin.location.reload($.cookie('page-url')+'&is_admin=1');
        }, 'json');
      });


      $('.block .add-box', frame).on('click', function(){
        var this_block = $(this).closest('div[id]').find('> div');
        if ($(this).hasClass('add-box-single')){
          this_block = $(this).parent();
        }
        var block_name = this_block.data('name');

        $('body').append('<div class="popup-box-wrap"><div class="around-pop-up"></div><div class="popup-box widgets"><div class="pop-up-close"></div><div class="pop-up-content widgets"><div class="preloader"></div></div></div></div>');
        $('.around-pop-up, .pop-up-close').on('click', function(){
          $('.popup-box-wrap').trigger('remove').remove()
        });

        var page = 'default';
        if ($('body', frame).hasClass('catalog-index')) page = 'category';
        var block_type =  '';
        if (this_block.data('type')) {
          block_type = this_block.data('type');
        } else {
          this_block.closest('div[data-type]').each(function(){
            block_type = $(this).data('type')
          })
        }
        $.get('design/widgets-list', {page: page, type: block_type}, function(data){
            data = data.sort(sortWidgets)
          $.each(data, function(i, item){
            if (!$('.pop-up-content .box-group-'+item.type).length){
                $('.pop-up-content').append('<div class="box-group box-group-'+item.type+'"></div>')
            }
            $('.pop-up-content .preloader').remove();
            if (item.name == 'title'){
              $('.pop-up-content .box-group-'+item.type).prepend('<div class="title">' + item.title + '</div>');
            } else {
              $('.pop-up-content .box-group-'+item.type).append('<div class="widget-item ico-' + item.class + '" data-name="' + item.name + '">' + item.title + '</div>');
            }

            popUpPosition();
          });
          $('.box-group').each(function(){
            if ($(this).text().length == 0){
              $(this).remove()
            }
          });


          $('.pop-up-content div[data-name]').on('click', function(){
            var data = {
              'theme_name': op.theme_name,
              'block': block_name,
              'box': $(this).data('name'),
              'order': $('> div', this_block).length + 1
            };
            $.post('design/box-add', data, function(){
              $(window).trigger('reload-frame')
            }, 'json');
          })
        }, 'json');

        popUpPosition();
      });


      $('.menu-widget .edit-css', frame).off('click').on('click', {frame: frame}, editCss);

      $('.menu-widget .edit-box', frame).off('click').on('click', function(){
        var this_block = $(this).closest('div[id]');
        var block_id = this_block.attr('id');
        var block_name = this_block.data('name');
        var block_type = '';
        this_block.closest('div[data-type]').each(function(){
          block_type = $(this).data('type')
        });
          if (widgetsList[$(this).closest('div[data-name]').data('name')]) {
              var widgetTitle = widgetsList[$(this).closest('div[data-name]').data('name')].title;
          } else {
              var widgetTitle = '&nbsp;';
          }

        $('body').append('<div class="popup-box-wrap"><div class="around-pop-up around-widget-settings"></div><div class="popup-box widget-settings"><div class="pop-up-close"></div><div class="pop-up-content"><div class="preloader"></div></div></div></div>');
        $('.around-pop-up, .pop-up-close').on('click', function(){
          $('.popup-box-wrap').trigger('remove').remove()
        });

        $('.popup-box:last').draggable({ handle: ".popup-heading" });

        $.get('design/box-edit', {id: block_id, name: block_name, block_type: block_type}, function(data){
          $('.pop-up-content').html(data);
          var boxSave = $('#box-save');
          /*boxSave.on('submit', saveSettings);*/
          saveSettings();
          $('.popup-buttons .btn-cancel').on('click', function(){
            $('.popup-box-wrap').trigger('remove').remove()
          });

          $('.widget-settings .popup-heading').text(widgetTitle);

          var showChanges = function(){
            var style = $('#style .style-tabs-content > .active');
            $('.changed', style).removeClass('changed');
            $('input:not([type="radio"]), select', style).each(function(){
              if (
                ($(this).val() !== '' && $(this).attr('type') != 'checkbox') ||
                ($(this).attr('type') == 'checkbox' && $(this).prop( "checked" ))
              ) {
                $(this).closest('.setting-row').find('label').addClass('changed');
                $(this).closest('label').addClass('changed');
                var id = $(this).closest('.tab-pane').attr('id');
                $('.nav a[href="#'+id+'"]').addClass('changed');
                id = $(this).closest('.tab-pane').parents('.tab-pane').attr('id');
                $('.nav a[href="#'+id+'"]').addClass('changed');
              }
            })
          };
          showChanges();
          boxSave.on('change', showChanges);

          popUpPosition();
        });

        popUpPosition();
      });

      $('.menu-widget .export', frame).off('click').on('click', function(){
        window.location=window.location.pathname.replace('elements', "export-block?id=" + $(this).closest('div[id]').attr('id'))
      });

      $('.import-box', frame).each(function(){
        var block_name = $(this).closest('div[data-name]').parent().closest('div[data-name]').data('name');
        var box_id = $(this).parent().attr('id');
        $(this).dropzone({
          url: 'design/import-block?theme_name=' + op.theme_name + '&block_name=' + block_name + '&box_id=' + box_id,
          success: function(){
            $(window).trigger('reload-frame')
          },
            acceptedFiles: '.json'
        })
      });


      var type_box = '';
      $('.block[data-type]', frame).each(function(){
        var type = $(this).data('type');
        if (type != 'header' && type != 'footer'){
          type_box = type
        }
      });
      $('body', frame).prepend('<div class="widgets-list"></div>');

      var widgets_list = $('.widgets-list', frame);

      $(window).on('scroll', function(){
        if ($(window).scrollTop() > 191) {
          widgets_list.css('top', $(window).scrollTop() - 191)
        } else {
          widgets_list.css('top', 0)
        }
      });

      if ($.cookie('closed_widgets') == 1){
        widgets_list.addClass('closed')
      }
      widgets_list.on('click', '.close-widgets', function(){
        if (widgets_list.hasClass('closed')){
          widgets_list.removeClass('closed');
          $.cookie('closed_widgets', 0)
        } else {
          widgets_list.addClass('closed');
          $.cookie('closed_widgets', 1)
        }
      });
      $.get('design/widgets-list', {type: type_box}, function(data) {
          data = data.sort(sortWidgets)
        widgets_list.html('<div class="close-widgets"></div>');
        $.each(data, function (i, item) {
            if (!$('.widgets-list .box-group-'+item.type, frame).length){
                widgets_list.append('<div class="box-group box-group-'+item.type+'"></div>')
            }
          widgetsList[item.name] = item;
          if (item.name == 'title') {
            $('.widgets-list .box-group-' + item.type, frame).prepend('<div class="title">' + item.title + '</div>');
          } else {
            $('.widgets-list .box-group-' + item.type, frame).append('<div class="widget-item ico-' + item.class + '" data-name="' + item.name + '" title="' + item.title + '">' + item.title + '</div>');
          }

        });
        $('.widgets-list .box-group', frame).each(function () {
          if ($(this).text().length == 0) {
            $(this).remove()
          }
        });


        var sort_update = function( event, ui ) {
          $('.original-placeholder', frame).remove()
          var _this = $(this);

          if (ui.item.hasClass('widget-item')) {
            var block = _this.data('name');

            var data = {
              'theme_name': op.theme_name,
              'box': ui.item.data('name'),
              'block': block,
              'order': $('> div', this).length
            };
            data['id'] = {};
            $('> div', this).each(function(i){
              if ($(this).hasClass('widget-item')){
                data.id[i] = 'new';
              } else {
                data.id[i] = $(this).attr('id');
              }
            });
            $.post('design/box-add-sort', data, function(){
              $(window).trigger('reload-frame')
            }, 'json');

          } else {
            var blocks = {};
            blocks['name'] =  _this.data('name');
            blocks['theme_name'] =  op.theme_name;
            blocks['id'] = {};
            $('> div', this).each(function(i){
              blocks.id[i] = $(this).attr('id');
            });
            $.post('design/blocks-move', blocks, function(){
              if (newWin && typeof newWin.location.reload == 'function') {
                newWin.location.reload($.cookie('page-url')+'&is_admin=1');
              }
            }, 'json')
          }
        };
        var sort_scroll;
        $( ".block" , frame).sortable({
          connectWith: ".block",
          items: '> div.box, > div.box-block',
          cursor: 'move',
          handle: '.handle',
          update: sort_update,
          revert: true,
          tolerance: "pointer",
          scroll: false,
          sort: function(event, ui){
            var top = ui.offset.top + ui.item.height() + _frame.offset().top - $(window).scrollTop();
            if (top < 150) {
              clearInterval(sort_scroll);
              sort_scroll = setInterval(function () {
                var s = $(window).scrollTop() - 20;
                if (s < 0) s = 0;
                $(window).scrollTop(s)
              }, 100)
            } else if (top > $(window).height() - 40) {
              clearInterval(sort_scroll);
              sort_scroll = setInterval(function () {
                var s = $(window).scrollTop() + 20;
                if (s < 0) s = 0;
                $(window).scrollTop(s)
              }, 100)
            } else {
              clearInterval(sort_scroll);
            }
          },
          start: function (e, ui) {
            var clone = ui.item.clone();
            clone.addClass('original-placeholder')
            ui.item.parent().append(clone);
          },
          stop: function() {
            $('.original-placeholder', frame).remove()
          }
        });
        $( ".widgets-list", frame).sortable({
          connectWith: $(".block", frame),
          items: '.widget-item',
          forcePlaceholderSize: false,
          helper: function(e,li) {
            copyHelper = li.clone().insertAfter(li);
            return li.clone();
          },
          stop: function() {
            copyHelper && copyHelper.remove();
          },
          update: function( event, ui){
            if (ui.item.parent().hasClass('box-group')){
              return false;
            }
          }
        });
        $( ".block" , frame).sortable({
          handle: '.handle',
          receive: function(e,ui) {
            copyHelper= null;
          }
        });
      }, 'json');


      instruments(function(instruments){
        widgetsResizing(instruments, frame);
      });


      var update_height = function(){
        var h = $('body', frame).height();
        if (frame_height > 999 && (frame_height < h || frame_height > h+200) && h > 1000){
          _frame.animate({'height': h + 150});
          frame_height = h + 150;
        }

      };
      update_height();
      setTimeout(update_height, 1000);
      setTimeout(update_height, 3000);
      setTimeout(update_height, 5000);
    };

    var instruments = function(created){
      $('.instruments').remove();
      $('body').append('<div class="instruments"><div class="ins-heading"><div class="ins-hide"></div></div><div class="ins-ico"></div><div class="ins-content"></div></div>');

      var instruments = $('.instruments');
      instruments.draggable({ handle: ".ins-heading" });


      created($('.ins-content'));
    };

    var widgetsResizing = function(instruments, frame){
      instruments.prepend('<div class="widgets-resizing"><div class="smaller" title="Alt+\'-\'"></div><div class="scale"><span>100</span>%</div><div class="bigger disable" title="Alt+\'+\'"></div></div>');

      var bigger = $('.widgets-resizing .bigger');
      var smaller = $('.widgets-resizing .smaller');
      var scale = $('.widgets-resizing .scale span');
      var categories = $('.categories', frame);
      var scaleText = scale.text() * 1;

      smaller.off('click').on('click', function(){
        bigger.removeClass('disable');
        smaller.removeClass('disable');
        categories.css('width', categories.width())
        if (scaleText === 100) {
          scaleText = 75;
        } else if (scaleText === 75) {
          scaleText = 66;
        } else if (scaleText === 66) {
          scaleText = 50;
        } else if (scaleText <= 10) {
          smaller.addClass('disable');
          scaleText = 10;
        } else {
          scaleText = scaleText - 10;
          if (scaleText === 10) {
            smaller.addClass('disable');
          }
        }
        $('.box > *, .tab-navigation', frame).css({'zoom': scaleText+'%'});
        scale.text(scaleText);
        $('body', frame).addClass('sizing')
      });

      bigger.off('click').on('click', function(){
        bigger.removeClass('disable');
        smaller.removeClass('disable');
        if (scaleText >= 100) {
          scaleText = 100;
          bigger.addClass('disable');
          categories.css('width', '')
        } else if (scaleText === 75) {
          scaleText = 100;
          bigger.addClass('disable');
          $('body', frame).removeClass('sizing')
        } else if (scaleText === 66) {
          scaleText = 75;
        } else if (scaleText === 50) {
          scaleText = 66;
        } else {
          scaleText = scaleText + 10;
        }
        $('.box > *, .tab-navigation', frame).css({'zoom': scaleText+'%'});
        scale.text(scaleText);
      });

      var smallerWidgets = function(){
        smaller.trigger('click')
      };
      $(document).bind('keydown', 'Alt+-', smallerWidgets);
      $(frame).bind('keydown', 'Alt+-', smallerWidgets);

      var biggerWidgets = function(){
        bigger.trigger('click')
      };
      $(document).bind('keydown', 'Alt+=', biggerWidgets);
      $(frame).bind('keydown', 'Alt+=', biggerWidgets);
    };



    var editCss = function(event){
      var frame = event.data.frame;

      var thisBox = $(this).closest('div[id]');
      var boxId = thisBox.attr('id');
      var boxName = thisBox.data('name');
      var boxType = '';
      thisBox.closest('div[data-type]').each(function(){
        boxType = $(this).data('type')
      });

      var windowWidth = $('body', frame).width();
      var widgetWidth = thisBox.width();

      var k = 1.2;
      if (widgetWidth < 300) k = 1.7;
      var popupWidth = widgetWidth * k;
      if (popupWidth > windowWidth - 20) {
        popupWidth = windowWidth - 20;
      }
      var popupLeft = (windowWidth - popupWidth) / 2;
      var popupTop = thisBox.offset().top;

      $('body', frame).append('' +
        '<div class="popup-widget-style">' +
        '  <div class="pop-up-close"></div>' +
        '  <div class="popup-heading">Edit "' + boxName + '" widget styles</div>' +
        '  <div class="popup-content"><div class="' + thisBox.attr('class') + '"></div></div>' +
        '</div>');
      var popupWidgetStyle = $('.popup-widget-style:last', frame);
      popupWidgetStyle.css({
        left: popupLeft,
        width: popupWidth,
        top: popupTop
      });
      $('.pop-up-close', popupWidgetStyle).on('click', function(){
        popupWidgetStyle.remove()
      });
      popupWidgetStyle.draggable({ handle: ".popup-heading" });

      var box = $('.popup-content > div', popupWidgetStyle);
      box.removeClass('box');
      box.removeClass('box-active');
      var thisBoxHtml = $(thisBox.html());
      thisBoxHtml = thisBoxHtml.not('script');
      thisBoxHtml = thisBoxHtml.not('.menu-widget');
      thisBoxHtml = thisBoxHtml.not('.move-block');
      $('script', thisBoxHtml).remove();
      box.append(thisBoxHtml);

      $('input, select, textarea, img', popupWidgetStyle).each(function(){
        $(this).wrap('<div class="input-helper"></div>')
      });

      var widgetClass = box.attr('class');
      $('*:hidden', popupWidgetStyle).show();
      $('.popup-content > div *[class]:not(input, select, textarea, img, .products-listing, products-listing *)', popupWidgetStyle).each(function(){
        var elementClass = $(this).attr('class');
        if ($(this).hasClass('input-helper')) {
          elementClass = $('input, select, textarea, img', this).attr('class');
        }
        if (widgetClass && elementClass) {
          widgetClass = widgetClass.replace(/\s+/g, ".");
          elementClass = elementClass.replace(/\s+/g, ".");
          $(this)
            .addClass('edit-class')
            .attr('data-class', '.' + widgetClass + ' .' + elementClass);
        }

        if ($(this).css('display') == 'inline') {
          $(this).css({display: 'inline-block', 'vertical-align': 'top'})
        }
      });
      $('*[data-class]', popupWidgetStyle)
        .append('<span class="menu-widget"><span class="edit-box" title="Edit Block"></span></span>')
        .hover(function(){
          $(this).addClass('active')
        }, function(){
          $(this).removeClass('active')
        })
        .each(function(){
          $('.edit-box', this).attr('title', $(this).data('class'))
        });


      $('.edit-box', popupWidgetStyle).on('click', function(e){
        $('.popup-draggable').remove();

        $('body').append('<div class="popup-draggable" style="left:'+(e.pageX*1+200)+'px; top: '+(e.pageY*1+200)+'px"><div class="pop-up-close"></div><div class="preloader"></div></div>');
        var popup_draggable = $('.popup-draggable');
        popup_draggable.css({
          left: ($(window).width() - popup_draggable.width())/2,
          top: $(window).scrollTop() + 200
        });
        $('.pop-up-close').on('click', function(){
          popup_draggable.remove()
        });
        var selector = $(this).parent().parent().data('class');

        $.get('design/style-edit', {data_class: selector, theme_name: op.theme_name}, function(data){
          popup_draggable.html(data);
            saveStyles()
          $('.popup-content').prepend('<span class="popup-heading-small-text">'+selector+'</span>');
          $('.pop-up-close').on('click', function(){
            popup_draggable.remove();
            $('#dynamic-style', frame).remove()
          });
          $( ".popup-draggable" ).draggable({ handle: ".popup-heading" });

          $('#dynamic-style', frame).remove();
          $('head', frame).append('<style id="dynamic-style"></style>');
          var boxSave = $('#box-save');
          boxSave.on('change', function(){
            $.post('design/demo-styles', $(this).serializeArray(), function(data){
              $('#dynamic-style', frame).html(data);
            })
          });

          var showChanges = function(){
            $('.changed', boxSave).removeClass('changed');
            $('input, select', boxSave).each(function(){
              if ($(this).val() !== '') {
                $(this).closest('.setting-row').find('label').addClass('changed');
                var id = $(this).closest('.tab-pane').attr('id');
                $('.nav a[href="#'+id+'"]').addClass('changed');
                id = $(this).closest('.tab-pane').parents('.tab-pane').attr('id');
                $('.nav a[href="#'+id+'"]').addClass('changed');
              }
            })
          };
          showChanges();
          boxSave.on('change', showChanges);

        });


        popup_draggable.draggable(/*{ handle: "p" }*/);
      })

    };


    var main = function() {

      var body = $('body');

      if (op.clear_url) $.cookie('page-url', '');
      if ($.cookie('page-url') == undefined || $.cookie('page-url') == 'undefined' || $.cookie('page-url') == ''){
        var url = op.page_url;
        $.cookie('page-url', url);
        history[history_i] = url;
      } else {
        url = $.cookie('page-url');
      }
      op.page_url = url;

      var generalPage = false;
      $('.js-catalog_url_set li[data-href="'+$.cookie('page-url')+'"]').each(function(){
          generalPage = true;
        $('.js-catalog_url_set li').removeClass('active');
        $(this).addClass('active');

          var id = $(this).closest('.tab-pane').attr('id');
          $('.nav-tabs a[href="#'+id+'"]').trigger('click')
      });

      if (!generalPage) {
          $('.nav-tabs a[href="#any"]').trigger('click')
          $('.any-page').val(op.page_url)
      }

      $(this).html('<iframe src="' + op.page_url + '" width="100%" height="1000" frameborder="no" id="info-view"></iframe>');
      var _frame = $('#info-view');
      _frame.height($(window).height() - 150);
      _frame.on('load', function(){

        var frame = _frame.contents();
        $('body', frame).addClass('edit-blocks');

        applyBlocks();


        $('.js-catalog_url_set li').off('click').on('click', function(){
          if ($(this).hasClass('add-page')){
            var pageType = $(this).data('page-type');
            $('<a href="design/add-page"></a>').popUp({
              data: {
                theme_name: op.theme_name,
                page_type: pageType
              }
            }).trigger('click');
          } else {
            var url = $(this).data('href');
            if (!url) {
              url = op.page_url;
              url = url.replace('http:', '');
            }
            $.cookie('page-url', url);
            _frame.attr('src', url);

            $('.js-catalog_url_set li').removeClass('active');
            $(this).addClass('active')
          }
        });

        $('.any-page').on('change', function () {
            var url = $(this).val();
            if (!url) {
                url = op.page_url;
                url = url.replace('http:', '');
            }
            var url = url + (url.match(/\?/) ? '&' : '?') + 'theme_name=' + op.theme_name ;
            $.cookie('page-url', url);
            _frame.attr('src', url);
        })



        $('.btn-preview').on('click', function(){
          $('.btn-edit').show();
          $('.btn-preview').hide();
          $('body', frame).removeClass('edit-blocks');
          $('body', frame).addClass('view-blocks');
        });
        $('.btn-edit').on('click', function(){
          $('.btn-preview').show();
          $('.btn-edit').hide();
          $('body', frame).addClass('edit-blocks');
          $('body', frame).removeClass('view-blocks');
        });

        var clickPreview = function(){
          if ($('body', frame).hasClass('edit-blocks')){
            $('.btn-edit').show();
            $('.btn-preview').hide();
            $('body', frame).removeClass('edit-blocks');
            $('body', frame).addClass('view-blocks');
          } else {
            $('.btn-preview').show();
            $('.btn-edit').hide();
            $('body', frame).addClass('edit-blocks');
            $('body', frame).removeClass('view-blocks');
          }
        };
        $(document).bind('keydown', 'Alt+p', clickPreview);
        $(frame).bind('keydown', 'Alt+p', clickPreview);

        $('.btn-preview-2').on('click', function(){
          newWin = window.open($.cookie('page-url')+'&is_admin=1', "Preview", "left=0,top=0,width=1200,height=900,location=no");
        });

      });

      $(window).off('reload-frame').on('reload-frame', function(){
        if (newWin && typeof newWin.location.reload == 'function') {
          newWin.location.reload($.cookie('page-url')+'&is_admin=1');
        }
        $('.popup-box-wrap').trigger('remove').remove();

        var _frame = $('#info-view');
        _frame.parent().css('position', 'relative');
        _frame.attr('id', 'info-view-1');
        _frame.css({
          'position': 'relative',
          'z-index': 2
        });
        _frame.after('<iframe src="' + $.cookie('page-url') + '" width="100%" height="'+_frame.height()+'" frameborder="no" id="info-view"></iframe>');
        var _frame_new = $('#info-view');
        _frame_new.css({
          'position': 'absolute',
          'left': '0',
          'top': '0'
        });
        _frame_new.on('load', function(){
          var frame = _frame_new.contents();
          $('body', frame).addClass('edit-blocks');
          applyBlocks();
          setTimeout(function(){
            _frame.remove();
            _frame_new.css({
              'position': 'relative'
            });
          }, 100);


          $('.js-catalog_url_set li').off('click').on('click', function(){
            if ($(this).hasClass('add-page')){
              var pageType = $(this).data('page-type');
              $('<a href="design/add-page"></a>').popUp({
                data: {
                  theme_name: op.theme_name,
                  page_type: pageType
                }
              }).trigger('click');
            } else {
              var url = $(this).data('href');
              if (!url) {
                url = op.page_url;
                url = url.replace('http:', '');
                url = url.replace('https:', '');
              }
              $.cookie('page-url', url);
              _frame_new.attr('src', url);

              $('.js-catalog_url_set li').removeClass('active');
              $(this).addClass('active')
            }
          });
        });
      })
    };

    return this.each(main)
  };

})(jQuery);

function sortWidgets(widgetA, widgetB) {
      if (widgetA.title.toLowerCase() > widgetB.title.toLowerCase()) {
          return 1
      } else {
          return -1
      }
}



function saveStyles () {

    var boxSave = $('#box-save');


    window.boxInputChanges = {};

    boxSave.on('change blur click keyup', 'input, select, textarea', function(){
        if ($(this).attr('type') == 'checkbox' && !$(this).is(':checked')) {
            window.boxInputChanges[$(this).attr('name')] = '';
        } else if ($(this).attr('type') == 'checkbox' && $(this).is(':checked')) {
            window.boxInputChanges[$(this).attr('name')] = 1;
        }else {
            window.boxInputChanges[$(this).attr('name')] = $(this).val();
        }
    });



    boxSave.on('submit', function(){

        window.boxInputChanges['id'] = $('input[name="id"]', this).val();
        var params = $('input[name="params"], select[name="params"]', this).val();
        if (params) {
            window.boxInputChanges['params'] = params;
        }

        var values = [];
        $.each( window.boxInputChanges, function(name, value) {
            values = values.concat({ "name": name, "value": value});
        });

        values = values.concat(
            $('.visibility input[disabled]', this).map(function() {
                return { "name": this.name, "value": 1}
            }).get()
        );

        $('.check_on_off').each(function(){
            values = values.concat({ "name": $(this).attr('name'), "value": $(this).prop( "checked" )});
        });

        var data = values.reduce(function(obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});

        $.post('design/style-save', {
            'values': JSON.stringify(data),
            'theme_name': $('input[name="theme_name"]', this).val(),
            'data_class': $('input[name="data_class"]', this).val()
        }, function(){
            $(window).trigger('reload-frame')
        });
        $('.popup-draggable').remove();
        return false
    });

};