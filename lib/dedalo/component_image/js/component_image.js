// JavaScript Document

switch(page_globals.modo) {

	case 'tool_transcription':		
		break;
	case 'edit' :
		break;
	case 'list':		
		$(function() {
			// Remove background spinner in image container
			$('IMG.thumb_in_list').load(function(){
			    $(this).parent('.div_image_image_in_list').css({backgroundImage:'none'});
			    	//if(DEBUG) console.log('new image loaded: ' + this.src);
			})			
		});
		break;
}
/*

*/
/**
 * Trigger a callback when the selected images are loaded:
 * @param {String} selector
 * @param {Function} callback
 *//*
var onImgLoad = function(selector, callback){
    $(selector).each(function(){
        if (this.complete || $(this).height() > 0) {
            callback.apply(this);
        }
        else {
            $(this).on('load', function(){
                callback.apply(this);
            });
        }
    });
};
onImgLoad($('.thumb_in_list'), function(){
    $(this).hide().fadeIn(700);
});
*/

/**
* COMPONENT_IMAGE CLASS
*/

var cuadrado;
var circulo;
var puntero;
var anadido;
var vectores;
var toolZoom;
var salvar;

var component_image = new function() {

	// URL TRIGGER
	this.url_trigger = DEDALO_LIB_BASE_URL + '/component_image/trigger.component_image.php';	

	var canvas_obj, context, 
		canX , canY , canXold =0 , canYold =0, mouseIsDown = 0, len = 0;
	var node =[];
	var currentPaper;
	var buttonsLoaded = false;
	var ar_tag_loaded = new Array();

	var movePath = false;
	var segment, path, handle;
	var types = ['point', 'handleIn', 'handleOut'];
	var currentSegment, mode, type;
	var hitOptions = {
		segments: true,
		stroke: true,
		fill: true,
		tolerance: 5
	};


	// CANVAS : INIT
	this.init_canvas = function(canvas_id) {
		
		//paper.install(window);		
		window.onload = function() {
		//$(function() {			

			// CANVAS
			canvas_obj = document.getElementById(canvas_id);
				//console.log(canvas_obj)


			// INIT CANVAS ONLY WHEN IMAGE IS LOADED
			//$(canvas_obj).find('img').first().load(function() {

				//MSG
				//document.getElementById('header_info').innerHTML ="canvas with standar js";

				context 	= canvas_obj.getContext('2d');
				
				// IMG
				var img 	= document.getElementById('img_'+canvas_id),
					img_w 	= img.naturalWidth,
					img_h 	= img.naturalHeight;
					//canvas_obj.width = window.innerWidth;
					//canvas_obj.height = window.innerHeight;

					//console.log(ratio_window );
					//console.log(1/ratio_window );
					

				// CANVAS -> IMAGE MATCH SIZE
				canvas_obj.width 	= img_w;
				canvas_obj.height 	= img_h;
				/**/
//return;
			//	var zoomOpciones = new Array(
			//		'800','400','200','100','75','50','25','12.5','6.25','5','1','');
				var nivelZoom = null;

				// create select document.body
				//var contexto = canvas_obj.parentNode.parentNode;
				/*var contexto = document.getElementsByClassName('image_buttons')[0];
				var estado="zoom";

					var select = document.createElement("select");
					select.setAttribute("name", estado);
					select.setAttribute("id", estado);
					select.style.width = "75px";
					//console.log(event);
					//select.onchange = func;
					//console.log(func);

					// añadimos las opciones
				var option;
					//console.log(zoomOpciones.length);
					for ( var i = 0; i < zoomOpciones.length; i++ ){
						option = document.createElement("option");
						option.setAttribute("value", zoomOpciones[i]);
						//if (zoomOpciones[i] == "100"){
						//	option.setAttribute("selected", true);
						//}
						//option.value = '1';
						//option.appendChild(document.createTextNode('PM'));
						option.innerHTML = zoomOpciones[i] + "%";
						select.appendChild(option);
					}

				//lo añadidmos a la página
				contexto.appendChild(select);*/
				var select = document.getElementById('zoom');
				select.onchange = function(){
						nivelZoom = select.value;
						toolZoom.activate(); 
								zoomselecion(nivelZoom);
				}

				// PAPER 
				currentPaper = paper.setup(canvas_id);
				with(currentPaper) {
					//var zoomLayer = project.activeLayer.scale;
						 //var zoomLayer = item.bounds.size;
					//console.log (zoomLayer);
						var raster = new Raster('img_'+canvas_id);
						raster.position = view.center;
						var zoomActual = 1.0;
						
					// ZOOM
						toolZoom = new Tool();
						toolZoom.onMouseDown = function(event) {

							segment = path = null;
							var hitResult = project.hitTest(event.point, hitOptions);
							if (hitResult) {
									path = hitResult.item;
									//console.log(hitResult.type);
									if (hitResult.type == 'pixel') {		
										var location = hitResult.location;
										//segment = path.insert(location.index +1, event.point);
										if (event.modifiers.shift) {
											canvas_obj.width 		= canvas_obj.width * 0.5;
											canvas_obj.height 	= canvas_obj.height * 0.5;
											//canvas_obj.scale(zoomActual * 0.5, zoomActual * 0.5);
											//canvas_obj.restore();
											//canvas_obj.draw();
											view.zoom = zoomActual * 0.5;
									//view.scrollBy(0,0);
											view.scrollBy(new Point(-view.bounds.x, -view.bounds.y));
											//canvas_obj.style.backgroundPosition(event.point.x, );
											//var ctop=(-ui.position.top * canvas_obj.height / canvasWrapperHeight);
											zoomActual = zoomActual * 0.5;
											return;
										}else{
											canvas_obj.width 	= canvas_obj.width * 2.0;
											canvas_obj.height 	= canvas_obj.height * 2.0;
											//canvas_obj.scale(zoomActual * 2.0, zoomActual * 2.0);
											//canvas_obj.restore();
											//canvas_obj.draw();
											view.zoom = zoomActual * 2.0;
									//view.scrollBy(0,0);
											view.scrollBy(new Point(-view.bounds.x, -view.bounds.y));
											$(canvas_obj.parentNode).animate({ scrollTop: event.point.y + canvas_obj.parentNode.scrollTop, scrollLeft: event.point.x + canvas_obj.parentNode.scrollLeft}, 0);	

											//project.view.scrollBy(event.point);
											zoomActual = zoomActual * 2.0;
											return;
										}//end if (event.modifiers.shift)
									}//end if (hitResult.type == 'pixel')
							}//end if (hitResult)
						}//end toolZoom.onMouseDown = function(event)

							zoomselecion = function(nivelZoom) {
								a = nivelZoom/100;
								ratioZoom = a/zoomActual;
								zoomActual = a; 
								canvas_obj.width 	= canvas_obj.width * ratioZoom;
								canvas_obj.height 	= canvas_obj.height * ratioZoom;

								//console.log(canvas_obj.width);
								//console.log(canvas_obj.height);

								//context.zoom(ratioZoom,ratioZoom);
								//context.restore();
								view.zoom = a;
								//view.scrollBy(0,0);
								view.scrollBy(new Point(-view.bounds.x, -view.bounds.y));
								//context.scale(ratioZoom,ratioZoom)
								//project.activeLayer.translate(0,0);
								//raster.position = view.center;
								//console.log(a);
								//console.log(ratioZoom);
								//drawScreen();
								//drawScreen();
								return;
							}//end zoomselecion = function(nivelZoom)
			
						//var contexto = canvas_obj.parentNode.parentNode;
				//window.onload = function(event) {
						//if(ratio_window < 1){

							//var ventana_h =  window.innerHeight;
							//var ventana_h_util 	= ventana_h - 60;
							//var ratio_window 	= ventana_h_util /img_h;
							div_width= canvas_obj.parentNode.clientHeight;
							

							var ratio_window 	= div_width /img_h;


							porcentaje = ratio_window*100;
							zoomselecion(porcentaje);
							porcentaje_round = Math.round(porcentaje * 100) / 100;
							//console.log(porcentaje_round);
							//var seleccion_zoom = document.getElementById("zoom");
							var option = document.createElement("option");
		
							//option = document.createElement("option");
							//select.insert(new Element('option', {value: ratio_window, selected: true }).update('zoom'));
							option.setAttribute("value", porcentaje_round);
							option.setAttribute("selected", true);
							option.innerHTML = porcentaje_round + "%";
							//option.value = '1';
							//option.appendChild(document.createTextNode('PM'));
							//option.innerHTML = zoomOpciones[i] + "%";
							select.options[11] =option;
							select.selectedIndex = 11;
							seleccion_de_zoom = 11;
							//select.lastChild.text(option);
							//ratio_window = 1;
						//}
				//	}

					// Get a reference to the canvas object
					//var canvas = document.getElementById('myCanvas');
					// Create an empty project and a view for the canvas:
					//console.log(tool)
				}//end with(currentPaper)
			

			//});//end $(canvas_obj).find('img').first().load(function()
		//}//end onload window
		};//end $(function() {

	}//end this.init_canvas
	//Variables generales de los tools



//Botones de tools
			//SELECT del ZOOM
			// Crear opciones de select para el zoom
			
	this.load_svg_editor = function(tag) {

		// MODE : Only allow mode 'tool_transcription'
		if(page_globals.modo!=='tool_transcription') return null;

		if (buttonsLoaded == false){
				buttonsLoaded = true;
				this.cargartools();
			}

		//console.log(tag);
		parts_of_tag = component_image.get_parts_of_tag(tag);

		ar_tag_loaded[parts_of_tag.capaId]=tag;

		var data = parts_of_tag.data;
		var capaId = parts_of_tag.capaId;
		

		
		/* setting an onchange event */
		//selectNode.onchange = function() {dbrOptionChange()};
		 /* we are going to add two options */
		/* create options elements 
	 
		option = document.createElement("option");
		option.setAttribute("value", "100%");
		option.innerHTML = "100";
		select.appendChild(option);*/
		//CON PAPER
		//paper.setup(canvas_id);
		with(currentPaper) {

		//IMPORTACION DE CAPAS

			if ( data.indexOf('Layer')!=-1 ) {
				
				//project.layers[capaId].remove();				
				//children['example'].fillColor = 'red';
				for (var i=0; i < project.layers.length ; i++){
					if (project.layers[i].name == capaId){
						project.layers[i].remove();
							console.log("-> borrada capa: " + capaId);
					}
				}
				var capa = project.importJSON(data);
					console.log("-> importada json capa: " + capa.name);

				var color = capa.fillColor;
				
				project.deselectAll();
				project.view.draw();
				//console.log(project.layers[1].name);
				//console.log(capa.fillColor);
			}else{
				var create_new_capa = true;
				// Verificamos si el nombre del layer existe
				for (var i=0; i < project.layers.length ; i++){
					if (project.layers[i].name == capaId){
						capa = project.layers[i];
						capa.activate();
						create_new_capa = false;
						console.log("-> usando existente capa: " + capa.name);
						break;
					}
				}//end for
				if (create_new_capa == true) {
					var capa = new Layer();
					capa.name = capaId;
					console.log("-> creada nueva capa: " + capa.name);
					var color = new Color({
						hue: 360 * Math.random(),
						saturation: 1,
						brightness: 1,
						alpha: 0.3,
						});
					capa.fillColor = color;		
				}

			};// end else
			segment = path = handle= null;
			capa.activate();
			project.view.draw();
			project.deselectAll();
		};//end whith(paper)

	};// end load_svg_editor
			
	
			//console.log(capa.name)
	
	this.cargartools = function(){

		$('.main_buttons').show();
		/*
		//var contexto = canvas_obj.parentNode.parentNode;
		var contexto = document.getElementsByClassName('image_buttons')[0];
		//console.log(contexto);

			function createButton(contexto, estado, func){
					var button = document.createElement("input");
					button.type = "button";
					button.value = estado;
					button.onclick = func;
					contexto.appendChild(button);
					//document.body.appendChild(button);
				}

		if (buttonsLoaded == true) {
			createButton(contexto, "cuadrado", function(){ 
							cuadrado.activate(); 
					});
			createButton(contexto, "circulo", function(){ 
							circulo.activate(); 
					});

			createButton(contexto, "puntero", function(){ 
							puntero.activate(); 
					});

			createButton(contexto, "vectores", function(){ 
							vectores.activate(); 
					});

			createButton(contexto, "añadido", function(){ 
							anadido.activate(); 
					});
			createButton(contexto, "salvar", function(){ 
							salvar();
					});


			buttonsLoaded = true;		
		}//end if (buttonsLoaded == true)

*/

			with(currentPaper) {
		//CUADRADO
				cuadrado = new Tool();

				cuadrado.onMouseDown = function(event){
					segment = path = handle= null;
					project.deselectAll();
				};

				cuadrado.onMouseDrag = function(event){
					//console.log(project.activeLayer.name);
					//var rect = new Rectangle();
					var tama = new Size ({
						width: event.point.x - event.downPoint.x,
						height: event.point.y - event.downPoint.y
						});

					var cuadradopath = new Path.Rectangle({
						point: event.downPoint,
						size: tama,
						fillColor: project.activeLayer.fillColor,
						strokeColor: 'black'
					});

					// Remove this path on the next drag event:
					cuadradopath.removeOnDrag();
				};
		//CIRCULO
				circulo = new Tool();

				circulo.onMouseDown = function(event){
					segment = path = handle= null;
					project.deselectAll();
				};

				circulo.onMouseDrag = function(event){
					//var rect = new Rectangle();
					var a =new Point ({
						x: event.downPoint.x - event.point.x,
						y: event.downPoint.y - event.point.y,
						});

					var circulopath = new Path.Circle({
						center: event.downPoint,
						radius: a.length,
						fillColor: project.activeLayer.fillColor,
						strokeColor: 'black'
					});

					//console.log((event.downPoint.x - event.point.x).length);

					// Remove this path on the next drag event:
					circulopath.removeOnDrag();
				};


		//AÑADIR PUNTO				
				anadido =new Tool();
				
				anadido.onMouseDown = function(event) {
					segment = path = handle= null;
					var hitResult = project.hitTest(event.point, hitOptions);
					if (hitResult) {
							path = hitResult.item;
							//console.log(hitResult.type);
							if (hitResult.type == 'stroke') {		
								var location = hitResult.location;
								segment = path.insert(location.index +1, event.point);
								//path.smooth();
							}
						}
				};
					
				anadido.onMouseMove = function(event){
					var hitResult = project.hitTest(event.point, hitOptions);
					project.activeLayer.selected = false;
					if (hitResult && hitResult.item)
						hitResult.item.selected = true;
				};
				
				anadido.onMouseDrag = function(event) {
					if (segment) {
						segment.point.x = event.point.x;
						segment.point.y = event.point.y;
					}
				};
				
		//PUNTERO			
				puntero = new Tool();

				puntero.onMouseDown = function(event) {
					segment = path = handle= null;
					//project.activeLayer.selected = false;
					var hitResult = project.hitTest(event.point, { fill: true, stroke: true, segments: true, tolerance: 5, handles: true });

					if (event.modifiers.shift) {
						if (hitResult.type == 'segment') {
							hitResult.segment.remove();
						};
						if(hitResult.type == 'fill'){
							path = hitResult.item;
							//console.log(path.layer.name);
							//console.log(project.activeLayer.name);
							if (project.activeLayer.name == path.layer.name) {
							//	console.log("mismo layer");
								path.selected = true;
								//console.log(capa);
								//path.selected = true;
							}else{
							//	console.log("distinto layer");
								project.deselectAll();
								capa = path.layer;
								capa.activate();
								path.selected = true;
							}

						}
						if(hitResult.type == 'pixel'){
							project.activeLayer.selected = false;
						}
						console.log(hitResult.type);
						return;
					}

					if (hitResult) {
						if(hitResult.type == 'fill'){
							project.deselectAll();
							path = hitResult.item;
							capa = path.layer;
							capa.activate();
							//console.log(capa.name)
							path.selected = true;
						}
						if(hitResult.type == 'pixel'){
							project.deselectAll();
							project.activeLayer.selected = false;
							//path.selected = false;
							//path = null;
						}
						//console.log(hitResult.type);
						if (hitResult.type == 'segment') {
							project.deselectAll();
							path = hitResult.item;
							path.fullySelected = true;
							segment = hitResult.segment;
							//segment = hitResult.segment;
						} 
						if (hitResult.type == 'stroke') {
							var location = hitResult.location;
							path = hitResult.item;
							segment = path.insert(location.index +1, event.point);
							//path.smooth();
						}
						if (hitResult.type == 'handle-in') {	
							handle = hitResult.segment.handleIn;
						}
						if (hitResult.type == 'handle-out') {	
							handle = hitResult.segment.handleOut;
						}
			
						console.log(hitResult.type);
					}
					
					movePath = hitResult.type == 'fill';
					/*if (movePath)
						project.activeLayer.addChild(hitResult.item);*/
				};
				/*
				puntero.onMouseMove = function(event){
					var hitResult = project.hitTest(event.point, hitOptions);
					project.activeLayer.selected = false;
					if (hitResult && hitResult.item)
						hitResult.item.selected = true;
				}*/

				puntero.onMouseDrag = function(event) {

					if (handle){
						handle.x += event.delta.x;
						handle.y += event.delta.y;
					}

					if (segment) {
					//console.log(segment);
						segment.point.x = event.point.x;
						segment.point.y = event.point.y;
						//console.log(event);
						//console.log(segment);
						//path.smooth();
					}

					if (movePath){
						path.position.x += event.delta.x;
						path.position.y += event.delta.y;
						}
				};
				puntero.onKeyUp = function(event){

					if (event.key == "backspace" || event.key == "delete"){
						//console.log(event.key);
						var seleccionados = project.selectedItems;
						//console.log(seleccionados);
						for (var i = 0; i < seleccionados.length; i++ ){
								seleccionados[i].remove();
								segment = path = null;
						}			
					}
				};
				puntero.activate(); 


		//VECOTRES
				vectores =new Tool();

				findHandle = function(path, point) {
					//console.log("path: " + path);
					//console.log("path.segments.length "+path.segments.length);

					for (var i = 0, l = path.segments.length; i < l; i++) {
						for (var j = 0; j < 3; j++) {
							var type = types[j];
							var segment = path.segments[i];
							if (type == 'point'){
									segmentPoint = segment.point;
							}else{
								segmentPoint.x = segment.point.x + segment[type].x;
								segmentPoint.y = segment.point.y + segment[type].y;
							}
							//var segmentPoint = type == 'point'
							//		? segment.point
							//		: segment.point.x + segment[type].x;
							var distancia = new Point;// = (point - segmentPoint).length;
							distancia.x = (point.x - segmentPoint.x);
							distancia.y = (point.y - segmentPoint.y);
							var distance = distancia.length;

							//console.log("point " + point);
							//console.log("segmentPoint: " + segmentPoint);
							//console.log("distance " + distance);

							if (distance < 3) {
								return {
									type: type,
									segment: segment
								};
							}
						}
					}
					//console.log(point)
					return null;
				};
				//console.log("path: "+path);
				//function onMouseDown(event) {
				vectores.onMouseDown = function(event) {
					//console.log(currentSegment);

					if (currentSegment){
						currentSegment.selected = false;
					}				
					mode = type = currentSegment = null;
					//
					if (!path) {
						path = new Path({
							strokeColor : 'black',
							fillColor : project.activeLayer.fillColor
						});
					}

					var result = findHandle(path, event.point);
					console.log("result: " + result);
					if (result) {
						currentSegment = result.segment;
						type = result.type;
						//console.log(path.segments.length);
						//console.log(result.type);
						//console.log(result.segment.index);

						if (path.segments.length > 1 && result.type == 'point'
								&& result.segment.index == 0) {
							mode = 'close';
							path.closed = true;
							path.selected = false;
							path = null;
						}
					}

					if (mode != 'close') {						
						mode = currentSegment ? 'move' : 'add';
						if (!currentSegment)
							currentSegment = path.add(event.point);
						currentSegment.selected = true;
					}
				};
				
				vectores.onMouseDrag = function(event) {
					if (mode == 'move' && type == 'point') {
						currentSegment.point = event.point;
					} else if (mode != 'close') {
						var delta = event.delta.clone();	
						if (type == 'handleOut' || mode == 'add') {
							//console.log(delta.x +" "+(delta.x)*-1)
							//console.log(delta)
							//delta = -delta;
							delta.x =	(delta.x)*-1
							delta.y =	(delta.y)*-1	

						}
						//console.log(delta);						
						//currentSegment.handleIn += delta;
						currentSegment.handleIn.x += delta.x;
						currentSegment.handleIn.y += delta.y;

						//currentSegment.handleOut -= delta;
						currentSegment.handleOut.x -= delta.x;
						currentSegment.handleOut.y -= delta.y;
					}
				};

		// SALVAR
				salvar = function(){
					//capa.activate();
					//console.log (project.activeLayer.exportJSON());
					// TAG : Obtiene el nombre que es el del layer activo
					var tag 			= ar_tag_loaded[project.activeLayer.name],
						parts_of_tag 	= component_image.get_parts_of_tag(tag),
						tagId 			= parts_of_tag.capaId,
						tagState 		= parts_of_tag.tagState,
						data 			= project.activeLayer.exportJSON();

					//console.log("tag: "+tag)

					// UPDATE : Actualiza el contenido data del tag (y fuerza salvar en el text area)
					ar_tag_loaded[project.activeLayer.name] = component_text_area.update_svg_tag(tag,tagId,tagState,data);
					//alert(tag);
				};
			};//end whith(paper)
		}; //end cargartools
				


	this.get_parts_of_tag = function(tag){

		var ar_tag = tag.split('-');
		if (ar_tag[0] != '[svg'){
			return alert("invalid tag here!!!!");
			};
		var tagState 	= ar_tag[1];
		var capaId 		= ar_tag[2];
		//var pos 		= tag.indexOf('data:\'');
		//var	data 	= tag.substring( pos+6, tag.length-7); // ':data]
		var pos 		= tag.indexOf('data:');
		var	data 		= tag.substring( pos+5, tag.length-6); // :data]
		// RESTORE COMILLAS "
		data = replaceAll('\'','"',data);

		var parts_of_tag = new Object({
			capaId : capaId,
			tagState : tagState,
			data : data
		});
		//console.log(parts_of_tag);

		return parts_of_tag;
	}


}; //end component_image class


