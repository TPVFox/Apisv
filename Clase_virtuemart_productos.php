<?php 
class APISV_virtuemart_productos {
        public function descontarStock($productos){
            // @ Objetivo:
            // Descontar el stock de una lista de productos.
            // @ Devolvemos:
            // Array con:
            //   [row_afectados] => Cantidad de productos cambiados con exito.
            //   [error] => Array con
            //              [grave] 0> Si hubo error en update
            //              [] -> Si no se cambio nada. ( no hubo error, pero no existe producto)
            $resultado=array();
            $db = JFactory::getDbo();
            $query=$db->getQuery(true);
            $resultado['error'] = array();
            $resultado['consultas'] = array();

            try{
                $row_afectados = 0;                
                foreach ( $productos as $producto ){
                    if (trim($producto['idVirtuemart']) <>''){ 
                        $query='UPDATE #__virtuemart_products set product_in_stock=(product_in_stock-'.$producto['nunidades'].') ,product_ordered="0" where virtuemart_product_id='.$producto['idVirtuemart'];
                        $resultado['consultas'][]= $db->replacePrefix((string) $query);
                        $db->setQuery($query);
                        $db->execute();
                        $row_afectados = $db->getAffectedRows();
                        if ($db->getAffectedRows() == 0){
                            // Algo salio mal...
                            $resultado['error'][] ='Error en producto:'.$producto['idVirtuemart'];
                            error_log('Error en Clase virtuemart en Apisv_ Error en producto al descontar stock id:'.$producto['idVirtuemart']);
                        }
                    }
                }
                $resultado['row_afectados']=$row_afectados;                 
            }catch (Exception $e) {
                $resultado['error']['grave']=$e->getMessage();
            }
             return $resultado;
        }

        public function CambiarStockYPrecios($productos){
            // Objetivo cambiar stock y precios de todos los productos que enviamos de tpv.
            $resultado=array();
            $db = JFactory::getDbo();
            $query=$db->getQuery(true);
            try{
                
                foreach ( $productos as $producto ){
                    if (trim($producto['idVirtuemart']) <>''){ 
                        // Cambiamos stock
                        $query='UPDATE #__virtuemart_products set product_in_stock="'.$producto['stockOn'].'",product_ordered="0" where virtuemart_product_id='.$producto['idVirtuemart'];
                        
                        $resultado['consulta1'][]=$db->replacePrefix((string) $query);
                        
                        $db->setQuery($query);
                        $db->execute();
                        // Cambiamos Precio

                        $query='UPDATE #__virtuemart_product_prices SET product_price="'.$producto['pvpSiva'].'" WHERE `virtuemart_product_id`='.$producto['idVirtuemart'];
                        
                        $resultado['consulta2'][]=$db->replacePrefix((string) $query);
                        
                        $db->setQuery($query);
                        $db->execute();
                        
                    }
                
                     
                }
                 
            }catch (Exception $e) {
                $resultado['error']=$e->getMessage();
               
                
            }
            //~ error_log(json_encode($resultado));
             return $resultado;
        }
        
        function ObtenerDatosDeProducto($id_virtuemart){
            // @ Objetivo
            // Obtener los datos de un producto para utilizar en tpvFox.
            // @ Parametros
           
            //  $id_virtuemart-> (int) Id del producto virtuemart queremos obtener.
       
            $resultado=array();
            $db = JFactory::getDbo();
            $query=$db->getQuery(true);
            try{
                $select=' c.virtuemart_product_id AS idVirtual, c.published as estado, c.product_sku AS refTienda,'
                .' c.product_gtin AS codBarra, c.created_on AS fechaCre, c.created_by AS usuCre, c.modified_on AS fechaMod, p.product_tax_id as idIva,'
                .' c.modified_by AS usuMod, e.product_name AS articulo_name, e.slug AS alias ,p.product_price AS precioSiva,'
                .' coalesce(( select calc_value from  #__virtuemart_calcs as e WHERE e.virtuemart_calc_id = p.product_tax_id),0)'
                .' as iva ';
                
                $query->select($select)
                    ->from('#__virtuemart_products AS c  ')
                    ->join('LEFT', '#__virtuemart_products_es_es AS e ON c.virtuemart_product_id = e.virtuemart_product_id')
                    ->join('LEFT', '#__virtuemart_product_prices AS p ON c.virtuemart_product_id = p.virtuemart_product_id')
                    ->where('c.virtuemart_product_id ='.$id_virtuemart);
                $db->setQuery($query);
              
                $resultado['item']=$db->loadAssoc();
            }catch (Exception $e) {
                $resultado['error']=$e->getMessage();
            }
            return $resultado;
           
        }
        public function ObtenerIvasweb(){
                $resultado=array();
                $db = JFactory::getDbo();
                $query=$db->getQuery(true);
            try{
                $query->select('*')
                        ->from ('#__virtuemart_calcs');
                $db->setQuery($query);
                $db->query();
                $resultado['items']=$db->loadObjectList();
            
            }catch (Exception $e) {
                $resultado['error']=$e->getMessage();
            }
            return $resultado;
        }
        
