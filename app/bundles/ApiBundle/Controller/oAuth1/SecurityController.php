<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Controller\oAuth1;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception as Exception;
use Symfony\Component\Security\Core\SecurityContext;

class SecurityController extends CommonController
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        //get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }
        if (!empty($error)) {
            if (($error instanceof Exception\BadCredentialsException)) {
                $msg = 'mautic.user.auth.error.invalidlogin';
            } else {
                $msg = $error->getMessage();
            }

            $this->addFlash($msg, [], 'error', null, false);
        }

        return $this->render(
            'MauticApiBundle:Security:login.html.php',
            [
                'last_username' => $session->get(SecurityContext::LAST_USERNAME),
                'route'         => 'mautic_oauth1_server_auth_login_check',
                'whitelabelBrandingName' => $this->coreParametersHelper->getParameter('whitelabel_branding_name','Mautic'),
                'whitelabelBrandingVersion' => $this->coreParametersHelper->getParameter('whitelabel_branding_version',MAUTIC_VERSION),
                'whitelabelBrandingCopyright' => $this->coreParametersHelper->getParameter('whitelabel_branding_copyright', 'Mautic '.MAUTIC_VERSION),
                'whitelabelBrandingFavicon' => $this->coreParametersHelper->getParameter('whitelabel_branding_favicon', 'media/images/favicon.ico'),       
                'whitelabelBrandingAppleFavicon' => $this->coreParametersHelper->getParameter('whitelabel_branding_apple_favicon', 'media/images/apple-touch-icon.png'),
                'whitelabelBrandingLogo' => $this->coreParametersHelper->getParameter('whitelabel_branding_logo', 'media/images/apple-touch-icon.png'),
                'whitelabelBrandingLeftLogo' => $this->coreParametersHelper->getParameter('whitelabel_branding_left_logo', 'media/images/apple-touch-icon.png')
            ]
        );
    }

    /**
     * @return Response
     */
    public function loginCheckAction()
    {
        return new Response('', 400);
    }
}
