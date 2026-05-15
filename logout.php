<?php
require_once __DIR__ . '/config/database.php';

ensure_session();
$_SESSION = [];
session_destroy();

ensure_session();
flash('success', 'Sesion cerrada correctamente.');
redirect('login.php');