        public function ModificarProducto($datos){
            $resultado=array();
            // Formateo los datos para evitar errores.
            $datos['referencia'] = htmlspecialchars($datos['referencia']);
            $datos['nombre'] =  htmlspecialchars($datos['nombre']);
            // Ahora modificamos.
            $db = JFactory::getDbo();
            $query=$db->getQuery(true);
            
            try{
                $patron = array(' ', '&', '(', '.', ')', '[', '^', ';', ']', '*', ';', '/', '%');
                $usuario=365;
                $alias = str_replace($patron, "_", $datos['alias']);
                $query='UPDATE #__virtuemart_products AS c
                LEFT JOIN
                    #__virtuemart_products_es_es AS e
                        ON
                        c.virtuemart_product_id = e.virtuemart_product_id
                LEFT JOIN
                    #__virtuemart_product_prices AS p
                        ON
                        c.virtuemart_product_id = p.virtuemart_product_id
                SET
                    c.published ='.$datos['estado'].',
                    c.product_sku="'.$datos['referencia'].'",
                    c.product_gtin="'.$datos['codBarras'].'",
                    c.product_in_stock="'.$datos['stock'].'",
                    c.modified_by='.$usuario.',
                    c.modified_on=NOW(),
                    e.product_name="'.$datos['nombre'].'",
                    p.product_price='.$datos['precioSiva'].',
                    e.slug="'.$alias.'",
                    p.product_tax_id='.$datos['iva'].'
                WHERE
                    c.virtuemart_product_id ='.$datos['id'];
                $resultado['consulta']=$db->replacePrefix((string) $query);
                $db->setQuery($query);
                $db->execute();
            }catch (Exception $e) {
                $resultado['error']=$e->getMessage();
            }
            return $resultado;
            
        }
        
