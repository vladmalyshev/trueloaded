<div class="w-chat-box">
	<a href="javascript:void(0);" onclick="toggleChat()"></a>
</div>
<script>
    var openChat = function(){
        {$script}

		var waitChat = setInterval(function(){
		    if (typeof $_Tawk !== "undefined") {
		        setTimeout(function(){
                    $_Tawk.toggle();
				}, 200);
				clearInterval(waitChat)
            }
		}, 200);
	};

    function toggleChat() {
        openChat();
        openChat = function(){ };
        if (typeof $_Tawk !== "undefined") {
            $_Tawk.toggle();
        }
        return false;
    }
</script>