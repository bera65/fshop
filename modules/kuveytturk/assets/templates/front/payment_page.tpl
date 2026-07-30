<link rel="stylesheet" href="{$domain}modules/kuveytturk/assets/css/kuveytturk.css">

<div class="container py-5">
    <div class="kuveytturk-card-container">

        <div class="kuveytturk-header">
            <span class="kuveytturk-header-title">Ödeme yöntemi</span>
            <span class="kuveytturk-header-subtitle">Kredi/Banka Kartı</span>
        </div>

        {if $paymentError}
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> {$paymentError|escape}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
        {/if}

        <form action="" method="POST" id="kuveytturk-card-form">

            <div class="mb-4">
                <label class="kuveytturk-label">Kart Üzerindeki Ad Soyad:</label>
                <input type="text" name="name" class="kuveytturk-input" autocomplete="cc-name" required>
            </div>

            <div class="mb-4">
                <label class="kuveytturk-label">Kart Numarası:</label>
                <div class="kuveytturk-input-icon-wrapper">
                    <input type="text" name="cardNo" id="kuveytturk_card_no" class="kuveytturk-input" placeholder="•••• •••• •••• ••••" maxlength="19" autocomplete="cc-number" required>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-8">
                    <label class="kuveytturk-label">Son Kullanma Tarihi:</label>
                    <div class="d-flex gap-2">
                        <select name="mm" class="kuveytturk-input" required>
                            <option value="">AA</option>
                            {foreach $months as $m}
                                <option value="{$m}">{$m}</option>
                            {/foreach}
                        </select>
                        <select name="yy" class="kuveytturk-input" required>
                            <option value="">YY</option>
                            {foreach $years as $y}
                                <option value="{$y.short}">{$y.short}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="col-4">
                    <label class="kuveytturk-label">CVC/CVV:</label>
                    <input type="text" name="cvv" class="kuveytturk-input" placeholder="CVV" maxlength="4" autocomplete="cc-csc" required>
                </div>
            </div>

            <button type="submit" name="submitKuveytturk" class="kuveytturk-btn-submit">
                ₺{$order.total|number_format:2:',':'.'} Ödeme Yap
            </button>

            <div class="kuveytturk-card-logos d-flex justify-content-center">
				<img src="{$domain}modules/kuveytturk/assets/img/payment.png" alt="Payment" height="48px" />
			</div>

            <p class="kuveytturk-footer-note">
                Güvenli Ortak Ödeme Sayfamız üzerinden tüm banka kartları ile ödeme yapabilirsiniz. Ödeme yaptığınız bu ekran, online satış işlemlerinizin KuveytTürk güvencesiyle gerçekleştirilmesini sağlar.
            </p>

        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var cardInput = document.getElementById('kuveytturk_card_no');
    if (cardInput) {
        cardInput.addEventListener('input', function(e) {
            var value = e.target.value.replace(/\D/g, '');
            var formatted = '';
            for (var i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += ' ';
                }
                formatted += value[i];
            }
            e.target.value = formatted.substring(0, 19);
        });
    }
});
</script>
