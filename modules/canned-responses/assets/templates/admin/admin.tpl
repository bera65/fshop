{if $flash}
<div class="alert alert-info">{$flash|escape}</div>
{/if}

<div class="row">
	<div class="col-md-4">
		<div class="card">
			<div class="card-header">
				{if $editResponse}
					{'Hazır Cevap Düzenle'|adminT}
				{else}
					{'Yeni Hazır Cevap Ekle'|adminT}
				{/if}
			</div>
			<div class="card-body">
				<form method="post" action="{$adminUrl}module-canned-responses">
					<input type="hidden" name="saveResponse" value="1">
					<input type="hidden" name="token" value="{$adminToken}">
					{if $editResponse}
						<input type="hidden" name="id_canned_response" value="{$editResponse.id_canned_response}">
					{/if}

					<div class="mb-3">
						<label class="form-label">{'Başlık (Kısa İsim)'|adminT}</label>
						<input type="text" name="title" class="form-control" value="{$editResponse.title|default:''|escape}" required>
					</div>

					<div class="mb-3">
						<label class="form-label">{'Mesaj İçeriği'|adminT}</label>
						<textarea name="message" class="form-control" rows="5" required>{$editResponse.message|default:''|escape}</textarea>
					</div>

					<div class="mb-3">
						<label class="form-label">{'Sıra'|adminT}</label>
						<input type="number" name="position" class="form-control" value="{$editResponse.position|default:'0'|escape}">
					</div>

					<button type="submit" class="btn btn-primary">{'Kaydet'|adminT}</button>
					{if $editResponse}
						<a href="{$adminUrl}module-canned-responses" class="btn btn-secondary">{'İptal'|adminT}</a>
					{/if}
				</form>
			</div>
		</div>
	</div>
	
	<div class="col-md-8">
		<div class="card">
			<div class="card-header">{'Kayıtlı Hazır Cevaplar'|adminT}</div>
			<div class="table-responsive">
				<table class="table table-hover mb-0">
					<thead>
						<tr>
							<th width="40">ID</th>
							<th>{'Başlık'|adminT}</th>
							<th>{'İçerik (Özet)'|adminT}</th>
							<th width="80">{'Sıra'|adminT}</th>
							<th width="120" class="text-end">{'İşlem'|adminT}</th>
						</tr>
					</thead>
					<tbody>
						{foreach $responses as $res}
						<tr>
							<td>{$res.id_canned_response}</td>
							<td><strong>{$res.title|escape}</strong></td>
							<td><small class="text-muted">{$res.message|truncate:50|escape}</small></td>
							<td>{$res.position}</td>
							<td class="text-end">
								<a href="{$adminUrl}module-canned-responses?edit={$res.id_canned_response}" class="btn btn-sm btn-outline-primary">
									{'Düzenle'|adminT}
								</a>
								<form method="post" action="{$adminUrl}module-canned-responses" class="d-inline-block" data-confirm-message="{'Silmek istediğinize emin misiniz?'|adminT}">
									<input type="hidden" name="deleteResponse" value="1">
									<input type="hidden" name="token" value="{$adminToken}">
									<input type="hidden" name="id_canned_response" value="{$res.id_canned_response}">
									<button type="submit" class="btn btn-sm btn-outline-danger">{'Sil'|adminT}</button>
								</form>
							</td>
						</tr>
						{foreachelse}
						<tr>
							<td colspan="5" class="text-center text-muted py-3">{'Henüz hazır cevap eklenmemiş.'|adminT}</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
