<?php

header('content-type: application/json; charset=utf-8');
header("access-control-allow-origin: *");
define('JOOMLA_MINIMUM_PHP', '5.3.10');

$resultado ='No';
$respuesta = array();
if (version_compare(PHP_VERSION, JOOMLA_MINIMUM_PHP, '<'))
{
    die('Your host needs to use PHP ' . JOOMLA_MINIMUM_PHP . ' or higher to run this version of Joomla!');
}

/**
 * Constant that is checked in included files to prevent direct access.
 * define() is used in the installation folder rather than "const" to not error for PHP 5.2 and lower
 */
define('_JEXEC', 1);
define('JPATH_BASE','./../../');// Ya sabemos ruta por lo que vamos raiz administrador 
if (!defined('_JDEFINES'))
{
    require_once JPATH_BASE . '/includes/defines.php';
}


require_once JPATH_BASE .'/includes/framework.php';

// require_once JPATH_BASE . '/includes/helper.php';
// require_once JPATH_BASE . '/includes/toolbar.php';
$Configuracion =  JFactory::getConfig();

include_once ('./Clase_virtuemart_productos.php');

// Inicializamos framework
$plugin = JPluginHelper::getPlugin('system', 'apisv'); 
// Obtenemos:
// Si es correcto un objecto.
// Si es incorrecto un  array.

$pluginParams = new JRegistry();
$pluginParams->loadString($plugin->params);
$clave = $pluginParams->get('clave_apisv');
$ClaseProductosVirtual=new APISV_virtuemart_productos();
//~ echo '<pre>';
//~ print_r($ClaseProductosVirtual->ObtenerIvasweb());
//~ echo '</pre>';
//~ echo $ClaseProductosVirtual;

//~ echo $ClaseProductosVirtual;
$method = $_SERVER['REQUEST_METHOD'];
 
// tendremos que tratar esta variable para obtener el recurso adecuado de nuestro modelo.
$resource = $_SERVER['REQUEST_URI'];

