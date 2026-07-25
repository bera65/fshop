{foreach $tabs as $tab}
<div class="tab-pane fade pct-tab-pane" id="{$tab.slug|escape}" role="tabpanel">
	<div class="pct-tab-content">
		{$tab.content nofilter}
	</div>
</div>
{/foreach}
