<?php
/**
 * Nota: usar Cylix_App::setCSS($chave, $url) e Cylix_App::setJS($chave, $url)
 */
class Cylix_Form{

    const METHOD_POST = 'post';
    const METHOD_GET = 'get';
    const ENCTYPE_URLENCODED = 'application/x-www-form-urlencoded';
    const ENCTYPE_MULTIPART = 'multipart/form-data';
    const CLASS_REQUIRED = 'required';
    const CKEDITOR_BASICO = 'basico';
    const CKEDITOR_USUARIO = 'normal';
    const CKEDITOR_ADMIN = 'root';
    const DATEPICKER_DATETIME = 'datetime';
    const DATEPICKER_DATE = 'date';
    const DATEPICKER_TIME = 'time';
    const COLORPICKER_TEXT = 'color-text';
    const COLORPICKER_SELECT = 'color-select';

    protected $_acao;
    protected $_metodo;
    protected $_id;
    protected $_enctype;
    protected $_outros;
    protected $_inputs;
    protected $_regras;
    protected $_scripts;
    protected $_alertas;
    protected $_ajustes;//caso keira incluir no final do form coisas tipo jQuery('#id').css('display','none')
    protected $_validar;//se utiliza ou nÃ£o o jquery validate
    protected $_decoration;
    protected $_valid_class;
    protected $_error_class;
    protected $_error_elem;
    
    public static $file_max_size = 5242880;//5MB
    public static $captchaFilePath = "uploads/config";
    public  static $input_default_class='form-control';

    protected function _transfId($name,&$outros=array()){
        if(isset($outros['id'])){
            $id = trim($outros['id']);
            unset($outros['id']);//elimina redundancia
        }else{
            $id = str_replace(array('[',']'), array('_',''), $name);
        }
        return $id;
    }
    
    /**
     * Informa dados iniciais pra fazer o form
     * @param string $acao
     * @param string $metodo
     * @param string $enctype
     * @param array $outros
     */
    public function  __construct($acao='',$metodo='post',$id='form',$enctype='',$outros=array()){
        $this->_acao = $acao;
        $this->_metodo = $metodo;
        $this->_enctype = $enctype;
        $this->_scripts = array();
        $this->_id = $id;unset($outros['id']);
        
        $lib = 'lib/jquery/jquery.validate.js';
        if(file_exists(INDEX_PATH.$lib)){
            $this->_validar = true;
            Cylix_App::setJS('validator', getBaseUrl($lib));
        }else{
            $this->_validar = false;
        }

        foreach($outros as $k => $v){
            $this->_outros .= " $k=\"$v\"";
        }
        $this->_inputs = $this->_regras = $this->_ajustes = $this->_regras = array();
        
        $this->_valid_class = "has-success";
        $this->_error_class = "has-error";
        $this->_error_elem = "div";
    }

    public function   __call($name, $arguments) {
        if(method_exists(self,$name)){
            $this->__call($name, $arguments);
        }else{
            throw new Cylix_Exception("Element type of $name not declared", "Cylix_Form error");
        }
        
    }
    /**
     * classe ao lanÃ§ar validaÃ§Ã£o true
     * @param string $v (se nao passar eh get e se passar eh set
     * @return string 
     */
    function validClass($v=null){
        $this->_valid_class = (is_null($v)) ? $this->_valid_class : $v;
        return $this->_valid_class;
    }
    /**
     * classe ao lanÃ§ar validaÃ§Ã£o false
     * @param string $v (se nao passar eh get e se passar eh set
     * @return string 
     */
    function errorClass($v=null){
        $this->_error_class = (is_null($v)) ? $this->_error_class : $v;
        return $this->_error_class;
    }
    /**
     * ao lanÃ§ar validaÃ§Ã£o appenda este elemento abaixo do input
     * @param string $v (se nao passar eh get e se passar eh set
     * @return string (padrÃ£o Ã© DIV)
     */
    function errorElement($v=null){
        $this->_error_elem = (is_null($v)) ? $this->_error_elem : $v;
        return $this->_error_elem;
    }
    /**
     * auxiliar de criaÃ§Ã£o de labels
     * @param string $label
     * @param mixed $prepend_name colocar este label antes de um elemento(false: retorna o html, string: Ã© o name que receberÃ¡ o label)
     * @param $title $title inclusao de title para ajudar em explicaÃ§Ãµes do input
     * @param string $class input-label Ã© o padrÃ£o
     * @param string $for id de um elemento form
     * @param string $tag tag que irÃ¡ envolver (div padrao)
     * @return string 
     */
    public function label($label,$prepend_name=false,$title='',$class='control-label',$for='',$tag='div'){
        $title = (strlen($title) > 1) ? ' title="'.  str_replace("\n", "", $title).'"': '';
        ob_start();
        ?>
<<?php echo $tag?> class="<?php echo $class; echo (strlen($for)>0) ? ' label-'.$for : '';?>"<?php echo (strlen($for) > 0) ? ' for="'.$for.'"' : '';echo $title;?>><?php echo $label;?></<?php echo $tag?>>
        <?php
        if($prepend_name != false && is_string($prepend_name)){
            $this->_inputs[$prepend_name] = ob_get_clean().$this->_inputs[$prepend_name];
        }else{
            return ob_get_clean();
        }
        
    }

    ##################################
    ####        string
    ##################################
    /**
     * contrutor de input[type=text]
     * @param string $name
     * @param string $valor
     * @param boolean $requerido
     * @param int $min minimo de caracteres
     * @param int $max maximo de caracteres
     * @param array $outros outros elementos inline pro input
     * @param string $msg mensagem para campo obrigatório
     * @param string $outros_json_validator outros parametros para jquery validate (obs.: colocar uma vÃ­rgula no final)
     * @return string name
     */
    public function string($name,$valor='',$requerido=true,$min=0,$max=255,$outros=null,$msg='Campo obrigatório',$outros_json_validator=''){
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $id = $this->_transfId($name,$outros);
        $req = $requerido ? 'true' : 'false';
        $this->_regras[$name] = "
            '$name': {
                required: $req,$outros_json_validator
                minlength: $min
            }
        ";
        $outros_opt = '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        $req_msg = str_replace('%i', $min, Cylix_I18n::t("Precisa ter mais de %i caracteres"));
        $msg = Cylix_I18n::t($msg);
        $this->_alertas[$name] = "
            '$name': {required:'$msg',minlength:'$req_msg'}
        ";
        ob_start();
        ?>
<input type="text" name="<?php echo $name;?>" id="<?php echo $id;?>" value="<?php echo $valor;?>" maxlength="<?php echo $max;?>" class="<?php echo self::$input_default_class;?> input-string<?php echo $classe_ini?>"<?php echo $outros_opt;?> />
        <?php
        $this->_inputs[$name] = ob_get_clean();
        return $name;
    }

