<?php
/*
* CLASS TOOL UPLOAD
*/
require_once( dirname(dirname(dirname(__FILE__))) .'/config/config4.php');


class tool_upload extends tool_common {
	
	# media component
	protected $component_obj ;

	# file_obj container of util file vars
	public $file_obj;

	
	/**
	* __CONSTRUCT
	* @param $component_obj object
	* @param $modo string (optional)
	*/
	public function __construct($component_obj, $modo='button') {
		
		# Fix current media component
		$this->component_obj = $component_obj;
			#dump($component_obj, ' component_obj');
		
		# Fix modo
		$this->modo = $modo;

		$this->file_obj = new stdClass();

	}




	/**
	* UPLOAD_FILE
	* @param $quality string
	* @return $html string
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
						#dump($folder_path,'$folder_path'); die();
					$current_extension 		= DEDALO_PDF_EXTENSION;
					$ar_allowed_extensions 	= unserialize(DEDALO_PDF_EXTENSIONS_SUPPORTED);
					break;
		}
				

		# VARS : Verify
		if(!$SID) 		exit('Error SID not defined (1)');
		if(!$quality) 	exit('Error: quality not defined (1)');	
		
			#dump($_FILES["fileToUpload"],'$_FILES["fileToUpload"]');

		# VERIFICAMOS SI EL ARCHIVO ES VÁLIDO
		$f_name 		= $_FILES["fileToUpload"]['name'];
		$f_type 		= $_FILES["fileToUpload"]['type'];
		$f_temp_name	= $_FILES["fileToUpload"]['tmp_name'];
		$f_size			= $_FILES["fileToUpload"]['size'];
		$f_error		= $_FILES["fileToUpload"]['error'];
		$f_error_text 	= tool_upload::error_number_to_text($f_error);
		$f_extension 	= strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
			//dump($f_extension,'$f_extension for '.$f_temp_name.' - '.$f_name);

		$this->file_obj->f_name 	 = $f_name;

		# EXTENSIONS : Validate extension file
		$is_valid_extension = $this->validate_extension( $f_extension, $ar_allowed_extensions );
		if ($is_valid_extension !== true) {
			return (string)$is_valid_extension; // msg html
		}

		# NOMBRE_ARCHIVO : Nombre final del archivo	
		$nombre_archivo = $SID . '.' . $f_extension ;		

		# LOG UPLOAD BEGINS			
			$tipo 			= $this->component_obj->get_tipo();
			$parent 		= $this->component_obj->get_parent();			
			$file_size_mb 	= round( ($f_size/1024)/1024 , 2 );			
			
			# LOGGER ACTIVITY : QUE(action normalized like 'LOAD EDIT'), LOG LEVEL(default 'logger::INFO'), TIPO(like 'dd120'), DATOS(array of related info)
			logger::$obj['activity']->log_message(
				'UPLOAD',
				logger::INFO,
				$tipo,
				NULL,
				array(	"msg"				=> "Upload file start",					
						"tipo"				=> $tipo,
						"parent"			=> $parent,
						"top_id"			=> TOP_ID,
						"top_tipo"			=> TOP_TIPO,
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

		$this->file_obj->uploaded_file_path = $folder_path . $nombre_archivo;	
	


		# Move temp uploaded file to final dir 
		if(file_exists($f_temp_name)) {

			$fileUploadOK =0;

			#If the file is a .zip (DVD) create the folder and copy the VIDEO_TS and AUDIO_TS to the destination folder.
			if($f_extension == 'zip'){

				$zip = new ZipArchive;
				$res = $zip->open($f_temp_name);

				if ($res === TRUE) {
					# Create the directorys
					if( !is_dir($folder_path.'/'.$SID) ) {
						$create_dir = mkdir($folder_path.'/'.$SID, 0777);
						$create_dir = mkdir($folder_path.'/'.$SID.'/VIDEO_TS/', 0777);
						$create_dir = mkdir($folder_path.'/'.$SID.'/AUDIO_TS/', 0777);
						if(!$create_dir) {
							throw new Exception("Error on read or create directory for \"$SID\" folder. Permission denied ! ", 1);
						}
					}
					# See al .zip files for located the VIDEO_TS and AUDIO_TS folders
					for ($i=0; $i < $zip->numFiles; $i++) {

						  $filename = $zip->getNameIndex($i);
						  
						  if(strpos($filename,'VIDEO_TS')!== false){

						  	$fileinfo = pathinfo($filename);
						  	# Don't copy the original VIDEO_TS in the zip file
							if ($fileinfo['basename'] == 'VIDEO_TS') {
								continue;
							}
							#Copy al files of the VIDEO_TS zip file into the VIDEO_TS destination file
							copy("zip://".$f_temp_name.'#'.$filename, $folder_path.$SID.'/VIDEO_TS/'.$fileinfo['basename']);

					        $fileUploadOK = 1;


						  }else if(strpos($filename,'AUDIO_TS')!== false){
							$fileinfo = pathinfo($filename);
							# Don't copy the original AUDIO_TS in the zip file
							if ($fileinfo['basename'] == 'AUDIO_TS') {
								continue;
							}
							#Copy al files of the VIDEO_TS zip file into the AUDIO_TS destination file					        
					        copy("zip://".$f_temp_name.'#'.$filename, $folder_path.$SID.'/AUDIO_TS/'.$fileinfo['basename']);
					        $fileUploadOK = 1;

						  }else{
						  	$fileUploadOK = 0;
						  }
					}
					$zip->close();
			 
				}
			}else{

				$move_file = (bool)move_uploaded_file($f_temp_name, $this->file_obj->uploaded_file_path);
				if (!$move_file) {
					$msg = "File $f_temp_name exists. Error on move to: " . $this->file_obj->uploaded_file_path ; 
					trigger_error($msg);
				}
			}
		}else{
			$msg = "Error[upload_trigger]: temporal file $f_temp_name not exists. I can't move the file to final location.";
			trigger_error($msg);
			exit($msg);	
		}


			//dump($fileUploadOK, ' fileUploadOK'.to_string());
		#
		# ERROR : FILE NOT FOUND
		if( !file_exists($this->file_obj->uploaded_file_path) && $fileUploadOK == 0 ) {		
			
			$fileUploadOK = 0;	# ERROR	NUMBER

			$msg = "Error[upload_trigger]: ". label::get_label('error_al_subir_el_archivo') .' '. label::get_label('el_directorio_no_existe') .' [1]';
			trigger_error($msg);
			

			$html .= "<!-- UPLOAD MSG ERROR -->";
			$html .= "<div class=\"uploadMsg\">";
				$html .= label::get_label('error_al_subir_el_archivo') . "<br>";
				$html .= label::get_label('el_directorio_no_existe') ;
				$html .= "<br><a href=\"javascript:history.go(-1);\">".label::get_label('volver')."</a>";
				if(SHOW_DEBUG) {
					$html .= "<br>" . $this->file_obj->uploaded_file_path;
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
						"tipo"				=> $tipo,
						"parent"			=> $parent,
						"top_id"			=> TOP_ID,
						"top_tipo"			=> TOP_TIPO,
						"component_name" 	=> $component_name,
						"quality" 			=> $quality,
						"file_name" 		=> $nombre_archivo,
						"file_size_mb" 		=> $file_size_mb,
						"time_sec" 			=> $time_sec,
						"f_error"			=> $f_error
					)
			);

		#
		# OK : FILE FOUND
		}else{
			
			$fileUploadOK = 1;	# OK
			
			# AJUSTAMOS LOS PERMISOS
			/*
			try{
				$ajust_permissions = chmod($this->file_obj->uploaded_file_path, 0775);
				if (!$ajust_permissions) {
					$msg = "Error[upload_trigger]: Error on set permissions [2]";
					trigger_error($msg);		
				}
			} catch (Exception $e) {
				$msg = 'Exception[upload_trigger][SET_PERMISSIONS]: ' . $e->getMessage() . "\n";
				trigger_error($msg);
			}
			*/

			
			$html .= " <!-- UPLOAD MSG OK -->";
			$html .= "<div class=\"uploadMsg\">";
			$html .= "<div class=\"uploadMsg_ok\">";
			$html .= 'Ok. '. label::get_label('fichero_subido_con_exito');

