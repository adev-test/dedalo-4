<?php
/*
* CLASS COMPONENT RELATION
*/


class component_relation extends component_common {
		
	# Overwrite __construct var lang passed in this component
	protected $lang = DEDALO_DATA_NOLAN;

	protected $valor ;
	protected $link ;
	protected $section ;
	protected $ar_etiquetas_de_los_campos ;
	protected $ar_components ;

	public $current_tipo_section;


	function __construct($id=NULL, $tipo=NULL, $modo='edit', $parent=NULL, $lang=NULL) {

		# Force allways DEDALO_DATA_NOLAN
		$lang = $this->lang;

		# EDIT : Si se pasa un id vacío (desde class.section es lo normal), se verifica si existe en matrix y si no, se crea un registro que se usará en adelante
		if($modo=='edit' && empty($id)) {

			# Despeja el id si existe a partir del tipo y el parent
			$id = component_common::get_id_by_tipo_parent($tipo, $parent, $lang);	
				#dump($id,"id calculado (get_id_by_tipo_parent) para tipo:$tipo - parent:$parent - lang:$lang");

			# Si no existe, creamos un registro, si o si
			if(empty($id)) {

				#throw new Exception("component_relation id not found. var: id:$id, tipo:$tipo, modo:$modo, parent:$parent, lang:$lang ", 1);			

				if( !empty($tipo) && intval($parent)>0 ) {
					$matrix_table 		= common::get_matrix_table_from_tipo($tipo);
					$RecordObj_matrix 	= new RecordObj_matrix($matrix_table,NULL, $parent, $tipo, $lang);	#($id=NULL, $parent=false, $tipo=false, $lang=DEDALO_DATA_LANG, $matrix_table='matrix')
					$RecordObj_matrix->set_parent($parent);
					$RecordObj_matrix->set_tipo($tipo);
					$RecordObj_matrix->set_lang($lang);
					$RecordObj_matrix->set_dato('');

					$RecordObj_matrix->Save();
					$id = $RecordObj_matrix->get_ID();

					# DEBUG
					if(SHOW_DEBUG===true) {
					$msg = "INFO: Created component_relation record $id with: ($tipo, $parent, $lang)";
					error_log($msg);
					}
				}else{
					$msg = "Impossible create new component_relation record ";
					if(SHOW_DEBUG===true) {
					$msg .= " (id:$id, tipo:$tipo, parent:$parent, lang:$lang, modo:$modo)";
					}
					throw new Exception($msg, 1);
				}
			}
		}

		# ya tenemos registro en matrix, a continuar...
		parent::__construct($id, $tipo, $modo, $parent, $lang);
	}

	
	# OVERRIDE COMPONENT_COMMON METHOD
	public function get_ar_tools_obj() {
		
		# Remove common tools (time machine and lang)
		#unset($this->ar_tools_name);

		# Add tool_transcription
		#$this->ar_tools_name[] = 'tool_relation';
		
		return parent::get_ar_tools_obj();
	}
	
