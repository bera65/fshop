$(document).ready(function(){
	$('#addComment').on('click', function() {
		var rating 		= $('#ratingValue').val();
		var site 		= $('#site').val();
		var orderNumber = $('#orderNumber').val();
		var comment 	= $('#comment').val();
		var secureKey 	= $('#secureKey').val();
		$.ajax({
			url: '', // backend url
			type: 'POST',
			data: {
				rating: rating,
				site: site,
				orderNumber: orderNumber,
				secureKey: secureKey,
				comment: comment
			},
			success: function (res) {
				if (res.success) {
					$('#addCommentModal').modal('hide');
					$('#toastMessages').html(res.message);
					var toastEl = document.getElementById('notiToast');
					if (toastEl) {
						var toast = new bootstrap.Toast(toastEl, {
							autohide: true,
							delay: 5000
						});
						toast.show();
					}
				} else {
					//$('#addCommentModal').modal('hide');
					$('#toastMessages').html(res.message);
					var toastEl = document.getElementById('notiToast');
					if (toastEl) {
						var toast = new bootstrap.Toast(toastEl, {
							autohide: true,
							delay: 5000
						});
						toast.show();
					}
				}
			},
			error: function () {
				var toast = new bootstrap.Toast(document.getElementById('dangerToast'));
				$('#toastBodyDanger').html('Yorum kaydedilemedi.');
				toast.show();
			}
		});
	});
});
document.addEventListener("DOMContentLoaded", function() {

    const stars = document.querySelectorAll(".rating-input .star");
    const ratingInput = document.getElementById("ratingValue");
    let selectedValue = 0;

    stars.forEach(star => {

        // Hover
        star.addEventListener("mouseover", function() {
            const value = this.dataset.value;

            stars.forEach(s => {
                s.classList.toggle("hovered", s.dataset.value <= value);
            });
        });

        // Hover kalkınca
        star.addEventListener("mouseout", function() {
            stars.forEach(s => s.classList.remove("hovered"));
        });

        // Tıklama → seçili yıldızlar
        star.addEventListener("click", function() {
            selectedValue = this.dataset.value;
            ratingInput.value = selectedValue;

            stars.forEach(s => {
                s.classList.toggle("selected", s.dataset.value <= selectedValue);
            });
        });

    });

});

function goTo(el) {
	let ref     = el.dataset.ref;
	let key     = el.dataset.key;
	let product = el.dataset.product;
	let site    = el.dataset.site;

	let url = "/link?ref=" + ref + "&key=" + key + "&product=" + product + "&site=" + site;

	setTimeout(() => {
		window.open(url, '_blank', 'noopener,noreferrer');
	}, 150);
}