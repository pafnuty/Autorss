<?php
/**
 ==========================================================
 Название модуля: AutoRSSImport for DLE 9.8 (так же должен работать и на 9.6-9.7)
 ----------------------------------------------------------
 Версия: 6.0 релиз от 02.03.2012
 ---------------------------------------------------------
 Правообладатель: Виталий Чуяков (tcse-cms.com)
 ---------------------------------------------------------
 Автор версии 4+: Генри Хофман (henryhofman.com)
 ---------------------------------------------------------
 Автор версии 5+: Кирилл Родэ (kolos450@gmail.com)
 ---------------------------------------------------------
 Автор доработок версии 6+: ПафНутиЙ (pafnuty10@gmail.com)
 ---------------------------------------------------------
 Файл: autorss.php
 ---------------------------------------------------------
 Назначение: автоматический импорт rss-лент
 с преобразованием в формат новостей DLE
 ==========================================================
 * 
 * История версий:
 * 
 * 6.0.7 (от 03.03.2013)
 * - Исправил адрес дефолтной заглушки, заменил {THEME} на прямой путь (имя шаблона берётся из настроек dle)/
 * - Добавил возможность переопределять глобальные настройки модуля для каждого канала отдельно. Указываются так же как и  
 * - Можно переопределять любую переменную, кроме sendPM (т.к. она не привязана к конкретной rss-ленте). Переменные указываем в поле "Маска для поиска" в формате примерно так: offline=true||pseudoLinks=false и т.д. Достуны следующие переменные: offline, noimage, checkDouble, text_limit, chpu_cut, author_login, allowNewUsers, newUserGroup, source_text, pseudoLinks, source_target. 
 * 
 * 6.0.6 (от 02.03.2012)
 * - Исправил ошибку с префиксом dle_ в запросе.
 * - Регистрация нового пользователя теперь работает только в рабочем режиме - в тестовом пользователи не регитрируются.
 * - Убрал использование dle_api для регистрации пользователя.
 * - Добавил переменную pseudoLinks - для указания типа используемых ссылок на источник.
 * 
 * 6.0.5 (от 01.03.2012)
 * - Добавлена отправка Персонального сообщения с результатами работы скрипта админу (пользователю с id=1)/
 * - Подчистил лишний код в функции создания метатегов.
 * - Мелкие правки.
 * 
 * 6.0.4 (от 01.03.2013)
 * - Починил добавления тегов в облако тегов.
 * - Починил обновление кол-ва новостей у пользователя.
 * - Добавил возможность задавать индивидуальные настройки для каждого канала непосредственно из админки. (За подробностями в личку - описание будет позже)
 * 
 * 6.0.3 (от 28.02.2013)
 * - Поправил косяк с добавлением одинаковых картинок новостям, не содержащим картинки.
 * - Поправил косяк с ошибкой MySQL при наличи в новости одинакрной кавычки.
 * 
 * 6.0.2b (от 26.02.2013)
 * - Добавлена функция safeParce - для удобства, чтоб одно и тоже не писать по сто раз.
 * - Добавлен пароль для запуска скрипта. Пременная $pass.
 * - Новости в БД добавляются с текущей датой - почему то не работает дата из канала (возможно я что-то не то делаю).
 * - Добавил вывод ссылок на добавленные новости по завершению работы скрипта (отключаемо).
 * - Добавил переменную &nolinks - если её указать - то ссылки на добавленные новости показываться не будут.
 * - Добавил проврку дублей новостей (отключаемо) в настройках ниже.
 * - Добавил вывод заголовков НЕ добавленных новостей с отметкой дублей.
 * - Проведена оптимизация запросов в БД.
 * 
 * 6.0.1b (от 25.02.2013)
 * - Поправил ф-цию транслита, а то некоторые буквы съедались.
 * - Добавил авторегистрацию пользователя, имя которого указано в rss-канале, отключается в настройках. Пользователь регистрируется с именем, которое приходит с rss-канала, почтой вида [транслит_имени_пользователя@текущий_домен] и паролем, сотоящим из первых трёх символов имени пользователя и 7 случайных символов
 * - Исправления ошибок в фильтрации данных.
 * - Добавил показ времени выполнения скрипта и затрачиваемую память (насчёт показателей памяти не уверен в правдивости).
 * - Пока работает только тестовый режим, реальный выдаёт ошибку.
 * 
 * 6.0.0b (от 25.02.2013)
 * - Приемлемый вид тестового режима.
 * - Работает транслитерация заголовка. Так же обрезается динна транслитерированного заголовка, чтоб было симпотичнее.
 * - Переработан цикл получения и парсинга новостей, не проверял, но должен работать быстрее.
 * - Нормальное формирование текста новости. Убрано всё лишнее, обрезка текста вынесена в отдельную функцию.
 * - Добавлена возможность тонко настраивать обработку rss-канала по его id (пока требует осмысления и переработки для более удобного использования. Возможно нужно брать данные из настроек канала, но это потом).
 * - Ссылки на источник являются псевдоссылками http://cdpn.io/usoyw (исх. код: https://gist.github.com/5026041).
 * - Категории, приходящие с RSS преобразуются в теги, удаляются дубли, добавляются к уже существющим (из настроек rss-потока).
 * - Нормальный парсинг картинок. Используется надёжная регулярка.
 * 
 * 5.0.0 (от 23.06.2012)
 * - адаптирован для работы на движке DLE 9.6
 * - добавил прикрепление rel='nofollow' и <noindex>, починил комментарии.
 * 
 * 4.2.1 (от 17.11.2010)
 * - внес правки для максимальной ширины изображения img style='max-width: вместо img style='width:
 * 
 * 
 * 4.2
 * - исправленна проблема с через мерной защитой от SQL инъекций, в результате чего некоторые ссылки не работали
 * 
 * 4.1
 * - добавление в полную версию ссылок на источник
 * - жесткая обрезка новостей с большим количеством тегов (переменная $strong_limit)
 * - выведено вверх несколько дополнительных переменных
 * - добавлен css класс для внешней ссылки
 * 
 * 4.0
 * - поддержка DLE 8.x
 * - перенос функций на API DLE (для предотвращения несовместимости с будущими версиями DLE)
 * - обрезка краткой версии с учетом тегов и слов
 * - мелкие исправления
*/