	/**
	* GET SECTIONS HTML FOR INSERT IN SECTION GROUPS
	* Devuelve un array con el html de cada seccion preparado para su posterior carga mediante ajax y
	* preparado para insertar en section_groups creados en section
	*
	* @return $ar_html_for_section_groups
	*	Array $section_tipo => $section_html
	*/
	/*
	public function get_sections_html_for_insert_in_section_groups() {	
		
		$ar_html_for_section_groups = array();
		$ar_tipo_for_section_groups	= array();

		$modo 			= 'edit';
		$called_class 	= get_class($this);

		# FIXED RELATIONS DEFINED IN STRUCTURE HTML
		$ar_fixed_relations 	= $this->get_ar_fixed_relations();		#dump($ar_fixed_relations,'ar_fixed_relations');	

		foreach ($ar_fixed_relations as $tipo) {
			$ar_tipo_for_section_groups[] = $tipo;
		}
		
		# DINAMIC RELATIONS DEFINED IN DATO HTML
		$ar_dinamic_relations 	= $this->get_ar_dinamic_relations();	#dump($ar_dinamic_relations,'$ar_dinamic_relations');	#die();
		
		foreach ($ar_dinamic_relations as $tipo) {
			$modelo_name 	= RecordObj_ts::get_modelo_name_by_tipo($tipo);							
				#dump($modelo_name,'$modelo_name');
			
			# Sólo incluir secciones (como 1021.0.0.0) como boxes. Descartar los componentes (como 254.dd52.1) que se incluirán dentro del listado como secciones
			if ($modelo_name=='section') {				
				$ar_tipo_for_section_groups[] = $tipo;
			}
		}

		$tipo = $this->get_tipo();


		# Iterate all section tipo and get html one to one
		foreach ($ar_tipo_for_section_groups as $section_tipo) {
			
			$section_name			= RecordObj_ts::get_termino_by_tipo($section_tipo);
			$id 					= $this->get_id();		
			$lang 					= $this->get_lang();
			$component_name			= get_class($this);
			$dato_string			= $this->get_dato_as_string();
			$identificador_unico	= $this->get_identificador_unico();
			$permissions			= common::get_permissions($section_tipo);
			$parent					= $this->get_parent();

			
			$file_include	= DEDALO_LIB_BASE_PATH .'/'. $called_class . '/html/' . $called_class . '_' .$modo.'.phtml' ;
			#
			#$file_include	= DEDALO_LIB_BASE_PATH .'/'. $called_class . '/' . $called_class . '.php' ;
			#
			ob_start();
			include ( $file_include );
			$html =  ob_get_contents();
			ob_get_clean();
			
			$ar_html_for_section_groups[$section_tipo] = $html;
		}

		#dump($ar_tipo_for_section_groups,'$ar_tipo_for_section_groups');
		return $ar_html_for_section_groups;
	}
	*/




	/**
	* REMOVE RELATION TO DATO
	* Remove element (tag) received on relations array (dato) and return resultant array
	* !Important: force build new array to keep numeric key correlation (maintain json array format in matrix)
	* @param $rel_locator
	*	String full tag like '861.0.0'
	* @param $dato
	*	Array of relations. Key=auto, Value=tag,  like '0=>861.0.0,1=>875.0.0'
	*/
	public static function remove_relation_to_dato($rel_locator, $dato) {

		return component_common::remove_locator_to_dato($rel_locator, $dato);		
	}



	
	/**
	* GET ARRAY OF SECTION GROUPS (DEFINED AS RELATED TERMS IN STRUCTURE)
	* Devuelve el grupo de 'boxes' fijos definidos en estructura (términos relacionados con el 'component_relation' actual)
	*/
	public function get_ar_fixed_relations() {

		/*
		$terminoID 					= $this->get_tipo();	
		$ar_terminos_relacionados 	= RecordObj_ts::get_ar_terminos_relacionados($terminoID, $cache=false, $simple=true);		
			dump($ar_terminos_relacionados,'ar_terminos_relacionados: ' . $this->tipo );
		*/
		# método acceso directo al componente
		$ar_terminos_relacionados = $this->get_relaciones();
			#dump($ar_terminos_relacionados,'ar_terminos_relacionados: ' );
		
		$ar_fixed_relations = array();
		foreach ($ar_terminos_relacionados as $tipo) {
			$ar_fixed_relations[] 	= $tipo;	
		}		
		#dump($ar_fixed_relations,'$ar_fixed_relations');
		return $ar_fixed_relations;
	}

