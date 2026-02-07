<?php
session_start();
unset($_SESSION['ba_user_id'], $_SESSION['ba_site_id']);
session_destroy();
header("Location: login.php");
exit;
