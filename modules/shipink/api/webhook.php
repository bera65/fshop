<?php

	if (!defined('IN_SCRIPT')) {
		exit;
	}

	header('Content-Type: application/json; charset=utf-8');

	$provided = trim((string) Tools::getValue('token'));
	$stored = trim((string) Settings::get('SHIPINK_LINK_TOKEN'));

	if ($stored === '' || !hash_equals($stored, $provided)) {
		http_response_code(401);
		echo json_encode(['success' => false, 'message' => 'Token gerekli']);
		exit;
	}

	$rawBody = file_get_contents('php://input');
	$data = json_decode((string) $rawBody, true);

	if (!is_array($data)) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Geçersiz JSON']);
		exit;
	}

	$payload = is_array($data['data'] ?? null) ? $data['data'] : $data;
	$statusRaw = strtolower(trim((string) ($payload['status'] ?? $payload['tracking']['status'] ?? $data['event'] ?? '')));
	$trackingNumber = trim((string) (
		$payload['carrier']['shipment_id']
		?? $payload['tracking_number']
		?? $payload['tracking']['code']
		?? $payload['shipment_id']
		?? ''
	));
	$shipmentId = trim((string) ($payload['id'] ?? $payload['shipment_id'] ?? ''));
	$shipinkOrderId = trim((string) ($payload['order_id'] ?? ''));
	$carrierName = trim((string) ($payload['carrier']['carrier_id'] ?? $payload['carrier'] ?? ''));
	$trackingUrl = trim((string) ($payload['carrier']['tracking_url'] ?? $payload['tracking_url'] ?? ''));

	$fshopOrderId = 0;

	if ($shipmentId !== '') {
		$fshopOrderId = (int) DB::getValue('SELECT id_order FROM shipink WHERE shipment_id = ? LIMIT 1', [$shipmentId]);
	}

	if ($fshopOrderId <= 0 && $trackingNumber !== '') {
		$fshopOrderId = (int) DB::getValue('SELECT id_order FROM shipink WHERE tracking_number = ? LIMIT 1', [$trackingNumber]);
	}

	if ($fshopOrderId <= 0 && $shipinkOrderId !== '') {
		$fshopOrderId = (int) DB::getValue('SELECT id_order FROM shipink WHERE shipink_order_id = ? LIMIT 1', [$shipinkOrderId]);
	}

	$salesOrderId = (int) ($payload['sales_channel']['order_id'] ?? $payload['order']['sales_channel']['order_id'] ?? 0);

	if ($fshopOrderId <= 0 && $salesOrderId > 0) {
		$fshopOrderId = (int) DB::getValue('SELECT id_order FROM orders WHERE id_order = ? LIMIT 1', [$salesOrderId]);
	}

	if ($fshopOrderId <= 0) {
		echo json_encode(['success' => false, 'message' => 'Sipariş eşleşmedi']);
		exit;
	}

	$statusMap = [
		'created' => Order::STATUS_PROCESSING,
		'ready_to_ship' => Order::STATUS_PROCESSING,
		'ready-to-ship' => Order::STATUS_PROCESSING,
		'label_created' => Order::STATUS_PROCESSING,
		'shipped' => Order::STATUS_SHIPPED,
		'in_transit' => Order::STATUS_SHIPPED,
		'in-transit' => Order::STATUS_SHIPPED,
		'out_for_delivery' => Order::STATUS_SHIPPED,
		'delivered' => Order::STATUS_DELIVERED,
		'returned' => Order::STATUS_RETURNED,
		'returning' => Order::STATUS_SHIPPED,
		'cancelled' => Order::STATUS_CANCELLED,
		'canceled' => Order::STATUS_CANCELLED,
	];

	$statusId = $statusMap[$statusRaw] ?? 0;
	$update = [];

	if ($statusId > 0) {
		$update['status'] = $statusId;
	}

	if ($carrierName !== '') {
		$update['cargo_company'] = mb_substr($carrierName, 0, 64);
	}

	if ($trackingNumber !== '') {
		$update['tracking_number'] = mb_substr($trackingNumber, 0, 64);
	}

	if ($update !== []) {
		DB::update('orders', $update, 'id_order = :id_order', ['id_order' => $fshopOrderId]);
	}

	$shipinkUpdate = [];

	if ($trackingNumber !== '') {
		$shipinkUpdate['tracking_number'] = mb_substr($trackingNumber, 0, 128);
	}

	if ($trackingUrl !== '') {
		$shipinkUpdate['tracking_url'] = mb_substr($trackingUrl, 0, 512);
	}

	if ($carrierName !== '') {
		$shipinkUpdate['carrier'] = mb_substr($carrierName, 0, 128);
	}

	if ($shipmentId !== '') {
		$shipinkUpdate['shipment_id'] = mb_substr($shipmentId, 0, 64);
	}

	if ($shipinkUpdate !== []) {
		$exists = (int) DB::getValue('SELECT id FROM shipink WHERE id_order = ? LIMIT 1', [$fshopOrderId]);

		if ($exists > 0) {
			DB::update('shipink', $shipinkUpdate, 'id_order = :id_order', ['id_order' => $fshopOrderId]);
		}
	}

	echo json_encode([
		'success' => true,
		'message' => 'Güncellendi',
		'id_order' => $fshopOrderId,
	], JSON_UNESCAPED_UNICODE);
