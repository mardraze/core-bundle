<?php
namespace Mardraze\CoreBundle\Twig;

use Mardraze\CoreBundle\Service\Depedencies;
use Mardraze\CoreBundle\Service\LoremIpsumGenerator;

class CoreExtension extends \Twig_Extension
{
    /**
     * @var Depedencies
     */
    private $depedencies;

    public function __construct(Depedencies $depedencies){
        $this->depedencies = $depedencies;
    }

    public function getFunctions()
    {
        return array(
            'pager' => new \Twig_Function_Function(array($this, 'pager'), array('is_safe' => array('html'))),
            'bundleName' => new \Twig_Function_Function(array($this, 'bundleName'), array('is_safe' => array('html'))),
            'uniqid' => new \Twig_Function_Function(array($this, 'uniqid'), array('is_safe' => array('html'))),
            'js' => new \Twig_Function_Function(array($this, 'jsArray'), array('is_safe' => array('html'))),
            'lorem' => new \Twig_Function_Function(array($this, 'lorem'), array('is_safe' => array('html'))),
            'safe_call' => new \Twig_Function_Function(array($this, 'safe_call'), array('is_safe' => array('html'))),
            'd' => new \Twig_SimpleFunction('d', array($this, 'var_dump'), array('is_safe' => array('html'), 'needs_context' => true, 'needs_environment' => true)),
            'mardraze_menu' => new \Twig_SimpleFunction('mardraze_menu', array($this, 'menu'), array('is_safe' => array('html'))),
            'mardraze_page' => new \Twig_SimpleFunction('mardraze_page', array($this, 'page'), array('is_safe' => array('html'))),

        );
    }

    public function getFilters()
    {
        return array(
            'odmiana' => new \Twig_SimpleFilter('odmiana', array($this, 'odmiana')),
            'slugify' => new \Twig_SimpleFilter('slugify', array($this, 'slugify')),
            'js' => new \Twig_SimpleFilter('js', array($this, 'js')),
            'str_remove' => new \Twig_SimpleFilter('str_remove', array($this, 'str_remove')),
            'bootstrap_alert' => new \Twig_SimpleFilter('bootstrap_alert', array($this, 'bootstrap_alert')),
            'slowniePLN' => new \Twig_SimpleFilter('slowniePLN', array($this, 'slowniePLN')),
            'pln' => new \Twig_SimpleFilter('pln', array($this, 'pln')),
            'price' => new \Twig_SimpleFilter('price', array($this, 'price')),
        );
    }

    public function page($vars = null){
        return $this->depedencies->getMenuMaker()->getPage($vars);
    }

    public function menu($vars = array()){
        return $this->depedencies->getMenuMaker()->renderMenu($vars);
    }

    public function pln($val){
        return $this->price($val).' PLN';
    }

    public function price($val){
        return str_replace('.', ',', number_format($val*1.0, 2, '.', ''));
    }

    private function str_split($string,$len = 1) {
        if ($len < 1) return false;
        for($i=0, $rt = Array();$i<ceil(strlen($string)/$len);$i++)
            $rt[$i] = substr($string, $len*$i, $len);
        return($rt);
    }

    private $slowa=Array('minus',Array('zero','jeden','dwa','trzy','cztery','pięć','sześć','siedem','osiem','dziewięć'),Array('dziesięć','jedenaście','dwanaście','trzynaście','czternaście','piętnaście','szesnaście','siedemnaście','osiemnaście','dziewiętnaście'),Array('dziesięć','dwadzieścia','trzydzieści','czterdzieści','pięćdziesiąt','sześćdziesiąt','siedemdziesiąt','osiemdziesiąt','dziewięćdziesiąt'),Array('sto','dwieście','trzysta','czterysta','pięćset','sześćset','siedemset','osiemset','dziewięćset'),Array('tysiąc','tysiące','tysięcy'),Array('milion','miliony','milionów'),Array('miliard','miliardy','miliardów'),Array('bilion','biliony','bilionów'),Array('biliard','biliardy','biliardów'),Array('trylion','tryliony','trylionów'),Array('tryliard','tryliardy','tryliardów'),Array('kwadrylion','kwadryliony','kwadrylionów'),Array('kwintylion','kwintyliony','kwintylionów'),Array('sekstylion','sekstyliony','sekstylionów'),Array('septylion','septyliony','septylionów'),Array('oktylion','oktyliony','oktylionów'),Array('nonylion','nonyliony','nonylionów'),Array('decylion','decyliony','decylionów'));