define('PASS', '123456'); // пароль для запуска. 

$pass = PASS;

if(empty($_REQUEST['pass']) || $_REQUEST['pass'] != $pass) {
  die('что-то не так');
}

@error_reporting(E_ALL ^ E_NOTICE);
@ini_set('display_errors', true);
@ini_set('html_errors', false);
@ini_set('error_reporting', E_ALL ^ E_NOTICE);

$test = false;
if (isset($_REQUEST['test'])) {
	$test = true;
}
$nolinks = true;
if (isset($_REQUEST['nolinks'])) {
	$nolinks = false;
}
$start = microtime(true);
$mem_usg = (function_exists( "memory_get_peak_usage" )) ? round(memory_get_peak_usage()/(1024*1024),2)."Мб" : "функция memory_get_peak_usage отключена на хостинге";


/**
 * Переменные пользователя 
 * 
 * Внимание! чуть ниже по коду есть слово AHTUNG - найдите и почитайте!!! 
 */
$checkDouble   = true;		                           // Проверка на дубли (добавляет +1 запрос на каждую новость), чтобы отключить - нужно вписать false;
$text_limit    = 500;                                  // Кол-во символов в кратком новости
$chpu_cut      = 30;                                   // Кол-во символов в ЧПУ новости
$author_login  = "eptit";                              // Имя пользователя под которым будут опубликованы новости из RSS (ПОЛЬЗОВАТЕЛЬ ДОЛЖЕН СУЩЕСТВОВАТЬ!!!)
$sendPM        = true;                                 // Отправлять PM админу с информацией о работе скрипта.
$allowNewUsers = true;                                 // Разрешать добавление новых пользователей в БД, если их имя есть в rss-канале
$newUserGroup  = 3;                                    // Группа, в которую будут регистрироваться новые пользователи
$source_text   = "<hr class=\"separator\"> Источник";  // Надпись "Источник"
$pseudoLinks   = true;                                 // Использовать вместо настоящей ссылки на источник псевдоссылку. Не забываем прописать а js вот такую штуку
					                                   // /*Псевдо-ссылки*/
					                                   // /*Внутренние*/
					                                   // $('body').on('click', '[data-target-self]', function() {window.location.href = ($(this).data('targetSelf'));});
					                                   // /*Внешние*/
					                                   // $('body').on('click', '[data-target-blank]', function() {window.open($(this).data('targetBlank'));});
