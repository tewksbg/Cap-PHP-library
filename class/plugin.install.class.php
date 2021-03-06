<?php
/*
 *  Copyright (c) 2015  Guido Schratzer   <schratzerg@backbone.co.at>
 *  Copyright (c) 2015  Niklas Spanring   <n.spanring@backbone.co.at>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file      	/class/plugin.install.class.php
 *  \ingroup   	core
 *	\brief      JQuery Form to Insert Data to CAP-File and create config File
 */


/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 *
 */

	class Plugin{

		var $plugin_folder;

		var $name;
		var $json_array;

		var $cap_engine;

		var $svg_id;
		var $svg_name;
		var $svg_src;
		var $svg_val;

		var $area_codes;

		var $AWT;
		var $AWL;

		var $error;
		var $error_file;

		function __construct()
		{
			$this->error = array();
			$this->error[-1] = 'plugin not found!';
			$this->error[-2] = 'plugin folder not found!';
			$this->error[-3] = 'cap_engine not found!';
			$this->error[-4] = 'svg_src not valid!';
			$this->error[-5] = 'could not parse AreaCodesArray!';
			$this->error[-6] = 'could not parse ParameterArray->AWT!';
			$this->error[-7] = 'could not parse ParameterArray->AWL!';
			$this->error[-8] = 'SVG not found!';
			$this->error[-9] = 'Image not found!';
		}

		function fetch($name)
		{
			if(is_dir('plugin'))
			{
				if(is_dir('plugin/'.$name))
				{
					$this->name = $name;

					$json_string = file_get_contents('plugin/'.$name.'/'.$name.'.json');
					$this->json_array = json_decode($json_string);

					if(count($this->json_array) < 1) return -1;

					if(!empty($this->json_array->CAP->engine->src)) $this->cap_engine = 'plugin/'.$name.'/'.$this->json_array->CAP->engine->src;
					else  $this->cap_engine = 'lib/cap.standard.producer.php';

					if(!is_file($this->cap_engine)) return -3;

					$this->svg_id = $this->json_array->SVG->info->id;
					$this->svg_name = $this->json_array->SVG->info->name;
					$this->svg_src = 'plugin/'.$name.'/'.$this->json_array->SVG->info->svgsrc;

					$this->svg_val = file_get_contents($this->svg_src);

					foreach($this->json_array->AreaCodesArray as $aid => $area_data)
					{
						if($id == '_comment') continue 1;
						$this->area_codes[$aid]['aid'] = $aid;
						$this->area_codes[$aid]['AreaCaption'] = $area_data->AreaCaption;
					}

					foreach($this->json_array->ParameterArray->AWT as $id => $awt_data)
					{
						if($id == '_comment') continue 1;
						$this->AWT[$id]['img_src'] = 'plugin/'.$name.'/'.$awt_data->imgsrc;
						$this->AWT[$id]['hazard_type_DESC'] = $awt_data->hazard_type_DESC;

						if(!is_file('plugin/'.$name.'/'.$awt_data->imgsrc)) 
						{
							$this->error_file = 'plugin/'.$name.'/'.$awt_data->imgsrc;
							return -9;
						}
					}

					foreach($this->json_array->ParameterArray->AWL as $id => $awt_data)
					{
						if($id == '_comment') continue 1;
						$this->AWL[$id]['id'] = $id;
						$this->AWL[$id]['name'] = $awt_data->name;
						$this->AWL[$id]['level'] = $awt_data->hazard_level;
						$this->AWL[$id]['hazard_level'] = $awt_data->hazard_level_color;
						$this->AWL[$id]['hazard_level_color'] = $awt_data->hazard_level_color;
					}

					if(empty($this->svg_src)) return -4;
					if(empty($this->area_codes)) return -5;
					if(empty($this->AWT)) return -6;
					if(empty($this->AWL)) return -7;

					if(empty($this->svg_val)) return -8;

					unset($this->json_array);
					return 1;
				}
				else
				{
					return -1;
				}
			}
			else
			{
				return -2;
			}
		}

		function get_all_plugin()
		{
			$plugin_folder = scandir('plugin');
			foreach($plugin_folder as $key => $plugin)
			{
				if($plugin != '.' && $plugin != '..')
				{
					$this->plugin_folder[$plugin] = $plugin;
				}
			}

			return $this->plugin_folder;
		}

		function install_plugin($location)
		{
			if(!is_dir('plugin'))
			{
				mkdir("plugin", 0766);
				if(!is_dir('plugin'))
				{
					return -2;
				}
			}
			$zip = new ZipArchive;
			if ($zip->open($location) === TRUE) 
			{
				$zip->extractTo('plugin/');
				$zip->close();
				return 1;
			} 
			else 
			{
				return -1;
			}
		}

		function get_error($res)
		{
			$this->error[-1] = 'Plugin not found! ['.$this->name.']';
			$this->error[-2] = 'Plugin folder not found! [plugin/]';
			$this->error[-3] = 'cap_engine not found! ['.$this->cap_engine.']';
			$this->error[-4] = 'svg_src not valid! ['.$this->svg_src.']';
			$this->error[-5] = 'Could not parse AreaCodesArray!';
			$this->error[-6] = 'Could not parse ParameterArray->AWT!';
			$this->error[-7] = 'Could not parse ParameterArray->AWL!';
			$this->error[-8] = 'SVG not found! ['.$this->svg_src.']';
			$this->error[-9] = 'Image not found! ['.$this->error_file.']';

			return $this->error[$res];
		}

	}