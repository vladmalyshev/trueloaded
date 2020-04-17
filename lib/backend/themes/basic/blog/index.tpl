<div class="" style="margin: -20px">
  <iframe src="{$url}" frameborder="0" id="wp_blog" width="100%" scrolling="no"></iframe>
</div>

<script type="text/javascript">
  (function($){
    $(function(){

      var intervalID;
      var _frame = $('#wp_blog');

      if ($.cookie('blog_url')) _frame.attr('src', $.cookie('blog_url'));

      _frame.on('load', function() {
        var frame = _frame.contents();
        var wpwrap = $('#wpwrap', frame);

        $.cookie('blog_url', document.getElementById("wp_blog").contentWindow.location.href);

        var height = 0;
        var frame_height = function () {
          var h = wpwrap.height();
          if (h < 700) h = 700;
          if (height != h) {
              _frame.animate({
                  'height': h
              });
              height = h;
          }
        };
        frame_height();

        if (intervalID) clearInterval(intervalID);
        intervalID = setInterval(frame_height, 1000);
      })


    })
  })(jQuery)
</script>