<?php
// Include the routes class
require_once 'includes/routes.php';

// Redirect to register page
Routes::redirect(Routes::baseUrl() . '/register');
?> 