<?php
	/*
		Главный конфиг системы 
		Разработчик: Роман Сергеевич Гринько
	 	Cайт: https://it-stories.ru
	 	E-Mail: rsgrinko@gmail.com
	*/
	/* Настройки бота */
	define('TOKEN', 'QWERTY12345678900YEYEYEYY');                      // Токен бота
	define('URL', 'https://api.telegram.org/bot'.TOKEN);               // Адрес запроса
	$ADMIN_ID = '412790359';                                           // ID администратора бота (узнать можно отправив команду тест боту)
	
	/* Настрокйи CRM */
	$crm = 'companyname.bitrix24.ru';             					   // Адрес CRM Битрикс24
	$login = 'login@domain.ru';              						   // Логин на портале, с доступом к разделу Заявки
	$pass = 'MyPassw';              						           // Пароль пользователя CRM
?>