{if $flash}
<div class="alert alert-info py-2">{$flash|escape}</div>
{/if}

<div class="admin-toolbar d-flex flex-wrap gap-2 mb-3">
	<a href="{$adminUrl}module-question?filter=pending" class="btn btn-sm {if $filter == 'pending'}btn-dark{else}btn-outline-dark{/if}">
		Cevap Bekleyen ({$pendingCount})
	</a>
	<a href="{$adminUrl}module-question?filter=answered" class="btn btn-sm {if $filter == 'answered'}btn-dark{else}btn-outline-dark{/if}">Cevaplanmış</a>
	<a href="{$adminUrl}module-question?filter=all" class="btn btn-sm {if $filter == 'all'}btn-dark{else}btn-outline-dark{/if}">Tümü</a>
</div>

<div class="admin-panel">
	{foreach $questions as $row}
	<div class="border rounded p-3 mb-3">
		<div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
			<div>
				<strong>{$row.product_name|escape}</strong>
				<span class="text-muted mx-2">·</span>
				<span>{$row.author_name|escape}</span>
				<span class="text-muted small ms-2">{$row.date_formatted|escape}</span>
			</div>
			<div>
				{if $row.answer && $row.active}
				<span class="badge bg-success">Yayında</span>
				{elseif $row.answer}
				<span class="badge bg-secondary">Gizli</span>
				{else}
				<span class="badge bg-warning text-dark">Cevap bekliyor</span>
				{/if}
			</div>
		</div>

		<div class="mb-2">
			<div class="small text-muted mb-1">Soru</div>
			<div>{$row.question|escape|nl2br nofilter}</div>
		</div>

		<form method="post" class="mb-2">
			<input type="hidden" name="questionAction" value="1">
			<input type="hidden" name="token" value="{$adminToken}">
			<input type="hidden" name="id_question" value="{$row.id_question}">
			<label class="form-label small mb-1">Cevap</label>
			<textarea name="answer" class="form-control form-control-sm mb-2" rows="3" required minlength="3">{$row.answer|escape}</textarea>
			<div class="d-flex flex-wrap gap-2">
				<button type="submit" name="action" value="answer" class="btn btn-sm btn-dark">Kaydet ve Yayınla</button>
				{if $row.answer && $row.active}
				<button type="submit" name="action" value="hide" class="btn btn-sm btn-outline-secondary">Gizle</button>
				{elseif $row.answer}
				<button type="submit" name="action" value="publish" class="btn btn-sm btn-outline-dark">Yayınla</button>
				{/if}
				<button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger js-admin-confirm" data-confirm-message="Silinsin mi?">Sil</button>
			</div>
		</form>
	</div>
	{foreachelse}
	<p class="text-muted mb-0">Kayıt yok.</p>
	{/foreach}
</div>

{if $pagination && $pagination.total_pages > 1}
{include file='default/plugin/pagination.tpl'}
{/if}
