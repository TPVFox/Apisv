<?php
class APISV_privacy {
    public function tieneConsentimiento($id){
        $db = JFactory::getDbo();
        $query=$db->getQuery(true);
        try{
                $select=' state,id';
                
                $query->select($select)
                    ->from('#__privacy_consents')
                    ->where('user_id ='.$id)
                    ->order('id DESC');
                $db->setQuery($query);
              
                $r=$db->loadAssoc();
                 if (isset($r['state'])){
                    if (trim($r['state']) === ''){
                        $resultado = '0';
                    } else {
                        $resultado =$r['state'];
                    }
                 } else {
                      $resultado = '0';
                }
            }catch (Exception $e) {
                $resultado['error']=$e->getMessage();
            }
            return $resultado;

    }

}
