<?php
/**
 =========================================================
 Название модуля: AutoRSSImport for DLE 9.8 (так же должен работать и на 9.6-9.7)
 ---------------------------------------------------------
 Версия: 6.0 релиз от 13.04.2012
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
 * История версий в файле engine/modules/autorss/changelog.txt
*/

define('AUTORSS_PASS', '123456'); // пароль для запуска. 

$pass = AUTORSS_PASS;

if(empty($_REQUEST['pass']) || $_REQUEST['pass'] != $pass) {
	die('что-то не так');
}

@error_reporting(E_ALL ^ E_NOTICE);
@ini_set('display_errors', true);
@ini_set('html_errors', false);
@ini_set('error_reporting', E_ALL ^ E_NOTICE);

// Если прописать с адресной строке  &test - будет запущен тестовый режим
$test = false;
if (isset($_REQUEST['test'])) {
	$test = true;
}
// Если прописать с адресной строке &fulldebug - будет показываться полны дебаг (исходые данные канала)
$fulldebug = false;
if (isset($_REQUEST['fulldebug'])) {
	$fulldebug = true;
}
// Если прописать с адресной строке &channelid=1,2,3 - будут обрабатыватьс только каналы с соответсствующим id
$channelid = false;
if (isset($_REQUEST['channelid'])) {
	$channelid = true;
}
// Если прописать с адресной строке &channellimit=0x10 - будут взяты только первые 10 каналов (можно написать &channellimit=10x10 - будут взяты 10 каналов начиная с 10го по счёту)
$channellimit = false;
if (isset($_REQUEST['channellimit'])) {
	$channellimit = true;
}
// Если прописать с адресной строке &nolinks - не будут показываться ссылки на добавленные новости (экономия 1 запроса на новость)
$nolinks = true;
if (isset($_REQUEST['nolinks'])) {
	$nolinks = false;
}
$start = microtime(true);
$mem_usg = (function_exists("memory_get_peak_usage")) ? round(memory_get_peak_usage()/(1024*1024),2)."Мб" : "функция memory_get_peak_usage отключена на хостинге";


/**
 * Глобальные переменные и настройки 
 * значения переменных для каналов указаны ниже
 */
// Отправлять PM админу с информацией о работе скрипта.
$sendPM = true;

/**
 * Подключение к DLE
 */

define('ROOT_DIR', dirname(__FILE__));
define('ENGINE_DIR', ROOT_DIR . '/engine');
define('AUTORSS_DIR', ENGINE_DIR . '/modules/autorss/');
require_once ENGINE_DIR . '/api/api.class.php';
require_once ENGINE_DIR . '/modules/functions.php';
// Переменная для подсчёта кол-ва запросов в БД.
$qi = 0;
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
	$quotes = array(",",".","¬","#",";",":","@","~","=","-","+",")","(","*","&","^","%","$","<",">","?","!",'"');
	$fastquotes = array("\x27","\x22","\x60","\t","\n","\r",'"',"'",'\r','\n',"/","\\","{","}","[","]");
	
	$story = preg_replace("'\[hide\](.*?)\[/hide\]'si", "", $story);
	$story = preg_replace("'\[attachment=(.*?)\]'si", "", $story);
	$story = preg_replace("'\[page=(.*?)\](.*?)\[/page\]'si", "", $story);
	$story = str_replace("{PAGEBREAK}", "", $story);
	
	$story = str_replace('<br />', ' ', $story);
	$story = trim(strip_tags($story));
	
	$story = str_replace($fastquotes, '', $story);
	$headers['description'] = $dle_api->db->safesql(substr($story, 0, 190));
	$qi++;	
	
	$story = str_replace($quotes, '', $story);
	$arr = explode(" ", $story);
	
	foreach ($arr as $word) {
	if (strlen($word) > 4)
	$newarr[] = $word;
	} //$arr as $word
	
	$arr = array_count_values($newarr);
	arsort($arr);
	$arr = array_keys($arr);
	$offset = 0;
	$arr = array_slice($arr, $offset, $keyword_count);
	$headers['keywords'] = $dle_api->db->safesql(implode(", ", $arr));
	$qi++;

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
	global $config;
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
    "&"=>"-","»"=>"","«"=>"","+"=>"","/"=>"-",'"'=>"","'"=>"","["=>"","]"=>"",
    "#"=>"","?"=>"",":"=>"",";"=>"","("=>"",")"=>"","$"=>"",
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
}