$source_target = "blank";                              // Атрибут будет добавлен к data-* если используются псевдоссылки или к target="_*" если используются настоящие ссылки


/**
 * Подключение к DLE
 */

define('ROOT_DIR', dirname(__FILE__));
define('ENGINE_DIR', ROOT_DIR . '/engine');
require_once ENGINE_DIR . '/api/api.class.php';
require_once ENGINE_DIR . '/modules/functions.php';

/**
 * Создаём метатеги
 * @param string $story 
 * @return string
 */
function create_metatags($story)
{
	global $dle_api;
	
	$keyword_count = 20;
	$newarr = array();
	$headers = array();
	$quotes = array("\x27","\x22","\x60","\t",'\n','\r',"\n","\r",'\\',"'",",",".","/","¬","#",";",":","@","~","[","]","{","}","=","-","+",")","(","*","&","^","%","$","<",">","?","!",'"');
	$fastquotes = array("\x27","\x22","\x60","\t","\n","\r",'"',"'",'\r','\n',"/","\\","{","}","[","]");
	
	$story = preg_replace("'\[hide\](.*?)\[/hide\]'si", "", $story);
	$story = preg_replace("'\[attachment=(.*?)\]'si", "", $story);
	$story = preg_replace("'\[page=(.*?)\](.*?)\[/page\]'si", "", $story);
	$story = str_replace("{PAGEBREAK}", "", $story);
	
	$story = str_replace('<br />', ' ', $story);
	$story = trim(strip_tags($story));
	
	$story = str_replace($fastquotes, '', $story);
	$headers['description'] = $dle_api->db->safesql(substr($story, 0, 190));
	
	
	$story = str_replace($quotes, '', $story);
	$arr = explode(" ", $story);
	
	foreach ($arr as $word) {
	if (strlen($word) > 4)
	$newarr[] = $word;
	} //$arr as $word
	
	$arr = array_count_values($newarr);
	arsort($arr);
	$arr = array_keys($arr);
	$total = count($arr);
	$offset = 0;
	$arr = array_slice($arr, $offset, $keyword_count);
	$headers['keywords'] = $dle_api->db->safesql(implode(", ", $arr));

	return $headers;
}

/**
 * Генерация хэша - используется для создания пароля нового пользователя))
 * @return hash
 */
function generateHash()
{
	$arr = array('1','2','3','4','5','6','7','8','9','A','B','C','D','E','F','G','H','J','K','M','N','P','R','S','T','U','V','X','Y','Z','a','b','c','d','e','f','g','h','j','k','m','n','p','r','s','t','u','v','x','y','z');
	
	$hash  = "";
	$count = count($arr) - 1;
	
	for ($i = 0; $i < 7; $i++) {
		$index = mt_rand(0, $count);
		$hash .= $arr[$index];
	} //$i = 0; $i < 7; $i++
	return $hash;
}

/**
 * Обрезка текста до логического конца слова.
 * @param $data - текст для обрезки
 * @param $count - на сколько обрезать (по умолчанию 500)
 * @param $showDots - показывать точки в конце
 * @return обрезанный текст
 */

