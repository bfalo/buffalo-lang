<?php
require_once('BuffaloInterpreter.php');

$program = 'buffalo.bfalo';
$Buffalo = new BuffaloInterpreter(file_get_contents('../../examples/' . $program));