/**
 * Обработка строк, чтобы по сто раз одно и тоже не писать, для удобаства в общем))
 * @param $data - входящая строка
 * @return обработанная строка
 */
function safeParse($data)
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
	$group = intval( $group );
	$now = time() + ($config['date_adjust'] * 60);
	$q = $db->query( "insert into " . USERPREFIX . "_users (email, password, name, user_group, reg_date) VALUES ('$email', '$password', '$login', '$group', '$now')" );
	$qi++;
	return 1;
}	


/**
 * Запрос списка rss-лент из БД
 */
// Задаём параметры отбора каналов
// Конкретные id каналов
$rssWhere = ($channelid) ? ' WHERE id regexp "[[:<:]]('.str_replace(',', '|', $_REQUEST['channelid']).')[[:>:]]"' : '';
// Лимит отбора 
if ($channellimit) {
	$rssLimits = str_replace('x', ', ',$_REQUEST['channellimit']);
	$rssLimit = ' LIMIT '.$rssLimits;
} else {
	$rssLimit = '';
}


$rssListQuery = $db->query("SELECT * FROM ".PREFIX."_rss{$rssWhere}".$rssLimit."");
while ($row = $db->get_row($rssListQuery)) {
	$rssList[] = $row;
	
} //$row = $db->get_row($rssListQuery)
$qi++;

include_once ENGINE_DIR . '/classes/rss.class.php';
include_once ENGINE_DIR . '/classes/parse.class.php';

$parse             = new ParseFilter(Array(), Array(), 1, 1);
$parse->leech_mode = true;

/**
 * Импорт лент
 */

