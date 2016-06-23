<?php
	chdir('../');
	error_reporting(E_ERROR);
	require_once 'class/translate.class.php';
	require_once 'cap.create.class.php';

	$langs = new Translate();

	if(file_exists('conf/conf.php'))
	{
		include 'conf/conf.php';
		if(! empty($_GET['lang'])) $conf->user->lang = $_GET['lang'];
		$langs->setDefaultLang($conf->user->lang);		
		$langs->load("main");	
	}

	$language = array();
	/**
	 * Output RFC 3066 Array
	 *     
	 * @return	string						Array with RFC 3066 Array
	 */
	function getlang($config = false){
		global $conf, $language;
		
		if(is_array($language))
		{
			foreach($language as $key => $lang_name)
			{
				$out[$lang_name] = $lang_name;
			}
		}

		$out_tmp = $conf->lang;

		foreach($out_tmp as $key => $lang_name)
		{
			if($conf->select->lang[$key] == true) $out[$key] = $out_tmp[$key];
		}
		
		return $out;
	}

	/**
	* encrypt and decrypt function for passwords
	*     
	* @return	string
	*/
	function encrypt_decrypt($action, $string, $key = "") 
	{
		global $conf;
		
		$output = false;

		$encrypt_method = "AES-256-CBC";
		$secret_key = ($key?$key:'NjZvdDZtQ3ZSdVVUMXFMdnBnWGt2Zz09');
		$secret_iv = ($conf->webservice->securitykey ? $conf->webservice->securitykey : 'WebTagServices#hash');

		// hash
		$key = hash('sha256', $secret_key);
		
		// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = substr(hash('sha256', $secret_iv), 0, 16);

		if( $action == 1 ) {
			$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
			$output = base64_encode($output);
		}
		else if( $action == 2 ){
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}

		return $output;
	}
	

	$severity = array(
		1 => 'Minor',
		2 => 'Moderate',
		3 => 'Severe',
		4 => 'Extreme'
	);

	$event_type = array(
		1 => 'Wind',
		2 => 'snow-ice',
		3 => 'Thunderstorm',
		4 => 'Fog',
		5 => 'high-temperature',
		6 => 'low-temperature',
		7 => 'coastalevent',
		8 => 'forest-fire',
		9 => 'avalanches',
		10 => 'Rain',
		12 => 'flooding',
		13 => 'rain-flood'
	);

	$headline_level = array(
		1 => 'Green',
		2 => 'Yellow',
		3 => 'Orange',
		4 => 'Red'
	);

	$awareness_level = array(
		1 => '1; green; Minor',
		2 => '2; yellow; Moderate',
		3 => '3; orange; Severe',
		4 => '4; red; Extreme'
	);

	$awareness_type = array(
		1 => '1; Wind',
		2 => '2; snow-ice',
		3 => '3; Thunderstorm',
		4 => '4; Fog',
		5 => '5; high-temperature',
		6 => '6; low-temperature',
		7 => '7; coastalevent',
		8 => '8; forest-fire',
		9 => '9; avalanches',
		10 => '10; Rain',
		12 => '12; flooding',
		13 => '13; rain-flood'
	);

	// Delete all Caps in output
	$files = glob($conf->cap->output.'/*'); // get all file names
	foreach($files as $file){ // iterate files
		if(is_file($file) && empty($_POST['no_del'])) unlink($file); // delete file
	}


	$conf->meteoalarm = 1;
	if($conf->meteoalarm == 1)
	{			
		if($conf->webservice->on > 0)
		{
			if(file_exists('lib/cap.meteoalarm.webservices.vl.php'))
			{
				require_once 'includes/nusoap/lib/nusoap.php';		// Include SOAP
				include 'lib/cap.meteoalarm.webservices.vl.php';		
				if($_GET['web_test'] == 1) die(print_r($AreaCodesArray));
				if(!empty($AreaCodesArray['document']['AreaInfo']))
				{
					$AreaCodesArray = $AreaCodesArray['document']['AreaInfo'];
				}
			}
		}
	}

	//print '<pre>vl: ';
	//	print_r($AreaCodesArray);
	//print '</pre>';

	foreach($AreaCodesArray as $key => $vl_warn)
	{
		$cap_ident[$vl_warn['type']][$vl_warn['EMMA_ID']]['id'] 		= $vl_warn['identifier'];
		$cap_ident[$vl_warn['type']][$vl_warn['EMMA_ID']]['level'] 		= $vl_warn['level'];
		$cap_ident[$vl_warn['type']][$vl_warn['EMMA_ID']]['sender'] 	= $vl_warn['sender'];
		$cap_ident[$vl_warn['type']][$vl_warn['EMMA_ID']]['timestamp'] 	= $vl_warn['timestamp'];
	}

	//print '<pre>ident: ';
	//	print_r($cap_ident);
	//print '</pre>';

	$cap_data = array();
	if($_POST['cap_array']) $cap_array_tmp = $_POST['cap_array'];
	if($_GET['cap_array']) $cap_array_tmp = $_GET['cap_array'];
	$cap_array = json_decode($cap_array_tmp);

	//print '<pre>cap array: ';
	//	print_r($cap_array);
	//print '</pre>';
	foreach($cap_array as $aid => $warr)
	{
		//print $warr->name;
		//if(count($warr[0]) > 0)
		foreach($warr as $key => $warning)
		{
			if($warning->level > 0)
			{
				//print '<br>'.$warning->type.' '.$warning->eid.' '.$cap_ident[$warning->type][$warning->eid];
				$ident = $cap_ident[$warning->type][$warning->eid]['id'];
				if($ident != "" && $ident == $warning->ident && $cap_ident[$warning->type][$warning->eid]['level'] == $warning->level)
				{
					// made no warning
				}
				elseif($ident != "")
				{
					// made a Update
					$warning->sender 		= $cap_ident[$warning->type][$warning->eid]['sender'];
					$warning->timestamp 	= $cap_ident[$warning->type][$warning->eid]['timestamp'];
					$warning->references 	= $ident;
					$warning->aid = $aid;
					$cap_data['Update'][$warning->type][$warning->level][addslashes($warning->from)][addslashes($warning->to)][addslashes($warning->text_0)][] = $warning;
				}
				else
				{
					// made a Alert
					$warning->aid = $aid;
					$cap_data['Alert'][$warning->type][$warning->level][addslashes($warning->from)][addslashes($warning->to)][addslashes($warning->text_0)][] = $warning;
				}
			}
			else
			{
				$white_data[$aid] = $warning;
			}
		}
	}

	//print '<pre>cap data: ';
	//	print_r($cap_data);
	//print '</pre>';

	$langs_arr = getlang();	
							
	foreach($langs_arr as $key_l => $val_l)
	{
		if(in_array($key,$language)) unset($langs_arr[$key]);
	}
	foreach ($langs_arr as $key_l => $val_l) 
	{
		$langs_keys[] = $key_l;
	}

	$capid = 0;
	foreach($cap_data as $ref => $ref_arr)
	{
		foreach($ref_arr as $type => $level_arr)
		{
			//print '<br>type:'.$type;
			// Neues CAP
			foreach ($level_arr as $level => $area_arr)
			{
				//print '<br>level:'.$level;
				foreach($area_arr as $from => $from_arr)
				{
					//print '<br>from:'.$from;
					foreach($from_arr as $to => $to_arr)
					{
						//print '<br>to:'.$to;
						// Neues CAP
						foreach($to_arr as $text_0 => $data_arr)
						{
							if($data_arr[0]->type > 0 && $data_arr[0]->level > 0 && $aid > 0)
							{
								$post['identifier']				= $conf->identifier->WMO_OID.'.'.$conf->identifier->ISO.'.'.strtotime('now').'.1'.$data_arr[0]->type.$data_arr[0]->level.$aid;
								if($ref == "Update") 
								{
									$post['identifier']				= $conf->identifier->WMO_OID.'.'.$conf->identifier->ISO.'.'.strtotime('now').'.2'.$data_arr[0]->type.$data_arr[0]->level.$aid;
									if($data_arr[0]->sender == "") $data_arr[0]->sender = "CapMapImport@meteoalarm.eu";
									$post['references'] 			= $data_arr[0]->sender.','.$data_arr[0]->references.','.date('Y-m-d\TH:i:s\+01:00', strtotime(str_replace('&nbsp;', ' ',$data_arr[0]->timestamp)));
								}
								$post['sender']					= 'admin@meteoalarm.eu';
								$post['status']['date'] 		= date('Y-m-d');
								$post['status']['time'] 		= date('H:i:s');
								$post['status']['plus'] 		= '+';
								$post['status']['UTC']  		= '01:00';
								$post['status'] 				= 'Actual';
								if($ref == "Update")
								{
									$post['msgType'] 				= 'Update'; // Or Update / Cancel
								}
								else
								{
									$post['msgType'] 				= 'Alert'; // Or Update / Cancel
								}
								$post['scope'] 					= 'Public';
								$post['event'][$langs_keys[0]]	= $severity[$data_arr[0]->level].' '.$event_type[$data_arr[0]->type].' warning';
								$post['event'][$langs_keys[1]]	= $severity[$data_arr[0]->level].' '.$event_type[$data_arr[0]->type].' warning';
								$post['category'] 				= 'Met';				
								$post['responseType']			= 'Monitor';
								$post['urgency'] 				= 'Immediate';
								$post['severity'] 				= $severity[$data_arr[0]->level];
								$post['certainty'] 				= 'Likely';
								
								$post['effective']['date'] = date("Y-m-d");
								$post['effective']['time'] = date('H:i:s', strtotime($data_arr[0]->from));
								$post['effective']['plus'] = '+';
								$post['effective']['UTC'] = '01:00';
								$post['onset']['date'] = date("Y-m-d");
								$post['onset']['time'] = date('H:i:s', strtotime($data_arr[0]->from));
								$post['onset']['plus'] = '+';
								$post['onset']['UTC'] = '01:00';
								$post['expires']['date'] = date("Y-m-d");
								$post['expires']['time'] = date('H:i:s', strtotime($data_arr[0]->to));
								$post['expires']['plus'] = '+';
								$post['expires']['UTC'] = '01:00';

								$post['senderName'] = 'ZAMG Zentralanstalt für Meteorologie und Geodynamik';

								if($data_arr[0]->text_0 != "")	
								{
									$post['language'][] = $langs_keys[0];
									$post['headline'][$langs_keys[0]] = $headline_level[$data_arr[0]->level].' '.$event_type[$data_arr[0]->type].' for '.$data_arr[0]->name;
									$post['description'][$langs_keys[0]] = $data_arr[0]->text_0;
									if($data_arr[0]->inst_0 != "") $post['instruction'][$langs_keys[0]] = $data_arr[0]->inst_0;
								}
								if($data_arr[0]->text_1 != "")
								{
									$post['language'][] = $langs_keys[1];
									$post['headline'][$langs_keys[1]] = $headline_level[$data_arr[0]->level].' '.$event_type[$data_arr[0]->type].' for '.$data_arr[0]->name;
									$post['description'][$langs_keys[1]] = $data_arr[0]->text_1;
									if($data_arr[0]->inst_1 != "") $post['instruction'][$langs_keys[1]] = $data_arr[0]->inst_1;
								}

								$post['areaDesc'] = $data_arr[0]->name;

								$post['parameter']['valueName'][0] = 'awareness_level';
								$post['parameter']['value'][0] =  $awareness_level[$data_arr[0]->level];

								$post['parameter']['valueName'][1] = 'awareness_type';
								$post['parameter']['value'][1] = $awareness_type[$data_arr[0]->type];

								foreach($data_arr as $key => $data)
								{
									$post['geocode']['value'][] = $data->eid.'<|>emma_id';
								}

								$cap = new CAP_Class($post);
								$cap->buildCap();
								$cap->destination = $conf->cap->output;
								$path = $cap->createFile();
								unset($post);
							}
						}
					}
				}

			}
		}
	}

	unset($data);
	foreach($white_data as $aid => $data)
	{
		$white_area[] = array( 'aid' => $aid,  'eid' => $data->eid, 'name' => $data->name);
	}

	echo json_encode($white_area);
	//$files = glob('../'.$conf->cap->output.'/*'); // get all file names
	//echo json_encode($files);

?>