    private function liczba($int){ // odmiana dla liczb < 1000
        $slowa = $this->slowa;
        $wynik = '';
        $j = abs((int) $int);

        if ($j == 0) return $slowa[1][0];
        $jednosci = $j % 10;
        $dziesiatki = ($j % 100 - $jednosci) / 10;
        $setki = ($j - $dziesiatki*10 - $jednosci) / 100;

        if ($setki > 0) $wynik .= $slowa[4][$setki-1].' ';

        if ($dziesiatki > 0)
            if ($dziesiatki == 1) $wynik .= $slowa[2][$jednosci].' ';
            else
                $wynik .= $slowa[3][$dziesiatki-1].' ';

        if ($jednosci > 0 && $dziesiatki != 1) $wynik .= $slowa[1][$jednosci].' ';
        return $wynik;
    }


    public function slowniePLN($int){
        $grosze = doubleval($int) - intval($int);
        if($grosze > 0){
            $int = intval($int).'';
            $grosze = intval($grosze * 100).'';
        }
        $result = '';
        if($int < 1000000){
            $tysiace = intval($int/1000);
            if($tysiace > 0){
                $result .= $this->slownie($tysiace).' '.$this->odmiana($tysiace, array('tysiąc', 'tysiące', 'tysięcy')).' ';
            }
            $setki = $int % 1000;
            if($setki > 0 || !$tysiace){
                $result .= $this->slownie($setki);
            }
            $result .= ' PLN';
            if($grosze > 0){
                $result .= ' '.$this->slownie($grosze).' gr';
            }
        }
        return $result;
    }

    private function slownie($int){
        $slowa = $this->slowa;

        $in = preg_replace('/[^-\d]+/','',$int);
        $out = '';

        if ($in{0} == '-'){
            $in = substr($in, 1);
            $out = $slowa[0].' ';
        }

        $txt = $this->str_split(strrev($in), 3);

        if ($in == 0) $out = $slowa[1][0].' ';

        for ($i = count($txt) - 1; $i >= 0; $i--){
            $liczba = (int) strrev($txt[$i]);
            if ($liczba > 0)
                if ($i == 0)
                    $out .= $this->liczba($liczba).' ';
                else
                    $out .= ($liczba > 1 ? $this->liczba($liczba).' ' : '')
                        .$this->odmiana($liczba, $slowa[4 + $i]).' ';
        }
        return trim($out);
    }

    public function var_dump(\Twig_Environment $env, $context){
        if (!$env->isDebug()) {
            return;
        }

        ob_start();

        $count = func_num_args();
        if (2 === $count) {
            $vars = array();
            foreach ($context as $key => $value) {
                if (!$value instanceof \Twig_Template) {
                    $vars[$key] = $value;
                }
            }

            var_dump(array_keys($vars));
        } else {
            for ($i = 2; $i < $count; $i++) {
                var_dump(func_get_arg($i));
            }
        }

        return ob_get_clean();
    }
    public function safe_call($twigFunction){
        $obj = $this->depedencies->getTwig()->getFunction($twigFunction);
        if($obj && method_exists($obj, 'getCallable')){
            $callable = $obj->getCallable();
            $args = func_get_args();
            unset($args[0]);
            return call_user_func_array($callable, $args);
        }
    }

    public function lorem($count = 40){
        $lorem = new LoremIpsumGenerator();
        return $lorem->getContent($count, 'plain', false);
    }

