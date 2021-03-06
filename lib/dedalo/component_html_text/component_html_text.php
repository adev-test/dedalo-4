<?php
	
	# CONTROLLER

	$id 					= $this->get_id();
	$tipo 					= $this->get_tipo();
	$parent 				= $this->get_parent();
	$modo					= $this->get_modo();		
	$dato 					= $this->get_dato();
	$caller_id 				= navigator::get_selected('caller_id');		
	$caller_tipo 			= navigator::get_selected('caller_tipo');	

	$label 					= $this->get_label();				
	$required				= $this->get_required();
	$debugger				= $this->get_debugger();
	$permissions			= common::get_permissions($tipo); 	
	$ejemplo				= $this->get_ejemplo();
	$html_title				= "Info about $id";
	
	$valor					= $this->get_valor();				
	$lang					= $this->get_lang();
	$lang_name				= $this->get_lang_name();
	$identificador_unico	= $this->get_identificador_unico();
	$component_name			= get_class($this);
	$dato_raw 				= tools::truncate_text(htmlspecialchars($valor),300);	#tools::truncate_text($string, $limit, $break=" ", $pad="...")
	$context 				= $this->get_context();
		#dump($context,'context');
		#echo "context:$context - $modo";

	# Propiedades puede asignar valores de configuración del editor de texto (tinyMCE)
	$propiedades = $this->get_propiedades();
	$propiedades_json = json_handler::encode($propiedades);
		#dump($propiedades,'propiedades');


	# Verify component content record is inside section record filter
	if ($this->get_filter_authorized_record()===false) return NULL ;
	

	$file_name				= $modo;


	
	switch($modo) {
		
		#case 'portal_list'	:
						#$file_name = 'edit';
		case 'edit'	:	$ar_css				= $this->get_ar_css();
						$id_wrapper 		= 'wrapper_'.$identificador_unico;
						$input_name 		= "{$tipo}_{$id}";
						$text_area_tm 		= NULL;
						
						# DATO_REFERENCE_LANG
						$dato_reference_lang= NULL;
						if (empty($dato) && $this->get_traducible()=='si') { # && $traducible=='si'
							#$dato_reference_lang = $this->get_dato_default_lang();
							$default_component = $this->get_default_component();
								#dump($default_component,'$default_component');
						}						
						break;

		
		case 'tool_lang' :	
						$ar_css				= $this->get_ar_css();

						$id_wrapper 		= 'wrapper_'.$identificador_unico.'_tool_lang';
						$input_name 		= "{$tipo}_{$id}";
						$text_area_tm 		= NULL;
						$dato_reference_lang= NULL;
						# Force file_name
						#$file_name  = 'edit';
						break;

		
		case 'tool_time_machine' :	
						$ar_css				= $this->get_ar_css();
						
						# Asignado al componente en trigger time machine
						#$version_date 		= $this->get_version_date();	#dump($version_date,'version_date');

						$id_wrapper 		= 'wrapper_'.$identificador_unico.'_tm';
						$input_name 		= "{$tipo}_{$id}_tm";
						$text_area_tm 		= 'text_area_tm';
						$dato_reference_lang= NULL;												
						if (empty($dato)) { # && $traducible=='si'
							$dato_reference_lang = $this->get_dato_default_lang();		
						}
						# Force file_name
						$file_name  = 'edit';	
						break;
		
		case 'search' :	$ar_css		= false; 							
						break;					
		
		case 'portal_list'	:
						if(empty($dato)) return null;
						$file_name = 'list';			
		case 'list_tm' :
						$file_name = 'list';
						
		case 'list'	:	$ar_css		= false;
						$max_char = 256;
						if(strlen($valor)>$max_char) $valor = substr($valor,0,$max_char).'..';
						break;						
		
		case 'lang'	:	$ar_css		= $this->get_ar_css();
						$ar_tools_obj			= $this->get_ar_tools_obj();	
						$html_tools				= '';
						# load only time machime tool
						foreach($ar_tools_obj as $tool_obj) {
							if( get_class($tool_obj) == 'tool_time_machine') {																		
								$html_tools .= $tool_obj->get_html();								
							}
						}
		case 'diffusion' :						

						break;	
	}

		
	$page_html	= DEDALO_LIB_BASE_PATH .'/'. get_class($this) . '/html/' . get_class($this) . '_' . $file_name . '.phtml';
	if (!file_exists($page_html)) {
		throw new Exception("Error Processing Request. Mode <b>$file_name</b> is not valid! (2) ", 1);		
	}
	include($page_html);


?>