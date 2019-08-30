<?php

namespace MauticPlugin\DcodeMauticUtilsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;

class TrackingController extends CommonController {

	public function clearTrackingCookiesAction() {
		$deviceTrackingService = $this->get('mautic.lead.service.device_tracking_service');
		$deviceTrackingService->clearTrackingCookies();
	}
}
