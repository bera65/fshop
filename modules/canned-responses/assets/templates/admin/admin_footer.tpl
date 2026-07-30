<script>
(function() {
	var textarea = document.querySelector('textarea[name="reply"]');
	if (!textarea) return;

	// Hazır cevap verilerini JSON olarak script içinde tutalım
	var cannedResponses = {$cannedResponses|@json_encode nofilter};

	// Dropdown veya buton grubu oluştur
	var wrapper = document.createElement('div');
	wrapper.className = 'mb-2 d-flex gap-2';
	
	var select = document.createElement('select');
	select.className = 'form-select form-select-sm w-auto';
	
	var defaultOption = document.createElement('option');
	defaultOption.value = '';
	defaultOption.textContent = '{'-- Hazır Cevap Seçin --'|adminT}';
	select.appendChild(defaultOption);

	cannedResponses.forEach(function(item) {
		var option = document.createElement('option');
		option.value = item.id_canned_response;
		option.textContent = item.title;
		select.appendChild(option);
	});

	var btn = document.createElement('button');
	btn.type = 'button';
	btn.className = 'btn btn-sm btn-outline-secondary text-nowrap';
	btn.textContent = '{'Yeni Ekle'|adminT}';
	
	// Yeni cevap ekleme sayfasına yönlendir (modül ayar sayfası)
	btn.addEventListener('click', function() {
		window.open('{$adminUrl}module-canned-responses', '_blank');
	});

	wrapper.appendChild(select);
	wrapper.appendChild(btn);

	// Textarea'nın hemen üstüne ekleyelim
	textarea.parentNode.insertBefore(wrapper, textarea);

	// Select değiştiğinde direkt ekle
	select.addEventListener('change', function() {
		var selectedId = select.value;
		if (!selectedId) return;

		var selectedItem = cannedResponses.find(function(item) {
			return item.id_canned_response == selectedId;
		});

		if (selectedItem) {
			var startPos = textarea.selectionStart;
			var endPos = textarea.selectionEnd;
			var text = textarea.value;

			var prefix = (text.length > 0 && startPos === endPos && startPos === text.length) ? '\n\n' : '';
			
			textarea.value = text.substring(0, startPos) + prefix + selectedItem.message + text.substring(endPos);
			
			textarea.focus();
			textarea.selectionStart = startPos + prefix.length + selectedItem.message.length;
			textarea.selectionEnd = textarea.selectionStart;
			
			select.value = ''; // Seçimi sıfırla ki aynı cevabı tekrar seçebilsin
		}
	});
})();
</script>
