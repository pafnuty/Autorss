Autorss
=======

Вот все дефолтные настройки:
```
$checkDouble   = true;  	                           // Проверка на дубли (добавляет +1 запрос на каждую новость), чтобы отключить - нужно вписать false;
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

offline       
noimage       
checkDouble   
text_limit    
chpu_cut      
author_login  
allowNewUsers 
newUserGroup  
source_text   
pseudoLinks   
source_target 
```
