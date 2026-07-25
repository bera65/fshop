{include file='admin/marketplace/_nav.tpl'}

<div class="admin-panel p-3 mb-3">
	<div class="alert alert-light border small mb-3">
		Cron: <code class="user-select-all">{$cronQuestionsUrl|escape}</code>
	</div>
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h2 class="h5 mb-0">Trendyol ürün soruları</h2>
		<form method="post" class="m-0">
			<input type="hidden" name="syncTrendyolQuestions" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<button type="submit" class="btn btn-dark btn-sm">Soruları Çek</button>
		</form>
	</div>

	{if !$tyQuestions|@count}
	<p class="text-muted small mb-0">Henüz soru yok.</p>
	{else}
	<div class="vstack gap-3">
		{foreach $tyQuestions as $q}
		<div class="border rounded p-3">
			<div class="d-flex justify-content-between gap-2 mb-1">
				<strong class="small">{$q.product_name|escape}</strong>
				<span class="badge {if $q.answered}text-bg-success{else}text-bg-warning{/if}">{$q.status|escape}</span>
			</div>
			<p class="mb-2 small">{$q.question_text|escape}</p>
			{if $q.answered && $q.answer_text}
			<p class="mb-0 small text-success"><strong>Cevap:</strong> {$q.answer_text|escape}</p>
			{else}
			<form method="post" class="mt-2">
				<input type="hidden" name="answerTrendyolQuestion" value="1">
				<input type="hidden" name="token" value="{$adminToken}">
				<input type="hidden" name="question_id" value="{$q.question_id}">
				<textarea name="answer_text" class="form-control form-control-sm mb-2" rows="2" placeholder="Cevabınız…" required></textarea>
				<button type="submit" class="btn btn-sm btn-primary">Cevapla</button>
			</form>
			{/if}
			<div class="text-muted small mt-1">#{$q.question_id} · {$q.question_date|escape}</div>
		</div>
		{/foreach}
	</div>
	{/if}
</div>

{include file='admin/marketplace/_close.tpl'}
