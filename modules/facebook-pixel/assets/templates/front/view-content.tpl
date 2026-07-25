<script>
(function () {
	var eventData = {$eventJson nofilter};
	if (typeof fbq !== 'function' || !eventData || !eventData.name) return;
	fbq('track', eventData.name, eventData.payload || {});
})();
</script>
