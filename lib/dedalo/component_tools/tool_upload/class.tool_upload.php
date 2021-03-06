<?php
/*
* CLASS TOOL UPLOAD
*/
require_once( dirname(dirname(dirname(__FILE__))) .'/config/config4.php');


class tool_upload extends tool_common {
	
	# media component
	protected $component_obj ;


	#protected $current_

	
	public function __construct($component_obj, $modo='button') {
		
		# Fix modo
		$this->modo = $modo;

		# Fix current media component
		$this->component_obj = $component_obj;
	}



	/**
	* UPLOAD_FILE
	*/
	public function upload_file( $quality ) {

		$start_time = start_time();

		$html ='';

		# Current component name
		$component_name = get_class( $this->component_obj );

		# VARS : Fix
		switch ($component_name) {
			case 'component_av' :
					$SID 					= $this->component_obj->get_video_id();						
					#$folder_path			= DEDALO_MEDIA_BASE_PATH . DEDALO_AV_FOLDER . '/' . $quality;
					$this->component_obj->set_quality($quality);
					$folder_path			= $this->component_obj->get_target_dir();
						#dump($folder_path,'$folder_path'); die();
					$current_extension 		= DEDALO_AV_EXTENSION;
					$ar_allowed_extensions 	= unserialize(DEDALO_AV_EXTENSIONS_SUPPORTED);
					break;
			case 'component_image' :					
					$SID 					= $this->component_obj->get_image_id();						
					#$folder_path			= DEDALO_MEDIA_BASE_PATH . DEDALO_IMAGE_FOLDER . '/' . $quality;
					$this->component_obj->set_quality($quality);
					$folder_path			= $this->component_obj->get_target_dir();	//DEDALO_MEDIA_BASE_PATH . DEDALO_IMAGE_FOLDER .'/'. $this->aditional_path . $this->get_quality() ;
						#dump($folder_path,'$folder_path'); die();
					$current_extension 		= DEDALO_IMAGE_EXTENSION;
					$ar_allowed_extensions 	= unserialize(DEDALO_IMAGE_EXTENSIONS_SUPPORTED);
					break;
			case 'component_pdf' : 
					$SID 					= $this->component_obj->get_pdf_id();
					#$folder_path			= DEDALO_MEDIA_BASE_PATH . DEDALO_PDF_FOLDER . '/' . $quality;
					$this->component_obj->set_quality($quality);
					$folder_path			= $this->component_obj->get_target_dir();
					$current_extension 		= DEDALO_PDF_EXTENSION;
					$ar_allowed_extensions 	= unserialize(DEDALO_PDF_EXTENSIONS_SUPPORTED);
					break;
		}
				

		# VARS : Verify
		if(!$SID) 		exit('Error SID not defined');
		if(!$quality) 	exit('Error: quality not defined');	
		
			#dump($_FILES["fileToUpload"],'$_FILES["fileToUpload"]');

		# VERIFICAMOS SI EL ARCHIVO ES VÁLIDO
		$f_name 		= $_FILES["fileToUpload"]['name'];
		$f_type 		= $_FILES["fileToUpload"]['type'];
		$f_temp_name	= $_FILES["fileToUpload"]['tmp_name'];
		$f_size			= $_FILES["fileToUpload"]['size'];
		$f_error		= $_FILES["fileToUpload"]['error'];
		$f_error_text 	= $this->error_number_to_text($f_error);
		$f_extension 	= strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
			#dump($f_extension,'$f_extension for '.$f_temp_name.' - '.$f_name);

		# EXTENSIONS : Validate extension file
		$this->validate_extension( $f_extension, $ar_allowed_extensions );

		# NOMBRE_ARCHIVO : Nombre final del archivo	
		$nombre_archivo = $SID . '.' . $f_extension ;		

		# LOG UPLOAD BEGINS
			$id_matrix 		= $this->component_obj->get_id();
			$tipo 			= $this->component_obj->get_tipo();
			$parent 		= $this->component_obj->get_parent();
			$top_id 		= $_SESSION['config4']['top_id'];
			$top_tipo 		= $_SESSION['config4']['top_tipo'];
			$file_size_mb 	= round( ($f_size/1024)/1024 , 2 );			
			
			# LOGGER ACTIVITY : QUE(action normalized like 'LOAD EDIT'), LOG LEVEL(default 'logger::INFO'), TIPO(like 'dd120'), DATOS(array of related info)
			logger::$obj['activity']->log_message(
				'UPLOAD',
				logger::INFO,
				$tipo,
				NULL,
				array(	"msg"				=> "Upload file start",
						"id" 				=> $id_matrix,
						"tipo"				=> $tipo,
						"parent"			=> $parent,
						"top_id"			=> $top_id,
						"top_tipo"			=> $top_tipo,
						"component_name" 	=> $component_name,
						"quality" 			=> $quality,
						"file_name" 		=> $nombre_archivo,
						"file_size_mb" 		=> $file_size_mb
					)
			);
			

		# Verificamos que NO hay ya un fichero anterior con ese nombre. Si lo hay, lo renombramos y movemos a deleted files
		# Recorremos todas las extensiones válidas (recordar que los ficheros de tipo 'tif', etc. se guardan también)
		$this->rename_old_files_if_exists( $SID, $folder_path, $ar_allowed_extensions );
		
		# Add / if need
		if(substr($folder_path, -1)!='/') $folder_path .= '/';

		$uploaded_file_path = $folder_path . $nombre_archivo;	
		
		
		# MOVEMOS EL FICHERO SUBIDO A SU CARPETA		
		if(file_exists($f_temp_name)) {
			$move_file = move_uploaded_file($f_temp_name, $uploaded_file_path);
			if (!$move_file) {
				$msg = "File $f_temp_name exists. Error on move to: " . $uploaded_file_path ; 
				trigger_error($msg);
			}
		}else{
			$msg = "Error[upload_trigger]: temporal file $f_temp_name not exists. I can't move the file to final location.";
			trigger_error($msg);
			exit($msg);	
		}



		# ERROR : FILE NOT FOUND
		if( !file_exists($uploaded_file_path) ) {		
			
			$fileUploadOK = 0;	# ERROR	NUMBER

			$msg = "Error[upload_trigger]: ". label::get_label('error_al_subir_el_archivo') .' '. label::get_label('el_directorio_no_existe') .' [1]';
			trigger_error($msg);
			

	        $html .= "<!-- UPLOAD MSG ERROR -->";
			$html .= "<div class=\"uploadMsg\">";
				$html .= label::get_label('error_al_subir_el_archivo') . "<br>";
				$html .= label::get_label('el_directorio_no_existe') ;
				$html .= "<br><a href=\"javascript:history.go(-1);\">".label::get_label('volver')."</a>";
				if(SHOW_DEBUG) {
					$html .= "<br>" . $uploaded_file_path;
				}
	        $html .= "</div>";


			$time_sec 	= exec_time_unit($start_time,'sec');
			
			# LOGGER ACTIVITY : QUE(action normalized like 'LOAD EDIT'), LOG LEVEL(default 'logger::INFO'), TIPO(like 'dd120'), DATOS(array of related info)
			logger::$obj['activity']->log_message(
				'UPLOAD',
				logger::INFO,
				$tipo,
				NULL,
				array(	"msg"				=> "Error on upload file",
						"id" 				=> $id_matrix,
						"tipo"				=> $tipo,
						"parent"			=> $parent,
						"top_id"			=> $top_id,
						"top_tipo"			=> $top_tipo,
						"component_name" 	=> $component_name,
						"quality" 			=> $quality,
						"file_name" 		=> $nombre_archivo,
						"file_size_mb" 		=> $file_size_mb,
						"time_sec" 			=> $time_sec,
						"f_error"			=> $f_error
					)
			);

		# OK : FILE FOUND
		}else{

			
			$fileUploadOK = 1;	# OK
			
			# AJUSTAMOS LOS PERMISOS
			try{
				$ajust_permissions = chmod($uploaded_file_path, 0775);
				if (!$ajust_permissions) {
					$msg = "Error[upload_trigger]: Error on set permissions [2]";
					trigger_error($msg);		
				}
			} catch (Exception $e) {
			    $msg = 'Exception[upload_trigger][SET_PERMISSIONS]: ' . $e->getMessage() . "\n";
			    trigger_error($msg);
			}
		

			# POSTPROCESSING_FILE : Procesos a activar tras la carga del archivo
			$this->postprocessing_file($component_name, $SID, $quality, $uploaded_file_path);
	        
	        
			$html .= " <!-- UPLOAD MSG OK -->";
	        $html .= "<div class=\"uploadMsg\">";
	      	
			$html .= "<div class=\"uploadMsg_ok\">";				
			$html .= 'Ok. '. label::get_label('fichero_subido_con_exito') ;				
			$html .= "</div>";							
				
			# FILE EXISTS BUT ERROR OCURRED
			if($f_error>0)
			$html .= "Error {$f_error}: $f_error_text. Notify this error to your administrator<br />";	            	
				
	        $html .= " <a class=\"cerrar_link\" onclick=\"tool_upload.cerrar()\">".label::get_label('cerrar')."</a>";            
	        $html .= " \n</div>";


	        $time_sec 	= exec_time_unit($start_time,'sec');

			# LOGGER ACTIVITY : QUE(action normalized like 'LOAD EDIT'), LOG LEVEL(default 'logger::INFO'), TIPO(like 'dd120'), DATOS(array of related info)
			logger::$obj['activity']->log_message(
				'UPLOAD COMPLETE',
				logger::INFO,
				$tipo,
				NULL,
				array(	"msg"				=> "Upload file complete",
						"id" 				=> $id_matrix,
						"tipo"				=> $tipo,
						"parent"			=> $parent,
						"top_id"			=> $top_id,
						"top_tipo"			=> $top_tipo,
						"component_name" 	=> $component_name,
						"quality" 			=> $quality,
						"file_name" 		=> $nombre_archivo,
						"file_size_mb" 		=> $file_size_mb,
						"time_sec" 			=> $time_sec,
						"f_error"			=> $f_error
					)
			);

		}

		return $html;

	}#end upload_file


	