			if (defined('DEDALO_AV_RECOMPRESS_ALL') && DEDALO_AV_RECOMPRESS_ALL==1) {
				if ($file_size_mb>10) {
					$html .= "<hr> The uploaded file is transcoding in background now. Please, wait some minutes before play your media file.";
					if(SHOW_DEBUG) {
						$html .= " MB:$file_size_mb";
					}
				}				
			}//end if (defined('DEDALO_AV_RECOMPRESS_ALL') && DEDALO_AV_RECOMPRESS_ALL==1) {


				# POSTPROCESSING_FILE : Procesos a activar tras la carga del archivo
				$postprocessing_result = $this->postprocessing_file($component_name, $SID, $quality);
				# POSTPROCESSING_FILE NOTIFICATIONS
				if ( strpos( strtolower($postprocessing_result), 'error')!==false || strpos( strtolower($postprocessing_result), 'exception')!==false ) {
				$html .= "<div class=\"warning\">";
				$html .= ' File was uploaded correctly but an ERROR was found in the processing: '. $postprocessing_result;
				$html .= "</div>";
				}

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
						"tipo"				=> $tipo,
						"parent"			=> $parent,
						"top_id"			=> TOP_ID,
						"top_tipo"			=> TOP_TIPO,
						"component_name" 	=> $component_name,
						"quality" 			=> $quality,
						"file_name" 		=> $nombre_archivo,
						"file_size_mb" 		=> $file_size_mb,
						"time_sec" 			=> $time_sec,
						"f_error"			=> $f_error
					)
			);
		}

		# Save component refresh 'valor_list'
		$this->component_obj->Save();

		return $html;

	}#end upload_file


	





	/**
	* ERROR_NUMBER_TO_TEXT
	* @param $f_error_number int
	* @return $f_error_text strint
	*/
	public static function error_number_to_text( $f_error_number ) {

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
	* @param $SID string
	* @param $folder_path string
	* @param $ar_allowed_extensions array
	*/
	protected function rename_old_files_if_exists( $SID, $folder_path, $ar_allowed_extensions ) {	//$SID, $folder_path, $nombre_archivo, $curent_allowed_extension, $ar_allowed_extensions

		# DELETED FOLDER : Verificamos / creamos el directorio "deleted"
		if(!file_exists($folder_path . "/deleted")) mkdir($folder_path."/deleted", 0777,true);

		$dateMovement = date("Y-m-d_Gis"); # like 2011-02-08_182033

		# Recorremos todas las opciones de terminación posibles buscando ficheros a eliminar
		foreach ($ar_allowed_extensions as $current_allowed_extension) {

			$current_possible_file = $folder_path .'/'. $SID .'.'. $current_allowed_extension;
			if(SHOW_DEBUG) {
				//dump($current_possible_file,'current_possible_file');
			}
			if(file_exists($current_possible_file)) {
					//dump($current_possible_file, ' current_possible_file'.to_string());
				$file_to_move_renamed = $folder_path . '/deleted/'. $SID . '_deleted_'. $dateMovement . '.' . $current_allowed_extension ;			
				rename($current_possible_file, $file_to_move_renamed);
			}
		}

		if(is_dir($folder_path.'/'.$SID)) {
			$file_to_move_renamed = $folder_path . '/deleted/'. $SID . '_deleted_'. $dateMovement ;			
			rename($folder_path.'/'.$SID , $file_to_move_renamed);
		}
	}
	
	
	/**
	* VALIDATE_EXTENSION
	* @param string $f_extension like 'mp4'
	* @param array $ar_allowed_extensions
	* @return bool true or string $msg
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

		return $msg;
	}



	/**
	* POSTPROCESSING_FILE
	* @param $component_name string (modelo name like component_av)
	* @param $SID string 
	* @param $quality string
	*/
	protected function postprocessing_file($component_name, $SID, $quality) {
		$result=null;

		switch ($component_name) {
			case 'component_av' : 
					# FFMPEG 
					try{

						#
						# EXTENSION
						$file_name 	= pathinfo($this->file_obj->uploaded_file_path, PATHINFO_BASENAME);
						$file_ext 	= pathinfo($this->file_obj->uploaded_file_path, PATHINFO_EXTENSION);
						if (empty($file_ext)) {
							throw new Exception("Error Processing Request. File extension is unknow", 1);							
						}
						if(SHOW_DEBUG) {
							#dump($file_ext, ' uploaded_file_path');;
						}

						$AVObj = new AVObj($SID, $quality);

						#
						# AUDIO CASE
						if ($quality=='audio') {
							# AUDIO Extensions supported
							$ar_audio_only_ext = array('mp3','aiff','aif','wave','wav');
							if (in_array($file_ext, $ar_audio_only_ext)) {
								# Audio conversion							
								$Ffmpeg = new Ffmpeg();
								$Ffmpeg->convert_audio($AVObj, $this->file_obj->uploaded_file_path);
							}else{
								throw new Exception("Error Processing Request. Current audio extension is not supported", 1);								
							}
						#
						# VIDEO CASE
						}else{

							#
							# DEDALO_AV_RECOMPRESS_ALL
							# When config DEDALO_AV_RECOMPRESS_ALL is set to 1, all video files are re-compressed to 960k/s variable bit rate and keyframe every 75 frames
							if (defined('DEDALO_AV_RECOMPRESS_ALL') && DEDALO_AV_RECOMPRESS_ALL==1) {
								
								# If default quality file not exists, generate default quality version now
								# $target_file  = $AVObj->get_local_full_path(); ???????????????????????????????? SURE ???????
								$AVObj_target = new AVObj($SID, DEDALO_AV_QUALITY_DEFAULT);
								$target_file  = $AVObj_target->get_local_full_path();
								if (!file_exists($target_file)) {
									$source_file = $this->file_obj->uploaded_file_path;
									/*
									$source_file2= $source_file.'_original.'.$file_ext;
									if( !rename($source_file, $source_file2) ) {
										throw new Exception("Error Processing Request. File $source_file access denied", 1);									
									}
									*/
									
									Ffmpeg::convert_to_dedalo_av( $source_file, $target_file );
									if(SHOW_DEBUG) {
										#dump($command, ' command');
									}
								}//end if (!file_exists($target_file)) {							
							}

							#
							# POSTERFRAME
							# Create posterframe of current video if not exists	
							$PosterFrameObj = new PosterFrameObj($SID);		
							if(Ffmpeg::get_ffmpeg_installed_path() && !$PosterFrameObj->get_file_exists()) {
								$timecode = '00:00:05';
								$Ffmpeg = new Ffmpeg(); 
								$Ffmpeg->create_posterframe($AVObj, $timecode);
							}

							#
							# CONFORM HEADERS
							# Apply qt-faststart to optimize file headers position						
							#$Ffmpeg = new Ffmpeg();
							#$Ffmpeg->conform_header($AVObj);							
						}						
												

					} catch (Exception $e) {
						$msg = 'Exception[upload_trigger][FFMPEG]: ' .  $e->getMessage() . "\n";
						trigger_error($msg);
					}					
					break;

			case 'component_image' : 
					# IMAGEMAGIK . CONVERTIMOS EL ACHIVO AL FORMATO DE TRABAJO DE DEDALO (default is 'JPG')		
					try{

						$this->file_obj->aditional_path = $this->component_obj->get_aditional_path();

						#
						# DEFAULT_IMAGE_FORMAT : If uploaded file is not in Dedalo standar format (jpg), is converted, and original is conserved (like filename.tif)
						$this->file_obj->default_format_file = $this->build_standar_image_format();
						
						#
						# THUMB . Eliminamos el thumb anterior si existiese. Los thumbs se crean automáticamente al solicitarlos (list)
						#$this->file_obj->thumb_file = $this->build_thumb_file($SID);


						# POSTPROCESSING_IMAGE_SCRIPT
						if (defined('POSTPROCESSING_IMAGE_SCRIPT')) {
							sleep(1);
							require( POSTPROCESSING_IMAGE_SCRIPT );
							$result = custom_postprocessing_image($this);
								#dump($result, ' result');
						}
						/*
						if ($quality==DEDALO_IMAGE_QUALITY_ORIGINAL) {
							# Create default version too
							$this->component_obj->convert_quality( $quality, DEDALO_IMAGE_QUALITY_DEFAULT );
							# Create thumb from default quality version
							$this->component_obj->generate_thumb();
						}
						*/

						# Save force update data and create default and thumb qualitys
						$this->component_obj->Save();

					} catch (Exception $e) {
						$msg = 'Exception[upload_trigger][ImageMagick]: ' .  $e->getMessage() . "\n";
						trigger_error($msg);
					}
					break;
					
			case 'component_pdf' :

					# TO DO : Tumb of current pdf (first page) like filename.pdf[0] 
					#dump($this->file_obj, ' this->file_obj->thumb_file');
					#dump($SID, ' $SID');
					
					/*
					#
					# THUMB	
					if(!isset($this->file_obj->aditional_path)) $this->file_obj->aditional_path=null;				
					$source_file 	= DEDALO_MEDIA_BASE_PATH.DEDALO_PDF_FOLDER.'/'.DEDALO_PDF_QUALITY_DEFAULT.$this->file_obj->aditional_path.'/'.$SID.'.'.DEDALO_PDF_EXTENSION;
					$target_file 	= DEDALO_MEDIA_BASE_PATH.DEDALO_PDF_FOLDER.'/thumb'.$this->file_obj->aditional_path.'/'.$SID.'.'.DEDALO_IMAGE_EXTENSION;
					$dimensions 	= '102x57';					
					$command = MAGICK_PATH."convert \"$source_file\"[0] \
         									-thumbnail {$dimensions} -gravity center -extent {$dimensions} -unsharp 0x.5 jpg -quality 90 \"$target_file\" ";
         			$output   = shell_exec( $command );
         				#dump($output, ' output - '.$command);
					*/
					break;
		}

		return $result;

	}#end postprocessing_file




	/**
	* BUILD_standar_IMAGE_FORMAT
	* If uploaded file is not in Dedalo standar format (jpg), is converted, and original is conserved (like filename.tif)
	*/
	protected function build_standar_image_format() {

		$f_extension 	= strtolower(pathinfo($this->file_obj->uploaded_file_path, PATHINFO_EXTENSION));
		if ($f_extension!=DEDALO_IMAGE_EXTENSION) {
			$new_file_path 	= substr($this->file_obj->uploaded_file_path, 0, -(strlen($f_extension)) ).DEDALO_IMAGE_EXTENSION;
			ImageMagick::convert($this->file_obj->uploaded_file_path, $new_file_path);
				#dump($new_file_path,"converted from: $this->file_obj->uploaded_file_path");

			return $new_file_path;
		
		}else{
			return $this->file_obj->uploaded_file_path;
		}		
	}


	

	
}
?>