	/**
	* GET ARRAY OF SECTION GROUPS (DEFINED AS SECTIONS ARRAY IN MATRIX DATA)
	* Devuelve el grupo de 'boxes' dinámico definidos en matrix data
	* Para calcularlo, recorremos las secciones que hay en matrix 'dato' y despejamos el tipo de cada una de ellas
	* Eliminamos los tipos ya definidos en estructura (los fijos) y devolvemos el resto
	*/
	public function get_ar_dinamic_relations() {

		$ar_sections_id 		= $this->get_dato();				#dump($ar_sections_id,'ar_sections_id');
		$ar_fixed_tipos 		= $this->get_ar_fixed_relations();
		$ar_dinamic_relations	= array();

		if (is_array($ar_sections_id) && count($ar_sections_id)>0) foreach ($ar_sections_id as $rel_locator) {
			
			# Convert rel_locator to id (191.0.0 => 191)
			$locator_as_obj 	= component_common::get_locator_relation_as_obj($rel_locator);
			$section_id 		= $locator_as_obj->section_id_matrix;

			$matrix_table 	= common::get_matrix_table_from_tipo($this->get_tipo());		

			$current_tipo	= common::get_tipo_by_id($section_id, $matrix_table );			

			if (isset($current_tipo)) {

				if (!in_array($current_tipo, $ar_fixed_tipos)) {
					$ar_dinamic_relations[] = $current_tipo;
				}
			}			
		}
		$ar_dinamic_relations = array_unique($ar_dinamic_relations);		
		#dump($ar_dinamic_relations,'$ar_dinamic_relations');

		return $ar_dinamic_relations;
	}


	/**
	* GET ALL CONTENT SECTIONS
	* Resolve all authorized sections to this user for create sections selector
	*/
	public static function get_all_authorized_content_sections() {

		#
		# NOTA: CAMBIAR ESTE CALCULO PARA DESPEJARLO A PARTIR DEL CALCULO PREVIO DEL MENU ( ) !!!!!!!!!
		#		
		
		# STATIC CACHE	
		# unset($_SESSION['config4']['all_authorized_content_sections']);
		if(isset($_SESSION['config4']['all_authorized_content_sections'][DEDALO_DATA_LANG])) {
			return $_SESSION['config4']['all_authorized_content_sections'][DEDALO_DATA_LANG];
		}
		if(SHOW_DEBUG) $start_time = start_time();

		$ar_sections 	= array();
		
		# SECCIONES EN INVENTARIO (AREA_ROOT)
		$ar_terminoID_by_modelo_name = RecordObj_ts::get_ar_terminoID_by_modelo_name($modelo_name='area_root', $prefijo='dd');

		if(!empty($ar_terminoID_by_modelo_name)) {

			$terminoID = $ar_terminoID_by_modelo_name[0];	
			
			# Despejamos todos sus hijos 			[$usableIndex != 'no' &&]
			$RecordObj_ts	= new RecordObj_ts($terminoID);	
			$ar_sections 	= array_merge( $ar_sections, $RecordObj_ts->get_ar_recursive_childrens_of_this($terminoID) );		
		}
		
		# SECCIONES EN RECURSOS (AREA_RESOURCE)
		$ar_terminoID_by_modelo_name = RecordObj_ts::get_ar_terminoID_by_modelo_name($modelo_name='area_resource', $prefijo='dd');

		if(!empty($ar_terminoID_by_modelo_name)) {

			$terminoID = $ar_terminoID_by_modelo_name[0];	
			
			# Despejamos todos sus hijos
			$RecordObj_ts	= new RecordObj_ts($terminoID);			
			$ar_sections 	= array_merge( $ar_sections, $RecordObj_ts->get_ar_recursive_childrens_of_this($terminoID) );
		}
		#dump($ar_sections,'$ar_sections');

		if(empty($ar_sections)) return NULL;

		# Seleccionamos los de modelo 'section'
		$ar_mix = array();
		foreach ($ar_sections as $tipo) {

			$modelo_name  = RecordObj_ts::get_modelo_name_by_tipo($tipo);
			
			# Exclude 'section_list_of_values' and others
			if($modelo_name==='section') {

				# Excluimos las secciones marcadas como 'visible=no' en estructura
				$RecordObj_ts	= new RecordObj_ts($tipo);
				$visible 		= $RecordObj_ts->get_visible();		#dump($visible,'visible');
				if ($visible != 'no') {
					$section_name = RecordObj_ts::get_termino_by_tipo($tipo,DEDALO_DATA_LANG);	#dump($section_name);
					$ar_mix[$tipo]= $section_name;
				}				
			}									
		}
		asort($ar_mix);


		# 5 Eliminamos los que no son autorizados (en caso de NO ser admin global)
		$userID_matrix_logged = navigator::get_userID_matrix();
		if( !component_security_administrator::is_global_admin($userID_matrix_logged) ) {
			
			$security = new security();
			foreach ($ar_mix as $tipo => $section_name) {
				# Si los permisos son inferiores a 2 (lectura/escritura) lo eliminamos del array final
				if( $security->get_security_permissions($tipo)!=2 ) {
					unset($ar_mix[$tipo]);
				}
			}
		}
		#dump($ar_mix,'ar_mix'); #die();

		# STORE CACHE DATA
		$_SESSION['config4']['all_authorized_content_sections'][DEDALO_DATA_LANG] = $ar_mix;

		# LOG
		#$log = logger::get_instance();
		#$log->log_message("Calculated all_authorized_content_sections. Time to generate:" . round( microtime(TRUE) - tools::get_request_time() ,4), logger::DEBUG, __METHOD__);

		#dump($ar_mix);
		if(SHOW_DEBUG) $GLOBALS['log_messages'] .= exec_time($start_time, __METHOD__, $ar_mix);
		
		return $ar_mix;
	}

