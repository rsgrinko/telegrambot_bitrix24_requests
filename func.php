<?php
	/*
		Файл, содержащий вспомогательные функции 
		Разработчик: Роман Сергеевич Гринько
	 	Cайт: https://it-stories.ru
	 	E-Mail: rsgrinko@gmail.com
	*/

/* Отрпвка сообщений в телеграм (вспомогательная) */
function sendTelegram($method, $response)
{
	$ch = curl_init('https://api.telegram.org/bot'.TOKEN.'/'.$method);  //?parse_mode=html
	curl_setopt($ch, CURLOPT_POST, 1);  
	curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	$res = curl_exec($ch);
	curl_close($ch);
 
	return $res;
}

/* Выполнение запроса */
function execRequest($telegram_req_url){
	$telegram_ch = curl_init();
	curl_setopt($telegram_ch, CURLOPT_URL, $telegram_req_url);
	curl_setopt($telegram_ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($telegram_ch, CURLOPT_HTTPGET, true);		// необязательно
	curl_setopt($telegram_ch, CURLOPT_SSL_VERIFYPEER, false);	// отменяем проверку сертификатов
	curl_setopt($telegram_ch, CURLOPT_SSL_VERIFYHOST, false);	// (это для тестов, ну а что делать)
	curl_setopt($telegram_ch, CURLOPT_MAXREDIRS, 10);		// необязательно
	curl_setopt($telegram_ch, CURLOPT_CONNECTTIMEOUT, 5);		// необязательно (таймаут попытки подключения)
	curl_setopt($telegram_ch, CURLOPT_TIMEOUT, 20);			// необязательно (таймаут выполнения запроса)
 
	$telegram_ch_result = curl_exec($telegram_ch);
	
	return $telegram_ch_result;
}


/* Отправка сообщения в телеграм (вспомогательная) */
function telegram_send($message, $chat_id, $keys = ''){
$message = str_replace("\n", '%0A', $message);
$message = str_replace('<br>', '%0A', $message);
$message = str_replace('<br />', '%0A', $message);
$message = str_replace(' ', '%20', $message);

//file_get_contents('https://api.telegram.org/bot'.TELEGRAM_TOKEN.'/sendmessage?chat_id='.$chat_id.'&parse_mode=HTML&text='.$message);
execRequest('https://api.telegram.org/bot'.TOKEN.'/sendmessage?chat_id='.$chat_id.'&parse_mode=HTML&reply_markup='.$keys.'&text='.$message);
}



/* Основной класс для работы с телеграмм */

class BOT {
    /*
	    sendMessage принимает агрументы
     @ $chatid           - ид получателя
     @ $msg              - сообщение
     @ $keyboard         - клавиатура
     @ $keyboard_opt[0]  - тип клавиатуры keyboard/inline_keyboard
     @ $keyboard_opt[1]  - спрятать клавиатуру при клике
     @ $keyboard_opt[2]  - авторазмер клавиатуры при клике
     @ $parse_preview[0] - маркировка html/markdown
     @ $parse_preview[1] - предпросмотр ссылок
     */
    
    function sendMessage($chatid, $msg, $keyboard = [], $keyboard_opt = [], $parse_preview = ['html', false]) {
        if(empty($keyboard_opt)) {
            $keyboard_opt[0] = 'keyboard';
            $keyboard_opt[1] = false;
            $keyboard_opt[2] = true;
        }
        $options = [
            $keyboard_opt[0]    => $keyboard,
            'one_time_keyboard' => $keyboard_opt[1],
            'resize_keyboard'   => $keyboard_opt[2],
        ];
        $replyMarkups   = json_encode($options);
        $removeMarkups  = json_encode(['remove_keyboard' => true]);

        /* Если в массиве $keyboard передается [0], то клавиатура удаляется */
        if($keyboard == [0]) {
	        file_get_contents(URL.'/sendMessage?disable_web_page_preview='.
	        					$parse_preview[1].'&chat_id='.$chatid.
	        					'&parse_mode='.$parse_preview[0].
	        					'&text='.urlencode($msg).
	        					'&reply_markup='.urlencode($removeMarkups));
	     }

        /* Или же если в массиве $keyboard передается [], то есть пустой массив, то клавиатура останется прежней */
        elseif($keyboard == []) {
	        file_get_contents(URL.'/sendMessage?disable_web_page_preview='.
	        					$parse_preview[1].'&chat_id='.$chatid.
	        					'&parse_mode='.$parse_preview[0].
	        					'&text='.urlencode($msg));
	    }

        /* Если вышеуказанные условия не соблюдены, значит в $keyboard передается клавиатура, которую вы создали */
        else {
	        file_get_contents(URL.'/sendMessage?disable_web_page_preview='.
	        					$parse_preview[1].'&chat_id='.$chatid.
	        					'&parse_mode='.$parse_preview[0].
	        					'&text='.urlencode($msg).
	        					'&reply_markup='.urlencode($replyMarkups));
	    }
    }
}
?>