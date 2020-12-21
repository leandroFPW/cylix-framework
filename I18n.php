<?php

/*
 * Tradução inline
 */

/**
 * Description of I18n
 *
 * @author Leandro
 */
class Cylix_I18n {

    static $locale;
    static $csv_dir;
    static private $_array = array();

    static function csv_to_array($input, $delim_col=',',$delim_string='"') {
        $file = file_get_contents($input);
        $file = str_replace("\r\n", "\n", $file);
        $f_lines = explode("\n", $file);
        $data = array();
        foreach($f_lines as $line){
            $line = str_getcsv($line, $delim_col,$delim_string);
            $data[] = $line;
        }
        return $data;
    }

    /**
     *
     * Translate the string - CSV
     * @param string $string frase
     * @param string $locale specific locale
     * @return string 
     */
    static function t($string) {
        $string = (isset (self::$_array[self::$locale][$string])) ? self::$_array[self::$locale][$string] : $string;
        return $string;
    }
    /**
     * funcao de carregamento dos csv's
     * @param string $locale 
     * @param string $csv_dir diretorio dos arquivos csv
     */
    static function getCSV($locale='pt-BR', $csv_dir='./sys/etc/locale') {
        $locale = self::normalizeLocale($locale);
        self::$locale = $locale;
        $arq = $csv_dir.'/L_'.Cylix_App::camelcase($locale).'.csv';
        if(!isset(self::$_array[$locale])){
            self::$_array[$locale] = array();
        }
        if(file_exists($arq)){
            foreach(self::csv_to_array($arq) as $line){
                self::$_array[$locale]["$line[0]"] = (string)$line[1];
            }
        }
    }
    
    static function normalizeLocale($locale){
        $locale = strlen($locale) > 4 ? $locale : 'pt-BR';
        $locale = preg_split("[-_]", $locale);
        $locale = implode('-', $locale);
        return $locale;
    }

}

?>
