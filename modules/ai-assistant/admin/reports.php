<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!Module::isEnabled('ai-assistant')) {
	http_response_code(404);
	AdminPage::add('404', 'Modül aktif değil');

	return;
}

$module = Module::loadInstance('ai-assistant', false);

if (!$module) {
	http_response_code(404);
	AdminPage::add('404', 'Modül bulunamadı');

	return;
}

$module->renderReportsAdminPage();