        function modificarFamilia($datos){
            $resultado=array();
            $db = JFactory::getDbo();
            $query=$db->getQuery(true);
            try{
                $query='UPDATE #__virtuemart_categories as c 
                LEFT JOIN #__virtuemart_category_categories as a on 
                c.virtuemart_category_id=a.category_child_id
                     LEFT JOIN #__virtuemart_categories_es_es as b on 
                    c.virtuemart_category_id=b.virtuemart_category_id SET 
                     a.category_parent_id='.$datos['idPadre'].', 
                     b.category_name="'.$datos['nombre'].'" 
                     where c.virtuemart_category_id='.$datos['id'];
                $resultado['consulta']=$db->replacePrefix((string) $query);
                $db->setQuery($query);
                $db->execute();
            }catch (Exception $e) {
                $resultado['error']=$e->getMessage();
            }
            return $resultado;
               
        }
         function AddProducto($datos){
            $resultado=array();
            // Formateo los datos para evitar errores.
            $datos['referencia'] = htmlspecialchars($datos['referencia']);
            $datos['nombre'] =  htmlspecialchars($datos['nombre']);
            // Ahora hacemos insert
            $db = JFactory::getDbo();
            $query=$db->getQuery(true);
             try{
            $query='INSERT INTO `#__virtuemart_products`(`product_sku`,  
            `product_gtin`, product_params,  `published`, `created_on`, 
            `created_by`, `modified_on`, `modified_by`, product_in_stock)VALUES("'.$datos['referencia'].'",
            "'.$datos['codBarras'].'","'.$datos['parametros'].'", '.$datos['estado'].', NOW(),
            '.$datos['usuario'].',NOW(),'.$datos['usuario'].', '.$datos['stock'].')';
            
            $resultado['consulta']=$db->replacePrefix((string) $query);
            $db->setQuery($query);
            $db->execute();
            $id = $db->insertid();
            $resultado['idArticulo']=$id;
             try{
                 
                 $query='INSERT INTO`#__virtuemart_product_prices`(`virtuemart_product_id`,product_tax_id,   override, product_override_price , product_discount_id,    product_currency,`product_price`,`created_on`,  `created_by`,  `modified_on`,   `modified_by`   )  VALUES('.$id.', '.$datos['iva'].', '.$datos['override'].', "'.$datos['product_override_price'].'", '.$datos['product_discount_id'].', '.$datos['product_currency'].' ,   '.$datos['precioSiva'].', NOW(), '.$datos['usuario'].', NOW(), '.$datos['usuario'].')';
                        $resultado['consulta']=$db->replacePrefix((string) $query);
                         $db->setQuery($query);
                         $db->execute();
                         
                         try{
                             
                             if($datos['alias']==""){
                                $alias=$id.'_'.$datos['nombre'];
                            }else{
                                $patron = array(' ', '&', '(', '.', ')', '[', '^', ';', ']', '*', ';', '/', '%');
                                $alias = str_replace($patron, "", $datos['alias']);
                            }
                            // Replazamos los simbolos estraños.
                            $patron = array( ' ','&','(',
                                             '.',')','[',
                                             '^',';',']',
                                             '*',';','/',
                                             '%','ª','º',
                                             '-','`',"'",
                                             '!','€','"'
                                             );
                            $alias = str_replace($patron, "_", $alias);
                            // Replazamos los 
                            $patron = array( 'ñ','Ñ'
                                             );
                            $alias = str_replace($patron, "nh", $alias);    
                            $query='INSERT
                                        INTO
                                            `#__virtuemart_products_es_es`(
                                                `virtuemart_product_id`,
                                                `product_s_desc`,
                                                `product_desc`,
                                                `product_name`,
                                                `metadesc`,
                                                `metakey`,
                                                `customtitle`,
                                                `slug`
                                            )
                                        VALUES('.$id.', "'.$datos['s_desc'].'", "'.$datos['desc'].'", "'.$datos['nombre'].'", "'.$datos['metadesc'].'",
                                         "'.$datos['metakey'].'", "'.$datos['title'].'", "'.$alias.'")';
                                         $resultado['consulta']=$db->replacePrefix((string) $query);
                                         $db->setQuery($query);
                                         $db->execute();
                                         try{
                                             if(count($datos['familias'])>0){
                                                 foreach ($datos['familias'] as $familia){
                                                     $query='INSERT INTO `#__virtuemart_product_categories` (`virtuemart_product_id`, `virtuemart_category_id`,  `ordering`) VALUES ('.$id.', '.$familia['idFamilia_tienda'].', 0)';
                                                    $db->setQuery($query);
                                                    $db->execute();
                                                 }
                                             }
                                             
                                         }catch (Exception $e) {
                                            $resultado['error']=$e->getMessage();
                                        }
                                         
                         }catch (Exception $e) {
                    $resultado['error']=$e->getMessage();
                }
                }catch (Exception $e) {
                    $resultado['error']=$e->getMessage();
                }
              }catch (Exception $e) {
                $resultado['error']=$e->getMessage();
            }
            return $resultado;
         }
         
         public function AddFamilia($datos){
            $resultado=array();
            $db = JFactory::getDbo();
            $query=$db->getQuery(true);
             try{
                   $query='INSERT INTO
                                    `#__virtuemart_categories`(
                                        `virtuemart_vendor_id`,
                                        `limit_list_step`,
                                        `limit_list_initial`,
                                        `hits`,
                                        `cat_params`,
                                        `published`,
                                        `created_on`,
                                        `created_by`,
                                        `modified_on`,
                                        `modified_by`,
                                        `locked_on`,
                                        `locked_by`
                                    )
                                VALUES(
                                  '.$datos['vendor'].', '.$datos['limit'].', '.$datos['limit'].',
                                  '.$datos['hits'].', '."'".$datos['parametros']."'".', '.$datos['publicado'].',
                                  "'.$datos['fecha'].'", '.$datos['usuario'].', "'.$datos['fecha'].'",
                                  '.$datos['usuario'].', '.$datos['locked_by'].', '.$datos['locked_by'].' 
                                )';
                $resultado['consulta']=$db->replacePrefix((string) $query);
                $db->setQuery($query);
                $db->execute();
                $id = $db->insertid();
                $resultado['idFamilia']=$id;
                 try{
                    $patron = array(' ', '&', '(', '.', ')', '[', '^', ';', ']', '*', ';', '/', '%');
                    $alias = str_replace($patron, "_", $datos['alias']);

                     $query='INSERT
                                INTO
                                    `#__virtuemart_categories_es_es`(
                                        `virtuemart_category_id`,
                                        `category_name`,
                                        `slug`
                                    )
                                VALUES(
                                  '.$id.', "'.$datos['nombreFamilia'].'", "'.$alias.'"
                                )';
                    $resultado['consulta']=$db->replacePrefix((string) $query);
                    $db->setQuery($query);
                    $db->execute();
                    try{
                        $query='INSERT
                                    INTO
                                        `#__virtuemart_category_categories`(
                                            `category_parent_id`,
                                            `category_child_id`
                                        )
                                    VALUES(
                                     '.$datos['padre'].', '.$id.'
                                    )';
                    $resultado['consulta']=$db->replacePrefix((string) $query);
                    $db->setQuery($query);
                    $db->execute();
                        
                    }catch (Exception $e) {
                        $resultado['error']=$e->getMessage();
                    }
                    
                                
                 }catch (Exception $e) {
                    $resultado['error']=$e->getMessage();
                }
            
             }catch (Exception $e) {
                $resultado['error']=$e->getMessage();
            }
             return $resultado;
         }
         
         
         
         
         
         public function ObtenerNotificacionesProducto($id_virtuemart){
               $resultado=array();
            $db = JFactory::getDbo();
            $query=$db->getQuery(true);
            try{
                $query= 'SELECT w.virtuemart_waitinguser_id as idNotificacion , count(w.notify_email) as cant,
                    w.virtuemart_product_id as idProducto, w.virtuemart_user_id as idUsuario,
                    w.notify_email as email, w.notified as notificacion, u.name as nombreUsuario 
                    from #__virtuemart_waitingusers as w LEFT JOIN #__users 
                    AS u on w.virtuemart_user_id=u.id where w.`virtuemart_product_id`='.$id_virtuemart.' and 
                    w.notified=0 GROUP by w.notify_email';
                $db->setQuery($query);
               
                $resultado['sql']=$db->replacePrefix((string) $query);
                $resultado['items']=$db->loadObjectList();
            }catch (Exception $e) {
                $resultado['error']=$e->getMessage();
         }
         return $resultado;
     }
     public function modificarNotificacion($idProducto, $email){
        $resultado=array();
        $db = JFactory::getDbo();
        $query=$db->getQuery(true);
        try{
            $query='UPDATE #__virtuemart_waitingusers SET notified=1 WHERE `virtuemart_product_id`= '.$idProducto.' and notify_email="'.$email.'"';
            $db->setQuery($query);
            $db->execute();
          
        }catch (Exception $e) {
            $resultado['error']=$e->getMessage();
        }
        return $resultado;
     }
     public function contarProductos(){
        $resultado=array();
        $db = JFactory::getDbo();
        $query=$db->getQuery(true);
        try{
            $query='SELECT count(virtuemart_product_id ) AS productosWeb FROM  #__virtuemart_products';
            $db->setQuery($query);
            $resultado['item']=$db->loadAssoc();
        }catch (Exception $e) {
            $resultado['error']=$e->getMessage();
        }
         return $resultado;
     }
     public function DatosProductos($inicio, $final){
        $resultado=array();
        $db = JFactory::getDbo();
        $query=$db->getQuery(true);
         try{
           
                $sql=' SELECT c.virtuemart_product_id AS idVirtual, c.published as estado, c.product_sku AS refTienda,'
                .' c.product_gtin AS codBarra, c.created_on AS fechaCre, c.created_by AS usuCre, c.modified_on AS fechaMod, p.product_tax_id as idIva,'
                .' c.modified_by AS usuMod, e.product_name AS articulo_name, e.slug AS alias ,p.product_price AS precioSiva,'
                .' coalesce(( select calc_value from  #__virtuemart_calcs as e WHERE e.virtuemart_calc_id = p.product_tax_id),0)'
                .' as iva '
                .'FROM #__virtuemart_products AS c  LEFT JOIN #__virtuemart_products_es_es AS e ON c.virtuemart_product_id = e.virtuemart_product_id'
                .' LEFT JOIN #__virtuemart_product_prices AS p ON c.virtuemart_product_id = p.virtuemart_product_id'
                .' LIMIT '.$final.', '.$inicio;
                $db->setQuery($sql);
            
            $resultado['item']=$db->loadObjectList();
        }catch (Exception $e) {
            $resultado['sql']=$sql;
            $resultado['error']=$e->getMessage();
        }
         return $resultado;
     }
     
     public function datosFamilia($id){
        $resultado=array();
        $db = JFactory::getDbo();
        $query=$db->getQuery(true);
        try{
            $query='select  a.category_name as nombre, b.category_parent_id 
            as padre from #__virtuemart_category_categories as b 
            inner join #__virtuemart_categories_es_es as a where
             b.category_child_id='.$id.' and a.virtuemart_category_id='.$id;
             
            $db->setQuery($query);
            $resultado['item']=$db->loadAssoc();
        }catch (Exception $e) {
            $resultado['sql']=$query;
            $resultado['error']=$e->getMessage();
        }
         return $resultado;
     }

     public function todasFamilias(){
        $resultado=array();
        $db = JFactory::getDbo();
        $query=$db->getQuery(true);
        try{
            $query='SELECT a.virtuemart_category_id, a.category_name AS nombre, b.category_parent_id AS padre FROM #__virtuemart_category_categories AS b LEFT JOIN #__virtuemart_categories_es_es AS a ON b.category_child_id = a.virtuemart_category_id  ';
            $db->setQuery($query);
            $resultado['item']=$db->loadObjectList();
        }catch (Exception $e) {
            $resultado['sql']=$query;
            $resultado['error']=$e->getMessage();
        }
         return $resultado;
     }


    public function buscarImagenesParaRelacionar($productos){
        $db = JFactory::getDbo();
        $query=$db->getQuery(true);
        // Ahora consultamos si tiene imagenes los productos.
        foreach ($productos as $key=>$producto){
            
            try{
                $query='SELECT * FROM `#__virtuemart_product_medias` WHERE `virtuemart_product_id`='.$producto['idVirtuemart'];
                $db->setQuery($query);
                $r=$db->loadObjectList();
                if (count($r) == 0){
                    // Quiere decir que no tiene imagen
                    $idFamilia = str_pad($producto['IdFamilia'], 3, "0", STR_PAD_LEFT);
                    $url = 'images/virtuemart/product/'.$idFamilia.'/'.$producto['idArticulo'].'.jpg';
                    try{
                        $query='SELECT * FROM `#__virtuemart_medias` WHERE `file_url`="'.$url.'"';
                        $db->setQuery($query);
                        $m=$db->loadObjectList();
                        if ( count($m)== 1){
                            //Quiere decir que encontro una imagen.
                            $me = $m[0];
                            $query = 'INSERT INTO `#__virtuemart_product_medias`
                            ( `virtuemart_product_id`, `virtuemart_media_id`, `ordering`)
                            VALUES ('.$producto['idVirtuemart'].','.$me->virtuemart_media_id.',1) ';
                            $db->setQuery($query);
                            $db->execute();
                            $id = $db->insertid(); // Obtenemos el id del registro de la tabla que acabo de insertar.
                            $productos[$key]['imagenes_insert']=$id;
                        }
                    }catch (Exception $e) {
                            $productos[$key]['sql']=$query;
                            $productos[$key]['error']=$e->getMessage();
                    }
                }
            }catch (Exception $e) {
                $productos[$key]['sql']=$query;
                $productos[$key]['error']=$e->getMessage();
            }
        }
        return $productos;
    }




    public function anhadirCampoPersonalizadoPeso($idVirtuemart){
        // @ Objetivo
        // Añadimos los campos personalizados de 100grs, 200grs y 500grs  al idvirtuemar que nos indica, pero
        // solo si no tiene ningun campo personalizado peso ya creado para ese id.
        // NOTA:
        // Hay que tener en cuenta que id del campo personalizado ahora lo pongo por defecto el 3, pero esto
        // tendría que ser un parametro de configuracion ,sino no tiene sentido..
        $resultado = array();
        $db = JFactory::getDbo();
        // Ahora consultamos si tiene registro para ese campo personalido ese producto.
        $id_plugin = 3;

        // Select de buscar si hay registro para es producto.

        $query = 'SELECT * FROM `#__virtuemart_product_customfields` WHERE `virtuemart_product_id`="'.$idVirtuemart.'"  AND virtuemart_custom_id="'.$id_plugin.'"';
        $db->setQuery($query);
        $m=$db->loadObjectList();
        if ( count($m) > 0){
            // Quiere decir que si tiene registro.
            $resultado['accion']['tipo'] = 'KO';
            $resultado['accion']['mensaje'] = 'Ya tiene registros, no se hace nada..';
        } else {
            // No tiene campos personalizados para ese producto.
            // Monstamos insert para añadir.
            $resultado['accion']['tipo'] = 'OK';
            $resultado['accion']['mensaje'] = 'Se añaden campos personalizados para peso..';
            // Valores por peso
            $valores = array (
                            '0'=> array ( 'nombre' => '100 grs',
                                        'precio' => '-90'
                                ),
                            '1'=> array ( 'nombre' => '200 grs',
                                        'precio' => '-80'
                                ),
                            '2'=> array ( 'nombre' => '500 grs',
                                        'precio' => '-50'
                                )
                        );
            // Valores genericos
            $disabler           = 0;
            $override           = 0;
            $customfield_params = '';
            $published          = 0;
            $created_on         = '0000-00-00 00:00:00';
            $created_by         = 0;
            $modified_on        = date('Y-m-d H:i:s');
            $modified_by        = 198;
            $locked_on          = '0000-00-00 00:00:00';
            $locked_by          = 0;

            
            $campos = " `virtuemart_product_id`, `virtuemart_custom_id`, `customfield_value`, `customfield_price`, `disabler`, `override`, `customfield_params`,`published`, `created_on`, `created_by`, `modified_on`, `modified_by`, `locked_on`, `locked_by`, `ordering`";
            foreach ( $valores as $key=>$v){
                $query = 'INSERT INTO `#__virtuemart_product_customfields` ('.$campos.') VALUES ("'
                        .$idVirtuemart.'","'
                        .$id_plugin.'","'
                        .$v['nombre'].'","'
                        .$v['precio'].'","'
                        .$disabler.'","'
                        .$override.'","'
                        .$customfield_params.'","'
                        .$published.'","'
                        .$created_on.'","'
                        .$created_by.'","'
                        .$modified_on.'","'
                        .$modified_by.'","'
                        .$locked_on.'","'
                        .$locked_by.'","'
                        .$key.'")';
                $db->setQuery($query);
                $db->execute();
                $id = $db->insertid();
                $resultado['insert']['resultado'] = $id;
                $resultado['insert']['query'][] =$query;
            }
            
        }

        $resultado['Items'] = $m;
        $resultado['consulta'] = $query;

        
        return $resultado;
    }
     
}



?>
