<?php
	/*
		Файл, запускаемый по крону раз в 5 минут.
		Нужен для оповещения менеджеров о новых заявках в Битрикс24
		Разработчик: Роман Сергеевич Гринько
	 	Cайт: https://it-stories.ru
	 	E-Mail: rsgrinko@gmail.com
	*/

set_time_limit(15);
require_once 'config.php';
require_once 'func.php';
require_once 'bitrix24.class.php';



$flag = false;
	$output_array = array();
	
	
	$B24 = new Bitrix24('rg@seoven.ru', 'j2medit', 'seoven.bitrix24.ru');
	
	if (file_exists('flag.txt') && time()-3600 < filemtime('flag.txt')) {
		$arResult = $B24->checkRequests();
	} else {
		$B24->Authorize();
		$arResult = $B24->checkRequests();
		file_put_contents('flag.txt', 'string');
	}
    
    if(count($arResult) > (int)file_get_contents('data.txt')){
	    /* Если новых заявок стало больше чем было, то... */
	    $folder = './requests/';
		
	    foreach($arResult as $key => $item){
		    if(!file_exists($folder.$item[7].'.txt')){
			$flag = true; 
		    $output_array[] = $item;
		    }
	    }
		
	    
	    
	    
	    
	    
	    
	    
	    
	    /*  Чистим кэш заявок */
	    foreach(scandir($folder) as $k=>$v){
		    if($v == '.' or $v =='..') continue;
		    unlink($folder.$v);
	    }
	    
	    /*  Логируем заявки */
	    foreach($arResult as $key => $value){
		    $string = str_replace(':||:', '', $value[0]);
		    $string = preg_split('#([\n\r]+)#Usi',$string);
		    $string = implode(' ', $string);
		    file_put_contents($folder.$value[7].'.txt', $value[2].':||:'.$value[1].':||:'.$string);
	    }
	    
	    file_put_contents('data.txt', count($arResult));
    } else {
	    /*Если количество заявок не поменялось или стало меньше*/
	    file_put_contents('data.txt', count($arResult));
    }
    /***********************/
    
    
 if($flag) {
	 $bot = new BOT();
	 $requests_c = count($output_array);
	 echo 'flag TRUE ('.count($output_array).')';
	 echo '<pre>'.print_r($output_array,true).'</pre>';
	 
	 $message = '<b>!!! ЕСТЬ НОВЫЕ ЗАЯВКИ !!!</b>'."\n";
	 
	 //telegram_send('Доступны новые заявки!', '412790359'); //я
	 //telegram_send('Доступны новые заявки!', '266327248'); //паша
	 
	 
	 
	 
	 
	 foreach($output_array as $key => $item){
		$message .= '<b>ЗАЯВКА '.($key+1).'</b>'."\n";
		$message .= '<b>Город:</b> '.$item[2]."\n";
		$message .= '<b>Дата:</b> '.$item[1]."\n";
		$message .= '<b>Тип:</b> '.$item[6]."\n";
		$message .= '<b>Система:</b> '.$item[5]."\n"; 
		$message .= '<b>Текст заявки:</b> '."\n";
		$message .= '<i>'.$item[0].'</i>'."\n\n"; 
	 }
	 
	 //echo '<pre>'.$message.'</pre>';
	 //telegram_send(urlencode($message), '412790359'); //я
	 //telegram_send(urlencode($message), '266327248'); //паша
	 foreach(scandir('data/users/') as $k =>$v){
		 if($v == '.' or $v == '..') continue;
		 $user = 'data/users/'.$v;
		 $arUser = explode(':||:', file_get_contents($user));
		 if($arUser[4]=='1') {
			//telegram_send(urlencode($message), $arUser[0]); 
			$bot->sendMessage($arUser[0], $message,
									[['/help', '/about']],
									['keyboard', false, true],
									['html', true]);
			
		 }
		 
	 }
	 
	 
	 }  else {
		 echo 'flag false';
	 } 
//telegram_send('end', '412790359');





















