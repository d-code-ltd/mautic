<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($item = ((isset($event['extra'])) ? $event['extra']['details'] : false)): ?>
    <p>
        <?php echo $item ?>
    </p>
<?php endif; ?>