    public function bootstrap_alert($type){
        $newType = $this->str_remove($type, 'sonata_flash_');
        if($newType == 'error'){
            $newType = 'danger';
        }
        if(!in_array($newType, array('success', 'info', 'danger', 'warning'))){
            $newType = 'info';
        }
        return $newType;
    }

    public function str_remove($subject, $search){
        if(!is_array($search)){
            $search = array($search.'');
        }
        return str_replace($search, '', $subject);
    }
    public function jsArray(){
        if(array_key_exists('mardraze_js', $_SESSION)){
            $ret = $_SESSION['mardraze_js'];
            unset($_SESSION['mardraze_js']);
            return array_unique($ret);
        }
    }

    public function js($js){
        if(!array_key_exists('mardraze_js', $_SESSION)){
            $_SESSION['mardraze_js'] = array();
        }
        if(!in_array($js, $_SESSION['mardraze_js'])){
            $_SESSION['mardraze_js'][] = $js;
        }
    }

    public function odmiana($int, $odmiany){ // $odmiany = Array('jeden','dwa','pięć')
        $txt = $odmiany[2];
        if ($int == 1) $txt = $odmiany[0];
        $jednosci = (int) substr($int,-1);
        $reszta = $int % 100;
        if (($jednosci > 1 && $jednosci < 5) &! ($reszta > 10 && $reszta < 20))
            $txt = $odmiany[1];
        return str_replace('%d', $int, $txt);
    }


    public function slugify($str) {
        $str = str_replace(
            array('ą', 'Ą','ć','Ć','ę','Ę','ł','Ł','ń','Ń','ś','Ś','ż','Ż','ź','Ź', 'ä', 'ö', 'ü','ë', 'ß','Ä','Ö','Ü','Ë','à','á','â','ã','ä', 'ç', 'è','é','ê','ë', 'ì','í','î','ï', 'ñ', 'ò','ó','ô','õ','ö', 'ù','ú','û','ü', 'ý','ÿ', 'À','Á','Â','Ã','Ä', 'Ç', 'È','É','Ê','Ë', 'Ì','Í','Î','Ï', 'Ñ', 'Ò','Ó','Ô','Õ','Ö', 'Ù','Ú','Û','Ü', 'Ý'),
            array('a', 'a', 'c', 'c', 'e', 'e', 'l', 'l', 'n', 'n', 's', 's', 'z', 'z', 'z', 'z', 'ae', 'oe', 'ue', 'ss','ae','oe','ue','ee','a','a','a','a','a', 'c', 'e','e','e','e', 'i','i','i','i', 'n', 'o','o','o','o','o', 'u','u','u','u', 'y','y', 'A','A','A','A','A', 'C', 'E','E','E','E', 'I','I','I','I', 'N', 'O','O','O','O','O', 'U','U','U','U', 'Y'),
            $str
        );

        $str = preg_replace('/[^A-Za-z0-9._]/', '-', trim($str)) ;
        $str = preg_replace('{(-)\1+}','$1',$str);

        return strtolower($str);
    }

    public function bundleName()
    {
        return BUNDLE_NAME;
    }

    public function uniqid()
    {
        return uniqid();
    }

    public function pager($options = array())
    {
        if(!is_array($options)) {
            $options = array('total' => $options);
        }
        if(!@$options['page']){
            $options['page'] = @$_REQUEST['page'] ? @$_REQUEST['page'] * 1 : 1;
        }
        if(!@$options['urlscheme']){
            $get = $_GET;
            if(array_key_exists('page', $get)){
                unset($get['page']);
            }
            $query = http_build_query($get);
            $options['urlscheme'] = '?page=%page%'.($query ? ('&'.$query) : '');
        }


        $pagination = new \Mardraze\CoreBundle\Service\Pagination();
        return $pagination->make($options);
    }

    public function phpFunction($function)
    {
        if(function_exists($function)){
            $args = func_get_args();
            unset($args[0]);
            return call_user_func_array($function, $args);
        }
        return 'ERROR!!! function not exists '.$function;
    }



    public function getName()
    {
        return 'mardraze_core_twig';
    }
}