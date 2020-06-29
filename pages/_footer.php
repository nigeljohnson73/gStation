
<!--
  _____           _
 |  ___|__   ___ | |_ ___ _ __
 | |_ / _ \ / _ \| __/ _ \ '__|
 |  _| (_) | (_) | ||  __/ |
 |_|  \___/ \___/ \__\___|_|

-->
<footer class="text-center" data-ng-controller="FooterCtrl">
	<div>
		<div class="container-fluid text-center">
			<a target="tribalRhino" href="https://tribalrhino.com/"><img
				src="/gfx/poweredby.png" alt="Powered by Tribal Rhino"
				style="width: 150px;" /></a>
		</div>

		<p class="visible-xs">
			&copy; 2009 - {{nowDate | date : 'yyyy'}} Nigel Johnson<br />all
			rights reserved
		</p>
		<p class="hidden-xs">&copy; 2009 - {{nowDate | date : 'yyyy'}} Nigel
			Johnson, all rights reserved.</p>

		<div
			style="position: fixed; bottom: 10px; right: 20px; font-size: 5pt; color: #ccc;">
			<span class="visible-xs size-indicator">XS</span> <span
				class="visible-sm size-indicator">SM</span> <span
				class="visible-md size-indicator">MD</span> <span
				class="visible-lg size-indicator">LG</span>
		</div>
	</div>
</footer>
<script>
$(document).ready(function() {
	// Hide protected images
	$('.covered').each(function() {

		$(this).append('<cover></cover>');
		$(this).mousedown(function(e) {
			if (e.button == 2) {
				e.preventDefault();
				return false;
			}
			return true;
		});

		$('img', this).css('display', 'block');

		$(this).hover(function() {
			var el = $('cover', this);
			if (el.length <= 0) {
				$(this).html('');
			}
		});
	});

	// get current URL path and assign 'active' class
	var pathname = window.location.pathname;
	logger("Setting path '" + pathname + "' as Active");
	$('.nav > li > a[href="'+pathname+'"]').parent().addClass('active');

//	toast("Welcome to the dark side");
});

</script>