<?php

namespace MauticPlugin\DcodeMauticUtilsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TrackingController extends CommonController {

	public function clearTrackingCookiesAction(Request $request) {
		$deviceTrackingService = $this->get('mautic.lead.service.device_tracking_service');
		$response = new JsonResponse(array('success' => true));

		try {
			$deviceTrackingService->clearTrackingCookies();
		} catch (\Exception $e) {
			$response = new JsonResponse(array('success' => false, 'error' => 'Couldn\'t remove cookies!'));
		}
		return $response;
	}
}
