<?php 


/**
 * This tool can generate a PDF report using TCPDF library. You have to include it and import the class into this class.
 * If you want to show the information on HTML page then no need to use TCPDF.
 * 
 * Required Resources : TCPDF PHP library, 
 * 
 */
// require 'tcpdf/tcpdf.php';
// require 'tcpdf/mypdf.php';

/**
 * Class SeoReport
 * @filesource SeoReport.php
 * @category SEO Report Generator
 * @version v1.1
 * @author Davide Guerra
 */
class SeoReport{
	
	protected $url = "";
	protected $start = null;
	protected $end = null;
	
	function __construct($url = ""){
		$this->url = $url;
	}
	
	/**
	 * This method need to call from your source class file to generate SEO Report
	 */
	public function getSeoReport(){
		
		$htmlInfo = array();
		
		$htmlInfo["dnsReachable"] = $this->isDNSReachable($this->url);
		
// 		if($htmlInfo["dnsReachable"] !== false){
		
			$isAlive = $this->isAlive();
			/* $this->pre($isAlive);
			 die; */
			
			if($isAlive["STATUS"] == true){
				$this->start = microtime(true);
				$grabbedHTML = $this->grabHTML($this->url);
				$this->end = microtime(true);
				
				$htmlInfo = array_merge($htmlInfo, $this->getSiteMeta($grabbedHTML));
				$htmlInfo["isAlive"] = true;
				/* $this->pre($htmlInfo);
				die; */
			}else{
				$htmlInfo["isAlive"] = false;
			}
// 		}
		$htmlInfo["url"] = $this->url;
		$reqHTML = $this->getReadyHTML($htmlInfo);
		return $reqHTML;
		
		// $this->exportSEOReportPDF($htmlInfo, $this->url);
	}
	
	/**
	 * This function used to print any data 
	 * @param mixed $data
	 */
	function pre($data){
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	}
	
	/**
	 * This function used to print any data
	 * @param mixed $data
	 */
	function dump($data){
		echo "<pre>";
		var_dump($data);
		echo "</pre>";
	}
	