	/**
	* GET AR ALL RELATION SECTIONS (BY TIPO)
	* Return array of all (fixed and dinamic sections related)
	*/
	public function get_ar_all_relation_sections() {

		$ar_all_relation_sections = array();

		# FIXED RELATIONS DEFINED IN STRUCTURE HTML
		$ar_fixed_relations 	= $this->get_ar_fixed_relations();		#dump($ar_fixed_relations,'ar_fixed_relations');	

		foreach ($ar_fixed_relations as $tipo) {
			$ar_all_relation_sections[] = $tipo;
		}
		
		# DINAMIC RELATIONS DEFINED IN DATO HTML
		$ar_dinamic_relations 	= $this->get_ar_dinamic_relations();	#dump($ar_dinamic_relations,'$ar_dinamic_relations');	#die();
		
		foreach ($ar_dinamic_relations as $tipo) {
			$modelo_name 	= RecordObj_ts::get_modelo_name_by_tipo($tipo);							
				#dump($modelo_name,'$modelo_name');
			
			# Sólo incluir secciones (como 1021.0.0.0) como boxes. Descartar los componentes (como 254.dd52.1) que se incluirán dentro del listado como secciones
			if ($modelo_name=='section') {				
				$ar_all_relation_sections[] = $tipo;
			}
		}

		return $ar_all_relation_sections;
	}

	/*
	protected function get_records_by_section_tipo($tipo) {

		$arguments=array();
		$arguments['parent']	= 0;
		$arguments['tipo']		= $tipo;
		$matrix_table 			= common::get_matrix_table_from_tipo($tipo);
		$RecordObj_matrix		= new RecordObj_matrix($matrix_table,NULL);
		$ar_result				= $RecordObj_matrix->search($arguments);

		#dump($ar_result,'ar_result');

		return $ar_result;
	}
	*/

	


	/**
	*
	* Devuelve un array múltiple de tipo: ar_section_relations[$id_record][] = $etiqueta
	* donde el índice será el grupo de id matrix section y su contenido un array con
	* todas los rel_locators con  ese id section 
	* tipo [0] => 1336.dd12.1, [1] => 1336.dd12.2, [2] => 1336.0.0 ..
	* El resultado será filtrado por el tipo de la sección actual 'current_tipo_section'
	*/
	public function get_ar_section_relations_for_current_tipo_section($modo='ar_multiple') {
		
		# Datos internos
		$dato 					= $this->get_dato();		
		$current_tipo_section 	= $this->get_current_tipo_section();		

		# Run static method version
		return component_relation::get_ar_section_relations_for_current_tipo_section_static($modo, $dato, $current_tipo_section);
	}


