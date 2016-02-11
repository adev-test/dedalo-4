<?php
/*
* CLASS TOOL_REPLACE_COMPONENT_DATA
*/


class tool_replace_component_data extends tool_common {


	
	

	# av component
	protected $component_obj ;
	public $search_options;


	
	public function __construct($component_obj, $modo='button') {
		
		# Fix modo
		$this->modo = $modo;

		# Fix current av component
		$this->component_obj = $component_obj;


		$search_options_key = 'section_'.$this->component_obj->get_section_tipo();
		if (!isset($_SESSION['dedalo4']['config']['search_options'][$search_options_key])) {
		 	throw new Exception("Error Processing Request. Sorry, search options are not fixed in session ($search_options_key)", 1);
		}
		$this->search_options = clone($_SESSION['dedalo4']['config']['search_options'][$search_options_key]);
	}



	/**
	* PROPAGATE_DATA
	* Note: when component is created in list mode, no default values from propiedaes' is set. In this case, 
	* for speed we use mode 'list' to avoid this process (in component_radio_button for example)
	* @return array $ar_records (all searched an changed records)
	*/
	public function propagate_data() {
		
		# Source component dato
		$source_dato = $this->component_obj->get_dato();

		$tipo 			= $this->component_obj->get_tipo();
		$section_tipo 	= $this->component_obj->get_section_tipo();
		$modelo_name 	= RecordObj_dd::get_modelo_name_by_tipo($tipo);
		$lang 			= $this->component_obj->get_traducible()=='no' ? DEDALO_DATA_NOLAN : DEDALO_DATA_LANG ;
		#dump($this->component_obj, '$this->component_obj'.to_string());	 	
	

		# Records to change
		$this->search_options->limit 	  = false;
		$this->search_options->offset 	  = false;
		$this->search_options->layout_map = array();
		$rows_data = search::get_records_data($this->search_options);
			#dump($rows_data, ' get_rows_data'.to_string()); die();
		$ar_records  = (array)$rows_data->result;
		$total_records = (int)$rows_data->options->full_count;

		
		$i=0;
		$j=0;
		foreach ($ar_records as $current_ar_record) {
			$record 		= reset($current_ar_record);
			$parent 		= $record['section_id'];

			$component_obj = component_common::get_instance($modelo_name, $tipo, $parent, 'list', $lang, $section_tipo);
				#dump($component, ' component'.to_string());
			if ($component_obj->get_dato()!=$source_dato) {
				$component_obj->set_dato($source_dato);
				$component_obj->Save();				
			}
			$i++;
			if(floor($i * 100 / $total_records) > $j){
				$percent  = floor($i * 100 / $total_records);
				$msg  	  = label::get_label('procesando')." $percent %";
					#echo "id: $i". PHP_EOL;
					echo "data: ".json_encode($msg). PHP_EOL;
					echo PHP_EOL;
					//ob_flush();
    				flush();
				$j++;
				//error_log($percent);
			}

		}

		# Update search_options cleaning current 'filter_by_search'
		$search_options_key = 'section_'.$section_tipo;
		$search_options 	= $_SESSION['dedalo4']['config']['search_options'][$search_options_key];		
		$search_options->filter_by_search = false; // Affect session object directly (not save is necessary)
		#$_SESSION['dedalo4']['config']['search_options'][$search_options_key] = $search_options;
			#dump($search_options, ' var '.to_string($search_options_key));

		return (array)$ar_records;

	}#end propagate_data



}//end tool_replace_component_data
?>