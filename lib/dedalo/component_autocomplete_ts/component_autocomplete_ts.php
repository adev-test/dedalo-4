<?php
	
	# CONTROLLER

	$id 					= $this->get_id();
	$tipo 					= $this->get_tipo();
	$parent 				= $this->get_parent();
	$modo					= $this->get_modo();		
	$dato 					= $this->get_dato();			#dump($dato);
	$label 					= $this->get_label();
	$required				= $this->get_required();
	$debugger				= $this->get_debugger();
	$permissions			= common::get_permissions($tipo);
	$ejemplo				= NULL;
	$html_title				= "Info about $id";
	$ar_tools_obj			= $this->get_ar_tools_obj();	
	$html_tools				= '';
	
	$valor_string			= $dato;
	$lang					= $this->get_lang();
	$identificador_unico	= $this->get_identificador_unico();
	$ar_referenced_tipo 	= $this->get_ar_referenced_tipo();
	$ar_referenced_tipo_json= json_handler::encode($this->get_ar_referenced_tipo());
	$component_name			= get_class($this);	

	#$ar_list_of_values		= $this->get_ar_list_of_values();	
		#dump($ar_list_of_values,"ar_list_of_values for dato:$dato - tipo:$tipo");

	#dump($this);

	# Verify component content record is inside section record filter
	if ($this->get_filter_authorized_record()===false) return NULL ;

	$file_name				= $modo;
	
	switch($modo) {
		
		
		case 'search' :	$id_wrapper = 'wrapper_'.$identificador_unico;
						$input_name = $tipo;
						# Valor searched
						$valor_searched 		= NULL;
						$valor_searched_string 	= NULL;
						if (!empty($_REQUEST[$tipo])) {
							$valor_searched 		= $_REQUEST[$tipo];
							$valor_searched_string 	= RecordObj_ts::get_termino_by_tipo($_REQUEST[$tipo]);
						}

						break;

		case 'edit'	:	$ar_css			= false;						
						$id_wrapper 	= 'wrapper_'.$identificador_unico;
						$input_name 	= $tipo;
						$valor 			= $this->get_valor();
						$ar_link_fields	= json_handler::encode($this->ger_ar_link_fields());


						break;

		case 'tool_time_machine'	:	
						return NULL;
						$ar_css		= $this->get_ar_css();
						$id_wrapper = 'wrapper_'.$identificador_unico.'_tm';
						$input_name = "{$tipo}_{$id}_tm";
						$file_name 	= 'edit';
						break;	
						
		case 'search99':	$ar_list_of_values		= null;	#$this->get_ar_list_of_values();
						$ar_css		= false;
						break;
						
		case 'list_tm' :
						$file_name = 'list';
						
		case 'portal_list':
						$file_name 	= 'list';

		case 'list'	:	$ar_css		= false;
						$valor 		= $this->get_valor();	#dump($valor);
						break;

		case 'relation':return NULL;
						# Force file_name to 'list'
						$file_name  = 'list';
						$ar_css		= false;
						break;
						
							
		
	}
		
	$page_html	= DEDALO_LIB_BASE_PATH .'/'. get_class($this) . '/html/' . get_class($this) . '_' . $file_name . '.phtml';
	if (!file_exists($page_html)) {
		throw new Exception("Error Processing Request. Mode <b>$file_name</b> is not valid! (2) ", 1);		
	}
	include($page_html);
?>