	/**
	* GET AR SECTION RELATION FOR CURRENT TIPO SECTION (STATIC VERSION)
	* Create a dummy component_relation_obj and set 'dato' and 'current_tipo_section' 
	* Exec dynamic version method over this obj
	*/
	public static function get_ar_section_relations_for_current_tipo_section_static($modo='ar_multiple', $dato, $current_tipo_section) {

		#if (is_array($dato)) foreach ($dato as $rel_locator) {
			#echo " $rel_locator <br>";
		#}
		#dump($dato);

		# Recorremos cada registro relacionado verificando su tipo. 
		# Los que sean del tipo correcto (el actual pasado a este objeto section) se guardan
		# y el resto se descarta
		$ar_id_records 			= array();
		$ar_rel_locator 		= array();
		$ar_section_relations 	= array();

		if(is_array($dato)) foreach ($dato as $rel_locator) {
			
			$locator_as_obj 	= component_common::get_locator_relation_as_obj($rel_locator);
			$section_id 		= $locator_as_obj->section_id_matrix;

			if(!empty($section_id)) {

				# Verificamos que es del tipo correcto
				$matrix_table 		= common::get_matrix_table_from_tipo($current_tipo_section);
				$RecordObj_matrix	= new RecordObj_matrix($matrix_table,$section_id);
				$tipo 				= $RecordObj_matrix->get_tipo();			

				if( !empty($tipo) && $tipo == $current_tipo_section ) {
					# Notar que aquí se sobreescriben los registros con el mismo id de section en la rel_locator (como 10.0.0.1 y 10.dd56.2)
					# y por tanto sólo se almacenarán en el array de id's uno por sección					
					if (!in_array($section_id, $ar_id_records)) {
						$ar_id_records[]	= $section_id ;
					}
					$ar_rel_locator[] 		= $rel_locator;								

					# Almacenamos el array de rel_locators de esta sección para usarlo en el listado de relaciones en la clase 'rows'
					$ar_section_relations[$section_id][] = $rel_locator;		
				}
			}						
			#dump($rel_locator,'$rel_locator', "id_record:".$ar_rel_locator[0]." rel_locator:$rel_locator");

		}# end foreach ($dato as $rel_locator)

		#dump($ar_section_relations,'$ar_section_relations');

		switch ($modo) {
			case 'ar_id_records':
				return $ar_id_records;
				break;

			case 'ar_rel_locator':
				return $ar_rel_locator;
				break;
			
			case 'ar_multiple':
			default:
				return $ar_section_relations;
				break;
		}
	}




	/**
	* DELETE REL_LOCATOR FROM ALL RELATIONS
	* Elimina todas las relaciones que pueda tener una etiqueta en cualquier registro
	* @param $rel_locator
	* 
	*/
	public static function delete_rel_locator_from_all_relations($rel_locator, $tipo=null) {

		if(empty($tipo) || strlen($tipo)<3) throw new Exception("Error Processing Request. Wrong tipo", 1);		

		$arguments=array();
		$arguments['dato:json']	= $rel_locator;
		$matrix_table 			= common::get_matrix_table_from_tipo($tipo);
		$RecordObj_matrix		= new RecordObj_matrix($matrix_table,NULL);		
		$ar_result				= $RecordObj_matrix->search($arguments);	#dump($ar_result,"ar_result from rel_locator: $rel_locator");
		foreach ($ar_result as $current_id) {

			$matrix_table 		= common::get_matrix_table_from_tipo($tipo);
			$RecordObj_matrix	= new RecordObj_matrix($matrix_table,$current_id);				
			$dato 				= $RecordObj_matrix->get_dato();
				#dump($RecordObj_matrix,"Before");
			$new_dato 			= self::remove_relation_to_dato($rel_locator, $dato);	
			$RecordObj_matrix->set_dato($new_dato);
			$RecordObj_matrix->Save();
				#dump($RecordObj_matrix,"After");
		}	
	}
	






