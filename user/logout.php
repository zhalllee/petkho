<?php
session_start();
include("../includes/config.php");
$_SESSION = [];
session_destroy();
header("Location: ../index.php");
exit();