	/**
	 * check if a url is online/alive
	 * @param string $url : URL of the website
	 * @return array $result : This containt HTTP_CODE and STATUS
	 */
	function isAlive() {
		set_time_limit(0);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 7200);
		curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
		curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 2 );
		curl_exec ($ch);
		$int_return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close ($ch);
		
		$validCodes = array(200, 301, 302, 304);
		
		if (in_array($int_return_code, $validCodes)){
			return array("HTTP_CODE"=>$int_return_code, "STATUS"=>true);
		}
		else return array("HTTP_CODE"=>$int_return_code, "STATUS"=>false);
	}
	
	/**
	 * This function is used to check the reachable DNS
	 * @param {String} $url : URL of website
	 * @return {Boolean} $status : TRUE/FALSE
	 */
	function isDNSReachable($url){
		$dnsReachable = checkdnsrr($this->addScheme($url));
		return $dnsReachable == false ? false : true;
	}
	
	/**
	 * This function is used to check for file existance on server
	 * @param {String} $filename : filename to be check for existance on server
	 * @return {Boolean} $status : TRUE/FALSE
	 */
	function checkForFiles($filename){
		$handle = curl_init("http://www.".$this->url."/".$filename);
		$handle = curl_init("https://www.".$this->url."/".$filename);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($handle);
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);
		if($httpCode == 200) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * This function is used to check broken link checking
	 * @param {String} $link : Link to be test as broken or not
	 * @return {Boolean} $status : TRUE/FALSE
	 */
	function brokenLinkTester($link){
		set_time_limit(0);
		$handle = curl_init($link);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($handle);
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);
		if($httpCode == 200) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	/**
	 * This function is used to check broken link checking for all anchors from page
	 * @param {Array} $anchors : Anchor tags from page
	 * @return {Number} $count : Count of broken link
	 */
	function getBrokenLinkCount($anchors){
		$count = 0;
		$blinks = array();
		foreach ($anchors as $a){
			array_push($blinks, $a->getAttribute("href"));
		}
		if(!empty($blinks)){
			foreach ($blinks as $ln){
				$res = $this->brokenLinkTester($ln);
				if($res){
					$count++;
				}
			}
		}
		
		return $count;
	}
	
	/**
	 * This function is used to check the alt tags for available images from page
	 * @param {Array} $imgs : Images from pages
	 * @return {Array} $result : Array of results
	 */
	function imageAltText($imgs){
		$totImgs = 0;
		$totAlts = 0;
		$diff = 0;
		foreach($imgs as $im){
			$totImgs++;
			if(!empty($im->getAttribute("alt"))){
				$totAlts++;
			}
		}
		return array("totImgs"=>$totImgs, "totAlts"=>$totAlts, "diff"=>($totImgs - $totAlts));
	}
	
	/**
	 * HTTP GET request with curl.
	 * @param string $url : String, containing the URL to curl.
	 * @return string : Returns string, containing the curl result.
	 */
	function grabHTML($url){
		set_time_limit(0);
		$ch  = curl_init($url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch,CURLOPT_MAXREDIRS,2);
		if(strtolower(parse_url($this->url, PHP_URL_SCHEME)) == 'https') {
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,1);
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);
		}
		$str = curl_exec($ch);
		curl_close($ch);
	
		return ($str)?$str:FALSE;
	}
	
	/**
	 * This function used to check that google analytics is included in page or not
	 * @param {Object} $grabbedHtml : Page HTML object
	 * @return {Boolean} $result : TRUE/FALSE
	 */
	function findGoogleAnalytics($grabbedHtml){
		$pos = strrpos($grabbedHtml, "GoogleAnalyticsObject");
		return ($pos > 0)?TRUE:FALSE;
	}
	
	/**
	 * This function used to add http protocol to the url if not available
	 * @param {Strin} $url : This is website url
	 * @param {String} $scheme : Protocol Scheme, default http
	 */
	function addScheme($url, $scheme = 'http://'){
		return parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url;
	}
	
	/**
	 * This function used to get meta and language information from HTML
	 * @param string $grabbedHTML : This is HTML string
	 * @return array $htmlInfo : This is information grabbed from HTML
	 */
	function getSiteMeta($grabbedHTML){
		
		$html = new DOMDocument();
		libxml_use_internal_errors(true);
		$html->loadHTML($grabbedHTML);
		libxml_use_internal_errors(false);
		$xpath = new DOMXPath( $html );
		$htmlInfo = array();
		$langs = $xpath->query( '//html' );
		foreach ($langs as $lang) {
			$htmlInfo['language'] = $lang->getAttribute('lang');
		}
		$metas = $xpath->query( '//meta' );		
		foreach ($metas as $meta) {
			if ($meta->getAttribute('name')){
				$htmlInfo[$meta->getAttribute('name')] = $meta->getAttribute('content');
			}
		}
		
		$favicon = $xpath->query("//link[@rel='shortcut icon']");
		if(!empty($favicon)){
			foreach($favicon as $fav){
				$htmlInfo[$fav->getAttribute("rel")] = $fav->getAttribute("href");
			}
		}
		
		$title = $xpath->query("//title");
		foreach ($title as $tit){
			$htmlInfo["titleText"] = $tit->textContent;
		}
		
		$htmlInfo = array_change_key_case($htmlInfo, CASE_LOWER);
		
		$onlyText = $this->stripHtmlTags($grabbedHTML);
		
		if(!empty($onlyText)){
			$onlyText = array(trim($onlyText));
			
			$count = $this->getWordCounts($onlyText);
			
			/* Stopwords */
			$grammar = array(" "=>"", "-"=>"", "-"=>"", "a"=>"", "b"=>"", "c"=>"", "d"=>"", "e"=>"", "f"=>"", "g"=>"", "h"=>"", "i"=>"", "j"=>"", "k"=>"", "l"=>"", "m"=>"", "n"=>"", "o"=>"", "p"=>"", "q"=>"", "r"=>"", "s"=>"", "t"=>"", "u"=>"", "v"=>"", "w"=>"", "x"=>"", "y"=>"", "z"=>"", "il"=>"", "lo"=>"", "la"=>"", "gli"=>"", "le"=>"", "un"=>"", "uno"=>"", "una"=>"", "di"=>"", "da"=>"", "in"=>"", "su"=>"", "per"=>"", "con"=>"", "tra"=>"", "fra"=>"", "al"=>"", "allo"=>"", "alla"=>"", "ai"=>"", "agli"=>"", "alle"=>"", "dal"=>"", "dallo"=>"", "dalla"=>"", "dai"=>"", "dagli"=>"", "dalle"=>"", "del"=>"", "dello"=>"", "della"=>"", "dei"=>"", "degli"=>"", "delle"=>"", "nel"=>"", "nello"=>"", "nella"=>"", "nei"=>"", "negli"=>"", "nelle"=>"", "sul"=>"", "sullo"=>"", "sulla"=>"", "sui"=>"", "sugli"=>"", "sulle"=>"", "davanti"=>"", "dietro"=>"", "stante"=>"", "durante"=>"", "sopra"=>"", "sotto"=>"", "salvo"=>"", "accanto"=>"", "avanti"=>"", "verso"=>"", "presso"=>"", "contro"=>"", "circa"=>"", "intorno"=>"", "fuori"=>"", "malgrado"=>"", "vicino"=>"", "lontano"=>"", "dentro"=>"", "indietro"=>"", "insieme"=>"", "assieme"=>"", "oltre"=>"", "senza"=>"", "attraverso"=>"", "nondimeno"=>"", "mio"=>"", "mia"=>"", "miei"=>"", "mie"=>"", "tuo"=>"", "tua"=>"", "tuoi"=>"", "tue"=>"", "suo"=>"", "sua"=>"", "suoi"=>"", "sue"=>"", "nostro"=>"", "nostra"=>"", "nostri"=>"", "nostre"=>"", "vostro"=>"", "vostra"=>"", "vostri"=>"", "vostre"=>"", "loro"=>"", "questo"=>"", "codesto"=>"", "cotesto"=>"", "quello"=>"", "ciò"=>"", "questa"=>"", "codesta"=>"", "cotesta"=>"", "quella"=>"", "io"=>"", "tu"=>"", "egli"=>"", "esso"=>"", "ella"=>"", "essa"=>"", "noi"=>"", "voi"=>"", "essi"=>"", "esse"=>"", "me"=>"", "mi"=>"", "te"=>"", "ti"=>"", "lui"=>"", "lei"=>"", "ce"=>"", "ci"=>"", "ve"=>"", "vi"=>"", "se"=>"", "si"=>"", "ne"=>"", "che"=>"", "colui"=>"", "colei"=>"", "cui"=>"", "chi"=>"", "sono"=>"", "sei"=>"", "è"=>"", "siamo"=>"", "siete"=>"", "sarebbe"=>"", "sarà"=>"", "essendo"=>"", "ho"=>"", "hai"=>"", "ha"=>"", "abbiamo"=>"", "avete"=>"", "hanno"=>"", "avrebbe"=>"", "avrà"=>"", "avendo"=>"", "avuto"=>"", "l\'"=>"", "un\'"=>"", "all\'"=>"", "dall\'"=>"", "dell\'"=>"", "sull\'"=>"", "nell\'"=>"", "quell\'"=>"", "c\'"=>"", "v\'"=>"", "po\'"=>"", "può"=>"", "potrà"=>"", "potrebbe"=>"", "potuto"=>"", "deve"=>"", "dovrà"=>"", "dovrebbe"=>"", "dovuto"=>"", "due"=>"", "tre"=>"", "quattro"=>"", "cinque"=>"", "sette"=>"", "otto"=>"", "nove"=>"", "dieci"=>"", "venti"=>"", "trenta"=>"", "quaranta"=>"", "cinquanta"=>"", "sessanta"=>"", "settanta"=>"", "ottanta"=>"", "novanta"=>"", "cento"=>"", "primo"=>"", "secondo"=>"", "terzo"=>"", "quarto"=>"", "quinto"=>"", "sesto"=>"", "settimo"=>"", "ottavo"=>"", "nono"=>"", "decimo"=>"", "ma"=>"", "però"=>"", "anzi"=>"", "tuttavia"=>"", "pure"=>"", "invece"=>"", "perciò"=>"", "quindi"=>"", "dunque"=>"", "pertanto"=>"", "ebbene"=>"", "orbene"=>"", "né"=>"", "neppure"=>"", "neanche"=>"", "nemmeno"=>"", "sia"=>"", "oppure"=>"", "ossia"=>"", "altrimenti"=>"", "cioè"=>"", "infatti"=>"", "invero"=>"", "difatti"=>"", "perché"=>"", "poiché"=>"", "giacché"=>"", "quando"=>"", "mentre"=>"", "finché"=>"", "affinché"=>"", "acciocché"=>"", "qualora"=>"", "purché"=>"", "sebbene"=>"", "quantunque"=>"", "benché"=>"", "nonostante"=>"", "come"=>"", "quasi"=>"", "fuorché"=>"", "tranne"=>"", "eccetto"=>"", "laddove"=>"", "ah"=>"", "oh"=>"", "eh"=>"", "orsù"=>"", "urrà"=>"", "ahimè"=>"", "suvvia"=>"", "basta"=>"", "insomma"=>"", "così"=>"", "qui"=>"", "qua"=>"", "lì"=>"", "là"=>"", "già"=>"", "allora"=>"", "prima"=>"", "dopo"=>"", "ora"=>"", "poi"=>"", "sempre"=>"", "mai"=>"", "presto"=>"", "tardi"=>"", "intanto"=>"", "frattanto"=>"", "talvolta"=>"", "spesso"=>"", "molto"=>"", "troppo"=>"", "poco"=>"", "più"=>"", "meno"=>"", "assai"=>"", "niente"=>"", "nulla"=>"", "alquanto"=>"", "altrettanto"=>"", "anche"=>"", "perfino"=>"", "persino"=>"", "altresì"=>"", "finanche"=>"", "abbastanza"=>"", "almeno"=>"", "ancora"=>"", "appunto"=>"", "attualmente"=>"", "certamente"=>"", "comunque"=>"", "altrove"=>"", "dove"=>"", "dovunque"=>"", "effettivamente"=>"", "forse"=>"", "generalmente"=>"", "inoltre"=>"", "insufficientemente"=>"", "inutilmente"=>"", "naturalmente"=>"", "no"=>"", "non"=>"", "nuovamente"=>"", "ovunque"=>"", "ovviamente"=>"", "piuttosto"=>"", "precedentemente"=>"", "probabilmente"=>"", "realmente"=>"", "realmente"=>"", "semplicemente"=>"", "sì"=>"", "solitamente"=>"", "soprattutto"=>"", "specificamente"=>"", "successivamente"=>"", "sufficientemente"=>"", "veramente"=>"", "lunedì"=>"", "martedì"=>"", "mercoledì"=>"", "giovedì"=>"", "venerdì"=>"", "sabato"=>"", "domenica"=>"", "gennaio"=>"", "febbraio"=>"", "marzo"=>"", "aprile"=>"", "maggio"=>"", "giugno"=>"", "luglio"=>"", "agosto"=>"", "settembre"=>"", "ottobre"=>"", "novembre"=>"", "dicembre"=>"", "pi"=>"", "h-h"=>"", "alcune"=>"", "alcuni"=>"", "alcuno"=>"", "altri"=>"", "altro"=>"", "certo"=>"", "chiunque"=>"", "ciascuno"=>"", "molti"=>"", "nessun"=>"", "nessuno"=>"", "ogni"=>"", "ognuno"=>"", "parecchi"=>"", "parecchio"=>"", "pochi"=>"", "qualche"=>"", "qualcosa"=>"", "qualcuno"=>"", "qualunque"=>"", "tanto"=>"", "tutti"=>"", "tutto"=>"", "qual"=>"", "quale"=>"", "quali"=>"", "quanto"=>"", "anno"=>"", "bene"=>"", "cosa"=>"", "cose"=>"", "data"=>"", "esempio"=>"", "male"=>"", "scelta"=>"", "vero"=>"", "via"=>"", "aperto"=>"", "attuale"=>"", "breve"=>"", "chiuso"=>"", "corto"=>"", "differente"=>"", "difficile"=>"", "dissimile"=>"", "diverso"=>"", "entrambe"=>"", "entrambi"=>"", "esterno"=>"", "fa"=>"", "facile"=>"", "falso"=>"", "grande"=>"", "inusuale"=>"", "inutile"=>"", "lungo"=>"", "impossibile"=>"", "improbabile"=>"", "insolito"=>"", "insufficiente"=>"", "maggiore"=>"", "maggior"=>"", "minore"=>"", "minor"=>"", "piccolo"=>"", "pieno"=>"", "possibile"=>"", "probabile"=>"", "pronto"=>"", "semplice"=>"", "siffatto"=>"", "simile"=>"", "sufficiente"=>"", "usuale"=>"", "utile"=>"", "vuoto"=>"", "interno"=>"", "mediante"=>"", "modo"=>"", "ovvio"=>"", "precedente"=>"", "primi"=>"", "propri"=>"", "proprio"=>"", "prossimo"=>"", "reale"=>"", "scelto"=>"", "soli"=>"", "solito"=>"", "solo"=>"", "soltanto"=>"", "specifico"=>"", "stessi"=>"", "stesso"=>"", "subito"=>"", "successivo"=>"", "super"=>"", "tale"=>"", "totale"=>"", "totali"=>"", "uguale"=>"", "uguali"=>"", "ulteriore"=>"", "ultimi"=>"", "ultimo"=>"", "vari"=>"", "vario"=>"", "verso"=>"", "sono"=>"", "è"=>"", "siamo"=>"", "siete"=>"", "ho"=>"", "hai"=>"", "ha"=>"", "abbiamo"=>"", "avete"=>"", "hanno"=>"", "mio"=>"", "tuo"=>"", "suo"=>"", "nostro"=>"", "vostro"=>"", "loro"=>"", "il"=>"", "lo"=>"", "la"=>"", "i"=>"", "gli"=>"", "le"=>"", "un"=>"", "un\'"=>"", "uno"=>"", "una"=>"", "di"=>"", "a"=>"", "da"=>"", "in"=>"", "con"=>"", "su"=>"", "per"=>"", "tra"=>"", "fra"=>"", "del"=>"",	"dello"=>"",  "della"=>"", 	"dei"=>"", 	"degli"=>"",  "delle"=>"",  "al"=>"",  "allo"=>"", 	"alla"=>"",  "ai"=>"", 	"agli"=>"",  "alle"=>"",  "dal"=>"",  "dallo"=>"",  "dalla"=>"",  "dai"=>"",  "dagli"=>"", 	"dalle"=>"",  "nel"=>"",  "nello"=>"", 	"nella"=>"",  "nei"=>"",  "negli"=>"", 	"nelle"=>"",  "col"=>"",  "coi"=>"",  "sul"=>"",  "sullo"=>"", 	"sulla"=>"",  "sui"=>"",  "sugli"=>"", 	"sulle"=>"", "a"=>"", "a"=>"",  "meno"=>"",  "che"=>"", "acciocché"=>"", "adunque"=>"", "affinché"=>"", "allora"=>"", "allorché"=>"", "allorquando"=>"", "altrimenti"=>"", "anche"=>"", "anco"=>"", "ancorché"=>"", "anzi"=>"", "anziché"=>"", "appena"=>"", "avvegna"=>"",  "che"=>"", "avvegnaché"=>"", "avvegnadioché"=>"", "avvengaché"=>"", "avvengadioché"=>"", "benché"=>"", "bensi"=>"", "bens"=>"", "che"=>"", "ché"=>"", "ciononostante"=>"", "comunque"=>"", "conciossiaché"=>"", "conciossiacosaché"=>"", "cosicché"=>"", "difatti"=>"", "donde"=>"", "dove"=>"", "dunque"=>"", "e"=>"", "ebbene"=>"", "ed"=>"", "embè"=>"", "eppure"=>"", "essendoché"=>"", "eziando"=>"", "fin"=>"", "finché"=>"", "frattanto"=>"", "giacché"=>"", "giafossecosaché"=>"", "imperocché"=>"", "infatti"=>"", "infine"=>"", "intanto"=>"", "invece"=>"", "laonde"=>"", "ma"=>"", "magari"=>"", "malgrado"=>"", "mentre"=>"", "neanche"=>"", "neppure"=>"", "no"=>"", "nonché"=>"", "nonostante"=>"", "né"=>"", "o"=>"", "ogniqualvolta"=>"", "onde"=>"", "oppure"=>"", "ora"=>"", "orbene"=>"", "ossia"=>"", "ove"=>"", "ovunque"=>"", "ovvero"=>"", "perché"=>"", "perciò"=>"", "pero"=>"", "perocché"=>"", "pertanto"=>"", "però"=>"", "poiché"=>"", "poscia"=>"", "purché"=>"", "pure"=>"", "qualora"=>"", "quando"=>"", "quindi"=>"", "se"=>"", "sebbene"=>"", "semmai"=>"", "senza"=>"", "seppure"=>"", "sia"=>"", "siccome"=>"", "solamente"=>"", "soltanto"=>"","talché"=>"", "ecco"=>"", "sta"=>"", "a"=>"", "an"=>"", "the"=>"", "shall"=>"", "should"=>"", "can"=>"", "could"=>"",
					"will"=>"", "would"=>"", "am"=>"", "is"=>"", "are"=>"", "we"=>"", "us"=>"", "has"=>"",
					"have"=>"", "had"=>"", "not"=>"", "yes"=>"", "no"=>"", "true"=>"", "false"=>"", "with"=>"",
					"to"=>"", "your"=>"", "more"=>"", "and"=>"", "in"=>"", "out"=>"", "login"=>"", "logout"=>"",
					"sign"=>"", "up"=>"", "coming"=>"", "going"=>"", "now"=>"", "then"=>"", "about"=>"",
					"contact"=>"", "my"=>"", "you"=>"", "go"=>"", "close"=>"", ""=>"", "of"=>"", "our"=>"");
			
			$count = array_diff_key($count, $grammar);
			
			arsort($count, SORT_DESC | SORT_NUMERIC);
			
			$htmlInfo["wordCount"] = $count;
			$htmlInfo["wordCountMax"] = array_slice($count, 0, 5, true);
		}
		
		if(!empty($htmlInfo["wordCount"]) && !empty($htmlInfo["keywords"])){
			$htmlInfo["compareMetaKeywords"] = $this->compareMetaWithContent(array_keys($htmlInfo["wordCount"]), $htmlInfo["keywords"]);
		}
		
		$h1headings = $xpath->query("//h1");
		$index = 0;
		foreach ($h1headings as $h1h){
			$htmlInfo["h1"][$index] = trim(strip_tags($h1h->textContent));
			$index++;
		}
		
		$h2headings = $xpath->query("//h2");
		$index = 0;
		foreach ($h2headings as $h2h){
			$htmlInfo["h2"][$index] = trim(strip_tags($h2h->textContent));
			$index++;
		}
		
		$htmlInfo["robots"] = $this->checkForFiles("robots.txt");
		$htmlInfo["sitemap"] = $this->checkForFiles("sitemap.xml");
		
		$htmlInfo["brokenLinkCount"] = 0;
		$anchors = $xpath->query("//a");
		if(!empty($anchors)){
// 			$htmlInfo["brokenLinkCount"] = $this->getBrokenLinkCount($anchors);
		}
		
		$htmlInfo["images"] = array();
		$imgs = $xpath->query("//img");
		if(!empty($imgs)){
			$htmlInfo["images"] = $this->imageAltText($imgs);
		}
		
		$htmlInfo["googleAnalytics"] = $this->findGoogleAnalytics($grabbedHTML);
		
		$htmlInfo["pageLoadTime"] = $this->getPageLoadTime();
		
		$htmlInfo["flashTest"] = FALSE;
		$flashExists = $xpath->query("//embed[@type='application/x-shockwave-flash']");
		if($flashExists->length !== 0){
			$htmlInfo["flashTest"] = TRUE;
		}
		
		$htmlInfo["frameTest"] = FALSE;
		$frameExists = $xpath->query("//frameset");
		if($frameExists->length !== 0){
			$htmlInfo["frameTest"] = TRUE;
		}
		
		$htmlInfo["css"] = array();
		$cssExists = $xpath->query("//link[@rel='stylesheet']");
		$htmlInfo["css"] = array_merge ($htmlInfo["css"], $this->cssFinder($cssExists));
		
		$htmlInfo["js"] = array();
		$jsExists = $xpath->query("//script[contains(@src, '.js')]");
		$htmlInfo["js"] = array_merge ($htmlInfo["js"], $this->jsFinder($jsExists));
		
		return $htmlInfo;
	}
	
	/**
	 * This function used to find all JS files
	 * @param {Array} $jsExists : JS exist count
	 * @return {Array} $push : JS result with js counts
	 */
	function jsFinder($jsExists){
		$push["jsCount"] = 0;
		$push["jsMinCount"] = 0;
		$push["jsNotMinFiles"] = array();
		
		if(!empty($jsExists)){
			foreach($jsExists as $ce){
				$push["jsCount"]++;
				if($this->formatCheckLinks($ce->getAttribute("src"))){
					$push["jsMinCount"]++;
				} else {
					array_push($push["jsNotMinFiles"], $ce->getAttribute("src"));
				}
			}
		}
		return $push;
	}
	
	/**
	 * This function used to find all CSS files
	 * @param {Array} $cssExists : CSS exist count
	 * @return {Array} $push : CSS result with css counts
	 */
	function cssFinder($cssExists){
		$push["cssCount"] = 0;
		$push["cssMinCount"] = 0;
		$push["cssNotMinFiles"] = array();
		$push["cssNotMinFilesLink"] = array();
		
		if(!empty($cssExists)){
			foreach($cssExists as $ce){
				$push["cssCount"]++;				
				if($this->formatCheckLinks($ce->getAttribute("href"))){
					$push["cssMinCount"]++;
					
				} else {
					array_push($push["cssNotMinFiles"], $ce->getAttribute("href"));
				}
			}
		}
		
		return $push;
	}
	
	/**
	 * This function used to check format checking for JS and CSS
	 * @param {String} $link : JS or CSS file link
	 * @return {Boolean} $result : TRUE/FALSE
	 */
	function formatCheckLinks($link){
		$cssFile = "";
		if(strpos($cssFile, '?') !== false){
			$cssFile = substr($link, strrpos($link, "/"), strrpos($link, "?") - strrpos($link, "/"));
		} else {
			$cssFile = substr($link, strrpos($link, "/"));
		}
		if (strpos($cssFile, '.min.') !== false) {
			return true;
		}else {
			return false;
		}
	}
	
	/**
	 * This function used to strip HTML tags from grabbed string
	 * @param {String} $str : HTML string to be stripped
	 * @return {String} $str : Stripped string
	 */
	function stripHtmlTags($str){
		$str = preg_replace('/(<|>)\1{2}/is', '', $str);
		$str = preg_replace(
				array(
						'@<head[^>]*?>.*?</head>@siu',
						'@<style[^>]*?>.*?</style>@siu',
						'@<script[^>]*?.*?</script>@siu',
						'@<noscript[^>]*?.*?</noscript>@siu',
				),
				"",
				$str );
		
		$str = $this->replaceWhitespace($str);
		$str = html_entity_decode($str);
		$str = strip_tags($str);
		return $str;
	}
	
	/**
	 * This function used to remove whitespace from string, recursively
	 * @param {String} $str : This is input string
	 * @return {String} $str : Output string, or recursive call
	 */
	function replaceWhitespace($str) {
		$result = $str;
		foreach (array(
				"  ","   ", " \t",  " \r",  " \n",
				"\t\t", "\t ", "\t\r", "\t\n",
				"\r\r", "\r ", "\r\t", "\r\n",
				"\n\n", "\n ", "\n\t", "\n\r",
		) as $replacement) {
			$result = str_replace($replacement, $replacement[0], $result);
		}
		return $str !== $result ? $this->replaceWhitespace($result) : $result;
	}
	
	/**
	 * This function use to get word count throughout the webpage
	 * @param array $phrases : This is array of strings
	 * @return array $count : Array of words with count - number of occurences
	 */
	function getWordCounts($phrases) {
		
		$counts = array();
		foreach ($phrases as $phrase) {
			$words = explode(' ', strtolower($phrase));
			
			$grammar = array("a", "an", "the", "shall", "should", "can", "could", "will", "would", "am", "is", "are",
					"we", "us", "has", "have", "had", "not", "yes", "no", "true", "false", "with", "to", "your", "more",
					"and", "in", "out", "login", "logout", "sign", "up", "coming", "going", "now", "then", "about",
					"contact", "my", "you", "of", "our");
			
			$words = array_diff($words, $grammar);
			
			foreach ($words as $word) {
				if(!empty(trim($word))){
					$word = preg_replace("#[^a-zA-Z\-]#", "", $word);
					if(isset($counts[$word])){
						$counts[$word] += 1;
					}else{
						$counts[$word] = 1;
					}
				}
			}
		}
		return $counts;
	}
	
	/**
	 * gets the inbounds links from a site
	 * @param string $url
	 * @param integer
	 */
	function googleSearchResult($url)
	{
		$url  = 'https://www.google.com/#q='.$url;
        $str  = $this->grabHTML($url);
        $data = json_decode($str);
	
        return (!isset($data->responseData->cursor->estimatedResultCount))
                ? '0'
                : intval($data->responseData->cursor->estimatedResultCount);
	}
	
	/**
	 * This function used to compare keywords with meta
	 * @param array $contentArray : This is content array
	 * @param string $kewordsString : This is meta keyword string
	 * @return array $keywordMatch : Match found
	 */
	function compareMetaWithContent($contentArray, $kewordsString){
		$kewordsString = strtolower(str_replace(',', ' ', $kewordsString));
		$keywordsArray = explode(" ", $kewordsString);
		$keywordMatch = array();
		foreach ($contentArray as $ca) {
			if(!empty(trim($ca)) && in_array($ca, $keywordsArray)){
				array_push($keywordMatch, $ca);
			}
		}
		
		/* $this->pre($contentArray);
		$this->pre($kewordsString); */
		
		return $keywordMatch;
	}
	
	/**
	 * This function is used to export requirements as PDF
	 * @param {String} $htmlInfo : This is HTML string which is to be print in PDF
	 * @param {String} $for : This website link for which we are generating report
	 */
	function exportSEOReportPDF($htmlInfo, $for) {
		set_time_limit ( 0 );
		ob_start();
		
		// $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf = new MYPDF ( PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );
	
		$fileName = $for;
		$htmlInfo["url"] = $for;
	
		if (! empty ( $htmlInfo )) {
			// set document information
			$pdf->SetCreator ( PDF_CREATOR );
			$pdf->SetAuthor ( 'CodeInsect' );
			$pdf->SetTitle ( "SEO Report" );
			$pdf->SetSubject ( 'SEO Report For ' );

			$logo = 'logo.png';

			// set default header data
			// $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 006', PDF_HEADER_STRING);
			$pdf->SetHeaderData ( $logo, 10, $for, "by CodeInsect" );

			// set header and footer fonts
			$pdf->setHeaderFont ( Array (
					PDF_FONT_NAME_MAIN,
					'',
					PDF_FONT_SIZE_MAIN
					) );
			$pdf->setFooterFont ( Array (
					PDF_FONT_NAME_DATA,
					'',
					PDF_FONT_SIZE_DATA
					) );

			// set default monospaced font
			$pdf->SetDefaultMonospacedFont ( PDF_FONT_MONOSPACED );

			// set margins
			$pdf->SetMargins ( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
			$pdf->SetHeaderMargin ( PDF_MARGIN_HEADER );
			$pdf->SetFooterMargin ( PDF_MARGIN_FOOTER );

			// set auto page breaks
			$pdf->SetAutoPageBreak ( TRUE, PDF_MARGIN_BOTTOM );

			// set image scale factor
			$pdf->setImageScale ( PDF_IMAGE_SCALE_RATIO );

			$pdf->AddPage ();
			
			$reqHTML = $this->getReadyHTML($htmlInfo);
			
			/* $this->pre($reqHTML);
			die; */
			
			// set font for utf-8 type of data
			$pdf->SetFont('freeserif', '', 12);
			
			$pdf->writeHTML ( $reqHTML, true, false, false, false, '' );
			$pdf->lastPage ();
		}
	
		$pdf->Output ( $fileName . '.pdf', 'D' );
	}
	
	/**
	 * This function is used to calculate simple load time of HTML page
	 */
	function getPageLoadTime(){
		if(!is_null($this->start) && !is_null($this->end)){
			return $this->end - $this->start;
		}else{
			return 0;
		}
	}
	
	/**
	 * This function used to clean the string with some set of rules
	 * @param {String} $string : String to be clean
	 * @return {String} $string : clean string
	 */
	function clean($string) {
		$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
		$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	
		$string  = preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
		return str_replace('-', ' ', $string);
	}
	
	/**
	 * Create HTML to print on PDF (or on page if you want to print on HTML page)
	 * Make Sure that HTML is correct, otherwise it will not print on PDF
	 * @param {Array} $htmlInfo : Array having total seo analysis
	 * @return {String} $html : Real html which is to be print 
	 */
	function getReadyHTML($htmlInfo){
	
		$html = '<div class="row">';
		$html .='<div class="col s12">';
		
		$html .= '<table class="highlight">';
		$html .= '<tbody>';
		
		
		/* if($htmlInfo["dnsReachable"] !== false){ */
		
			if( $htmlInfo["isAlive"]  == true){		
				
				/* Show Google search preview */
				$html .=	'<div class="row">';
				$html .=	'<div class="col s6 offset-s3">';
				$html .=	'<div class="card">';
				$html .=	'<div class="card-content white-text">';
				/* Google logo SVG */
				$html .=	'<span class="card-title center"><svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
				width="30" height="30"
				viewBox="0 0 48 48"
				style=" fill:#000000;"><g id="surface1"><path style=" fill:#FFC107;" d="M 43.609375 20.082031 L 42 20.082031 L 42 20 L 24 20 L 24 28 L 35.304688 28 C 33.652344 32.65625 29.222656 36 24 36 C 17.371094 36 12 30.628906 12 24 C 12 17.371094 17.371094 12 24 12 C 27.058594 12 29.84375 13.152344 31.960938 15.039063 L 37.617188 9.382813 C 34.046875 6.054688 29.269531 4 24 4 C 12.953125 4 4 12.953125 4 24 C 4 35.046875 12.953125 44 24 44 C 35.046875 44 44 35.046875 44 24 C 44 22.660156 43.863281 21.351563 43.609375 20.082031 Z "></path><path style=" fill:#FF3D00;" d="M 6.304688 14.691406 L 12.878906 19.511719 C 14.65625 15.109375 18.960938 12 24 12 C 27.058594 12 29.84375 13.152344 31.960938 15.039063 L 37.617188 9.382813 C 34.046875 6.054688 29.269531 4 24 4 C 16.316406 4 9.65625 8.335938 6.304688 14.691406 Z "></path><path style=" fill:#4CAF50;" d="M 24 44 C 29.164063 44 33.859375 42.023438 37.410156 38.808594 L 31.21875 33.570313 C 29.210938 35.089844 26.714844 36 24 36 C 18.796875 36 14.382813 32.683594 12.71875 28.054688 L 6.195313 33.078125 C 9.503906 39.554688 16.226563 44 24 44 Z "></path><path style=" fill:#1976D2;" d="M 43.609375 20.082031 L 42 20.082031 L 42 20 L 24 20 L 24 28 L 35.304688 28 C 34.511719 30.238281 33.070313 32.164063 31.214844 33.570313 C 31.21875 33.570313 31.21875 33.570313 31.21875 33.570313 L 37.410156 38.808594 C 36.972656 39.203125 44 34 44 24 C 44 22.660156 43.863281 21.351563 43.609375 20.082031 Z "></path></g></svg></span>';
								if(isset($htmlInfo["titletext"])){
									$html .= '<span style="color:#609;font-size:13px;"><u>'.$htmlInfo["titletext"].'</u></span><br>';
								}
								$html .= '<span style="color:#006621;font-size:11px;">'.$this->addScheme($htmlInfo["url"], "http://").'</span><br>';
								if(isset($htmlInfo["description"])){
									$html .= '<span style="color:#6A6A6A;font-size:11px;">'.$htmlInfo["description"].'</span>';
								}
								
				$html .=	'</div>
							</div>
							</div>
							</div>';
				
				/* Check if the webpage is on line */
				$html .= '<tr>';
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Ok, questa pagina è on-line.';			
					$html .= '</td>';
				$html .= '</tr>';
		
				/* Check webpage speed */
				$html .= '<tr>';
				if($htmlInfo["pageLoadTime"] == 0){
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Ci sono dei problemi con il server: non riesco a stimare il tempo di caricamento della tua pagina.<br><br><div><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Google considera ottimale un tempo di caricamento inferiore a 3 secondi.</span></div>';
					$html .= '</td>';
				}else if($htmlInfo["pageLoadTime"] < 3){
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Molto bene! La tua pagina si carica in '.round($htmlInfo["pageLoadTime"], 2).' secondi.';
					$html .= '</td>';
				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Non ci siamo! La tua pagina si carica in '.round($htmlInfo["pageLoadTime"], 2).' secondi.<br><br><div><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Google considera ottimale un tempo di caricamento inferiore a 3 secondi.</span></div>';
					$html .= '</td>';
				}
				$html .= '</tr>';


				/* Check title tag */
				$html .= '<tr>';
				if(isset($htmlInfo["titletext"])){
					if(strlen($htmlInfo["titletext"]) <= 60 ){
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Bene! Il meta title della pagina è lungo ' .strlen($htmlInfo["titletext"]).' caratteri:<br><br><span style="font-size:80%;"><strong>'.$htmlInfo["titletext"].'</strong></span>';
						$html .= '</td>';
					}else{
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Il meta title della pagina è lungo ' .strlen($htmlInfo["titletext"]).' caratteri:<br><br><span style="font-size:80%;"><strong>'.$htmlInfo["titletext"].'</strong></span><br><br><div><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Il meta title aiuta i motori di ricerca a capire meglio quale sia il contenuto della pagina indicizzata. Google considera ottimale un title tag di 50-60 caratteri.</span></div>';
						$html .= '</td>';
					}

				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Nella pagina non è presente alcun meta title.<br><br><div><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Il meta title aiuta i motori di ricerca a capire meglio quale sia il contenuto della pagina indicizzata. Google considera ottimale un title tag di 50-60 caratteri.</span></div>';
					$html .= '</td>';
				}
				$html .= '</tr>';

				
				/* Check description tag */
				$html .= '<tr>';
				if(isset($htmlInfo["description"])){
					
					if(strlen($htmlInfo["titletext"]) == 0 ){
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'La descrizione della pagina è lunga ' .strlen($htmlInfo["description"]).' caratteri:<br><br><span style="font-size:80%;"><strong>'.$htmlInfo["description"].'</strong></span><br><br><div><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Una descrizione aiuta i motori di ricerca a capire meglio quale sia il contenuto della pagina indicizzata. Google considera ottimale una descrizione di 150-160 caratteri.</span></div>';
						$html .= '</td>';
					}else if(strlen($htmlInfo["titletext"]) <= 160 ){
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Bene! La descrizione della pagina è lunga ' .strlen($htmlInfo["description"]).' caratteri:<br><br><span style="font-size:80%;"><strong>'.$htmlInfo["description"].'</strong></span>';
						$html .= '</td>';
					}else{
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'La descrizione della pagina è lunga ' .strlen($htmlInfo["description"]).' caratteri:<br><br><span style="font-size:80%;"><strong>'.$htmlInfo["description"].'</strong></span><br><br><div><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Una descrizione aiuta i motori di ricerca a capire meglio quale sia il contenuto della pagina indicizzata. Google considera ottimale una descrizione di 150-160 caratteri.</span></div>';
						$html .= '</td>';
					}
				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Nella pagina non è presente alcuna descrizione.<br><br><div><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Una descrizione aiuta i motori di ricerca a capire meglio quale sia il contenuto della pagina indicizzata. Google considera ottimale un title tag di 150-160 caratteri.</span></div>';
					$html .= '</td>';
				}
				$html .= '</tr>';
				

				/* Check most common keywords */	
				$html .= '<tr>';
				if(!empty($htmlInfo["wordCountMax"])){
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Ecco le keyword più usate nella pagina analizzata:<br><br>';
					foreach($htmlInfo["wordCountMax"] as $wordMaxKey => $wordMaxValue){
						$html .= '<a class="waves-effect waves-light btn-small btn-margin">'.$wordMaxKey.' ('.$wordMaxValue.' volte)</a>';}
						$html .='<br><br><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Anche se i moderni motori di ricerca non utilizzano più la densità delle keyword come fattore di ranking, è tuttavia utile tenere presente questo elemento poiché, attraverso di esso, i bot riescono a comprendere meglio l\'argomento della pagina.</span>';
					$html .= '</td>';
				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'La pagina web analizzata sembra non avere keyword.<br><br><div><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Anche se i moderni motori di ricerca non utilizzano più la densità delle keyword come fattore di ranking, è tuttavia utile tenere presente questo elemento poiché, attraverso di esso, i bot riescono a comprendere meglio l\'argomento della pagina.</span></div>';
					$html .= '</td>';
				}
				$html .= '</tr>';
				
				
				/* H1 Headings status */	
				$html .= '<tr>';
				if(isset($htmlInfo["h1"])){
					if(count(array_filter($htmlInfo["h1"])) == 1){
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Nella tua pagina c\'è un h1:<br><br>';
						foreach($htmlInfo["h1"] as $h1){
								$html .= '<a class="waves-effect waves-light btn-small btn-margin remove_empty">'.$h1.'</a>';}
								$html .= '</td>';
					}else{
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Nella tua pagina ci sono ' .count(array_filter($htmlInfo["h1"])).' h1:<br><br>';
						$html .= '<a class="open_h1" href="javascript:void(0)">Mostrali tutti</a>';
						$html .= '<div class="show_h1">';
						foreach($htmlInfo["h1"] as $h1){
								$html .= '<a class="waves-effect waves-light btn-small btn-margin remove_empty">'.$h1.'</a>';}
								$html .= '</div>';
								$html .='<br><br><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Troppi H1 peggiorano la comprensione della pagina da parte dei motori di ricerca. Utilizza anche gli altri heading per creare un ordine gerarchico all\'interno della pagina</span>';
								$html .= '</td>';
					}
				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Nella pagina web analizzata sembra non ci siano h1.<br><br><div><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Gli heading aiutano i motori di ricerca a comprendere meglio l\'argomento della pagina.</span></div>';
					$html .= '</td>';
				}
				$html .= '</tr>';
				

				
				/* H2 Headings status */	
				$html .= '<tr>';
				if(isset($htmlInfo["h2"])){
					$html .= '<tr>';
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Nella tua pagina ci sono ' .count(array_filter($htmlInfo["h2"])).' h2.<br><br>';
					$html .= '<a class="open_h2" href="javascript:void(0)">Mostrali tutti</a>';
						$html .= '<div class="show_h2">';
						foreach($htmlInfo["h2"] as $h2){
								$html .= '<a class="waves-effect waves-light btn-small btn-margin remove_empty">'.$h2.'</a>';}
						$html .= '</div>';
					$html .= '</td>';
				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Nella pagina web analizzata sembra non ci siano h2.<br><br><div><span style="font-size:80%;"><i class="material-icons tips">help_outline</i>Gli heading aiutano i motori di ricerca a comprendere meglio l\'argomento della pagina.</span></div>';
					$html .= '</td>';
				}
				$html .= '</tr>';


					/* Check robots.txt */		
					$html .= '<tr>';
					if($htmlInfo["robots"] == 200){
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Complimenti! Il tuo sito utilizza un file robots.txt. <a href="http://'.$htmlInfo["url"].'/robots.txt" target="_blank">Eccolo qui.</a>';
						$html .= '</td>';	
					}else{
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Attenzione! Sul tuo sito non è presente un file robots.txt';
						$html .= '</td>';
					}
					$html .= '</tr>';


					/*Check sitemap */
					$html .= '<tr>';
					if($htmlInfo["robots"] == 200){
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Benissimo! Il tuo sito ha una sitemap. <a href="http://'.$htmlInfo["url"].'/sitemap.xml" target="_blank">La puoi visualizzare qui.</a>';
						$html .= '</td>';	
					}else{
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Attenzione! Sul tuo sito non è presente una sitemap.';
						$html .= '</td>';
					}
					$html .= '</tr>';
			
				
				/* Broken links test */
				$html .= '<tr>';
					if(!empty($htmlInfo["brokenLinkCount"]) && $htmlInfo["brokenLinkCount"] != 0){
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Attenzione! Sul tuo sito sono presenti link rotti:' .$htmlInfo["brokenLinkCount"]. '<span></span>';
						$html .= '</td>';
					}else{
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Benissimo! in questa pagina sembrano non esserci link rotti!';
						$html .= '</td>';
					}
					$html .= '</tr>';
				
				
					/* Image Alt Test */	
					$html .= '<tr>';
					if(!empty($htmlInfo["images"])){
						if(isset($htmlInfo["images"]["totImgs"]) && $htmlInfo["images"]["totImgs"] != 0){
							if($htmlInfo["images"]["diff"] <= 0){
								$html .= '<td style="width: 20%;">';
								$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
								$html .= '</td>';
								$html .= '<td style="width: 80%;">';
								$html .= 'Congratulazioni. Nella pagina ci sono ' .$htmlInfo["images"]["totImgs"]. ' immagini e tutte hanno un testo alternativo!';
								$html .= '</td>';
							}else{
								if($htmlInfo["images"]["diff"] == 1){
								$html .= '<td style="width: 20%;">';
								$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
								$html .= '</td>';
								$html .= '<td style="width: 80%;">';
								$html .= 'Ho trovato ' .$htmlInfo["images"]["totImgs"]. ' immagini nella pagina e ' .$htmlInfo["images"]["diff"]. ' è priva di testo alternativo.';
								}else{
								$html .= '<td style="width: 20%;">';
								$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
								$html .= '</td>';
								$html .= '<td style="width: 80%;">';
								$html .= 'Ho trovato ' .$htmlInfo["images"]["totImgs"]. ' immagini nella pagina e ' .$htmlInfo["images"]["diff"]. ' sono prive di testo alternativo.';
								}
							}
						}else{
							$html .= '<td style="width: 20%;">';
							$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
							$html .= '</td>';
							$html .= '<td style="width: 80%;">';
							$html .= 'Nella pagina non sono presenti immagini!';
						}
					}else{
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Nella pagina non sono presenti immagini!';
					}
				$html .= '</tr>';
				
				
				/* Google Analytics */
				$html .= '<tr>';
				if($htmlInfo["googleAnalytics"] == true){
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Congratulazioni! Sulla pagina web è attivo il tracciamento di Google Analytics.';
					$html .= '</td>';
				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'In questa pagina non è attivo il tracciamento di Google Analytics.';
					$html .= '</td>';
				}
				$html .= '</tr>';


				/* Favicon */				
				$html .= '<tr>';
				if(isset($htmlInfo["shortcut icon"]) || isset($htmlInfo["icon"])){
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Ottimo. Questa pagina ha una favicon.';
					$html .= '</td>';
				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'In questa pagina non è presente alcuna favicon.';
					$html .= '</td>';
				}
				$html .= '</tr>';


				/* Flash objects */				
				$html .= '<tr>';
				if($htmlInfo["flashTest"] == true){
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Su questa pagina sono attivi degli oggetti Flash. Flash è una tecnologia ormai obsoleta, non ottimizzata per display mobili e difficilmente interpretabile dai crawler.';
					$html .= '</td>';
				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Ottimo! In questa pagina non ci sono oggetti Flash.';
					$html .= '</td>';
				}
				$html .= '</tr>';

				
				/* Frames */				
				$html .= '<tr>';
				if($htmlInfo["frameTest"] == true){
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Attento! Su questa pagina sono presenti dei frames!';
					$html .= '</td>';
				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons success">check_circle_outline</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Molto bene! Su questa pagina non sono presenti frames.';
					$html .= '</td>';
				}
				$html .= '</tr>';
				
				
				/* CSS minification */
				$cNMF = '';
				$cNMF = str_replace('http//', 'http://', $cNMF);
				$html .= '<tr>';
				if(!empty($htmlInfo["css"])){
					if($htmlInfo["css"]["cssCount"] > 0){
							$html .= '<td style="width: 20%;">';
							$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
							$html .= '</td>';
							$html .= '<td style="width: 80%;">';
							$html .= 'La pagina ha ' .$htmlInfo["css"]["cssCount"]. ' file CSS esterni. ';
						if($htmlInfo["css"]["cssMinCount"] > 0){
							$html .= 'Di questi, '.$htmlInfo["css"]["cssMinCount"].' file sono minificati';
						} else{
							$html .= 'Nessuno di questi è minificato.';
						}
							
						if(!empty($htmlInfo["css"]["cssNotMinFiles"])){
							$html .= '<br>Ecco i file da minificare:<br>';
							foreach($htmlInfo["css"]["cssNotMinFiles"] as $cNMF){
								$html .= '<a href="' .$cNMF.'" target="_blank">' .$cNMF. '</a><br>';
								}
						}
					}
					else{
						$html .= '<td style="width: 20%;">';
						$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
						$html .= '</td>';
						$html .= '<td style="width: 80%;">';
						$html .= 'Nella pagina non sono presenti file CSS esterni.';
						$html .= '</td>';
						}
				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Nella pagina non sono presenti file CSS esterni.';
					$html .= '</td>';
				}
				$html .= '</tr>';


				/* JS minification */
				$jNMF = '';
				$jNMF = str_replace('http//', 'http://', $jNMF);
				$html .= '<tr>';
				if(!empty($htmlInfo["js"])){
					if($htmlInfo["js"]["jsCount"] > 0){
						$html .= '<td style="width: 20%;">';
							$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
							$html .= '</td>';
							$html .= '<td style="width: 80%;">';
							$html .= 'La pagina ha ' .$htmlInfo["js"]["jsCount"]. ' file JS esterni. ';
						if($htmlInfo["js"]["jsMinCount"] > 0){
							$html .= 'Di questi, '.$htmlInfo["js"]["jsMinCount"].' file JS sono minificati.';
						} else{
							$html .= 'Nessuno di questi è minificato.';
						}
					
						if(!empty($htmlInfo["js"]["jsNotMinFiles"])){
							$html .= '<br>Ecco i file da minificare:<br>';
							foreach($htmlInfo["js"]["jsNotMinFiles"] as $jNMF){
								$html .= '<a href="' .$jNMF.'" target="_blank">' .$jNMF. '</a><br>';
							}
						}
					}
					else{
						$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Nella pagina non sono presenti file JS esterni.';
					$html .= '</td>';
					}
				}else{
					$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons warning">error_outline</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Nella pagina non sono presenti file JS esterni.';
					$html .= '</td>';
				}
				$html .= '</tr>';
				
			} else {
				$html .= '<tr>';
				$html .= '<td style="width: 20%;">';
					$html .= '<i style="align:left;" class="material-icons failed">highlight_off</i>';
					$html .= '</td>';
					$html .= '<td style="width: 80%;">';
					$html .= 'Questo sito non esiste!';
					$html .= '</td>';
					$html .= '</tr>';
			}
			
		
		
		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';
		$html .= '</div>';
		
		
		return $html;
	}
	
}