<?php
require 'scraperwiki.php';
//hasta aqui http://www.gsmarena.com/sendo-phones-18.php
require 'scraperwiki/simple_html_dom.php';           

class GSMAParser {
    
    var $brands = array();
    var $models = 0;
    var $html;

    function init() {
        $this->brands = array();
        $this->models = 0;        


        $this->parseBrands();
        echo count( $this->brands) . ' parsed brands'. "\n";
        
        $this->parseModels();
        echo $this->models . ' parsed models'. "\n";
    }

    function parseBrands(){
        $html_content = scraperwiki::scrape("http://www.gsmarena.com/makers.php3");
        $html = str_get_html($html_content);
    
        $i = 0;
        $temp = array();
        foreach ($html->find("div.st-text a") as $el) {
            
            if($i % 2 == 0){
                $img = $el->find('img',0);
                $b['link'] = 'http://www.gsmarena.com/'.$el->href;
                $b['img'] = $img->src;
                $b['name'] = $img->alt;
                $temp = explode('-',$el->href);
                $b['id'] = (int) substr($temp[2], 0, -4);
                
                $this->brands[] = $b;
                
                scraperwiki::save_sqlite(array("id"=>$b['id']), $b, "cellbrand");

            }           
        
            $i++;
 
        }
        
        $html->__destruct();
    }
    
    function parseModels(){
        $temp = array();
        foreach ($this->brands as $b) {
            
            $this->parseModelsPage($b['id'],$b['name'],$b['link']);
            
        }

    }

    function parseModelsPage($brandId,$brandName,$page){

        $html_content = scraperwiki::scrape($page);
        $this->html = str_get_html($html_content);
        
        foreach ($this->html->find("#main") as $el) {
            $img = $el->find('#specs-cp-pic img');
            $tmp = $el->find('.brand h1',0)->innertext;
            $m['name'] = str_replace(" ", "<br>", $tmp);
            $m['img'] = $img->src;
            $im = file_get_contents($img->src);
            $m['img'] = base64_encode($im);
            $tmp = explode(' ', $tmp, 2);
            $m['rname'] = $tmp[1];
            $out = explode('<td class="ttl"><a href="glossary.php3?term=chipset">Chipset</a></td>', $el);
            $m['desc'] = explode('</td>',$out[1]);
            $temp = explode('-',$el->href);
            $m['id'] = (int) substr($temp[1], 0, -4);

            scraperwiki::save_sqlite(array("id"=>$m['id']), $m, "cellmodel");

            $this->models++;

        }

        $pagination = $this->html->find("div.nav-pages",0);

        if($pagination){
           $nextPageLink = $pagination->lastChild();
           if($nextPageLink && $nextPageLink->title=="Next page"){
               $this->parseModelsPage($brandId,$brandName,'http://www.gsmarena.com/'.$nextPageLink->href);
           }
        }

        $this->html->__destruct();
      
    }

}

$parser = new GSMAParser();

$parser->init();


?>
