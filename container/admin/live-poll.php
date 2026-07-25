<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store, no-cache, must-revalidate');

	if (!Admin::isLoggedIn()) {
		http_response_code(401);
		echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
		exit;
	}

	$ordersPending = Order::countAdmin(Order::STATUS_PENDING) + Order::countAdmin(Order::STATUS_PROCESSING);
	$returnsPending = class_exists('ReturnRequest', false) ? ReturnRequest::countPending() : 0;
	$cancellationsPending = class_exists('CancelRequest', false) ? CancelRequest::countPending() : 0;
	$messagesUnread = Contact::countUnread();
	$notificationsUnread = AdminNotification::countUnread();

	$latestOrderId = (int) DB::getValue('SELECT MAX(id_order) FROM orders');
	$latestMessageId = 0;
	$msgTable = DB::execute("SHOW TABLES LIKE 'contact_messages'");
	if (!empty($msgTable)) {
		$latestMessageId = (int) DB::getValue('SELECT MAX(id_message) FROM contact_messages');
	}
	$latestNotificationId = 0;
	$notifTable = DB::execute("SHOW TABLES LIKE 'admin_notifications'");
	if (!empty($notifTable)) {
		$latestNotificationId = (int) DB::getValue('SELECT MAX(id_notification) FROM admin_notifications');
	}

	$latestOrderRef = '';
	if ($latestOrderId > 0) {
		$latestOrderRef = (string) DB::getValue(
			'SELECT reference FROM orders WHERE id_order = ?',
			[$latestOrderId]
		);
	}

	echo json_encode([
		'success' => true,
		'badges' => [
			'orders' => $ordersPending,
			'returns' => $returnsPending,
			'cancellations' => $cancellationsPending,
			'messages' => $messagesUnread,
			'notifications' => $notificationsUnread,
		],
		'latest' => [
			'order_id' => $latestOrderId,
			'order_ref' => $latestOrderRef,
			'message_id' => $latestMessageId,
			'notification_id' => $latestNotificationId,
		],
		'ts' => time(),
	], JSON_UNESCAPED_UNICODE);
	exit;