    ##################################
    ####        int - somente numeros
    ##################################
    /**
     * monta input text aceitando somente inteiros
     * @param string $name
     * @param string $valor valor default
     * @param boolean $requerido
     * @param int $max maximo de digitos
     * @param array $outros outros elementos inline pro input
     * @param string $msg mensagem de campo requerido
     * @return string name
     */
    public function int($name,$valor='',$requerido=true,$min=0,$max=255,$outros=null,$msg='Campo obrigatório'){
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $req = $requerido ? 'true' : 'false';
        $id = $this->_transfId($name,$outros);
        $dig_msg = Cylix_I18n::t("Somente valor inteiro");
        $msg = Cylix_I18n::t($msg);
        $req_msg = str_replace('%i', $min, Cylix_I18n::t("Precisa ter mais de %i caracteres"));
        $this->_regras[$name] = "
            '$name': {
                required: $req,
                minlength: $min,
                digits: true
            }
        ";
        $this->_alertas[$name] = "
            '$name': {required: '$msg', digits: '$dig_msg',minlength:'$req_msg'}
        ";
        $outros_opt = '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        ob_start();
        ?>
    <input type="text" name="<?php echo $name;?>" id="<?php echo $id;?>" value="<?php echo $valor;?>" maxlength="<?php echo $max;?>" class="<?php echo self::$input_default_class;?> input-string input-int<?php echo $classe_ini?>"<?php echo $outros_opt;?> />
        <?php
        $this->_inputs[$name] = ob_get_clean();
        return $name;
    }

    ##################################
    ####        text - textarea
    ##################################
    /**
     * textarea, com ou sem contador de caracteres
     * @param string $name
     * @param string $valor
     * @param boolean $requerido
     * @param int $min
     * @param int $limite limite para o contador
     * @param boolean $contador se vai ter contador
     * @param string $msg outros parametros para jquery validate
     * @return string name
     */
    public function text($name,$valor='',$requerido=true,$min=0,$limite=null,$contador=false,$outros=null,$msg='Campo obrigatório'){
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $id = $this->_transfId($name,$outros);
        $msg = Cylix_I18n::t($msg);
        if($limite > 0){
            $lib = 'lib/jquery/jquery.limit.js';
            if(file_exists(INDEX_PATH.$lib)){
                $_limitar = true;
                Cylix_App::setJS('limit', getBaseUrl($lib));
            }else{
                $_limitar = false;
            }
        }
		$outros_opt = '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        $req = $requerido ? 'true' : 'false';
        
        $this->_regras[$name] = "
            '$name': {
                required: $req,
                minlength: $min
           }
        ";
        $req_msg = str_replace('%i', $min, Cylix_I18n::t("Precisa ter mais de %i caracteres"));
        $char_label = Cylix_I18n::t('Caracteres restantes: %i');
        $char_label = str_replace('%i', '<span class="contador"></span>',$char_label);
        $this->_alertas[$name] = "
            '$name': {
                required:'$msg',
                minlength:'$req_msg'
            }
        ";
        ob_start();
        ?>
    <textarea name="<?php echo $name;?>" id="<?php echo $id;?>" rows="3" cols="40" class="<?php echo self::$input_default_class;?> input-text<?php echo $classe_ini?>"<?php echo $outros_opt;?>><?php echo $valor;?></textarea>
    <?php
    if($contador && $_limitar){
        $this->_scripts[] = 'jQuery("#'.$id.'").limit('.$limite.',"#jlimit-'.$id.' .contador");';
    }elseif($limite > 0 && $_limitar){
        $this->_scripts[] = 'jQuery("#'.$id.'").limit('.$limite.');';
    }
    ?>
    <?php if($contador && $_limitar): ?>
    <div id="jlimit-<?php echo $id;?>"><?php echo $char_label;?></div>
    <?php endif;?>

        <?php
        $this->_inputs[$name] = ob_get_clean();
        return $name;
    }

    ##################################
    ####        password
    ##################################
    /**
     * campopassword
     * @param string $name
     * @param boolean $requerido
     * @param int $min
     * @param int $max
     * @param array $outros outros elementos inline pro input
     * @param string $msg mensagem para campo obrigatório
     * @return string name
     */
    public function password($name,$requerido=true,$min=0,$max=255,$outros=null,$msg='Campo obrigatório'){
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $id = $this->_transfId($name,$outros);
        $msg = Cylix_I18n::t($msg);
        $req = $requerido ? 'true' : 'false';
        $this->_regras[$name] = "
            '$name': {
                required: $req,
                minlength: $min
            }
        ";
        $req_msg = str_replace('%i', $min, Cylix_I18n::t("Precisa ter mais de %i caracteres"));
        $this->_alertas[$name] = "
            '$name': {required:'$msg',minlength:'$req_msg'}
        ";
        $outros_opt = '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        ob_start();
        ?>
    <input type="password" name="<?php echo $name;?>" id="<?php echo $id;?>" maxlength="<?php echo $max;?>" class="<?php echo self::$input_default_class;?> input-password<?php echo $classe_ini?>"<?php echo $outros_opt;?> />

        <?php
        $this->_inputs[$name] = ob_get_clean();
        return $name;
    }

    ##################################
    ####        email
    ##################################
    /**
     * campo input-text com validador de email
     * @param string $name
     * @param string $valor
     * @param boolean $requerido
     * @param array $outros outros elementos inline pro input
     * @param string $msg mensagem para campo obrigatório
     * @return string name
     */
    public function email($name,$valor='',$requerido=true,$outros=null,$msg='Indique um e-mail vÃ¡lido'){
        $id = $this->_transfId($name,$outros);
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $req = $requerido ? 'true' : 'false';
        $msg = Cylix_I18n::t($msg);
        $this->_regras[$name] = "
            '$name': {
                required: $req,
                email: true
            }
        ";
        $this->_alertas[$name] = "
            '$name': '$msg'
        ";
        $outros_opt = '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        ob_start();
        ?>
    <input type="text" name="<?php echo $name;?>" id="<?php echo $id;?>" value="<?php echo $valor;?>" class="<?php echo self::$input_default_class;?> input-string input-email<?php echo $classe_ini?>"<?php echo $outros_opt;?> />
        <?php
        $this->_inputs[$name] = ob_get_clean();
        return $name;
    }