function textLimit($data, $count = '500', $showDots = true)
{
	$hellip = ($showDots == true) ? '&hellip;' : '' ;
	$data = stripslashes(trim(strip_tags($data, '<br>')));
	$data = trim(str_replace( array('<br>','<br />','<br />'), ' ', $data));

	if($count && dle_strlen($data, $config['charset']) > $count)
	{
		$data = dle_substr( $data, 0, $count, $config['charset']).$hellip;					
		if($word_pos = dle_strrpos( $data, ' ', $config['charset'])) 
			$data = dle_substr( $data, 0, $word_pos, $config['charset']).$hellip;

	}
	return $data;
}

/**
 * Преобразование в транслит
 * @param $string - входные данные
 * @return translit
 */
function translit($string){
    $replace = array(
    /*--*/
    ","=>"","."=>""," "=>"-","а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e",
    "ё"=>"yo","ж"=>"j","з"=>"z","и"=>"i","й"=>"i","к"=>"k","л"=>"l", "м"=>"m",
    "н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t",
    "у"=>"y","ф"=>"f","х"=>"h","ц"=>"c","ч"=>"ch", "ш"=>"sh","щ"=>"sh",
    "ы"=>"i","э"=>"e","ю"=>"u","я"=>"ya",
    /*--*/
    "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D","Е"=>"E", "Ё"=>"Yo",
    "Ж"=>"J","З"=>"Z","И"=>"I","Й"=>"I","К"=>"K", "Л"=>"L","М"=>"M",
    "Н"=>"N","О"=>"O","П"=>"P", "Р"=>"R","С"=>"S","Т"=>"T","У"=>"Y",
    "Ф"=>"F", "Х"=>"H","Ц"=>"C","Ч"=>"Ch","Ш"=>"Sh","Щ"=>"Sh",
    "Ы"=>"I","Э"=>"E","Ю"=>"U","Я"=>"Ya",
    "ь"=>"","Ь"=>"","ъ"=>"","Ъ"=>""
    );
    return strtr($string, $replace);
    // return $str = iconv ( "UTF-8", "UTF-8//IGNORE", strtr ( $string, $replace ) );
}

/**
 * Обработка строк, чтобы по сто раз одно и тоже не писать, для удобаства в общем))
 * @param $data - входящая строка
 * @return обработанная строка
 */
function safeParce($data)
{
	$data = str_replace("'", '&#039;', str_replace(array("\t","\n","\r"),"", trim($data)));
	return $data;
}

/**
 * Добавление в базу нового пользователя
 * @param $login - Имя пользователя
 * @param $password - Пароль
 * @param $email - почта
 * @param $group - группа, в которую регистрировать юзера
 * @return -1 провал или 1 успех.
 */
function newUserRegister($login, $password, $email, $group)
{
	global $db;
	if( preg_match( "/[\||\'|\<|\>|\[|\]|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\{\+]/", $login ) ) return -1;

	$password = md5( md5( $password ) );
	$not_allow_symbol = array ("\x22", "\x60", "\t", '\n', '\r', "\n", "\r", '\\', ",", "/", "¬", "#", ";", ":", "~", "[", "]", "{", "}", ")", "(", "*", "^", "%", "$", "<", ">", "?", "!", '"', "'", " " );
	$group = intval( $group );
	$now = time();
	$q = $db->query( "insert into " . USERPREFIX . "_users (email, password, name, user_group, reg_date) VALUES ('$email', '$password', '$login', '$group', '$now')" );
	return 1;
}	

/**
 * Запрос списка rss-лент из БД
 */

$rssListQuery = $db->query("SELECT * FROM ".PREFIX."_rss");
while ($row = $db->get_row($rssListQuery)) {
	$rssList[] = $row;
	
} //$row = $db->get_row($rssListQuery)


include_once ENGINE_DIR . '/classes/rss.class.php';
include_once ENGINE_DIR . '/classes/parse.class.php';

