{if $schemaJsonLd|@count}
{foreach $schemaJsonLd as $schemaBlock}
{if $schemaBlock}
<script type="application/ld+json">{$schemaBlock nofilter}</script>
{/if}
{/foreach}
{/if}
