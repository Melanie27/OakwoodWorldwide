<?php
 class Smarty_Internal_Compile_Block extends Smarty_Internal_CompileBase { public $required_attributes = array('name'); public $shorttag_order = array('name', 'hide'); public $optional_attributes = array('hide'); public function compile($args, $compiler) { $_attr = $this->getAttributes($compiler, $args); $save = array($_attr, $compiler->parser->current_buffer, $compiler->nocache, $compiler->smarty->merge_compiled_includes, $compiler->merged_templates, $compiler->smarty->merged_templates_func, $compiler->template->properties, $compiler->template->has_nocache_code); $this->openTag($compiler, 'block', $save); if ($_attr['nocache'] == true) { $compiler->nocache = true; } $compiler->inheritance = true; $compiler->smarty->merge_compiled_includes = true; $compiler->parser->current_buffer = new _smarty_template_buffer($compiler->parser); $compiler->has_code = false; return true; } public static function saveBlockData($block_content, $block_tag, $template, $filepath) { $_rdl = preg_quote($template->smarty->right_delimiter); $_ldl = preg_quote($template->smarty->left_delimiter); if (!$template->smarty->auto_literal) { $al = '\s*'; } else { $al = ''; } if (0 == preg_match("!({$_ldl}{$al}block\s+)(name=)?(\w+|'.*'|\".*\")(\s*?)?((append|prepend|nocache)?(\s*)?(hide)?)?(\s*{$_rdl})!", $block_tag, $_match)) { $error_text = 'Syntax Error in template "' . $template->source->filepath . '"   "' . htmlspecialchars($block_tag) . '" illegal options'; throw new SmartyCompilerException($error_text); } else { $_name = trim($_match[3], '\'"'); if ($_match[8] != 'hide' || isset($template->block_data[$_name])) { if (0 != preg_match_all("!({$_ldl}{$al}block\s+)(name=)?(\w+|'.*'|\".*\")([\s\S]*?)(hide)?(\s*{$_rdl})([\s\S]*?)({$_ldl}{$al}\\\$smarty\.block\.child\s*{$_rdl})([\s\S]*?{$_ldl}{$al}/block\s*{$_rdl})!", $block_content, $_match2)) { foreach ($_match2[3] as $key => $name) { $_name2 = trim($name, '\'"'); if ($_match2[5][$key] != 'hide' || isset($template->block_data[$_name2])) { if (isset($template->block_data[$_name2])) { $replacement = $template->block_data[$_name2]['source']; } else { $replacement = ''; } $search = array("!({$_ldl}{$al}block[\s\S]*?{$name}[\s\S]*?{$_rdl})([\s\S]*?)({$_ldl}{$al}\\\$smarty\.block\.child\s*{$_rdl})([\s\S]*?)({$_ldl}{$al}/block\s*{$_rdl})!", "/���child���/"); $replace = array('\2���child���\4', $replacement); $block_content = preg_replace($search, $replace, $block_content); } else { $block_content = preg_replace("!({$_ldl}{$al}block[\s\S]*?{$name}[\s\S]*?{$_rdl}[\s\S]*?{$_ldl}{$al}/block\s*{$_rdl})!", '', $block_content); } } } if (0 != preg_match("!({$_ldl}{$al}\\\$smarty\.block\.child\s*{$_rdl})!", $block_content, $_match2)) { if (isset($template->block_data[$_name])) { $replacement = $template->block_data[$_name]['source']; unset($template->block_data[$_name]); } else { $replacement = ''; } $block_content = preg_replace("!({$_ldl}{$al}\\\$smarty\.block\.child\s*{$_rdl})!", $replacement, $block_content); } if (isset($template->block_data[$_name])) { if (strpos($template->block_data[$_name]['source'], '%%%%SMARTY_PARENT%%%%') !== false) { $template->block_data[$_name]['source'] = str_replace('%%%%SMARTY_PARENT%%%%', $block_content, $template->block_data[$_name]['source']); } elseif ($template->block_data[$_name]['mode'] == 'prepend') { $template->block_data[$_name]['source'] .= $block_content; } elseif ($template->block_data[$_name]['mode'] == 'append') { $template->block_data[$_name]['source'] = $block_content . $template->block_data[$_name]['source']; } } else { $template->block_data[$_name]['source'] = $block_content; $template->block_data[$_name]['file'] = $filepath; } if ($_match[6] == 'append') { $template->block_data[$_name]['mode'] = 'append'; } elseif ($_match[6] == 'prepend') { $template->block_data[$_name]['mode'] = 'prepend'; } else { $template->block_data[$_name]['mode'] = 'replace'; } } } } public static function compileChildBlock($compiler, $_name = null) { $_output = ''; if ($_name == null) { $stack_count = count($compiler->_tag_stack); while (--$stack_count >= 0) { if ($compiler->_tag_stack[$stack_count][0] == 'block') { $_name = trim($compiler->_tag_stack[$stack_count][1][0]['name'], "'\""); break; } } $compiler->template->block_data[$_name]['compiled'] = true; } if ($_name == null) { $compiler->trigger_template_error('{$smarty.block.child} used out of context', $compiler->lex->taglineno); } if (!isset($compiler->template->block_data[$_name]['source'])) { return ''; } $_tpl = new Smarty_Internal_template('string:' . $compiler->template->block_data[$_name]['source'], $compiler->smarty, $compiler->template, $compiler->template->cache_id, $compiler->template->compile_id = null, $compiler->template->caching, $compiler->template->cache_lifetime); $_tpl->variable_filters = $compiler->template->variable_filters; $_tpl->properties['nocache_hash'] = $compiler->template->properties['nocache_hash']; $_tpl->source->filepath = $compiler->template->block_data[$_name]['file']; $_tpl->allow_relative_path = true; if ($compiler->nocache) { $_tpl->compiler->forceNocache = 2; } else { $_tpl->compiler->forceNocache = 1; } $_tpl->compiler->suppressHeader = true; $_tpl->compiler->suppressTemplatePropertyHeader = true; $_tpl->compiler->suppressMergedTemplates = true; if (strpos($compiler->template->block_data[$_name]['source'], '%%%%SMARTY_PARENT%%%%') !== false) { $_output = str_replace('%%%%SMARTY_PARENT%%%%', $compiler->parser->current_buffer->to_smarty_php(), $_tpl->compiler->compileTemplate($_tpl)); } elseif ($compiler->template->block_data[$_name]['mode'] == 'prepend') { $_output = $_tpl->compiler->compileTemplate($_tpl) . $compiler->parser->current_buffer->to_smarty_php(); } elseif ($compiler->template->block_data[$_name]['mode'] == 'append') { $_output = $compiler->parser->current_buffer->to_smarty_php() . $_tpl->compiler->compileTemplate($_tpl); } elseif (!empty($compiler->template->block_data[$_name])) { $_output = $_tpl->compiler->compileTemplate($_tpl); } $compiler->template->properties['file_dependency'] = array_merge($compiler->template->properties['file_dependency'], $_tpl->properties['file_dependency']); $compiler->template->properties['function'] = array_merge($compiler->template->properties['function'], $_tpl->properties['function']); $compiler->merged_templates = array_merge($compiler->merged_templates, $_tpl->compiler->merged_templates); $compiler->template->variable_filters = $_tpl->variable_filters; if ($_tpl->has_nocache_code) { $compiler->template->has_nocache_code = true; } foreach ($_tpl->required_plugins as $key => $tmp1) { if ($compiler->nocache && $compiler->template->caching) { $code = 'nocache'; } else { $code = $key; } foreach ($tmp1 as $name => $tmp) { foreach ($tmp as $type => $data) { $compiler->template->required_plugins[$code][$name][$type] = $data; } } } unset($_tpl); return $_output; } } class Smarty_Internal_Compile_Blockclose extends Smarty_Internal_CompileBase { public function compile($args, $compiler) { $compiler->has_code = true; $_attr = $this->getAttributes($compiler, $args); $saved_data = $this->closeTag($compiler, array('block')); $_name = trim($saved_data[0]['name'], "\"'"); if (isset($compiler->template->block_data[$_name]) && !isset($compiler->template->block_data[$_name]['compiled'])) { $_output = Smarty_Internal_Compile_Block::compileChildBlock($compiler, $_name); } else { if (isset($saved_data[0]['hide']) && !isset($compiler->template->block_data[$_name]['source'])) { $_output = ''; } else { $_output = $compiler->parser->current_buffer->to_smarty_php(); } unset($compiler->template->block_data[$_name]['compiled']); } $compiler->parser->current_buffer = $saved_data[1]; $compiler->nocache = $saved_data[2]; $compiler->smarty->merge_compiled_includes = $saved_data[3]; $compiler->inheritance = false; $compiler->suppressNocacheProcessing = true; return $_output; } } ?>