    ##################################
    ####        mask - campo com mascara
    ##################################
    /**
     * campo input-text com mÃ¡scara generica<br/>
     *  a - Represents an alpha character (A-Z,a-z)<br/>
     *  9 - Represents a numeric character (0-9)<br/>
     *  * - Represents an alphanumeric character (A-Z,a-z,0-9)
     * @param string $name
     * @param mixed $valor
     * @param string $mascara
     * @param boolean $requerido
     * @param array $outros outros elementos inline pro input
     * @param string $ph placeholder do jquery mask
     * @param string $msg mensagem para campo obrigatório
     * @return string name
     * @example
     * (999) 999-9999 = (031) 123-1234<br>
     * a*-999-a999 = P3-123-Y789
     */
    public function mask($name,$valor='',$mascara="",$requerido=true,$outros=null,$ph=' ',$msg='Campo obrigatório'){
        $id = $this->_transfId($name,$outros);
        $lib = 'lib/jquery/jquery.maskedinput.js';
        $msg = Cylix_I18n::t($msg);
        if(is_file(INDEX_PATH.$lib)){ //lib
        Cylix_App::setJS('maskedinput', getUrl($lib));
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $req = $requerido ? 'true' : 'false';
        $min = strlen($mascara);
        $this->_regras[$name] = "
            '$name': {
                required: $req
            }
        ";
        $outros_opt = '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        $this->_alertas[$name] = "
            '$name': {required:'$msg'}
        ";
        ob_start();
        ?>
    <input type="text" name="<?php echo $name;?>" id="<?php echo $id;?>" value="<?php echo $valor;?>" maxlength="<?php echo $max;?>" class="<?php echo self::$input_default_class;?> input-string input-mask<?php echo $classe_ini?>"<?php echo $outros_opt;?> />

        <?php
        $this->_scripts[] = 'jQuery("#'.$id.'").mask("'.$mascara.'",{placeholder:"'.$ph.'"});';
        $this->_inputs[$name] = ob_get_clean();
        }//lib
        return $name;
    }

    ##################################
    ####        money - formataÃ§Ã£o para moedas
    ##################################
    /**
     * input com mascara para dinheiro
     * @param string $name
     * @param string $valor
     * @param boolean $requerido
     * @param string $prefixo inÃ­cio da mÃ¡scara (R$ por padrÃ£o)
     * @param string $msg mensagem para campo obrigatório
     * @param string $cents separador de centavos
     * @param string $mil  separador de milhar
     * @return string name
     */
    public function money($name,$valor='',$requerido=true,$prefixo="R$",$msg='Campo obrigatório',$cents=',',$mil='.'){
        $id = $this->_transfId($name,$outros);
        $lib = 'lib/jquery/jquery.price_format.js';
        $msg = Cylix_I18n::t($msg);
        if(file_exists(INDEX_PATH.$lib)){//lib
        Cylix_App::setJS('price_format', getUrl($lib));
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $req = $requerido ? 'true' : 'false';
        $this->_regras[$name] = "
            '$name': {
                required: $req
            }
        ";
        $this->_alertas[$name] = "
            '$name': {required:'$msg'}
        ";
		if(is_numeric($valor)){
			$valor = number_format($valor, 2, '.', '');
		}
        ob_start();
        ?>
    <input type="text" name="<?php echo $name;?>" id="<?php echo $id;?>" value="<?php echo $valor;?>" class="<?php echo self::$input_default_class;?> input-string input-money<?php echo $classe_ini?>" />

        <?php
        $this->_scripts[] = 'jQuery("#'.$id.'").priceFormat({prefix: "'.$prefixo.' ",centsSeparator: "'.$cents.'",thousandsSeparator: "'.$mil.'"});';
        if(!$requerido){
            $this->_scripts[] = 'jQuery("#'.$id.'").blur(function(){if(jQuery(this).val()=="'.$prefixo.' 0'.$cents.'00"){jQuery(this).val("")}});';
        }
        $this->_inputs[$name] = ob_get_clean();
        }//lib
        return $name;
    }
    
    ##################################
    ####        float - formataÃ§Ã£o para decimais
    ##################################
    /**
     * input com mascara para decimais
     * @param string $name
     * @param string $valor
     * @param boolean $requerido
     * @param string $casas numero de casas (2 padrao)
     * @param string $msg mensagem para campo obrigatório
     * @param string $dec separador de decimal
     * @param string $mil  separador de milhar
     * @return string name
     */
    public function float($name,$valor='',$requerido=true,$casas=2,$msg='Campo obrigatório',$dec=',',$mil=''){
        $id = $this->_transfId($name,$outros);
        $lib = 'lib/jquery/jquery.price_format.js';
        $msg = Cylix_I18n::t($msg);
        if(file_exists(INDEX_PATH.$lib)){//lib
        Cylix_App::setJS('price_format', getUrl($lib));
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $req = $requerido ? 'true' : 'false';
        $this->_regras[$name] = "
            '$name': {
                required: $req
            }
        ";
        $this->_alertas[$name] = "
            '$name': {required:'$msg'}
        ";
		if(is_numeric($valor)){
			$valor = number_format($valor, $casas, $dec, $mil);
		}
        ob_start();
        ?>
    <input type="text" name="<?php echo $name;?>" id="<?php echo $id;?>" value="<?php echo $valor;?>" class="<?php echo self::$input_default_class;?> input-string input-float<?php echo $classe_ini?>" />

        <?php
        $this->_scripts[] = 'jQuery("#'.$id.'").priceFormat({centsLimit: '.$casas.',prefix: "",centsSeparator: "'.$dec.'",thousandsSeparator: "'.$mil.'"});';
        $this->_inputs[$name] = ob_get_clean();
        }//lib
        return $name;
    }