$addindex    = 0;
$addbadindex = 0;
$userindex = 0;
foreach ($rssList as $rsskey=>$rssline) {
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
	// Если задать переменную - канал не будет обрабатываться
	$offline       = $rssSettings['offline'];
	// Картинка-заглушка
	$noimage       = (isset($rssSettings['noimage']))       ? $rssSettings['noimage']       : '/templates/'.$config['skin'].'/images/noimage.png';
	// Проверка на дубли (добавляет +1 запрос на каждую новость), чтобы отключить - нужно вписать false;
	$checkDouble   = (isset($rssSettings['checkDouble']))   ? $rssSettings['checkDouble']   : true;
	// Кол-во символов в краткой новости
	$text_limit    = (isset($rssSettings['text_limit']))    ? $rssSettings['text_limit']    : '500';
	// Кол-во символов в ЧПУ новости
	$chpu_cut      = (isset($rssSettings['chpu_cut']))      ? $rssSettings['chpu_cut']      : '30';
	// Имя пользователя под которым будут опубликованы новости из RSS
	$author_login  = (isset($rssSettings['author_login']))  ? $rssSettings['author_login']  : 'eptit';
	// Разрешать добавление новых пользователей в БД, если их имя есть в rss-канале
	$allowNewUsers = (isset($rssSettings['allowNewUsers'])) ? $rssSettings['allowNewUsers'] : true;
	// Группа, в которую будут регистрироваться новые пользователи
	$newUserGroup  = (isset($rssSettings['newUserGroup']))  ? $rssSettings['newUserGroup']  : '3';
	// Надпись "Источник"
	$source_text   = (isset($rssSettings['source_text']))   ? $rssSettings['source_text']   : "<hr class=\"separator\"> Источник";
	// Использовать вместо настоящей ссылки на источник псевдоссылку.
	$pseudoLinks   = (isset($rssSettings['pseudoLinks']))   ? $rssSettings['pseudoLinks']   : true;
	// Атрибут будет добавлен к data-* если используются псевдоссылки или к target="_*" если используются настоящие ссылки
	$source_target = (isset($rssSettings['source_target'])) ? $rssSettings['source_target'] : 'blank';
	// Отключить обаботку и парсинг картинки из канала?
	$dasable_img   = (isset($rssSettings['dasable_img']))   ? $rssSettings['dasable_img']   : false;
	// Тянуть картинки себе на сайт
	$grab_img      = (isset($rssSettings['grab_img']))      ? $rssSettings['grab_img']      : true;
	// Размер уменьшеных картинок, можно задавать как 200x150, так и просто 250
	$imgSize       = (isset($rssSettings['imgSize']))       ? $rssSettings['imgSize']       : '200';
	// Тип создания уменьшенных изображений (exact, portrait, landscape, auto, crop)
	$resizeType    = (isset($rssSettings['resizeType']))    ? $rssSettings['resizeType']    : 'auto';

	
	// Показываем подробное содержание канала
	if ($fulldebug) {
		echo "<h2>".$rssList[$rsskey]['id']." - ".$rssList[$rsskey]['description']."</h2>";
		echo "<div style='display:none;'>";
		echo "<pre class='dle-pre'>"; print_r($rssSettings); echo "</pre>";		
		foreach ($xml->content as $xmlval) {
			echo "<pre class='dle-pre' data-text='Общая информация'>"; print_r("title: ".$xmlval['title']."<br>link: <a href='".trim($xmlval['link'])."' target='_blank'>".trim($xmlval['link'])."</a><br>author: ".$xmlval['author']); echo "</pre>";
			echo "<pre class='dle-pre' data-text='Тело статьи'>"; print_r(htmlspecialchars($xmlval['description'])); echo "</pre>";
		}
		// echo "<pre class='dle-pre' >"; print_r($xml); echo "</pre>";
		echo "</div>";
	}

	
	if (!$offline) {
		foreach ($xml->content as $content) {

			// Определяем id rss-потока для тонкой настройки каждого потока в дальнейшем
			$channelid = $rssline['id'];
			// Слеши - зло))
			$content['description'] = stripslashes($content['description']);

			// Подрубаем файл с настройками тонкой обработки каналов
			include AUTORSS_DIR.'rss.tiny.settings.php';

			// Определяем title тут потому, что он используется как alt для картинки
			$title = safeParse($content['title']);
			

		
			// Работаем с картинкой, если это не запрещено
			if (!$dasable_img) {
				// Задаём папку для картинок
				$dir_prefix = $imgSize.'/'.date("Y-m").'/';
				$dir = ROOT_DIR . '/uploads/rss/'.$dir_prefix;

				$imageUrl = $noimage;
				$imgForResize = '';
				$imgNameOut = $noimage;
	
				// Вылавливаем URL первой картинки
				if (preg_match_all('/<img(?:\\s[^<>]*?)?\\bsrc\\s*=\\s*(?|"([^"]*)"|\'([^\']*)\'|([^<>\'"\\s]*))[^<>]*>/i', $content['description'], $m)) {
					// Адрес первой картинки в новости
					$imageUrl = $m[1][0]; // @TODO - добавить возможность выбора номера картинки (вряд ли это нужно, но всёже)
				}	

				// Если есть картинка из источника - пытаемся её обработать
				if ($imageUrl != $noimage) {					
					$imgNameOut = $imageUrl;

					// Если включен граббинг картинок - пытаемся их тянуть
					if ($grab_img !='0') {
						// Если нет нужной папки - создаём её и устанавливаем нужные права
						if(!is_dir($dir)){						
							@mkdir($dir, 0755, true);
							@chmod($dir, 0755);
						} 
						if(!chmod($dir, 0755)) {
							@chmod($dir, 0755);
						}
						
						// подрубаем класс для ресайза (и граббинга) картинок
						include_once AUTORSS_DIR.'resize_class.php';

						// Разделяем высоту и ширину
						$imgSizes = explode('x', $imgSize); 	

						// Если указана только одна величина - присваиваем второй первую, будет квадрат для exact, auto и crop, иначе класс ресайза жестоко тупит, ожидая вторую переменную.
						if(count($imgSizes) == '1') 
							$imgSizes[1] = $imgSizes[0];
						$imgWidth = intval($imgSizes[0]);
						$imgHeight = intval($imgSizes[1]);

						// Назначаем переменной новое значение.
						$imgForResize = $imgNameOut;
						// Определяем имя будующей картинки.
						$imgName = date("md").substr(basename($imgNameOut), -14);
						// Если картинки не существует - создаём её.
						if(!file_exists($dir.$imgName)) {
							$resizeImg = new resize($imgForResize);
							$resizeImg -> resizeImage(
								$imgWidth,
								$imgHeight,
								$resizeType
								); 
							$resizeImg -> saveImage($dir.$imgName, 75); 
						}
						// И передаём дальше уже адрес отресайзенной картинки.
						$imgNameOut = '/uploads/rss/'.$dir_prefix.$imgName;
					}

				} 
			}
			// Формируем картинку для вставки в новость.
			$imageTag = (!$dasable_img) ? '<img class="post-image" src="'.$imgNameOut.'" alt="'.$title.'" /><br /> ' : '';

			// Обрезанная краткая новость
			$content['description'] = safeParse($content['description']);
			$shortText = $imageTag.' '.textLimit($content['description'], $text_limit);

			// Полная новость с уделённым форматированием
			$fullText = $imageTag.' '.textLimit($content['description'], 0);

			// Сложный процесс превращения категорий, приходящих с RSS тегов DLE
			$contentTags = '';
			if ($content['category'] !='') {
				$strCat = strtolower(str_replace("\n", ",", str_replace(array("\r","\t", "'", '"', "`"),"",trim($content['category']))));
				$arrCat = explode(',', $strCat);
				$arrCat = array_unique($arrCat);
				$contentTags = implode(', ', $arrCat);
			}
			
			/**
			 * Подготовка содержимого
			 */

			// Формирование ссылок на источник
			$sourceLink       = explode(',', $rssline['description']);
			$content['link']  = safeParse($content['link']);
			
			if($pseudoLinks == true) {
				$addSourceLink    = $source_text.": <span class=\"link\" title=\"Источник публикации\" data-target-".$source_target."=\"" . $content['link'] . "\">" . $sourceLink[0] . "</span>";
			} else {
				$addSourceLink    = $source_text.": <!--noindex--><a rel=\"nofollow\" class=\"link\" title=\"Источник публикации\" href=\"" . $content['link'] . "\" target=\"_".$source_target."\">" . $sourceLink[0] . "</a><!--/noindex-->";
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
			$lastdate     = $xml->lastdate;
		

			/**
			 * Теперь готовим контент для записи в БД	
			 */

			$short_story = $content['short'];
			$full_story  = $content['full'];
			
			$alt_name = strtolower(translit(textLimit($title, $chpu_cut, false)));

			/**
			 * Определяем имя пользователя, от которого будет добавляться новость (по умолчанию @$author_login)
			 */
			if ($allowNewUsers == true && $content['author'] !='' && $test == false) {
				$curUser = $dle_api->take_user_by_name(safeParse($content['author']), 'name');
				$qi++;
				$newsAuthor = $curUser['name'];
			
				if($curUser == false) {
					$newUser = $db->safesql(safeParse($content['author']));
					$translAuthor = translit($newUser);
					$password = textLimit($translAuthor, 3, false).'_'.generateHash();
					$email = trim($translAuthor.'@'.str_replace(array('http://', '/'), '', $config['http_home_url']));
					$addNewUser = newUserRegister($newUser, $password, $email, $newUserGroup);
					$newsAuthor = ($addNewUser == '1') ? $newUser : $newsAuthor;
					$qi++;
					$userindex++;
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
				$qi++;
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
				$db->query("UPDATE " . USERPREFIX . "_users set news_num=news_num+1 where name='{$newsAuthor}'");
				$qi++;
				$qi++;
				$qi++;
				$qi++;
				
				if ($tags != "") {
					
					$tags_arr = array();
					$tags     = explode(",", $tags);
					
					foreach ($tags as $value) {
						$tags_arr[] = "('" . $row['id'] . "', '" . trim($value) . "')";
					} //$tags as $value
					
					$tags_arr = implode(", ", $tags_arr);
					$db->query("INSERT INTO ".PREFIX."_tags (news_id, tag) VALUES " . $tags_arr);
					$qi++;
					
				}

				if (isset($tags))
					unset($tags);
				
				$addindex++;
			}
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


			if($nolinks == true && $existTitle == '0') {
				$addedNews = $dle_api->db->super_query("SELECT id, title FROM ".PREFIX."_post WHERE title='" . $title . "'");
				$qi++;
				if(!empty($addedNews)) {
					$addedNewsLink .= '<li><a href="/index.php?newsid='.$addedNews['id'].'" target="_blank">'.$addedNews['title'].'</a></li>';
				}
			}

		} //$xml->content as $content
	}
	
	
	if ($channelid && $lastdate) {
		if ($test == false) {
			$db->query("UPDATE ".PREFIX."_rss SET lastdate='$lastdate' WHERE id='$channelid'");		
			$qi++;
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
echo <<<HTML
<title>Автоимпорт новостей</title>
<style>pre{background:#fdf6e3;border-color:rgba(0,0,0,0.3);border-style:solid;border-width:30px 2px 2px;color:#586e75;display:block;font:normal 14px/20px Consolas,'Courier New',monospace;padding:20px;margin:20px;position:relative;text-shadow:0 1px 1px #fff;-webkit-border-radius:5px;border-radius:5px;-moz-box-shadow:inset 0 -1px 10px 0 rgba(0,0,0,0.1),inset 0 1px 0 0 rgba(0,0,0,0.5),0 0 30px 0 rgba(255,255,255,0.5);box-shadow:inset 0 -1px 10px 0 rgba(0,0,0,0.1),inset 0 1px 0 0 rgba(0,0,0,0.5),0 0 30px 0 rgba(255,255,255,0.5);white-space:pre;white-space:pre-wrap;word-break:break-all;word-wrap:break-word}pre::-moz-selection{background:#073642;text-shadow:0 1px 1px #000;color:#fff}pre::selection{background:#073642;text-shadow:0 1px 1px #000;color:#fff}pre:after{color:#fff;content:attr(data-text);font:normal 16px/30px Consolas,'Courier New',monospace;height:30px;left:20px;position:absolute;right:20px;text-shadow:0 1px 3px rgba(0,0,0,0.7);top:-30px}h2{cursor:pointer;}</style>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
 	<script> 
 		jQuery(document).ready(function ($) {
 			$("h2").click(function () {
				nextDiv = $(this).next("div");
 				if ($(this).hasClass('current')) {
					$("h2").removeClass('current').next().hide();
 				} else {
					$("h2").removeClass('current').next().hide();
 					$(this).addClass('current');
					nextDiv.show(); 					
 				};
			});
		}); 
	</script>
	<br /><b>Отладка:</b><br /><ol>$testcontent</ol>
HTML;

}
echo '<p style="color:red;">Время выполнения: <b>'. round((microtime(true) - $start), 6). '</b> c. <br /> Затраты памяти: <b>'.$mem_usg.'</b> <br />Кол-во запросов: <b>~'.$qi.'</b> <br />Добавлено новых пользователей: <b>'.$userindex.'</b></p>';
?>