	/*
	# Resolucion de la relacion	
	# resuelve y establece las variables de la clase
	public function load_relation_data() {
	
		# Creamos el array de la lista
		$ar_components	= array();		
		
		$dato			= $this->get_dato();
		$tipo			= $this->get_tipo();
		$lang 			= $this->get_lang();
		$modo 			= $this->get_modo();
		$termino_old 	= NULL;		
		
		dump($dato,'$dato');
		
		# En función del modo (list/edit) definimos el "parent de partida"	
		if($modo == 'list') {
			
			$RecordObj_ts			= new RecordObj_ts($tipo);	#echo "esxtamos en list" . $this->get_modo();
			$ar_childrens			= $RecordObj_ts->get_ar_childrens_of_this();	#var_dump($ar_childrens); die();
			if(!is_array($ar_childrens) || empty($ar_childrens)) print(__METHOD__ . " <span class='error'> component_relation without relation_list children defined! </span>");			
			$tipo					= $ar_childrens[0];								#var_dump($tipo);
		}
		
		# creamos el objeto de tipo tesauro con el tipo de campo que requiere la relación
		$RecordObj_ts				= new RecordObj_ts($tipo);		
		
		# Obtenemos los terminos relacionados del termino tesauro que nos lleva a todas las relaciones del término
		$ar_campos_relacionados		= $RecordObj_ts->get_relaciones();
		
		if (is_array ($ar_campos_relacionados))	foreach($ar_campos_relacionados as $ar_campos_relacionadosID) {

			foreach($ar_campos_relacionadosID as $modeloID => $terminoID) {

				# creamos un objeto de cada termino, obtenemos su modelo y resolvemos el nombre del modelo
				$termino		= RecordObj_ts::get_termino_by_tipo($terminoID);
				$modelo			= RecordObj_ts::get_termino_by_tipo($modeloID);
					
				if (is_array($dato)) foreach ($dato as $key => $dato_relacion) {
	
					$parent_id = $dato_relacion;
			
					# con el nombre del modelo resolvemos los que son "component_list_of_values" descartando otros tipos de relaciones del término
					if ($modelo == 'section') {						
						
						$this->link = true;
						$this->section = $terminoID;							
						
					}else{
												
						if (strpos($modelo, 'component_') !== false) {
							
							$ar_id_matrix	=  RecordObj_matrix::get_records_by_search(false, $dato_relacion, false , $terminoID, $lang);
							
							if (isset($ar_id_matrix[0])){
								$id_matrix = $ar_id_matrix[0];
							}else{										
								$id_matrix = NULL;
							}
							
							if($termino_old != $termino) {
								$this->ar_etiquetas_de_los_campos[] = $termino;	
							}
							
							$termino_old = $termino;
							
							#$current_component	=  component_common::load_component ($id_matrix, $terminoID, 'list');
							$current_component	=  new $modelo($id_matrix, $terminoID, 'list');
							$this->ar_components[$parent_id][] = $current_component;
							
							$this->ar_valor[$parent_id][]  = $current_component->get_dato();
						}
					}												
			
				}#foreach ($dato as $key => $dato_relacion)
			
			}#foreach ($ar_campos_relacionados as $terminoID)
		
		}
		
		#dump($this);
						
	}#load_relation_data
	*/
	
	
	# GET VALUE . DEFAULT IS GET DATO . OVERWRITE IN EVERY DIFFERENT SPECIFIC COMPONENT
	public function get_valor() {
		return " working here... ";
		# Si no se ha disparado "load_relation_data" lo ejecutamos
		if(!isset($this->ar_valor)) $this->load_relation_data();
		
		if(!isset($this->ar_valor)) return false;		
		
		# Cogemos el valor de la variable array $this->ar_valor y lo formateamos para salida como string
		$ar_valor = $this->ar_valor;
	
		$valor="";
		if(is_array($ar_valor)) foreach ($ar_valor as $parent_id => $ar_valores){
			
			if(is_array($ar_valores)) foreach ($ar_valores as $parte) {
				
				$valor .= $parte .", ";				
			}
			$valor .= "<br>";
		}
		
		$this->valor = $valor; 
		return $this->valor;
	}
	

	

	