	/**
	* ERROR_NUMBER_TO_TEXT
	*/
	protected function error_number_to_text( $f_error_number ) {

		if( $f_error_number==0 ) {
						# all is ok
						$f_error_text = label::get_label('archivo_subido_con_exito');
		}else{
			switch($f_error_number) {
						# Error by number
				case 1 : $f_error_text = label::get_label('el_archivo_subido_excede_de_la_directiva');	break;
				case 2 : $f_error_text = label::get_label('el_archivo_subido_excede_el_tamano_maximo');	break;
				case 3 : $f_error_text = label::get_label('el_archivo_subido_fue_solo_parcialmente_cargado');	break;
				case 4 : $f_error_text = label::get_label('ningun_archivo_fue_subido');	break;
				case 6 : $f_error_text = label::get_label('carpeta_temporal_no_accesible');	break;
				case 7 : $f_error_text = label::get_label('no_se_pudo_escribir_el_archivo_en_el_disco');	break;
				case 8 : $f_error_text = label::get_label('una_extension_de_php_detuvo_la_carga_de_archivos');	break;
			}
		}

		return $f_error_text;
	}



	/**
	* RENAME_OLD_FILES_IF_EXISTS
	*/
	protected function rename_old_files_if_exists( $SID, $folder_path, $ar_allowed_extensions ) {	//$SID, $folder_path, $nombre_archivo, $curent_allowed_extension, $ar_allowed_extensions

		# DELETED FOLDER : Verificamos / creamos el directorio "deleted"
		if(!file_exists($folder_path . "/deleted")) mkdir($folder_path."/deleted", 0777,true);

		$dateMovement = date("Y-m-d_Gis"); # like 2011-02-08_182033

		# Recorremos todas las opciones de terminación posibles buscando ficheros a eliminar
		foreach ($ar_allowed_extensions as $current_allowed_extension) {

			$current_possible_file = $folder_path . $SID .'.'. $current_allowed_extension;
				#dump($current_possible_file,'current_possible_file');
			
			if(file_exists($current_possible_file)) {
				
				$file_to_move_renamed = $folder_path . 'deleted/'. $SID . '_deleted_'. $dateMovement . '.' . $current_allowed_extension ;			
				rename($current_possible_file, $file_to_move_renamed);
			}
		}

		/*
		# verificamos que NO hay ya un fichero anterior con ese nombre. Si lo hay, lo renombramos y movemos a deleted files
		if(file_exists($folder_path .'/'. $nombre_archivo)) {		
			
			# verificamos / creamos el directorio "deleted"
			if(!file_exists($folder_path . "/deleted")) mkdir($folder_path."/deleted", 0777,true);
			
			$dateMovement 	= date("Y-m-d_Gis"); # like 2011-02-08_182033			
			$nameMovM 		= $SID . '_deleted_'. $dateMovement . '.' . $file_extension
			 ;
			
			rename($folder_path .'/'. $nombre_archivo, $folder_path . "/deleted/" . $nameMovM);
		}
		*/
	}
	
	
	/**
	* VALIDATE_EXTENSION
	* Die if current file extension is not allowed
	*/
	public function validate_extension( $f_extension, $ar_allowed_extensions ) {

		foreach ($ar_allowed_extensions as $current_allowed_extension) {
			if (strtolower($current_allowed_extension) == strtolower($f_extension)) {
				return true;
			}
		}

		# Extension is not in allowed extensions
		$msg  = "<div class=\"uploadMsg\">Error: " .$f_extension. " is an invalid file type !<br/><br/>";
					$msg .= " Allowed file types: ";
					$msg .= implode(',', $ar_allowed_extensions);
					$msg .= " <br/><br/><a href=\"javascript:history.go(-1);\"> < Go Back </a></div>";
					die($msg);
	}



