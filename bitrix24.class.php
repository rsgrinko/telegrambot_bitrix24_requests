<?php
	/*
	 	Класс для работы с заявками Битрикс24
	 	Разработчик: Роман Сергеевич Гринько
	 	Cайт: https://it-stories.ru
	 	E-Mail: rsgrinko@gmail.com	 	
	*/
	
	class Bitrix24 {
		public $ALL_REQUESTS = 0;
		public $AWAIBLE_REQUESTS = 0;
		
		private $CRM;
		private $LOGIN;
		private $PASSWORD;
		private $USER_AGENT;
		private $COOKIE;
		private $SSID;
		private $AUTH_ID;
		private $REFRESH_ID;
		private $AJAX_ID;
		
		/*
			Забиваем переменные тем, что передали при создании экземпляра класса
		*/
		public function __construct($login, $pass, $crm, $useragent = 'Telegram bot / test api v1.0.0'){
			$this->CRM = $crm;
			$this->LOGIN = $login;
			$this->PASSWORD = $pass;
			$this->USER_AGENT = $useragent;
			$this->SSID = file_get_contents('ssid.txt');
		}
		
		/*
			Метод для отправки запроса и получения ответа
		*/
		private function curlRequest($url, $post_var, $cookies){
			$ch = curl_init(); 
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
			curl_setopt ($ch, CURLOPT_USERAGENT, $this->USER_AGENT);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_var);

			$request = curl_exec($ch); 
			curl_close($ch);
			
			return $request;
				
		}
		
		/*
			Метод, производящий авторизацию в облачном Битрикс24
			Позволяет получить SSID и Cookies для дальнейшего использования
		*/
		public function Authorize(){
			$site_url = 'https://auth2.bitrix24.net/bitrix/services/main/ajax.php?action=b24network.authorize.check';
			$post_var = 'login='.$this->LOGIN.'&password='.$this->PASSWORD.'&remember=1&SITE_ID=s1';
			
			/* Получаем SSID */
			$text = $this->curlRequest($site_url, $post_var, 'cookie.txt');
			$text = json_decode($text);
			$text = $text->errors;
			$text = $text[0];
			$text = $text->customData;
			$text = $text->csrf;
			$ssid = $text;
			$this->SSID = $ssid;
			file_put_contents('ssid.txt', $ssid);
			
			/* Производим авторизацию и получение куков */
			$site_url = 'https://auth2.bitrix24.net/bitrix/services/main/ajax.php?action=b24network.authorize.check';
			$post_var = 'login='.$this->LOGIN.'&password='.$this->PASSWORD.'&remember=1&SITE_ID=s1&sessid='.$ssid;
			
			$text = $this->curlRequest($site_url, $post_var, 'cookie.txt');
			
			/* Куки есть, дополняем их запросом в саму CRM */
			$site_url2 = 'https://'.$this->CRM;
			$post_var = $post_var.'&sessid='.$ssid;
			$text = $this->curlRequest($site_url2, $post_var, 'cookie.txt');
			
			/* Ну и для верности в маркетплэйсе */
			$site_url = 'https://'.$this->CRM.'/marketplace/app/11/?sessid='.$ssid; 
			$post_var = 'DOMAIN='.$this->CRM;
			
			$text = $this->curlRequest($site_url, $post_var, 'cookie.txt');
			
			return true;
		}
		
		/*
			Метод для получения данных из CRM и обновления данных в переменных класса
		*/
		public function checkRequests(){
			$site_url = 'https://'.$this->CRM.'/bitrix/admin/user_options.php?p[0][c]=app_options&p[0][n]=options_app.5968a2f6885b38.23791993&p[0][v][page]=MAIN&sessid='.$this->SSID.'&_=1611942206043'; 
			$post_var = 'DOMAIN='.$this->CRM;
			$text = $this->curlRequest($site_url, $post_var, 'cookie.txt');
			//тут запрос должен венруть ОК
			
			$site_url = 'https://'.$this->CRM.'/marketplace/app/11/?bxrand=1611943288719'; 
			$post_var = 'DOMAIN='.$this->CRM;
			$text = $this->curlRequest($site_url, $post_var, 'cookie.txt');
			
			$text = explode('location.href=\'', $text);
			$text = explode('\'</sc', $text[1]);
			
			$site_url = $text[0];
			$post_var = 'DOMAIN='.$this->CRM;
			$text = $this->curlRequest($site_url, $post_var, 'cookie.txt');
			
			
			
			/*
				Тут мы получаем AUTH_ID, необходимый для работы прилодения
			*/
			$site_url = 'https://'.$this->CRM.'/marketplace/app/11/?current_fieldset=SOCSERV';
			$post_var = 'DOMAIN='.$this->CRM;
			$text = $this->curlRequest($site_url, $post_var, 'cookie.txt');
			
			
			$aid = explode('AUTH_ID" value="', $text);
			$aid = explode('" />', $aid[1]);
			$aid = $aid[0];
			$this->AUTH_ID = $aid;
			
			$rid = explode('REFRESH_ID" value="', $text);
			$rid = explode('" />', $rid[1]);
			$rid = $rid[0];
			$this->REFRESH_ID = $rid;
			
			$mid = explode('member_id" value="', $text);
			$mid = explode('">', $mid[1]);
			$mid = $mid[0];
			$this->MEMBER_ID = $mid;
			
			
			/*
				Тут выполняется основная работа - получение необходимых данных со страницы приложения
				https://util.1c-bitrix.ru/b24application/work.php
			*/
			$site_url = 'https://util.1c-bitrix.ru/b24application/work.php';
			$post_var = 'AUTH_ID='.$this->AUTH_ID.'&REFRESH_ID='.$this->REFRESH_ID.'&member_id='.$this->MEMBER_ID.
						'&DOMAIN='.$this->CRM.'&PROTOCOL=1&IS_ADMIN=false';
			$page = $this->curlRequest($site_url, $post_var, 'cookie.txt');
			
			$ajaxid = explode('data-ajaxid="', $page);
			$ajaxid = explode('">', $ajaxid[1]);
			$ajaxid = $ajaxid[0];
			$this->AJAX_ID = $ajaxid;
			
			
			/*
				На этом этапе мы уже имеем страницу, которую можно парсить
			*/
			/********************************************************/
			/*$site_url = 'https://util.1c-bitrix.ru/b24application/work.php';
						
			$post_var = 'AUTH_ID='.$this->AUTH_ID.'&AUTH_EXPIRES=3600&REFRESH_ID='.$this->REFRESH_ID.'&member_id='.$this->MEMBER_ID.'&status=F&PLACEMENT=DEFAULT';
			$text = $this->curlRequest($site_url, $post_var, 'cookie.txt');*/



			$site_url = 'https://util.1c-bitrix.ru/bitrix/services/main/ajax.php?analyticsLabel[FILTER_ID]=b24_partner_application_filter&analyticsLabel[GRID_ID]=b24_partner_application&analyticsLabel[PRESET_ID]=tmp_filter&analyticsLabel[FIND]=N&analyticsLabel[ROWS]=N&mode=ajax&c=bitrix%3Amain.ui.filter&action=setFilter';
						
			$post_var = 'apply_filter=Y&clear_nav=Y';
			$text = $this->curlRequest($site_url, $post_var, 'cookie.txt');
			
			/* Получаем временный ssid */
			$tmp_ssid = explode('{"csrf":"', $text);
			$tmp_ssid = explode('"', $tmp_ssid[1]);
			$tmp_ssid = $tmp_ssid[0];
			$text = $tmp_ssid;
			
			
			$site_url = 'https://util.1c-bitrix.ru/bitrix/services/main/ajax.php?analyticsLabel[FILTER_ID]=b24_partner_application_filter&analyticsLabel[GRID_ID]=b24_partner_application&analyticsLabel[PRESET_ID]=tmp_filter&analyticsLabel[FIND]=N&analyticsLabel[ROWS]=N&mode=ajax&c=bitrix%3Amain.ui.filter&action=setFilter';
			$post_var = 'params[FILTER_ID]=b24_partner_application_filter&params[GRID_ID]=b24_partner_application&params[action]=setFilter&params[forAll]=false&params[commonPresetsId]=&params[apply_filter]=Y&params[clear_filter]=N&params[with_preset]=N&params[save]=Y&data[fields][FIND]=&data[fields][PARTNERSHIP]=STANDARD&data[fields][DATE_ACTIVE_datesel]=NONE&data[fields][DATE_ACTIVE_from]=&data[fields][DATE_ACTIVE_to]=&data[fields][DATE_ACTIVE_days]=&data[fields][DATE_ACTIVE_month]=&data[fields][DATE_ACTIVE_quarter]=&data[fields][DATE_ACTIVE_year]=&data[fields][CITY]=&data[fields][APPLICATION_BUSY]=&data[fields][IMPLEMENTATION]=&data[fields][MY_OFFERS]=&data[fields][APPLICATION_STATE]=&data[fields][SHOW_ALL]=&data[fields][PERSONAL]=&data[fields][EVENTS]=&data[fields][PARTNER_DEAL_ID]=&data[rows]=PARTNERSHIP%2CDATE_ACTIVE%2CCITY%2CAPPLICATION_BUSY%2CIMPLEMENTATION%2CMY_OFFERS%2CAPPLICATION_STATE%2CSHOW_ALL%2CPERSONAL%2CEVENTS%2CPARTNER_DEAL_ID&data[preset_id]=tmp_filter&data[name]=%D0%A4%D0%B8%D0%BB%D1%8C%D1%82%D1%80&SITE_ID=ut&sessid='.$tmp_ssid;
			$text = $this->curlRequest($site_url, $post_var, 'cookie.txt');
			
			
			
			$site_url = 'https://util.1c-bitrix.ru/b24application/work.php?sessid='.$tmp_ssid.'&internal=true&grid_id=b24_partner_application&apply_filter=Y&clear_nav=Y&grid_action=showpage&bxajaxid=3f0f6daf332358cce325c3476e63f24b';
						
			$post_var = 'params[FILTER_ID]=b24_partner_application_filter&params[GRID_ID]=b24_partner_application&params[action]=setFilter&params[forAll]=false&params[commonPresetsId]=&params[apply_filter]=Y&params[clear_filter]=N&params[with_preset]=N&params[save]=Y&data[fields][FIND]=&data[fields][PARTNERSHIP]=STANDARD&data[fields][DATE_ACTIVE_datesel]=NONE&data[fields][DATE_ACTIVE_from]=&data[fields][DATE_ACTIVE_to]=&data[fields][DATE_ACTIVE_days]=&data[fields][DATE_ACTIVE_month]=&data[fields][DATE_ACTIVE_quarter]=&data[fields][DATE_ACTIVE_year]=&data[fields][CITY]=&data[fields][APPLICATION_BUSY]=&data[fields][IMPLEMENTATION]=&data[fields][MY_OFFERS]=&data[fields][APPLICATION_STATE]=&data[fields][SHOW_ALL]=&data[fields][PERSONAL]=&data[fields][EVENTS]=&data[fields][PARTNER_DEAL_ID]=&data[rows]=PARTNERSHIP%2CDATE_ACTIVE%2CCITY%2CAPPLICATION_BUSY%2CIMPLEMENTATION%2CMY_OFFERS%2CAPPLICATION_STATE%2CSHOW_ALL%2CPERSONAL%2CEVENTS%2CPARTNER_DEAL_ID&data[preset_id]=tmp_filter&data[name]=%D0%A4%D0%B8%D0%BB%D1%8C%D1%82%D1%80&SITE_ID=ut&sessid='.$tmp_ssid;
			$text = $this->curlRequest($site_url, $post_var, 'cookie.txt');		
			
			
			/* Самым наиглупейшим образом парсим данные */
			$text = explode('<tbody>', $text);
			$text = $text[1];
			$text = explode('</tbody>', $text);
			$text = $text[0];
			$text = strip_tags($text, '<span>');
			
			$text = str_replace(' class="main-grid-cell-content"', '', $text);
			$text = str_replace('<span></span>', '', $text);
			$text = str_replace(' data-prevent-default="true"', '', $text);
			
			$text = explode('<span class="partner-application-b24-list-description-outer js-description-outer">', $text);
			
			$arResult = array();
			foreach($text as $key => $item){
				if($key==0) continue;
				
				$string = str_replace('<span class="partner-application-b24-list-description-inner js-description-inner">', '', $item);
				
				$string = explode('</span><span>', $string);
				$aaa = array();
				foreach($string as $value){
					
				$aaa[] = str_replace('<span class="partner-application-b24-list-description js-description-text">', '', str_replace("\n", '', str_replace('	', '', str_replace('</span>', '', str_replace('<span>','', $value)))));
				}
				$arResult[] = $aaa;
			}
			
		
			/*
				Имеем массив $arResult с последними по дате 20 заявками. Ура.
			*/
			
			
			$tmp_result = array();
			
			for($i=0; $i<count($arResult); $i++){
				if(stristr($arResult[$i][4], 'Взять заявку')) {
					$arResult[$i][4] = 'Взять заявку';
					$tmp_result[] = $arResult[$i];
					
					}
			}
			unset($arResult);
			for($i=0; $i<count($tmp_result); $i++){
				$arResult[$i] = $tmp_result[$i];
				$arResult[$i][7] = md5($tmp_result[$i][0]);
			
			}
			
			
			
			$this->AWAIBLE_REQUESTS = count($arResult);
			
			
			return $arResult; // Возвращаем массив последних доступных заявок			
}
}	