    ##################################
    ####        datetimepicker geral
    ##################################
    /**
     * datetimepicker geral
     */
    public function datetimepicker($name,$valor='',$requerido=true,$outros=null,$tipo=self::DATEPICKER_DATETIME,$min=10,$max=16,$msg='Campo obrigatório',$step_min=5,$step_h=1,$outros_param_timepicker='',$validate_min='Favor colocar no formato dd/mm/aaaa',$tem_seg='false'){
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $msg = Cylix_I18n::t($msg);
        $id = $this->_transfId($name,$outros);
        $req = $requerido ? 'true' : 'false';
        $this->_regras[$name] = "
            '$name': {
                required: $req,
                minlength: $min
            }
        ";
        $outros_opt = '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        $this->_alertas[$name] = "
            '$name': {required:'$msg',minlength:'$validate_min'}
        ";
        //jQuery U.I.
        Cylix_App::setCSS('jquery-ui', getUrl('lib/jquery-ui/jquery-ui.min.css'));
        Cylix_App::setJS('jquery-ui', getUrl('lib/jquery-ui/jquery-ui.min.js'));
        Cylix_App::setJS('timepicker', getUrl('lib/timepicker/jquery-ui-timepicker-addon.js'));
        Cylix_App::setJS('timepicker_br', getUrl('lib/timepicker/ui.datepicker-br.js'));
        $has_but = (file_exists('themes/'.FLOW.'/img/calendar_clear.png')) ? true : false;
        ob_start();
        ?>
    <input type="text" name="<?php echo $name;?>" id="<?php echo $id;?>" value="<?php echo $valor;?>" maxlength="<?php echo $max;?>" class="<?php echo self::$input_default_class;?> input-<?php echo $tipo;?><?php echo $classe_ini?>"<?php echo $outros_opt;?> /><?php if($has_but): ?>
    <button type="button" id="button_datepicker_<?php echo $id;?>" class="button-datepicker-clear" onclick="jQuery('#<?php echo $id;?>').val('')" title="Apagar campo"><img src="<?php echo getSkinUrl('img/calendar_clear.png')?>" alt="x" /></button><?php endif; ?>
 
    <?php
    if($tipo==Cylix_Form::DATEPICKER_DATE){
        $this->_scripts[] = 'jQuery("#'.$id.'").datepicker();';
    }elseif($tipo==Cylix_Form::DATEPICKER_TIME){
        $this->_scripts[] = 'jQuery("#'.$id.'").datetimepicker({
    showSecond: false,
    timeFormat: "hh:mm",
    stepHour: '.$step_h.',
    stepMinute: '.$step_min.',
    minuteGrid: 10'.$outros_param_timepicker.'
});';
    }else{
        $this->_scripts[] = 'jQuery("#'.$id.'").datetimepicker({
    showSecond: false,
    timeFormat: "hh:mm",
    stepHour: '.$step_h.',
    stepMinute: '.$step_min.',
    minuteGrid: 10'.$outros_param_timepicker.'
});';
    }
    $this->_scripts[] = 'jQuery(\'#'.$id.'\').focus(function(){
    mudarBRDTP();
    jQuery("#ui-datepicker-div").click(function(){
        mudarBRDTP();
    });
});
        ';
        $this->_inputs[$name] = ob_get_clean();
    }

    ##################################
    ####        datetime 00/00/0000 00:00
    ##################################
    /**
     * datetime 00/00/0000 00:00
     * @param string $name
     * @param string $valor
     * @param boolean $requerido
     * @param array $outros outros elementos inline pro input
     * @param string $msg mensagem para campo obrigatório
     * @param int $step_min saltos para minutos (de 5 em 5 por padrÃ£o)
     * @param int $step_h saltos para horas (1 em 1 hora por padrÃ£o)
     * @param string $outros_param_timepicker outros parametros para o datepicker
     * @return string name
     */
    public function datetime($name,$valor='',$requerido=true,$outros=null,$msg='Campo obrigatório',$step_min=5,$step_h=1,$outros_param_timepicker=''){
        $this->datetimepicker($name, $valor, $requerido, $outros, self::DATEPICKER_DATETIME, $min=10,$max=16, $msg, $step_min, $step_h,$outros_param_timepicker,'Favor colocar data e hora','false');
        return $name;
    }
    ##################################
    ####        date 00/00/0000
    ##################################
    /**
     * calendario date 00/00/0000
     * @param string $name
     * @param string $valor
     * @param boolean $requerido
     * @param boolean $converter converter o valor ISO para ptBR
     * @param array $outros outros elementos inline pro input
     * @param string $msg mensagem para campo obrigatório
     * @param string $outros_param_timepicker outros parametros para o datepicker
     * @return string name
     */
    public function date($name,$valor='',$requerido=true,$converter=true,$outros=null,$msg='Campo obrigatório',$outros_param_timepicker=''){
        if($converter && strlen($valor) > 8){
            $valor = explode(' ',$valor);
            $horas = (isset($valor[1])) ? ' '.$valor[1] : '';
            $valor = $valor[0];
            if(strpos($valor, '-') > 1){
                $valor = explode('-', $valor);
                $valor = $valor[2].'/'.$valor[1].'/'.$valor[0].$horas;
                $valor = ($valor == '00/00/0000') ? '' : $valor;
            }
        }
        $this->datetimepicker($name, $valor, $requerido, $outros, self::DATEPICKER_DATE, $min=10,$max=10, $msg, null, null,$outros_param_timepicker,'Favor colocar no formato dd/mm/aaaa','false');
        return $name;
    }
    ##################################
    ####        time 00:00:00
    ##################################
    /**
     * horÃ¡rio 00:00:00
     * @param string $name
     * @param string $valor
     * @param boolean $requerido
     * @param array $outros outros elementos inline pro input
     * @param string $msg mensagem para campo obrigatório
     * @param int $step_min saltos para minutos (de 5 em 5 por padrÃ£o)
     * @param int $step_h saltos para horas (1 em 1 hora por padrÃ£o)
     * @param string $outros_param_timepicker outros parametros para o datepicker
     * @return string name
     */
    public function time($name,$valor='',$requerido=true,$outros=null,$msg='Campo obrigatório',$step_min=1,$step_h=1,$outros_param_timepicker=''){
        $this->datetimepicker($name, $valor, $requerido, $outros, self::DATEPICKER_TIME, $min=5,$max=8, $msg, $step_min, $step_h,$outros_param_timepicker,'Favor colocar o horï¿½rio corretamente','true');
        return $name;
    }

    ##################################
    ####        file
    ##################################
    /**
     * input de arquivo geral com validaÃ§Ã£o de formatos
     * @param string $name
     * @param boolean $requerido
     * @param string $ext extensÃµes permitidas (ex: 'pdf|doc|docx')
     * @param string $msg mensagem para campo obrigatório
     * @param string $valor caminho da imagem ou arquivo
     * @param string $tipo flag de tipo (file por padrÃ£o ou image)
     * @return string name
     */
    public function file($name,$requerido=true,$ext=null,$msg='Campo obrigatório',$valor=null,$tipo='file'){
        $id = $this->_transfId($name,$outros);
        $msg = Cylix_I18n::t($msg);
        if(is_array($valor)){
            //possivelmente Ã© um FILE
            $valor = '';
        }
        if(strlen($valor)>3){
            $requerido=false;
        }
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $req = $requerido ? 'true' : 'false';
        $aceitar = $ext ? ",accept: '$ext'" : '';
        $limite = (self::$file_max_size) ? self::$file_max_size : 1048576;//1MB
        $tam_mb = self::convertBytes($limite);//tamanho em MB
        $this->_regras[$name] = "
            '$name': {
                required: $req $aceitar, filesize: $limite
            }
        ";
        $ext = str_replace('|', ', ', $ext);
        $req_msg = str_replace('%s', $ext, Cylix_I18n::t("Permitido somente %s"));
        $label_remove = Cylix_I18n::t("Remover arquivo atual");
        $this->_alertas[$name] = "
            '$name': {required:'$msg',accept:'$req_msg',filesize:'Tamanho limite: $tam_mb'}
        ";
        $img = INDEX_PATH.$valor;
        $time = (file_exists($img)) ? filemtime($img) : date('YmdH');
        ob_start();
        ?>
    <?php if($tipo == 'image' && $valor): /*caso seja um tipo image*/ ?>
    <img src="<?php echo ABSOLUTE_URL.$valor.'?'.$time;?>" alt="" class="input-image-mini" />
    <?php elseif($tipo == 'upload' && $valor): ?>
    <a target="_blank" href="<?php echo (strlen($valor)) ? getUrl($valor) : '';?>" class="input-upload-link"><?php echo (strlen($valor)) ? getUrl($valor) : '';?></a>
    <?php endif;?>
    <input type="file" name="<?php echo $name;?>" id="<?php echo $id;?>" class="input-<?php echo $tipo.$classe_ini?>" />
    <?php if($tipo == 'image' || $tipo == 'upload'): /*caso seja um tipo image ou upload*/ ?>
    <input type="hidden" value="0" name="<?php echo $id;?>_remove" />
    <input type="checkbox" value="1" name="<?php echo $id;?>_remove"/><?php echo $label_remove;?>
    <?php endif;?>
        <?php
        $this->_inputs[$name] = ob_get_clean();
        return $name;
    }

    ##################################
    ####        imge = file + img
    ##################################
    /**
     * input de arquivo de imagem com validaÃ§Ã£o de formatos
     * @param string $name
     * @param string $valor caminho default da imagem (a partir de www/)
     * @param boolean $requerido
     * @param string $ext extensÃµes permitidas (ex: 'jpg|gif|png')
     * @param string $msg mensagem para campo obrigatório
     * @return string name
     */
    public function image($name,$valor=null,$requerido=true,$ext='jpeg|jpg|gif|png',$msg='Campo obrigatório'){
        return $this->file($name, $requerido, $ext, $msg, $valor,'image');
    }

    ##################################
    ####        upload = file + link download
    ##################################
    /**
     * input de arquivo de upload com validaÃ§Ã£o de formatos
     * @param string $name
     * @param string $valor caminho default da imagem (a partir de www/)
     * @param boolean $requerido
     * @param string $ext extensÃµes permitidas (ex: 'jpg|gif|png')
     * @param string $msg mensagem para campo obrigatório
     * @return string name
     */
    public function upload($name,$valor=null,$requerido=true,$ext='pdf|doc|docx|rtf|txt',$msg='Campo obrigatório'){
        return $this->file($name, $requerido, $ext, $msg, $valor,'upload');
    }

    ##################################
    ####        radio - sempre como grupo
    ##################################
    /**
     * Radio button ou grupo de radios
     * @param string $name do campo ou grupo
     * @param mixed $valor valor setado
     * @param array $option array com valor=>label
     * @param boolean $requerido obrigatorio
     * @param string $outros atrib=valor
     * @param string $msg mensagem para campo obrigatório
     * @return string name
     */
    public function radio($name,$valor='',$option=array(),$requerido=true,$outros=null,$msg='Campo obrigatório'){
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $id = $this->_transfId($name,$outros);
        $msg = Cylix_I18n::t($msg);
        $outros_opt = '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        $req = $requerido ? 'true' : 'false';
        $this->_regras[$name] = "
            '$name': {
                required: $req
            }
        ";
        $this->_alertas[$name] = "
            '$name': {required:'$msg'}
        ";
        ob_start();
        ?>
    <?php foreach($option as $k=>$v): ?>
    <label for="<?php echo $id."-$k";?>">
    <input type="radio" name="<?php echo $name;?>" id="<?php echo $id."-$k";?>" value="<?php echo $k;?>"<?php echo ((string)$k==(string)$valor)?' checked="checked"':''?> class="input-radio<?php echo $classe_ini?>"<?php echo $outros_opt;?> />
    <?php echo $v;?>
    </label>
    <?php endforeach; 
    if($requerido): /*devido a um bug que nao consegui arrumar*/
    ?>
    <<?php echo $this->_error_elem?> htmlfor="<?php echo $name;?>" class="<?php echo $this->_error_class?>" style="display: none;"><?php echo $msg?></<?php echo $this->_error_elem?>><?php endif;?>
        <?php
        $this->_inputs[$name] = ob_get_clean();
        return $name;
    }

    ##################################
    ####        checkbox
    ##################################
    /**
     * cria grupos de checkboxes
     * @param string $name do grupo
     * @param mixed $valor valor setado
     * @param array $option array com valor=>label
     * @param boolean $requerido obrigatorio
     * @param array $outros atrib=valor
     * @param string $msg mensagem para campo obrigatório
     * @return string name
     */
    public function check($name,$valor='',$option=array(),$requerido=true,$outros=null,$msg='Campo obrigatório'){
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $id = $this->_transfId($name,$outros);
        $msg = Cylix_I18n::t($msg);
        $outros_opt = '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        $req = $requerido ? 'true' : 'false';
        $this->_regras[$name] = "
            '{$name}[]': {
                required: $req
            }
        ";
        $this->_alertas[$name] = "
            '{$name}[]': {required:'$msg'}
        ";
        ob_start();
        ?>
    <input type="hidden" name="<?php echo $name?>" value="0" />
    <?php foreach($option as $k=>$v): ?>
    <label for="<?php echo $id."-$k";?>">
    <input type="checkbox" name="<?php echo $name.'[]';?>" id="<?php echo $id."-$k";?>" value="<?php echo $k;?>"<?php echo (((string)$k==(string)$valor) || (is_array($valor) && in_array($k, $valor)))?' checked="checked"':'';?> class="input-radio<?php echo $classe_ini?>"<?php echo $outros_opt;?> />
    <?php echo $v;?>
    </label>
    <?php endforeach;
    if($requerido): /*devido a um bug que nao consegui arrumar*/
    ?>
    <<?php echo $this->_error_elem?> htmlfor="<?php echo $name;?>[]" class="<?php echo $this->_error_class?>" style="display: none;"><?php echo $msg?></<?php echo $this->_error_elem?>><?php endif;?>
        <?php
        $this->_inputs[$name] = ob_get_clean();
        return $name;
    }

    ##################################
    ####        select - como multi ou simples
    ##################################
    /**
     * select agrupado ou nÃ£o
     * @param string $name
     * @param string $valor value direto
     * @param array $option montagem dos options (array simples ou array com array dentro c/ grupo)
     * @param boolean $requerido
     * @param boolean $multi indica se pode usar o CTRL para multiplas escolhas (multi line)
     * @param string $outros "inline"
     * @param string $msg
     * @return string name
     */
    public function select($name,$valor='',$option=array(),$requerido=true,$blank=false,$multi=false,$outros=null,$msg='Campo obrigatório'){
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $msg = Cylix_I18n::t($msg);
        if($blank === true){
            $blank = '';
        }
        $id = $this->_transfId($name,$outros);
        $name2 = $multi ? $name.'[]' : $name;
        $req = $requerido ? 'true' : 'false';
        $this->_regras[$name] = "
            \"$name\": {
                required: $req
            }
        ";
        $this->_alertas[$name] = "
            \"$name\": '$msg'
        ";
        $outros_opt = $multi ? ' multiple="multiple"' : '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        ob_start();
        ?><?php if($multi){ ?><input type="hidden" name="<?php echo $name?>" value="" /><?php } ?>
    <select id="<?php echo $id;?>" name="<?php echo $name2;?>" class="<?php echo self::$input_default_class;?> input-select<?php echo $classe_ini;?>"<?php echo $outros_opt;?>>
    <?php if($blank !== false):?><option value=""><?php echo $blank;?></option>
    <?php endif;
    foreach($option as $key=>$grupo): ?>
        <?php if(is_array($grupo)): ?>
        <optgroup label="<?php echo $key;?>">
            <?php foreach($grupo as $k=>$v): ?>
            <option id="<?php echo $id."-$k";?>" value="<?php echo (strlen($k))?$k:'';?>"<?php echo (((string)$k==(string)$valor) || (is_array($valor) && in_array($k, $valor)))?' selected="selected"':'';?>><?php echo $v;?></option>
            <?php endforeach; ?>
        </optgroup>
        <?php else: ?>
        <option id="<?php echo $id."-$key";?>" value="<?php echo (strlen($key))?$key:'';?>"<?php echo (((string)$key==(string)$valor) || (is_array($valor) && in_array($key, $valor)))?' selected="selected"':'';?>><?php echo $grupo;?></option>
        <?php endif;?>
    <?php endforeach; ?>
    </select>
    <?php
        $this->_inputs[$name] = ob_get_clean();
        return $name;
    }

    ##################################
    ####        color
    ##################################
    /**
     * colorpicker com input-text(TEXT) ou div-amostrador(SELECT)
     * @param string $name
     * @param string $valor hexadecimal da cor
     * @param boolean $requerido
     * @param string $tipo COLORPICKER_SELECT ou COLORPICKER_TEXT
     * @param array $outros inline attributes
     * @param string $msg 
     * @return string name
     */
    public function color($name,$valor='',$requerido=true,$tipo=self::COLORPICKER_SELECT,$outros=null,$msg='Campo obrigatório'){
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        $msg = Cylix_I18n::t($msg);
        $lib = 'lib/colorpicker/js/colorpicker.js';
        if(file_exists(INDEX_PATH.$lib)){
        //validando um hex
        $valor = trim($valor);
        $valor = (strpos($valor, '#') === 0 ) ? $valor : '#'.$valor;
        $valor = (strlen($valor) == 4) ? $valor[0].$valor[1].$valor[1].$valor[2].$valor[2].$valor[3].$valor[3] : $valor;
        //---------
        $id = $this->_transfId($name,$outros);
        $valor = (strlen($valor)>3) ? $valor : '#ffffff';
        $req = $requerido ? 'true' : 'false';
        $this->_regras[$name] = "
            '$name': {
                required: $req
            }
        ";
        $outros_opt = '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        $this->_alertas[$name] = "
            '$name': '$msg'
        ";

        Cylix_App::setCSS('colorpicker', getUrl('lib/colorpicker/css/colorpicker.css'));
        Cylix_App::setJS('colorpicker', getUrl($lib));
        ob_start();
        ?>
    <?php if($tipo==Cylix_Form::COLORPICKER_SELECT): ?>
    <div id="color-seletor-<?php echo $id;?>" class="color-selector">
        <div<?php echo (strlen($valor)>3) ? ' style="background-color: '.$valor.';"' : ''?>><input name="<?php echo $name;?>" id="<?php echo $id;?>" type="hidden" value="<?php echo $valor;?>" /></div>
    </div>
    <?php 
        $this->_scripts[] = '
    jQuery("#color-seletor-'.$id.'").ColorPicker({
	color: "'.$valor.'",
        onSubmit: function(hsb, hex, rgb, el) {
		jQuery(el).val("#" + hex);
		jQuery(el).ColorPickerHide();
	},
	onShow: function (colpkr) {
		jQuery(colpkr).fadeIn(500);
		return false;
	},
	onHide: function (colpkr) {
		jQuery(colpkr).fadeOut(500);
		return false;
	},
	onChange: function (hsb, hex, rgb) {
		jQuery("#color-seletor-'.$id.' div").css("backgroundColor", "#" + hex);
                jQuery("#color-seletor-'.$id.' div input[type=hidden]").val("#" + hex);
	}
    });';
        ?>
    <?php else: ?>
    <input type="text" name="<?php echo $name;?>" id="color-seletor-<?php echo $id;?>" value="<?php echo $valor;?>" class="<?php echo self::$input_default_class;?> input-color<?php echo $classe_ini?>" <?php echo $outros_opt;?>/>
    <?php
    $this->_scripts[] = '
    jQuery("#color-seletor-'.$id.'").ColorPicker({
	onSubmit: function(hsb, hex, rgb, el) {
		jQuery(el).val("#" + hex);
		jQuery(el).ColorPickerHide();
	},
	onBeforeShow: function () {
		jQuery(this).ColorPickerSetColor("#" + this.value);
	},
	onChange: function (hsb, hex, rgb) {
                jQuery("#color-seletor-'.$id.'").val("#" + hex);
	}
    })
    .bind("keyup", function(){
        jQuery(this).ColorPickerSetColor(this.value);
    });
    jQuery("#color-seletor-'.$id.'").ColorPickerSetColor("#'.$valor.'");';
    ?>
    <?php endif; ?>
        <?php
        $this->_inputs[$name] = ob_get_clean();
        }//lib
        return $name;
    }

    ##################################
    ####        button
    ##################################
    /**
     * input do tipo botÃ£o
     * @param string $id
     * @param string $valor
     * @param string $tipo button/submit
     * @param array $outros
     * @return string id
     */
    public function button($id,$valor='',$tipo='button',$class='input-button',$outros=null,$name=null){
        $outros_opt = '';
        if(is_array($outros)){
            foreach($outros as $k=>$v){
                $outros_opt .= " $k=\"$v\"";
            }
        }
        ob_start();
        ?>
    <button type="<?php echo $tipo;?>" id="<?php echo $id;?>" class="<?php echo $class ? $class : 'input-button';?>"<?php echo ($name) ? ' name="'.$name.'"' : '';?> <?php echo $outros_opt;?>><?php echo $valor;?></button>
        <?php
        $this->_inputs[$id] = ob_get_clean();
        return $id;
    }
    
    
    ##################################
    ####        submit button 
    ##################################
    /**
     * input do tipo botÃ£o e jÃ¡ para submit
     * @param string $id
     * @param string $valor
     * @param array $outros
     * @return string id
     */
    public function submit($id,$valor='',$name=null,$class='input-button',$outros=null){
        return $this->button($id, $valor, 'submit', $class, $name, $outros);
    }

    ##################################
    ####        hidden
    ##################################
    public function hidden($name,$valor=''){
        $id = $this->_transfId($name,$outros);
        ob_start();
        ?>
<input type="hidden" id="<?php echo $id;?>" name="<?php echo $name;?>" class="input-hidden" value="<?php echo $valor;?>" />
        <?php
        $this->_inputs[$name] = ob_get_clean();
        return $name;
    }


    ##################################
    ####        captcha
    ##################################
    /**
     * campo captcha
     *
     * @param string $name
     * @param int $tam numero de caracteres exibidos na imagem
     * @param string $msg mensagem de campo obrigatório
     * @return string name
     * @todo igualar o campo com o form::getCaptcha() pra saber se eh igual
     */
    public function captcha($name,$tam=6,$msg='Campo obrigatório'){
        $id = $this->_transfId($name,$outros);
        //lembrar de desativar cache com Cylix_Cache::semCache();
        $classe_ini = ' '.self::CLASS_REQUIRED;
        $msg = Cylix_I18n::t($msg);
        $this->_regras[$name] = "
            '$name': {
                required: true,
                minlength: $tam
            }
        ";
        $req_msg = str_replace('%i', $tam, Cylix_I18n::t("Precisa ter mais de %i caracteres"));
        $this->_alertas[$name] = "
            '$name': {required:'$msg',minlength:'$req_msg'}
        ";
        $img_file = self::$captchaFilePath;
        
        $this->_inputs[$name] = '
<img title="'.Cylix_I18n::t("Clique para trocar a imagem").'" src="'.getBaseUrl('lib/syscaptcha.php').'?v='.date('is').'&b='.base64_encode($img_file).'&t='.base64_encode($tam).'&f='.base64_encode(FLOW).'&s='.base64_encode(session_id()).'" border=0 alt="" style="display: block;cursor:pointer;" class="img-captcha" onclick="reloadCaptcha(this,\''.base64_encode($img_file).'\',\''.base64_encode($tam).'\',\''.base64_encode(FLOW).'\',\''.base64_encode(session_id()).'\')"/>
    <input type="text" name="'.$name.'" id="'.$id.'" value="" class="input-captcha'.$classe_ini.'" autocomplete="off" />';
        return $name;
    }
    
    /**
     * ckeditor que por questÃµes de validaÃ§Ã£o serÃ¡ sempre opcional em javascript<br>
     * Para tratar como obrigatório, isto terÃ¡ q ser feito no controller
     * @param string $name
     * @param string $valor
     * @param boolean $requerido
     * @param string $tema (basico,admin ou definido pelo config.js)
     * @param string $modulo (admin ou site)
     * @param string $altura valor inteiro
     * @param string $js_cfg config_editor.js
     * @return string name
     */
    public function html($name,$valor='',$requerido=true,$tema=self::CKEDITOR_BASICO,$modulo='admin',$altura='250',$js_cfg='config_editor.js'){
        $id = $this->_transfId($name,$outros);
        $lib = 'lib/ckeditor/ckeditor.js';
        if(file_exists(INDEX_PATH.$lib)){
        $classe_ini = $requerido ? ' '.self::CLASS_REQUIRED : '';
        Cylix_App::setJS('ckeditor', getUrl($lib));
        ob_start();
        ?>
    <textarea name="<?php echo $name;?>" id="<?php echo $id;?>" rows="5" cols="40" class="input-ckeditor input-html<?php echo $classe_ini?>"><?php echo $valor;?></textarea>
    <?php
    $this->_inputs[$name] = ob_get_clean();
    ob_start();
    if(false){?><script type="text/javascript"><?php }?>
      (function(){//encapsula o script para evitar interferencias externas
          CKEDITOR.config.toolbar = "<?php echo $tema;?>";
		  CKEDITOR.config.height = "<?php echo $altura;?>"; 
          var ckeditor_<?php echo $id;?> = CKEDITOR.replace( "<?php echo $id;?>"<?php echo ($modulo=='admin') ? ", { customConfig : '".getUrl('lib/ckeditor/'.$js_cfg)."' }" : '';?> );
      })();<?php if(false){?></script><?php }?>
        <?php
        $this->_scripts[] = ob_get_clean();
        }else{//lib
            $this->text($name, $valor, $requerido);
        }
        return $name;
    }
    
    ##################################
    ####        decoration
    ##################################
    
    public function setDecoration($tag='div',$class='bloco'){
        $this->_decoration = array('tag'=>$tag,'class'=>$class);
    }

    ##################################
    ####        toString
    ##################################

    public function  __toString() {
        $dec = $this->_decoration;
        $t_ini = $t_fim = '';
        if(is_array($dec) && count($dec)==2){
            $t_ini = '<'.$dec['tag'].' class="'.$dec['class'].'">';
            $t_fim = '</div>';
        }
        ob_start();
        ?>

<?php echo $this->ini();?>
    
        <?php
        foreach ($this->_inputs as $cada){
            echo $t_ini.$cada.$t_fim;
        }?>
<?php echo $this->end();?>
        <?php
        return ob_get_clean();
    }

    ###############################
    ###     Metodos separados para fora dos padroes
    ###############################

    /**
     * caso keira incluir no final do form coisas tipo jQuery('#id').css('display','none')
     * @param string $script 
     */
    public function setAjuste($script){
        $this->_ajustes[] = $script;
    }


    public function get($name){
        return $this->_inputs[$name];
    }

    public function ini(){
        ob_start();
        ?>

<form action="<?php echo $this->_acao?>" method="<?php echo $this->_metodo?>" name="<?php echo $this->_id?>" id="<?php echo $this->_id?>" enctype="<?php echo $this->_enctype?>"<?php echo $this->_outros?>>
        <?php
        return ob_get_clean();
    }
    /**
     * final do formulario
     * @param boolean $script inclui os scrits  no final
     * @param boolean $return imprime o fechamento do form e retorna os scripts
     * @return string 
     */
    public function end($script=true,$return=false,$utf8_enc=false){
        ob_start();
        ?>
    <?php 
    if(count($this->_regras) && $this->_validar && $script):
        $regras = implode(',', $this->_regras);
        $msg = implode(',', $this->_alertas);
        if($utf8_enc){
            $msg = utf8_encode($msg);
        }
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($j){
    jQuery("form#<?php echo $this->_id?>").validate({
        validClass: "<?php echo $this->_valid_class;?>",
        errorClass: "<?php echo $this->_error_class;?>",
        errorElement: "<?php echo $this->_error_elem;?>",
        rules: {<?php  echo $regras; ?>
        },
        messages: {<?php  echo $msg; ?>
        }
    });
    });
    </script>
    <?php endif;
    if(count($this->_ajustes)):
    ?>
    <script type="text/javascript">
    <?php foreach($this->_ajustes as $cada){ 
        echo "
        $cada";
    }?>
    </script>
    <?php endif;
    if(count($this->_scripts)):
    ?>
    <script type="text/javascript">
    <?php foreach($this->_scripts as $cada){ 
        echo "
        $cada";
    }?>

    </script>
    <?php endif; ?>
    <script type="text/javascript">
	try{
        jQuery.validator.addMethod('filesize', function(value, element, param) {
            // param = size (en bytes) 
            // element = element to validate (<input>)
            // value = value of the element (file name)
            return this.optional(element) || (element.files[0].size <= param) 
        });
	}catch(e){
		console.log(e);
	}
    </script>
    <?php
    $script_ob = ob_get_clean();
    ob_start();
    ?>
