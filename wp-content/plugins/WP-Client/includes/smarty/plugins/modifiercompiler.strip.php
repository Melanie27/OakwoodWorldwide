<?php
 function smarty_modifiercompiler_strip($params, $compiler) { if (!isset($params[1])) { $params[1] = "' '"; } return "preg_replace('!\s+!" . Smarty::$_UTF8_MODIFIER . "', {$params[1]},{$params[0]})"; } ?>