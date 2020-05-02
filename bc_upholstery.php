<?php

/**
 * Plugin Name: BC Upholstery
 * Description: WordPress CPT for BC Marine Canvas' upholstery service.
 * Version: 0.1.1
 * Author: James Boynton
 */

namespace BC\Upholstery;

$autoload_path = __DIR__ . '/vendor/autoload.php';

if (file_exists($autoload_path)) {
  require_once($autoload_path);
}

new Services();