</form>
        <?php
        if($return){
            echo ob_get_clean();
            return $script_ob;
        }
        return $script_ob.ob_get_clean();
    }

    /*      auxiliares      **/
    /**
     * troca as datas BR por ISO
     * @param array $array_campos names dos campos a serem vasculhados
     * @param boolean $post vai procurar nos posts(true) ou nos gets(false)
     */
    static function converteDatas($array_campos=array(),$post=true){
        if(is_array($array_campos) && count($array_campos)){
            foreach($array_campos as $key){
                try{
                    $valor = ($post) ? $_POST[$key] : $_GET[$key];
                    if($valor){
                        $valor = explode(' ',$valor);
                        $horas = (isset($valor[1])) ? ' '.$valor[1] : '';
                        $valor = $valor[0];
                        $valor = explode('/', $valor);
                        $valor = $valor[2].'-'.$valor[1].'-'.$valor[0].$horas;
                        if($post){
                            $_POST[$key] = $valor;
                        }else{
                            $_GET[$key] = $valor;
                        }
                    }
                }catch(Cylix_Exception $e){}
            }
        }
    }
    /**
     * vasculha a procura de datas pra converter para float
     * @param <type> $array_campos id dos campos a serem vasculhados
     * @param <type> $post procurar em post(true) ou get(false)
     */
    static function converteMoedas($array_campos=array(),$post=true){
        if(is_array($array_campos) && count($array_campos)){
            foreach($array_campos as $key){
                try{
                    $valor = ($post) ? $_POST[$key] : $_GET[$key];
                    if($valor){
                        $valor = str_replace(array('US$ ','Â£ ','US$','Â£','R$ ','$ ','R$','$','.'), '', $valor);
                        $valor = str_replace(',', '.', $valor);
                        if($post){
                            $_POST[$key] = $valor;
                        }else{
                            $_GET[$key] = $valor;
                        }
                    }
                }catch(Cylix_Exception $e){}
            }
        }
    }
    /**
     * DEPRECATED - usar Cylix_Model
     * @param array $chaves somente as chaves serem procuradas
     * @param array $other_post caso nao use $_POST
     * @return boolean 
     * @deprecated
     */
    static function requiredFields($chaves=array(),$other_post=null){
		$POST = ($other_post) ? $other_post : $_POST;
        $return = true;
        foreach($chaves as $cada){
            if(isset($POST[$cada])){
                $valor = trim($POST[$cada]);
                if(strlen($valor) < 1){
                    $return = false;
                }
            }
        }
        
        return $return;
    }
    /**
     * DEPRECATED - usar Cylix_Model
     * @param type $cpf
     * @return boolean 
     * @deprecated
     */
    static function cpfValido($cpf){
        if(trim($cpf)<8){
            return false;
        }
        $cpf = preg_replace("/[^0-9]/", "", $cpf);
        $digitoUm = 0;
        $digitoDois = 0;

        for($i = 0, $x = 10; $i <= 8; $i++, $x--){
            $digitoUm += $cpf[$i] * $x;
        }
        for($i = 0, $x = 11; $i <= 9; $i++, $x--){
            if(str_repeat($i, 11) == $cpf){
                return false;
            }
            $digitoDois += $cpf[$i] * $x;
        }

        $calculoUm  = (($digitoUm%11) < 2) ? 0 : 11-($digitoUm%11);
        $calculoDois = (($digitoDois%11) < 2) ? 0 : 11-($digitoDois%11);
        if($calculoUm <> $cpf[9] || $calculoDois <> $cpf[10]){
            return false;
        }
        return true;
    }

    static function cnpjValido($str) {
	if (!preg_match('|^(\d{2,3})\.?(\d{3})\.?(\d{3})\/?(\d{4})\-?(\d{2})$|', $str, $matches))
		return false;

	array_shift($matches);

	$str = implode('', $matches);
	if (strlen($str) > 14)
		$str = substr($str, 1);

	$sum1 = 0;
	$sum2 = 0;
	$sum3 = 0;
	$calc1 = 5;
	$calc2 = 6;

	for ($i=0; $i <= 12; $i++) {
		$calc1 = $calc1 < 2 ? 9 : $calc1;
		$calc2 = $calc2 < 2 ? 9 : $calc2;

		if ($i <= 11)
			$sum1 += $str[$i] * $calc1;

		$sum2 += $str[$i] * $calc2;
		$sum3 += $str[$i];
		$calc1--;
		$calc2--;
	}

	$sum1 %= 11;
	$sum2 %= 11;

	return ($sum3 && $str[12] == ($sum1 < 2 ? 0 : 11 - $sum1) && $str[13] == ($sum2 < 2 ? 0 : 11 - $sum2)) ? $str : false;
    }
    /**
     * retorna pro controller o captcha gerado na view
     * @return string 
     */
    static function getCaptcha(){
        $v = $_SESSION['captcha-'.FLOW]['val'];
        unset($_SESSION['captcha-'.FLOW]);
        return $v;
    }
    
    static function convertBytes($numero){
        $numero = (int)$numero;
        $return = '0 bytes';
        if($numero >= 1024){
            $numero = $numero / 1024;
            if($numero >= 1024){
                $numero = $numero / 1024;
                if($numero >= 1024){
                    $numero = $numero / 1024;
                    $return = round($numero, 2).' GB';
                }else{
                    $return = round($numero, 2).' MB';
                }
            }else{
                $return = round($numero, 2).' KB';
            }
        }else{
            $return = $numero.' bytes';
        }
        return $return;
    }
}

?>