	/**
	* GET_RELATION_REVERSE_RECORDS_FROM_ID_SECTION
	*/
	public static function get_relation_reverse_records_from_id_section( $rel_locator, $tipo=null ) {

		if(SHOW_DEBUG) $start_time = start_time();

		#$current_section_id_matrix = navigator::get_selected('id');
			#dump($current_section_id_matrix,'current_section_id_matrix');

		if(empty($tipo) || strlen($tipo)<3) {
			if(SHOW_DEBUG)
				dump($tipo,'tipo');
			throw new Exception("Error Processing Request. Wrong tipo : $tipo", 1);			
		}

		

		
		$arguments=array();
		$arguments['strPrimaryKeyName']	= 'parent';
		
		# Limitamos la búsqueda a los registros con modelo 'component_relation' (dd74) para evitar penalizaciones de velocidad en tablas InnoDB sin FullText
		$ar_terminoID_by_modelo_name = RecordObj_ts::get_ar_terminoID_by_modelo_name('component_relation');
			#dump($ar_terminoID_by_modelo_name,'$ar_terminoID_by_modelo_name');
		if(!empty($ar_terminoID_by_modelo_name))
		$arguments['tipo:or']			= $ar_terminoID_by_modelo_name;		

		$arguments['dato:json']			= $rel_locator;
		$matrix_table 					= common::get_matrix_table_from_tipo($tipo);
		$RecordObj_matrix				= new RecordObj_matrix($matrix_table,NULL);
		$ar_records						= $RecordObj_matrix->search($arguments);
			#if(SHOW_DEBUG) dump($ar_records,'ar_records '.print_r($arguments,true), array( 'exec'=>exec_time($start_time) ));
		
		

		# Las agrupamos por tipo
		$ar_section = array();
		foreach ($ar_records as $current_section_id) {
			$matrix_table 			= common::get_matrix_table_from_tipo($tipo);
			$RecordObj_matrix 		= new RecordObj_matrix($matrix_table,$current_section_id);
			$tipo 					= $RecordObj_matrix->get_tipo();
			$ar_section[$tipo][]	= $current_section_id;
		}
		#dump($ar_section,'$ar_section');
		
		if(SHOW_DEBUG) $GLOBALS['log_messages'] .= exec_time($start_time, __METHOD__. ' ' );
		
		return $ar_section;

		# DESARROLLO POSTERIOR PARA OBTENER EL HTML (HABILITAR EN CONTROLADOR):
		/*
		# Recorremos todos los tipos
		$relation_list_html = '';
		foreach ($ar_section as $tipo => $ar_values) {

			#$sections_text 	= implode(', ',$ar_values);
			$section_name 	= RecordObj_ts::get_termino_by_tipo($tipo,DEDALO_APPLICATION_LANG);
			$relation_list_html .= "<div class=\"tipo_group_related\"><h3 class=\"text_shadow_inset\">$section_name</h3></div>";							
			
			$section_ob = new section(NULL, $tipo, 'relation_reverse');			#dump($ar_values,'$ar_values'," tipo -> $tipo");
			# le asignamos los valores al objeto
			$section_ob->ar_id_section_custom 	= $ar_values;
			$section_ob->rel_locator 			= $rel_locator;
			$section_ob->tag 					= null;
				#dump($section_ob->ar_id_section_custom,'$section_ob->ar_id_section_custom'); 
				
			$relation_list_html .= $section_ob->get_html();
				#dump($section_ob,'section_ob');							
		}
		#dump($relation_list_html,'$relation_list_html');

		return $relation_list_html;
		*/
	}







		
}
?>