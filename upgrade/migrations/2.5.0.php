<?php

return new class {
	public string $version = '2.5.0';
	public string $title = '2.5.0 temel şema köprüsü (2.4 → 2.5)';

	public function up(): void
	{
		// Schema::forceEnsure() upgrade runner tarafından çağrılır.
		// Bu adım FS_VERSION işaretini ilerletir; şema idempotent tamamlanır.
		if (class_exists('Cargo', false)) {
			Cargo::ensureSchema();
		}

		if (class_exists('Address', false)) {
			Address::ensureSchema();
		}

		if (class_exists('CartPromotion', false)) {
			CartPromotion::ensureSchema();
		}
	}
};