$parse             = new ParseFilter(Array(), Array(), 1, 1);
$parse->leech_mode = true;

/**
 * Импорт лент
 */

$addindex    = 0;
$addbadindex = 0;
foreach ($rssList as $rssline) {
	$xml = new xmlParser(stripslashes($rssline['url']), $rssline['max_news']);
	if ($xml->rss_option == "UTF-8")
		$xml->convert("UTF-8", strtolower($dle_api->dle_config['charset']));
	elseif ($xml->rss_charset != strtolower($dle_api->dle_config['charset']))
		$xml->convert($xml->rss_charset, strtolower($dle_api->dle_config['charset']));
	$xml->pre_lastdate = $rssline['lastdate'];
	$xml->pre_parse($rssline['date']);
	

	/**
	 * Получаем индивидуальные настройки для каждого канала
	 */
	$rssLineTemp = explode("||", $rssline['search']);
	$rssSettings = array();
	foreach($rssLineTemp as $v) {
		$v = explode("=", $v);
		$rssSettings[trim($v[0])] = trim($v[1]);
	}
	// Назначаем переменные, выводящие соостветствующие настройки канала.
	$offline       = $rssSettings['offline'];
	$noimage       = (isset($rssSettings['noimage']))       ? $rssSettings['noimage']       : '/templates/'.$config['skin'].'/images/noimage.png';
	$checkDouble   = (isset($rssSettings['checkDouble']))   ? $rssSettings['checkDouble']   : $checkDouble;
	$text_limit    = (isset($rssSettings['text_limit']))    ? $rssSettings['text_limit']    : $text_limit;
	$chpu_cut      = (isset($rssSettings['chpu_cut']))      ? $rssSettings['chpu_cut']      : $chpu_cut;
	$author_login  = (isset($rssSettings['author_login']))  ? $rssSettings['author_login']  : $author_login;
	$allowNewUsers = (isset($rssSettings['allowNewUsers'])) ? $rssSettings['allowNewUsers'] : $allowNewUsers;
	$newUserGroup  = (isset($rssSettings['newUserGroup']))  ? $rssSettings['newUserGroup']  : $newUserGroup;
	$source_text   = (isset($rssSettings['source_text']))   ? $rssSettings['source_text']   : $source_text;
	$pseudoLinks   = (isset($rssSettings['pseudoLinks']))   ? $rssSettings['pseudoLinks']   : $pseudoLinks;
	$source_target = (isset($rssSettings['source_target'])) ? $rssSettings['source_target'] : $source_target ;

	
	if (!$offline) {
		foreach ($xml->content as $content) {

			// Определяем id rss-потока для тонкой настройки каждого потока в дальнейшем
			$rssid = $rssline['id'];
			// Слеши - зло))
			$content['description'] = stripslashes($content['description']);

			/**
			 * 
			 * 
			 * 	   #    #   #  #####  #   #  #   #   ###  
			 *	  # #   #   #    #    #   #  ##  #  #   # 
			 *	 #   #  #   #    #    #   #  ##  #  #     
			 *	 #   #  #####    #    #   #  # # #  #  ## 
			 *	 #####  #   #    #    #   #  #  ##  #   # 
			 *	 #   #  #   #    #    #   #  #  ##  #   # 
			 *	 #   #  #   #    #     ###   #   #   #### 
			 * 
			 * AHTUNG
			 * 
			 * Начало условий для тонкой обрботки конкретных каналов.
			 * 
			 * Ниже можно писать свои условия для парсинга контента с конкретных источников.
			 * 
			 */

			// Если id rss канала равен 3 - мы берём только текст первого абзаца, т.к. в остальных не нужная нам инфа (канал сайта designonstop.com).
			// @TODO - можно попробовать придумать как указывать условия для отбора контента через стандартное поле DLE c {get} и {skip}
			if ($rssid == '3') {
				if(preg_match_all('#<p>(.+?)</p>#is', $content['description'], $matches)) {
					$content['description'] = $matches[0][0]; 
				}				
			}

			// Если id rss канала равен 3 - мы берём только текст первого абзаца, т.к. в остальных не нужная нам инфа (канал сайта designmodo.com).
			// @TODO - можно попробовать придумать как указывать условия для отбора контента через стандартное поле DLE c {get} и {skip}
			if ($rssid == '4') {
				if(preg_match_all('#<div>(.+?)</div>#is', $content['description'], $matches0)) {
					$rssid4 = $matches0[0][0]; 
				}
				if(preg_match_all('#<p>(.+?)</p>#is', $content['description'], $matches)) {
					$content['description'] = str_ireplace('[...]', ' ', $rssid4.$matches[0][0]); 				
				}				
			}


			/**
			 * 
			 * 
			 * Конец условий для тонкой обрботки конкретных каналов.
			 * 
			 * 
			 * 
			 */

			// определяем title тут потому, что он используется как alt для картинки
			$title = safeParce($content['title']);
			
			$imageUrl = $noimage;
			// Вылавливаем URL первой картинки
			if (preg_match_all('/<img(?:\\s[^<>]*?)?\\bsrc\\s*=\\s*(?|"([^"]*)"|\'([^\']*)\'|([^<>\'"\\s]*))[^<>]*>/i', $content['description'], $m)) 
			{
				// Адрес первой картинки в новости
				$imageUrl = $m[1][0]; // @TODO - добавить возможность выбора номера картинки (вряд ли это нужно, но всёже)
			}		
				$imageTag = '<img src="'.$imageUrl.'" alt="'.$title.'"> ';

			// $shortText = $imageTag.$shortText;
			// Обрезанная краткая новость
			$content['description'] = safeParce($content['description']);
			$shortText = $imageTag.' '.textLimit($content['description'], $text_limit);

			// Полная новость с уделённым форматированием
			$fullText = $imageTag.' '.textLimit($content['description'], 0);


			// Сложный процесс превращения категорий, приходящих с RSS тегов DLE
			$strCat = strtolower(str_replace("\n", ",", str_replace("\r","",str_replace("\t","", trim($content['category'])))));
			$arrCat = explode(',', $strCat);
			$arrCat = array_unique($arrCat);
			$contentTags = implode(', ', $arrCat);
			
			/**
			 * Подготовка содержимого
			 */

			// Формирование ссылок на источник
			$sourceLink       = explode(',', $rssline['description']);
			$content['link']  = safeParce($content['link']);
			
			if($pseudoLinks == true) {
				$addSourceLink    = $source_text.": <span class=\"link\" title=\"Источник публикаци\" data-target-".$source_target."=\"" . $content['link'] . "\">" . $sourceLink[0] . "</span>";
			} else {
				$addSourceLink    = $source_text.": <a rel=\"nofollow\" class=\"link\" title=\"Источник публикаци\" href=\"" . $content['link'] . "\" target=\"_".$source_target."\">" . $sourceLink[0] . "</a>";
			}
			
			$shortText        .= $addSourceLink;		
			$content['short'] = $shortText;
			// полная новость
			$content['full']  = $fullText.$addSourceLink;
			// Дата
			$content['date']  = date("Y-m-d H:i:s", $content['date']);
			// Теги
			$tags  = $rssline['description'].', '.$contentTags;


			// Дополниетльные параметры
			$approve      = 1;
			$allow_comm   = intval($rssline['allow_comm']);
			$allow_main   = intval($rssline['allow_main']);
			$allow_rating = intval($rssline['allow_rating']);
			$text_type    = intval($rssline['text_type']);
			$lastdate     = $xml->lastdate;
		

			/**
			 * Теперь готовим контент для записи в БД	
			 */

			$short_story = stripslashes($content['short']);
			$full_story  = stripslashes($content['full']);
			
			$alt_name = strtolower(translit(textLimit(str_replace(array("[", "]"), "", $title), $chpu_cut, false)));

			/**
			 * Определяем имя пользователя, от которого будет добавляться новость (по умолчанию @$author_login)
			 */
			if ($allowNewUsers == true && $content['author'] !='' && $test == false) {
				$curUser = $dle_api->take_user_by_name(safeParce($content['author']), 'name');

				$newsAuthor = $curUser['name'];
			
				if($curUser == false) {
					$newUser = $db->safesql(safeParce($content['author']));
					$translAuthor = translit($newUser);
					$password = textLimit($translAuthor, 3, false).'_'.generateHash();
					$email = trim($translAuthor.'@'.str_replace(array('http://', '/'), '', $config['http_home_url']));
					$addNewUser = newUserRegister($newUser, $password, $email, $newUserGroup);
					$newsAuthor = ($addNewUser == '1') ? $newUser : $newsAuthor;
				}
				if ($newsAuthor == '') {
					$newsAuthor = $author_login;
				}			
			} else {
				$newsAuthor = $author_login;
			}

			// Проверяем на дубли
			$existTitle = '0';
			if($checkDouble == true) {
				$existTitle = $db->super_query("SELECT COUNT(*) as count FROM ".PREFIX."_post WHERE title = '$title'");	
				$existTitle = $existTitle['count'];
			}

			$category_list = $dle_api->db->safesql($rssline['category']);
			$metatags = create_metatags($short_story . $full_story);
			$thistime = date("Y-m-d H:i:s", time() + ($config['date_adjust'] * 60));	

			/**
			 * Пишем в БД
			 */
			if (trim($title) != "" && trim($short_story) != "" && $test == false && $existTitle == '0') {
				$db->query("INSERT INTO ".PREFIX."_post (date, autor, short_story, full_story, xfields, title, descr, keywords, category, alt_name, allow_comm, approve, allow_main, allow_br, tags) values ('$thistime', '$newsAuthor', '$short_story', '$full_story', '', '$title', '{$metatags['description']}', '{$metatags['keywords']}', '$category_list', '$alt_name', '$allow_comm', '$approve', '$allow_main', '$allow_br', '$tags')");
				$row['id'] = $dle_api->db->insert_id();
				
				$db->query("INSERT INTO ".PREFIX."_post_extras (news_id, allow_rate, votes, disable_index, access) VALUES('{$row['id']}', '$allow_rating', '0', '0', '')");				

					// $db->query("UPDATE ".PREFIX."_images set news_id='{$row['id']}' where author = '{$isUser['user_id']}' AND news_id = '0'");
					$db->query("UPDATE " . USERPREFIX . "_users set news_num=news_num+1 where name='{$newsAuthor}'");

				
				if ($tags != "") {
					
					$tags_arr = array();
					$tags     = explode(",", $tags);
					
					foreach ($tags as $value) {
						$tags_arr[] = "('" . $row['id'] . "', '" . trim($value) . "')";
					} //$tags as $value
					
					$tags_arr = implode(", ", $tags_arr);
					$db->query("INSERT INTO ".PREFIX."_tags (news_id, tag) VALUES " . $tags_arr);
					
				} //$tags != ""

				if (isset($tags))
					unset($tags);
				
				$addindex++;
			} //trim($title) != "" && trim($short_story) != "" && $test == false
			else {
				$addbadindex++;
				if ($existTitle >= '1') {
					$badTitle = '<span style="color: #f96">[дубль] '.$title.'</span>';
					$badIndexTitle .= '<li>'.$badTitle.'</li>';
				}  else {
					$badIndexTitle .= '<li>'.$title.'</li>';
				}

			}

			if ($test == true) {			
				$testcontent .= "<li><h2>".$title."</h2>";
				$testcontent .= "<div style='display:none;'>";
				$testcontent .= "<pre data-text='[Краткая новость]'>".htmlspecialchars($short_story)."</pre>";
				$testcontent .= "<pre data-text='[Полная новость]'>".htmlspecialchars($full_story)."</pre>";
				$testcontent .= "<pre data-text='[Автор, теги]'>".$newsAuthor."<br>".htmlspecialchars($tags)."</pre>";

				$testcontent .= "</div></li>";
			}

			// $addedNewsLink = '';
			if($nolinks == true && $existTitle == '0') {
				$addedNews = $dle_api->db->super_query("SELECT id, title FROM ".PREFIX."_post WHERE title='" . $title . "'");
				if(!empty($addedNews)) {
					$addedNewsLink .= '<li><a href="/index.php?newsid='.$addedNews['id'].'" target="_blank">'.$addedNews['title'].'</a></li>';
				}
			}

		} //$xml->content as $content
	}
	
	
	if ($rssid && $lastdate) {
		if ($test == false) {
			$db->query("UPDATE ".PREFIX."_rss SET lastdate='$lastdate' WHERE id='$rssid'");		
		}
	}
	
} //$rssList as $rssid => $rssline