	/**
	* POSTPROCESSING_FILE
	*/
	protected function postprocessing_file($component_name, $SID, $quality, $uploaded_file_path) {

		switch ($component_name) {
			case 'component_av' : 
					# FFMPEG 
					try{
						$AVObj = new AVObj($SID, $quality);
						
						# POSTERFRAME. GUARDAMOS EL POSTERFRAME SI NO EXISTE YA		
						$PosterFrameObj = new PosterFrameObj($SID);		
						if(Ffmpeg::get_ffmpeg_installed_path() && !$PosterFrameObj->get_file_exists()) {
							$timecode = '00:00:05';
							$Ffmpeg = new Ffmpeg(); 
							$Ffmpeg->create_posterframe($AVObj, $timecode);
						}
						# CONFORM HEADERS. CONFORMAMOS LAS CABECERAS DEL FICHERO
						if(Ffmpeg::get_ffmpeg_installed_path()) {
							$Ffmpeg = new Ffmpeg();
							$Ffmpeg->conform_header($AVObj);
						}						

					} catch (Exception $e) {
					    $msg = 'Exception[upload_trigger][FFMPEG]: ' .  $e->getMessage() . "\n";
					    trigger_error($msg);
					}					
					break;

			case 'component_image' : 
					# IMAGEMAGIK . CONVERTIMOS EL ACHIVO AL FORMATO DE TRABAJO DE DEDALO (default is 'JPG')		
					try{	
						if(!ImageMagick::test_image_magick()===true) {
							throw new Exception("Error Processing Request. ImageMagick daemon not found", 1);							
						}

						$f_extension 	= strtolower(pathinfo($uploaded_file_path, PATHINFO_EXTENSION));
						if ($f_extension!=DEDALO_IMAGE_EXTENSION) {
							$new_file 	= substr($uploaded_file_path, 0, -(strlen($f_extension)) ).DEDALO_IMAGE_EXTENSION;
							ImageMagick::convert($uploaded_file_path, $new_file);
								#dump($new_file,"converted from: $uploaded_file_path");
						}							
						
						$aditional_path = $this->component_obj->get_aditional_path();

						# THUMB . Eliminamos el thumb anterior si existiese. Los thumbs se crean automáticamente al solicitarlos (list)
						# dump($uploaded_file_path,"component_name:$component_name, SID:$SID, quality:$quality, uploaded_file_path");
						$path_thumb = DEDALO_MEDIA_BASE_PATH.DEDALO_IMAGE_FOLDER.'/'.DEDALO_IMAGE_THUMB_DEFAULT.$aditional_path.'/'.$SID.'.'.DEDALO_IMAGE_EXTENSION;
						if (file_exists($path_thumb)) {
							unlink($path_thumb);
							if(SHOW_DEBUG) error_log("INFO: Deleted thumb image: $path_thumb from upload tool");
						}
						$source_file = DEDALO_MEDIA_BASE_PATH.DEDALO_IMAGE_FOLDER.'/'.DEDALO_IMAGE_QUALITY_DEFAULT.$aditional_path.'/'.$SID.'.'.DEDALO_IMAGE_EXTENSION;
						if (file_exists($source_file)) {
							# Create thumb from default quality file
							ImageMagick::dd_thumb('list', $source_file, $path_thumb);
							if(SHOW_DEBUG) error_log("INFO: Created thumb image: $path_thumb from $source_file in upload tool");
						}else{
							if(SHOW_DEBUG) error_log("INFO: Not created thumb image: $path_thumb from $source_file in upload tool because source file not found");	
						}						

					} catch (Exception $e) {
					    $msg = 'Exception[upload_trigger][ImageMagick]: ' .  $e->getMessage() . "\n";
					    trigger_error($msg);
					}
					break;
					
			case 'component_pdf' : 
					
					break;
		}
	}

	
}
?>