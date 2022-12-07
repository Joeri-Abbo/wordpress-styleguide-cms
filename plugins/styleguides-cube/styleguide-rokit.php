<?php
/**
 * Styleguide cube
 * @wordpress-plugin
 * Plugin Name: Styleguide cube
 * Version:     1.2.22
 * Plugin URI:  https://joeriabbo.nl
 * Description: The styleguide plugin for cube
 * Author:      Joeri Abbo
 * Author URI:  https://joeriabbo.nl
 * Text Domain: styleguide-cube
 * Domain Path: /languages/
 */

//Acf addons
include "include/acf-toolbar-styleguide.php";
include "include/acf-fields.php";

//add site option page
include "include/acf-site-option-page.php";
include "include/acf-colors-option-page.php";

//add taxonomie to page
include "include/add-taxonomie-to-guide.php";

include "include/change-menu.php";

//add home option page
include "include/acf-home-option-page.php";

//add guides
include "include/post-type-guide.php";

include "include/helper-functions.php";

include "include/new-site-functions.php";

include "include/rest.php";