// Формирование отчёта о работе скрипта.
$result = "Добавлено: " . $addindex . " новостей.<br /><ol>".$addedNewsLink."</ol><hr />Новостей содержащих некорректные данные: " . $addbadindex."<br /><ol>".$badIndexTitle."</ol><br /><a href=\"/\">Вернуться на главную</a>";

// Отправка Персонального сообщения с результатами работы скрипта админу.
if($sendPM == true && $test == false) {
	$pmResult = 'Обновления от '.$thistime.'<hr />'.$result;
	$pmResult = $parse->BB_Parse($pmResult, false);
	$pmSend = $dle_api->send_pm_to_user('1', "Добавлены новости", $pmResult, $author_login);
	if ($sendPM == '1') {
		$result = $result.'<br />Сообщение админу отправлено.';
	}
	if ($sendPM == '0') {
		$result = $result.'<br />Сообщение админу <span style="color: red;">НЕ отправлено</span>.';
	}
}
	
clear_cache();

echo $result;

if ($test == true) {	
	echo "<title>Автоимпорт новостей</title><style>pre{background:#fdf6e3;border-color:rgba(0,0,0,0.3);border-style:solid;border-width:30px 2px 2px;color:#586e75;display:block;font:normal 14px/20px Consolas,'Courier New',monospace;padding:20px;margin:20px;position:relative;text-shadow:0 1px 1px #fff;-webkit-border-radius:5px;border-radius:5px;-moz-box-shadow:inset 0 -1px 10px 0 rgba(0,0,0,0.1),inset 0 1px 0 0 rgba(0,0,0,0.5),0 0 30px 0 rgba(255,255,255,0.5);box-shadow:inset 0 -1px 10px 0 rgba(0,0,0,0.1),inset 0 1px 0 0 rgba(0,0,0,0.5),0 0 30px 0 rgba(255,255,255,0.5);white-space:pre;white-space:pre-wrap;word-break:break-all;word-wrap:break-word}pre::-moz-selection{background:#073642;text-shadow:0 1px 1px #000;color:#fff}pre::selection{background:#073642;text-shadow:0 1px 1px #000;color:#fff}pre:after{color:#fff;content:attr(data-text);font:normal 16px/30px Consolas,'Courier New',monospace;height:30px;left:20px;position:absolute;right:20px;text-shadow:0 1px 3px rgba(0,0,0,0.7);top:-30px}h2{cursor:pointer;}</style>";
	echo '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>';
	echo '<script>jQuery(document).ready(function($){$("h2").click(function(){nextDiv=$(this).next("div");$("h2").next().hide();nextDiv.show();});});</script>';
	echo "<br /><b>Отладка:</b><br /><ol>$testcontent</ol>"; //тестовый режим
}
echo '<p style="color:red;">Время выполнения: <b>'. round((microtime(true) - $start), 6). '</b> c. <br /> Затраты памяти: <b>'.$mem_usg.'</b></p>';
?>