// Dependiendo del método de la petición ejecutaremos la acción correspondiente.
if (gettype($plugin) === 'object') {
    switch ($method) {
        case 'GET':
            // código para método GET
            break;
        case 'POST':
            $arguments = $_POST;
            if ($_POST['key'] === $clave){
                if ($_POST['action'] === 'ObtenerProducto'){
                   
                    $resultado=array();
                    $id_virtuemart = $_POST['id_virtuemart']; 
                    
                    if($id_virtuemart>0){
                        $resultado['datosProducto'] =$ClaseProductosVirtual->ObtenerDatosDeProducto($id_virtuemart);
                    }
                    $resultado['ivasWeb']=$ClaseProductosVirtual->ObtenerIvasweb();
                }
                if($_POST['action']==='ModificarProducto'){
                    $datos=json_decode($_POST['datos'], true);
                    $resultado=$ClaseProductosVirtual->ModificarProducto($datos);
                }
                if($_POST['action']==='AddProducto'){
                    $resultado=array();
                    $datos=json_decode($_POST['datos'], true);
                    $resultado=$ClaseProductosVirtual->AddProducto($datos);
                    
                }
                if($_POST['action']==='todasFamilias'){
                    $resultado=array();
                    $resultado= $ClaseProductosVirtual->todasFamilias();
                }

                
                if($_POST['action']==='modificarFamilia'){
                    $resultado=array();
                     $datos=json_decode($_POST['datos'], true);
                    $resultado=$ClaseProductosVirtual->modificarFamilia($datos);
                    
                }
                if($_POST['action']==='datosFamilia'){
                     $resultado=array();
                     $resultado=$ClaseProductosVirtual->datosFamilia($_POST['idWeb']);
                }
                if($_POST['action']==='AddFamilia'){
                    $resultado=array();
                    $datos=json_decode($_POST['datos'], true);
                    $resultado=$ClaseProductosVirtual->AddFamilia($datos);
                }
                if($_POST['action']==='ObtenerNotificacionesProducto'){
                    $id_virtuemart=$_POST['idProducto'];
                    $resultado=$ClaseProductosVirtual->ObtenerNotificacionesProducto($id_virtuemart);
                }
                if($_POST['action']==='modificarNotificacion'){
                    $idProducto=$_POST['idProducto'];
                    $email=$_POST['email'];
                    $resultado=$ClaseProductosVirtual->modificarNotificacion($idProducto, $email);
                }
                if($_POST['action']==='contarProductos'){
                    $resultado=$ClaseProductosVirtual->contarProductos();
                }
                 if($_POST['action']==='productosInicioFinal'){
                     $inicio=$_POST['inicio'];
                     $final=$_POST['final'];
                    $resultado=$ClaseProductosVirtual->DatosProductos($inicio, $final);
                }
                if($_POST['action']==='descontarStock'){
                    // No se porque pero trae ' a mayores al final y al principio
                    $productos = trim($_POST['productos'],"'"); 
                    $p = json_decode($productos,true);
                    $resultado=$ClaseProductosVirtual->descontarStock($p);
                    if (isset($resultado['error']) && count($resultado['error'])> 0){
                        error_log('Error en servidor apiSv:'.json_encode($resultado));
                    }
                }
                if($_POST['action']==='cambiarStockYPrecios'){
                    // Objetivo cambiar el stock y precios de todos los productos.
                    $resultado=array();
                    $datos=json_decode($_POST['datos'], true);
                    $resultado=$ClaseProductosVirtual->CambiarStockYPrecios($datos);

                }
                
                if($_POST['action']==='enviarCorreo'){
                   $datos=json_decode($_POST['datos']);
                   $resultado=array();
                   $resultado['datos']=$datos;
                   $mailer = JFactory::getMailer();
                   $mailfrom = $Configuracion->get('mailfrom');
                   $fromname = $Configuracion->get('fromname');
                   $sitename = $Configuracion->get('sitename');
                   $mailer->setSender(array($mailfrom, $fromname));
                   $mailer->setSubject($datos->asunto);
                   $mailer->setBody($datos->mensaje);
                   $mailer->IsHTML(true);

                   // Add recipients
                   $mailer->addRecipient($datos->email);

                   // Send the Mail
                   $rs  = $mailer->Send();

                   if ( JError::isError($rs) ) {
                       $msg = $rs->getError();
                       
                   } else {
                       $msg = "Mensaje enviado correctamente.";
                    
                   }
                   $resultado['mensaje']=$msg;
                   $resultado['mailer']=$rs;
                   $resultado['datos']=$datos;
                }

                if($_POST['action']==='buscarImagenesParaRelacionar'){
                    // @ Objetivo
                    // Buscar si el producto tiene imagenes asignadas, si no tiene , buscar si hay ya subidas
                    // pero sin relacionar.
                    
                     $resultado=array();
                     $productos = json_decode($_POST['datos'],true);
                     $resultado = $ClaseProductosVirtual->buscarImagenesParaRelacionar($productos);
                }

                if($_POST['action']==='AnadirCamposPersonalizado'){
                    // @ Objetivo
                    // Añadimos los campos personalizados de 100grs, 200grs y 500grs  al idvirtuemar que nos indica, pero
                    // solo si no tiene ningun campo personalizado peso ya creado para ese id.
                    // NOTA:
                    // Hay que tener en cuenta que id del campo personalizado ahora lo pongo por defecto el 3, pero esto
                    // tendría que ser un parametro de configuracion ,sino no tiene sentido..
                    
                    $resultado = array();
                    $idVirtuemart  = $_POST['idVirtuemart'];
                    $resultado = $ClaseProductosVirtual->anhadirCampoPersonalizadoPeso($idVirtuemart);

                    
                }
                
            } else {
                // Quiere decir que la clave es incorrecta.
                $respuesta['error'] = 'La clave es incorrecta revisa el plugin en Joomla';

            }
            // código para método POST
            break;
        case 'PUT':
            parse_str(file_get_contents('php://input'), $arguments);
            // código para método PUT
            break;

    }
$respuesta['post'] = $_POST;
$respuesta['QuienDevuelve']= $resource;
$respuesta['Datos'] = $resultado;
$respuesta['metodo_utilizado']=$method;
} else {
    // Quiere decir que no hay plugin... 
    $respuesta['error'] = ' No existe plugin en servidor';
}
echo json_encode($respuesta,true); // $response será un array con los datos de nuestra respuesta.
?>

