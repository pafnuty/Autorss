<?php 
/**
 =========================================================
 Название модуля: AutoRSSImport for DLE 9.8 (так же должен работать и на 9.6-9.7)
 ---------------------------------------------------------
 Правообладатель: Виталий Чуяков (tcse-cms.com)
 ---------------------------------------------------------
 Автор файла: ПафНутиЙ (pafnuty10@gmail.com)
 ---------------------------------------------------------
 Файл: rss.tiny.settings.php
 ---------------------------------------------------------
 Назначение: тонкая настройка обработки лент для модуля AutoRSSImport
*/
if(!defined('DATALIFEENGINE'))
{
  die("Hacking attempt!");
}

$rssid = $rssline['id'];

if ($rssid == '3') {
	if(preg_match_all('#<p>(.+?)</p>#is', $content['description'], $matches)) {
		$content['description'] = $matches[0][0]; 
	}				
}


if ($rssid == '4') {
	if(preg_match_all('#<div>(.+?)</div>#is', $content['description'], $matches0)) {
		$rssid4 = $matches0[0][0]; 
	}
	if(preg_match_all('#<p>(.+?)</p>#is', $content['description'], $matches)) {
		$content['description'] = str_ireplace('[...]', ' ', $rssid4.$matches[0][0]); 				
	}				
}


if ($rssid == '24') {
	$content['description'] = str_replace('jpg?84cd58', 'jpg', $content['description']);
}

 ?>
