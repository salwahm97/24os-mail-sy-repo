<?php
// Copy this file to config.php (it is git-ignored) and fill in your own values.
if(!defined("ABSPATH")) exit;

define("OS24_MAIL_SY_COMPANY_NAME",    "Your Company Name");
define("OS24_MAIL_SY_COMPANY_IBAN",    "DE00 0000 0000 0000 0000 00");
define("OS24_MAIL_SY_COMPANY_BIC",     "XXXXXXXX");
define("OS24_MAIL_SY_COMPANY_ADDRESS", "Street 1 / 00000 City");
define("OS24_MAIL_SY_ADMIN_EMAIL_DOCS","orders-docs@example.com"); // receives "official documents" orders
define("OS24_MAIL_SY_ADMIN_EMAIL_MAIL","orders-mail@example.com"); // receives "mail forwarding" orders

// Who may open the orders dashboard and use the admin API endpoints.
// Default "edit_posts" (contributors and above). Use "edit_others_posts" (editors+) or "manage_options" (admins only) for stricter access.
define("OS24_MAIL_SY_CAPABILITY",      "manage_options");
