{foreach $tabs as $tab}
<li class="nav-item" role="presentation">
	<button class="nav-link"
			data-bs-toggle="tab"
			data-bs-target="#{$tab.slug|escape}"
			type="button"
			role="tab">
		{$tab.title|escape}
	</button>
</li>
{/foreach}
