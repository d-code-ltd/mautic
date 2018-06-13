<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title><?php echo $view['slots']->get('pageTitle', 'Kiazaki'); ?></title>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('media/images/favicon.ico') ?>" />
    <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('media/images/apple-touch-icon.png') ?>" />
    <?php $view['assets']->outputSystemStylesheets(); ?>
    <?php echo $view->render('MauticCoreBundle:Default:script.html.php'); ?>
    <?php $view['assets']->outputHeadDeclarations(); ?>
</head>
<body>
<section id="main" role="main">
    <div class="container" style="margin-top:100px;">
        <div class="row">
            <div class="col-lg-4 col-lg-offset-4">
                <div class="panel" name="form-login">
                    <div class="panel-body">
                        <div class="mautic-logo img-circle mb-md text-center">
                            <svg width="100%" height="100%" viewBox="0 0 200 200" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:1.5;">
                                <g transform="matrix(0.39177,0,0,0.39177,-49.1512,-53.7944)">
                                    <g transform="matrix(1,0,0,1,-134.129,-21.9141)">
                                        <g transform="matrix(0.109753,0,0,0.109753,327.541,154.198)">
                                            <path d="M1694.85,66.359C815.382,66.359 99.539,782.202 99.539,1661.67C99.539,1920.73 167.715,2179.8 290.43,2411.6L338.153,2500.22L1544.86,4586.39C1592.58,4668.2 1701.66,4695.47 1783.47,4647.75C1810.74,4634.12 1831.2,4613.66 1844.83,4586.39L3051.54,2500.22L3099.26,2418.41C3515.13,1641.21 3221.98,673.121 2451.59,257.251C2219.8,134.535 1960.73,66.359 1694.85,66.359ZM1692.78,129.53C2540.2,129.53 3228.21,817.535 3228.21,1664.96C3228.21,2512.39 2540.2,3200.4 1692.78,3200.4C845.347,3200.4 157.343,2512.39 157.343,1664.96C157.343,817.535 845.347,129.53 1692.78,129.53Z"/>
                                        </g>
                                        <g transform="matrix(4.32885,0,0,4.32885,217.527,146.509)">
                                            <circle cx="68.229" cy="43.776" r="37.097" style="fill:rgb(255,212,0);stroke:black;stroke-width:0.44px;"/>
                                        </g>
                                    </g>
                                    <g transform="matrix(1.27677,0,0,1.27677,72.2394,-184.168)">
                                        <path d="M294.058,465.405C318.746,473.017 354.075,464.593 372.839,451.632C385.801,442.678 397.273,422.799 400.154,401.78C403.733,375.67 395.883,347.952 382.631,338.999C382.631,338.999 385.747,329.207 384.856,324.757C383.966,320.305 378.18,317.635 372.839,318.08C367.498,318.525 365.718,325.647 359.931,326.537C354.145,327.427 332.336,315.41 321.654,310.068C310.971,304.727 296.283,299.386 279.37,309.623C262.457,319.861 270.023,337.219 270.023,337.219C270.023,337.219 246.878,349.236 243.763,393.3C239.805,449.27 294.058,465.405 294.058,465.405Z" style="fill:white;fill-rule:nonzero;"/>
                                        <path d="M366.907,377.87C358.844,378.771 355.344,389.571 358.574,396.22C359.286,397.686 361.65,397.63 362.378,396.22C363.766,393.536 364.364,390.566 365.783,387.867C366.348,386.794 366.503,386.653 367.43,385.816C367.409,385.824 367.404,385.823 367.38,385.832C367.407,385.822 367.409,385.823 367.433,385.813C367.44,385.808 367.443,385.805 367.449,385.799C367.442,385.805 367.443,385.806 367.436,385.812C367.472,385.799 367.485,385.795 367.515,385.784C367.517,385.785 367.518,385.785 367.52,385.786C367.491,385.796 367.47,385.802 367.434,385.815C367.049,386.169 367.425,385.998 367.637,385.853C367.738,385.91 367.844,385.974 367.899,386.046C368.717,387.144 369.012,388.318 369.322,389.723C369.724,391.542 369.385,393.501 369.876,395.286C370.592,397.888 373.212,398.53 375.394,397.531C376.638,396.961 377.144,395.532 377.254,394.288C377.788,388.229 375.097,376.954 366.907,377.87Z" style="fill-rule:nonzero;"/>
                                        <path d="M367.433,385.813C367.432,385.814 367.432,385.815 367.43,385.816C367.432,385.815 367.432,385.815 367.434,385.815C367.435,385.814 367.435,385.814 367.436,385.812C367.435,385.813 367.434,385.813 367.433,385.813Z" style="fill-rule:nonzero;"/>
                                        <path d="M261.121,418.932C255.42,402.928 258.071,383.138 264.84,367.945C267.701,361.523 273.57,353.192 280.334,347.774C290.167,363.672 316.079,364.629 332.2,362.282C344.66,360.467 360.511,355.661 370.206,346.234C388.395,359.576 392.796,388.545 387.449,409.146C380.255,436.859 353.763,456.866 325.803,458.067C300.207,459.166 270.001,443.858 261.121,418.932ZM252.171,410.101C262.118,455.08 298.625,468.409 332.2,463.339C371.678,457.377 390.06,430.196 396.073,397.731C397.694,376.674 391.139,352.298 374.345,341.294C376.515,338.109 378.063,334.53 378.697,330.479C379.084,329.722 379.444,328.919 379.763,328.045C381.287,323.865 374.588,322.039 373.11,326.211C366.857,343.866 323.054,317.436 312.063,313.653C291.9,306.714 270.838,317.045 277.447,341.141C277.49,341.296 277.545,341.439 277.59,341.592C271.244,345.711 266.02,353.098 262.25,358.409C251.97,372.895 248.443,393.244 252.171,410.101Z" style="fill-rule:nonzero;"/>
                                    </g>
                                    <g transform="matrix(-1.27677,0,0,1.27677,688.214,-185.694)">
                                        <path d="M294.058,465.405C318.746,473.017 354.075,464.593 372.839,451.632C385.801,442.678 397.273,422.799 400.154,401.78C403.733,375.67 395.883,347.952 382.631,338.999C382.631,338.999 385.747,329.207 384.856,324.757C383.966,320.305 378.18,317.635 372.839,318.08C367.498,318.525 365.718,325.647 359.931,326.537C354.145,327.427 332.336,315.41 321.654,310.068C310.971,304.727 296.283,299.386 279.37,309.623C262.457,319.861 270.023,337.219 270.023,337.219C270.023,337.219 246.878,349.236 243.763,393.3C239.805,449.27 294.058,465.405 294.058,465.405Z" style="fill:white;fill-rule:nonzero;"/>
                                        <path d="M366.907,377.87C358.844,378.771 355.344,389.571 358.574,396.22C359.286,397.686 361.65,397.63 362.378,396.22C363.766,393.536 364.364,390.566 365.783,387.867C366.348,386.794 366.503,386.653 367.43,385.816C367.409,385.824 367.404,385.823 367.38,385.832C367.407,385.822 367.409,385.823 367.433,385.813C367.44,385.808 367.443,385.805 367.449,385.799C367.442,385.805 367.443,385.806 367.436,385.812C367.472,385.799 367.485,385.795 367.515,385.784C367.517,385.785 367.518,385.785 367.52,385.786C367.491,385.796 367.47,385.802 367.434,385.815C367.049,386.169 367.425,385.998 367.637,385.853C367.738,385.91 367.844,385.974 367.899,386.046C368.717,387.144 369.012,388.318 369.322,389.723C369.724,391.542 369.385,393.501 369.876,395.286C370.592,397.888 373.212,398.53 375.394,397.531C376.638,396.961 377.144,395.532 377.254,394.288C377.788,388.229 375.097,376.954 366.907,377.87Z" style="fill-rule:nonzero;"/>
                                        <path d="M367.433,385.813C367.432,385.814 367.432,385.815 367.43,385.816C367.432,385.815 367.432,385.815 367.434,385.815C367.435,385.814 367.435,385.814 367.436,385.812C367.435,385.813 367.434,385.813 367.433,385.813Z" style="fill-rule:nonzero;"/>
                                        <path d="M261.121,418.932C255.42,402.928 258.071,383.138 264.84,367.945C267.701,361.523 273.57,353.192 280.334,347.774C290.167,363.672 316.079,364.629 332.2,362.282C344.66,360.467 360.511,355.661 370.206,346.234C388.395,359.576 392.796,388.545 387.449,409.146C380.255,436.859 353.763,456.866 325.803,458.067C300.207,459.166 270.001,443.858 261.121,418.932ZM252.171,410.101C262.118,455.08 298.625,468.409 332.2,463.339C371.678,457.377 390.06,430.196 396.073,397.731C397.694,376.674 391.139,352.298 374.345,341.294C376.515,338.109 378.063,334.53 378.697,330.479C379.084,329.722 379.444,328.919 379.763,328.045C381.287,323.865 374.588,322.039 373.11,326.211C366.857,343.866 323.054,317.436 312.063,313.653C291.9,306.714 270.838,317.045 277.447,341.141C277.49,341.296 277.545,341.439 277.59,341.592C271.244,345.711 266.02,353.098 262.25,358.409C251.97,372.895 248.443,393.244 252.171,410.101Z" style="fill-rule:nonzero;"/>
                                    </g>
                                </g>
                            </svg>
                        </div>
                        <div id="main-panel-flash-msgs">
                            <?php echo $view->render('MauticCoreBundle:Notification:flashes.html.php'); ?>
                        </div>
                        <?php $view['slots']->output('_content'); ?>
                    </div>
                </div>
            </div>
        </div>
         <div class="row">
            <div class="col-lg-4 col-lg-offset-4 text-center text-muted">
                <?php echo $view['translator']->trans('mautic.core.copyright', ['%date%' => date('Y')]); ?>
            </div>
        </div>
    </div>
</section>
<?php echo $view['security']->getAuthenticationContent(); ?>
</body>
</html>
