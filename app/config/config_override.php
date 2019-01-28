<?php

$container->loadFromExtension("doctrine", array(
    "orm" => array(
        "metadata_cache_driver" => "memcached",
        "result_cache_driver"   => "memcached",
        "query_cache_driver"    => "memcached"
    )
));


/*
 * Overrides the configuration from security.php
 */
$container->setParameter('mautic.security.disableUpdates', true);