<?php
/*
NanoSpell TinyMCE Spellchecker for PHP 
Copyright tinymcespellcheck.com - All rights reserved
*/

$myLicence="
	NanoSpell TinyMCE Spellchecker for PHP 
	Copyright tinymcespellcheck.com - All rights reserved
	
	
	Built as a mod to PHPSpellCheck - www.phpspellcheck.com

	This is not open source software - de-obfuscation of this source breaks the license agreement for this software.
	
	I can see that you are both smart and curious, so... please do not distribute this code - nor any derivative works.	
	
	This code has not been strongly obfuscated for a good reason - to avoid commercial PHP bytecode encoders because they hamper compatibility and add proprietary DLLs to your PHP servers
	
	I am an independent software developer with dyslexia trying to earn an honest living
		
	Z:  webspellchecking @ gmail com
";


 function strSmallInt($string){
	$total=0;
	for($i=0; $i<strlen($string);$i++){
	$total += ($i+$i%6+ord(substr($string,$i,1)))	;
	}	
return 	7+($total % 22);
}

function int8Str($string,$seed){
	$out="";
	$total=$seed;

	for($i=0; $i<strlen($string)/2;$i++){
	$total += ($i+$i%6+ord(substr($string,$i,1)))	;
	$char = substr("1234567890QWERTYUPLKJHGFAZXCVNM",	$total % strlen("1234567890QWERTYUPLKJHGFAZXCVNM"),1);
	$out.=$char;
	}	
return 	$out;

}
function init(&$key){

if (strpos($key,'TRIAL') !== false) {
   $key = "TRIAL";
}else{
	$keys = explode(".",$key);
 
				$x = $keys[6];
				$keys[6]=$keys[3];
				$keys[3] = $x;
			
				
	if($keys[0]=="TINYMCE-SPELL" &&$keys[count($keys)-1]== int8Str($keys[0].$keys[2],strSmallInt($keys[1]))){
		$keys[0]="LIVESPELL";
		$x = $keys[4];
		$keys[4]=$keys[3];
		$keys[3] = $x;
		
		
		array_pop($keys);
		$key = implode("-",$keys);
	}else{
		 $key = "TRIAL";
	}
}
}

error_reporting(E_ERROR);
		
class PHP_MCESpellCheck{
	public $DictionaryPath = "dictionaries/" ;
	public $SaveToCentralDictionary="custom.txt";
	private $G_DictArray = array();
	private $G_METArray = array();
	private $G_PosArray = array();
	private $G_ContextArray = array();
	public $IgnoreAllCaps = true;
	public $IgnoreNumeric = true;
	public $CaseSensitive = false;
	public $SuggestionTollerance=1;
	public $LicenceKey = "";
	private $Suggestions_CACHE = array();
	private $unlocked = false;
	private $_unName = "LIVESPELL";
	private $_phpName = "PHPSPELL";
	private	$validReg = "1234567890QWERTYUPLKJHGFDAZXCVNM";

	private function randstr($len){
	$out="";
	while($len--){
	$index = rand(0,strlen($this->validReg)-1);
	$out.=substr($this->validReg,$index,1)	;
	}
	return $out;	
	}	

	private function strInteger($string){
		$total=0;
		for($i=0; $i<strlen($string);$i++){
		$total += ($i+$i%5+ord(substr($string,$i,1)))	;
		}	
	return 	1+($total % 78);
	}

	
	public function didYouMean($str){
		$tokens = $this->tokenizeString($str) ;
	 
	
		$changed = false;
		for ($i=0;$i<count($tokens);$i++){
	
	$token = $tokens[$i];
	
	 
			if($this->isWord($token)){
	
		
			if(!$this->SpellCheckWord($token)){
				$sug = $this->Suggestions($token);

				if(count($sug)>0){
		
					$tokens[$i] = $sug[0];
					$changed = true;
				}else{
						$changed = true;
						$tokens[$i] = "";
					
				}
				
				}	
				
				}
			}
		return $changed?implode("",	$tokens):"";
		}
	public function tokenizeString($str){
		
		$pattern = 	"/(\&amp\;[a-zA-Z0-9]{1,6}\;)|(\&[a-zA-Z0-9]{1,6}\;)| ([a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4})|(<\/?\w+[^>]*>)|([a-zA-Z]{2,5}:\/\/[^\s]*)|(www\.[^\s]+[\.][a-zA-Z-]{2,4})|([\w'`¥íë^x81-\xFF]+)|([^\s]+[\.][a-zA-Z-]{2,4})|([\w]+)/i";
		
		$tokens = preg_split($pattern, $str, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY  );
		
		return $tokens;
		}	
		
	private function isWord( $str ){
	$pattern = 	"/^([\w'`¥íë^x81-\xFF]+)$$/i";
	return preg_match ($pattern,$str);
	}
	
	
	private function getCase($word){
	$singleton = strlen($word)==1;
	
    if ($word == strToUpper($word))
    {
        if ($singleton)
        {
            return 3;  // Sentence = 3
        }
        return 2; // Upper = 2
    }

	  if (!$singleton)
      {
          if ($word[0] == strtoupper($word[0]))
          {
              return 3;
          }
      }
      return 0;
	
	}
	
	private function setCase($word, $wordCase){
		   if ($wordCase < 2 ) { return $word ;}
           if ($wordCase ==  2) { return strToUpper($word) ;}
		
		   if ($wordCase == 3)
              {
				$word = strToLower($word);
				$word[0] = strToUpper($word[0]);
              
              }
		
			return $word;
	}
	

	
	public// -- Function Name : Suggestions
	// -- Params :  $word
	// -- Purpose :
	function Suggestions( $word ){
		 $wordorigional = $word;
		if(!$this->unlocked){		
		$this->unlocked = $this->LoadPro($this->LicenceKey);
		}
		if(!$this->unlocked){
			if((117+$this->strInteger($word)) % ($this->Locael()) == 1){
		return array("*Unlicensed Trial*","Please register online") ;	
		}
			
		}
		
			
		if (array_key_exists(strtolower($word),$this->GEnforced)){
					return $this->GEnforced [strtolower($word)];
			}
		if(array_key_exists($word,$this->Suggestions_CACHE)){
			//return $this->Suggestions_CACHE [$word];
		}
		
		$cc = $this->CorrectCase($word, false);
		
		
		if($cc){
		 
		return array($cc) ;
		
			}
			
		$mycase = $this->getCase($word);	
	
		$word = strtolower($word);
	
		$M = $this -> arrMetaPhones( $word );
	
		$N = array();
		$N = $this -> arrNearMiss( $word );
		
		$All = array_merge( $M , $N );
		$All = array_unique( $All );
		$All = $this -> DistSort( $word, $All );
		
	          if (array_key_exists(strtolower($word),$this->GCommonTypos)){
					array_unshift($All,$this->GCommonTypos[strtolower($word)]);
				   $All = array_unique( $All );
	          }
	
		if($mycase>0){
			foreach($All as & $A){
				$A = $this->setCase($A, $mycase);	
			}
			
		}
		
		$this->Suggestions_CACHE [$wordorigional] = $All;
	
		return $All;
	}


	private
	function is_s_case($w){
		return $w [0] ===strtoupper($w [0]);
	}

	public
	function SetContext( $tokens ){
		$this->G_ContextArray = $tokens ;
	}

	public
	function CorrectCase($word, $bin_start_of_sentence){
		
		$dic_index_U = $this -> DicIndex( strtoupper( $word ) );
		$dic_index_l = $this -> DicIndex( strtolower( $word ) );
		$G_DictArray = &$this -> G_DictArray;
		$dictkeys = array_keys( $G_DictArray );
		$out = '';
		for( $i = 0; $i<count( $dictkeys ); $i++ ){
			$dictkey=$dictkeys [$i];
			
		 if(array_key_exists($dic_index_l, $G_DictArray [$dictkey] )){
			$r=$this->search_array_caseless( $word, $G_DictArray [$dictkey] [$dic_index_l]);
		}
			if($r){
				$r = array_values($r);
				$out=$r [0];
				break;
			}
 			
			if(array_key_exists($dic_index_U, $G_DictArray [$dictkey] )){
			$r=$this->search_array_caseless( $word, $G_DictArray [$dictkey] [$dic_index_U]);
		}
			if($r){
				$r = array_values($r);
				$out=$r [0];
				break;
			}

		}

		
		if($out&&$bin_start_of_sentence){
			$out [0]=strtoupper($out [0]);
		}

		
		return $out;
	}

	private
	function clear_CACHE(){
		unset($this->SimpleSpellCache_UNCASED,$this->SimpleSpellCache_CASED,$this->Suggestions_CACHE);
		$this->SimpleSpellCache_UNCASED = array();
		$this->SimpleSpellCache_CASED= array();
		$this->Suggestions_CACHE= array();
	}

	public// -- Function Name : LoadDictionary
	// -- Params :  $id
	// -- Purpose :
	function LoadDictionary( $id ){
		$G_DictArray = &$this -> G_DictArray;
		$DictionaryPath = &$this -> DictionaryPath;
		
		if( array_key_exists (  $id , $G_DictArray )){
		if( is_array( $G_DictArray [$id] ) ){
			return true;
		}
	}

		$filePath = $DictionaryPath . $id . ".dic";
		
		if( !file_exists( $filePath ) ){
			return false;
		}

		$strWholeDict = file_get_contents( $filePath );
		return $this -> DecypherStrDict( $id, $strWholeDict );
	}

	public// -- Function Name : ListLiveDictionaries
	// -- Params :
	// -- Purpose :
	function ListLiveDictionaries(){
		$G_DictArray = &$this -> G_DictArray;
		
		$list = array_keys( $G_DictArray );
		sort($list);
		
		return ( $list );
	}

	public// -- Function Name : ListDictionaries
	// -- Params :
	// -- Purpose :
	function ListDictionaries(){
		$DictionaryPath = &$this -> DictionaryPath;
		$G_METArray = &$this -> G_METArray;
		$G_PosArray = &$this -> G_PosArray;
		$myDirectory = opendir( $DictionaryPath );
		$dirArray = array();
		while( $entryName = readdir( $myDirectory ) ){
			
			if( strpos( $entryName, ".dic" ) === strlen( $entryName )-4 ){
				$dirArray  []= substr($entryName,0,strlen( $entryName )-4 );
			}

		}

		closedir( $myDirectory );
		return $dirArray;
	}

	public// -- Function Name : LoadUserDictionary
	// -- Params :  $ArrWholeDict
	// -- Purpose :
	function LoadUserDictionary( $ArrWholeDict ){
		$this -> BuildDictionary( $ArrWholeDict, "SPELL.DICT.USER" );
	}

	public// -- Function Name : LoadCustomDictionary
	// -- Params :  $filePath
	// -- Purpose :
	function LoadCustomDictionary( $filePath ){
		
		if( !file_exists( $filePath ) ){
			$filePath = $this->DictionaryPath.$filePath;
		}

		
		if( !file_exists( $filePath ) ){
			return false;
		}

		$key = "APP_CUSTOM_" . substr( md5( $filePath ), 0, 5 );
		$strWholeDict = file_get_contents( $filePath );
		$strWholeDict = preg_replace( "/\s+/", "\n", $strWholeDict );
		$this -> BuildDictionary( explode( "\n", $strWholeDict ), $key );
		
	}
	
	
	function LoadCommonTypos( $filePath ){
		if( !file_exists( $filePath ) ){
			$filePath = $this->DictionaryPath.$filePath;
		}

		if( !file_exists( $filePath ) ){
			return false;
		}

		$strWholeDict = file_get_contents( $filePath );
		$out = explode( "\n", trim( $strWholeDict) );
	 
			
		$this -> BuildCommonTypos( $out );
		
		}
	
		function LoadEnforcedCorrections( $filePath ){
		
		if( !file_exists( $filePath ) ){
			$filePath = $this->DictionaryPath.$filePath;
		}

		if( !file_exists( $filePath ) ){
			return false;
		}

		$strWholeDict = file_get_contents( $filePath );
	//	$strWholeDict = preg_replace( "/\s+/", "\n", $strWholeDict );
	
	
		$out = explode( "\n", trim( $strWholeDict) );
	 
			
		$this -> BuildEnforcedCorrections( $out );
	}
	
	public $GEnforced = array();
	
	private function BuildEnforcedCorrections($array){
		foreach ($array as $line){
			//USA--> United States Of America || United States Army
			list($key , $delimresults) = explode("-->" , $line);
			$key = trim($key);
			$delimresults = trim($delimresults);
		    $lineresults =  explode("||", $delimresults);
			for ($i=0;$i<count($lineresults);$i++){
				$lineresults[$i] = trim	($lineresults[$i]);
				}
			$this->GBanned[strtolower($key)] = false;
			$this->GEnforced[strtolower($key)] = $lineresults;
		
		}
	
		}
		private $GCommonTypos = array();
		public function BuildCommonTypos($array){
		
			foreach ($array as $line){
			$linea = explode("-->" , $line);
			if(count($linea)==2){
			list($key , $value) = $linea;
			$key = trim($key);
			$value = trim($value);
		
			$this->GCommonTypos[strtolower($key)] = $value;
		   }}
		 
			}
	
	public// -- Function Name : LoadCustomDictionary
	// -- Params :  $filePath
	// -- Purpose :
	function LoadCustomBannedWords( $filePath ){
		
		if( !file_exists( $filePath ) ){
			$filePath = $this->DictionaryPath.$filePath;
		}
		
		if( !file_exists( $filePath ) ){
			return false;
		}

		$strWholeDict = file_get_contents( $filePath );
		$strWholeDict = preg_replace( "/\s+/", "\n", $strWholeDict );
		$this -> AddBannedWords( explode( "\n", $strWholeDict ) );
	}
	
	public $GBanned = array();
	
	
	private function hashstr($string,$seed){
		$out="";
		$total=$seed;

		for($i=0; $i<strlen($string);$i++){
			$total += ($i+$i%3+ord(substr($string,$i,1)))	;
		$char = substr($this->validReg,	$total % strlen($this->validReg),1);
		$out.=$char;
		}	
	return 	$out;

	}
	
	public function
	AddBannedWords($array){
	
	foreach ($array as $key){
		$this->GBanned[strtolower($key)] = false;
		}
	
	}

	public// -- Function Name : LoadCustomDictionaryFromURL
	// -- Params :  $filePath
	// -- Purpose :
	function LoadCustomDictionaryFromURL( $filePath ){
		
				if( !substr_count( $filePath, "://" ) ){
					
			if( !file_exists( $filePath ) ){
				return false;
			}

			$filePath = $this -> filepath2url( $filePath );
		}

		$key = "APP_CUSTOM_" . substr( md5( $filePath ), 0, 5 );
		$strWholeDict = file_get_contents( $filePath );
		$strWholeDict = ereg_replace( " [ [:space:]]+", "\n", $strWholeDict );
		$this -> BuildDictionary( explode( "\n", $strWholeDict ), $key );
	}

 
	private static// -- Function Name : cleanPunctuation
	// -- Params :  $word
	// -- Purpose :
	function cleanPunctuation( $word ){
	
		$strNatural = array( '\"' , "'" , "-","-","'","~","`","'" );
		return str_replace( $strNatural, "", $word );
	}
	
	function SafeCleanPunctuation( $word ){
		
		$strFalse = array( '\"' , "'" , "-","-","'","~","`","'" );
		$strNatural = array( '\'' , "'" , " "," ","'","-","'","'" );
		return str_replace(  $strFalse,$strNatural, $word );
	}
	
	
	private static// -- Function Name : stripVowels
	// -- Params :  $word
	// -- Purpose :
	function stripVowels( $word ){
		$strOdd = explode( ",", "a,e,i,o,u,A,E,I,O,U" );
		$strNatural = "";
		return str_replace( $strOdd, $strNatural, $word );
	}
	
	public
	function SpellCheckWord ($word){
		
		
		if($this->IgnoreNumeric && strpbrk (  $word ,  "0123456789.:/" ) ){
			return true;
		}
		if( $this->IgnoreAllCaps && (strtoupper($word) === $word) && (strtoupper($word) != strtolower($word) )){
			return true ;
		}

		
		if( strtoupper($word) == strtolower($word)){
			return true;
		}

		$word = $this->SafeCleanPunctuation($word);
		$wordD = $this->DeCapitalise( $word);
		$wordU = strtoupper($word);
		/* CASE IN HERE FOR BLOCK CAPS */
		if($wordU==$word && $this->SimpleSpell( strtolower($word),  true)){
			return true;
		}
		
		
			return ($this->SimpleSpell( $word,  !$this->CaseSensitive) || $this->SimpleSpell($wordD,  !$this->CaseSensitive) );
		

	}
	
		public
	function ErrorTypeWord ($word){
		
			if(!$this->unlocked){		
			  $this->unlocked = $this->LoadPro($this->LicenceKey);
			}
			
		if (array_key_exists(strtolower($word),$this->GEnforced)){
					return "E";	
		}
		
		if (array_key_exists(strtolower($word),$this->GBanned)){
					return "B";
					}	
						if(!$this->unlocked){
										if(($this->strInteger($word) +117) % ($this->Locael()) == 1){	
											return "X";
												}
												}
		
		if($this->SimpleSpell( $word, true )){
			return "C";
					}
			
		 		return "S";			

	}

	private function Locael() {

			 if(  $_SERVER['HTTP_HOST']=="localhost" || $_SERVER['HTTP_HOST']=="127.0.0.1"){
				return 256;
			}
			return 5;
                }


	public// -- Function Name : SpellCheckAndSpaces
	// -- Params :  $string, $b_ignore_case
	// -- Purpose :
	function SpellCheckAndSpaces( $string, $b_ignore_case ){
		
			if(!$this->unlocked){		
		       	$this->unlocked = $this->LoadPro($this->LicenceKey);
			}
		if( substr_count( $string, " " ) == 0 ){
			return $this -> SimpleSpell( $string, $b_ignore_case );
		}

		$arrwords = str_word_count( $string, 1, "¿¡¬√ƒ≈‡·‚„‰Â»… ÀËÈÍÎ“”‘’÷ÛÙıˆ¯Ÿ⁄€‹˘˙˚¸ü›˝ˇå;ú;∆Êﬂäöö—Ò–ﬁ˛ˇ'" );
		foreach( $arrwords as $word ){
			
			if(strlen($word)==0){
				return false;
			}

			
			if( ! $this -> SimpleSpell( $word, $b_ignore_case ) ){
				return false;
			}

		}

		return true;
	}

	private// -- Function Name : arrNearMiss
	// -- Params :  $word
	// -- Purpose :
	function arrNearMiss( $w ){
		//$word = strtolower( $word );
		
		
		$results = array();
		$strTry = "abcdefghijklmnopqrstuvwxyz 'ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$words=array();
		
			if($w !=$this->DeCapitalise($w)){
			$words = array($w, $this->DeCapitalise($w));
			}
			else{
				$words = array(($w.""));
				}			
			
			


			foreach($words as $word){
		for( $l = 0; $l<strlen( $word ); $l++ ){
			
			for( $i = 0; $i<strlen( $strTry ); $i++ ){
				$letter = $strTry  [ $i ];
				
				if( $l == 0 || $i<28 ){
					$guess = $word;
					$guess  [ $l ] = $letter;
					$results  [ ] = trim($guess);
					$guess = substr( $word, 0, $l ) . $letter . substr( $word, $l ) ;
					$results  [ ] = trim( $guess );
					
					if( $letter == " " && $l>0 ){
						for( $m = $l+2; $m<strlen( $word )-1; $m++ ){
							$results  [ ] = trim( substr( $guess, 0, $m ) . " " . substr( $guess, $m ) );
						}

					}

				}

				
				if( $l == 0  && $letter !==" " && $letter !=="'" ){
					$guess = $word;
					$guess = $letter . $guess ;
					$results  [ ] = trim($guess);
				}

			}

			
			if( $l>0 ){
				$guess = $word;
				$guess2 = $guess;
				$guess  [ $l ] = $guess2  [  $l-1 ];
				$guess  [ $l-1 ] = $guess2  [  $l ];
				$results  [  ] = trim($guess);
			}

			//swap
			$guess = $word;
			$guess  [  $l ] = "^";
			$results  [  ] = trim(str_replace( "^","", $guess ));
			//delete
		}}

		$output = array();
		sort( $results );
		$results = array_unique( $results );
		sort( $results );
		//print_r($results);
		for( $l = 0; $l<( count( $results ) ); $l++ ){
			
			if( $this -> SpellCheckAndSpaces( $results [  $l ], false ) ){
				$output [  ] = $results [  $l ];
			}

		}

		return $output;
	}

	private// -- Function Name : arrMetaPhones
	// -- Params :  $word
	// -- Purpose :
	function arrMetaPhones( $word ){
		$G_DictArray = &$this -> G_DictArray;
		$G_METArray = &$this -> G_METArray;
		$p = $this -> PhoneticCode( $word );
		$PINDEX = $this -> DicIndex( $p );
		$results = array();
		$dictkeys = array_keys( $G_DictArray );

		for( $i = 0; $i<count( $dictkeys ); $i++ ){
			$dictkey = $dictkeys [  $i ];

			if( array_key_exists( $PINDEX,$G_METArray [  $dictkey ] ) ){
				
				if( array_key_exists($p,$G_METArray [  $dictkey ] [ $PINDEX ] )){
			 
					$lookups = explode( "|", $G_METArray [  $dictkey ] [ $PINDEX ] [ $p ] );
					foreach( $lookups as $lookup ){
						$lookupcode = $this -> DicIndex( $lookup [  0 ] );
						$lookindex = ( int ) substr( $lookup, 1 );
						$result = $G_DictArray [  $dictkey ] [ $lookupcode ] [ $lookindex ];
						$results [  ] = $result;
					}

				}

			}

		}

		$results = array_unique( $results );
		sort( $results );
		return $results ;
	}

	private// -- Function Name : DistSort
	// -- Params :  $word, $suggestions
	// -- Purpose :
	function DistSort( $word, $suggestions ){
		$G_PosArray = &$this -> G_PosArray;
		sort( $suggestions );
		$disarray = array();
		$dicts = $this->ListLiveDictionaries();
		foreach( $suggestions as $suggestion ){
	
		if (!array_key_exists(strtolower($suggestion),$this->GBanned)){
								
		
								
			$distance = levenshtein( $word, $suggestion );
			
			if($distance <5){
				$distance = $this -> psuedolevenshtein( $word, $suggestion );
				
				if($distance <3.5){
					foreach ($dicts as $dictName){
						
						if(array_key_exists($dictName,$G_PosArray) && $G_PosArray [$dictName]!=""){
							$PosStr = "$".$G_PosArray [$dictName];
							$p=0;
							
							if(substr_count($PosStr,$suggestion) ){
								$p = strpos($PosStr,strtoupper("$".$suggestion."$"));
							}

							
							if($p>0){
								///// POSITION
								
								if( strpos($PosStr,"$+=") ===0){
									$distance -=0.3;
								}

								elseif($p){
									$l = strlen($PosStr);
									$r = pow((($l-$p)/$l),2)*0.6;
									$distance -=$r;
								}

							}

							;
						}
					
					}

					
					if( count($this->G_ContextArray )){
						
						if(in_array($suggestion,$this->G_ContextArray,true )){
							$ccount = count(array_keys( $this->G_ContextArray,$suggestion,true));
							
							if($ccount) {
								$distance -= (pow($ccount,0.4)*.75);
							}

						}

					}

				}

			}

			$disarray [  $suggestion  /*."-".$distance*/] = $distance;
		}
		}
		asort( $disarray );
	
		$min=1;
		try{
		
		$disarraykeys = array_keys( $disarray );
		$min = $disarray [  $disarraykeys [  0 ] ];
			}
  			catch(Exception $e){;}	

		
		if( $min<0.4 ){
			$min = 0.4;
		}


		$maxvariance = sqrt($min) + $this->SuggestionTollerance +0.5;
		$maxres =  sqrt(strlen( $word )-1)+$this->SuggestionTollerance ;

		
		
		for( $i = 0; $i<count( $disarray ); $i++ ){
			if( $disarray [  $disarraykeys  [  $i ] ] >$maxvariance || $i > $maxres ){
				$disarraykeys = array_slice( $disarraykeys, 0, $i );
				
				$i=1000000;
			}

		}

		return $disarraykeys;
	}

	private static// -- Function Name : cleanForeign
	// -- Params :  $word
	// -- Purpose :
	function cleanForeign( $word ){
		$strForeign = explode( ",", "Ÿ,Ý,ý,ÿ,À,Á,Â,Ã,Ä,Å,à,á,â,ã,ä,å,È,É,Ê,Ë,è,é,ê,ë,Ì,Í,Î,Ï,ì,í,î,ï,Ò,Ó,Ô,Õ,Ö,ó,ô,õ,ö,ø,Ù,Ú,Û,Ü,ù,ú,û,ü,Œ,œ,Æ,æ,ß,Š,š,Ž,Ñ,ñ,ð,Ð,Þ,þ");
		$strNatural = explode( ",", "Y,Y,y,y,A,A,A,A,A,A,a,a,a,a,a,a,E,E,E,E,e,e,e,e,I,I,I,I,i,i,i,i,,O,O,O,O,O,o,o,o,o,o,U,U,U,U,u,u,u,u,OE,oe,AE,ae,ss,SH,sh,S,N,n,th,th,TH,TH");
		return str_replace( $strForeign, $strNatural, $word );
	}
	
	public function LoadPro($bin_Binary_Loader){
	$arrSorter = explode("-",$bin_Binary_Loader."");
	if(count($arrSorter) !==6){return false;}
	$rand1=$arrSorter[2];
	$v1 = (int) $this->strInteger($rand1);
	$checksum1 = $arrSorter[3];
	if($checksum1 !== $this->hashstr($rand1,$v1)){return false;}
	$string  = $arrSorter[4];
	$v2 = (int)  $this->strInteger($checksum1.$string);
	$checksum2 = $arrSorter[1];
	if($checksum2 != $this->hashstr($string."O1",$v2)){return false;}
	$bin_Binary_Loader = $arrSorter[0];
	$checksum3 = $arrSorter[5];
	if($checksum3 != $this->hashstr(substr($bin_Binary_Loader,0,5),$v2%$v1)){return false;}	
	return $bin_Binary_Loader == $this->_phpName ||$bin_Binary_Loader ==$this->_unName ;
	}

	
	// -- Params :  $word, $try
	// -- Purpose :
		// -- Function Name : psuedolevenshtein
		// -- Params :  $word, $try
		// -- Purpose :
		function psuedolevenshtein( $word, $try ){
			$casemod = ( ( strtolower( $word  [ 0 ] ) == $word  [ 0 ] ) != ( strtolower( $try  [ 0 ] ) == $try  [ 0 ] ) );
			$w = strtolower( $word );
			$t = strtolower( $try );

			if($w==$t){
				return 0.5;
			}
			$w = $this -> cleanPunctuation( $w );
			$t = $this -> cleanPunctuation( $t );
				if($w==$t){
					return 0.4;
				}

			$w = $this -> cleanForeign( $w );
			$t = $this -> cleanForeign( $t );



			if($w==$t){
				return 1;
			}


			$w_novowel = $this -> stripVowels( $w );
			$t_novowel = $this -> stripVowels( $t );






			$d = levenshtein( $w, $t );

				if($w_novowel==$t_novowel){
				$d-=2;
				}

			if($w[0]!=$t[0]){$d+=0.5;}
			if($w[strlen($w)-1]==$t[strlen($t)-1]){$d-=0.3;}



			if(strlen($w)>2){
				$ws = strtolower($w  [strlen($w)-1])==="s";

				if($ws){
					$wt = strtolower($t  [strlen($t)-1])==="s";

					if($wt){
						$d-=0.4;
					}

				}

			}

			$len = strlen( $t );
		

			if( $casemod ){
					$d++;	
				if( $len>3 ){
					$d += 0.15;
				}


				if( $len>4 ){
					$d += 0.25;
				}


				if( $len>5 ){
					$d += 0.25;
				}


				if( $len>6 ){
					$d += 0.1;
				}

			}








			if( substr_count( $t, " " ) ){
				$spacetest = explode( " ", $t );

					$d += count($spacetest)-1;

				foreach( $spacetest as $frag ){

					if($this->stripVowels( $frag )==$frag){
						$d+=1.5;
					if(strlen($frag)==1){
						$d+=1;	
					}
				}
						if(strlen($frag)==1){
							$d+=1.5;	
						}


				if(strlen($frag)<2){
					$d+=1;	
				}
				if(strlen($frag)<3){
					$d+=0.6;	
				}
				if(strlen($frag)<4){
					$d+=0.4;	
				}
					}


			}

	$w = str_replace( " ","", $w );
	$t = str_replace( " ","", $t );

			$w_arrray = str_split( $w );
			$t_arrray = str_split( $t );
			sort( $w_arrray );
			sort( $t_arrray );

			if( implode( "", $w_arrray ) == implode( "", $t_arrray ) ){
				$d += -.9;
			}

			$w_arrray = array_unique( $w_arrray );
			$t_arrray = array_unique( $t_arrray );
			sort( $w_arrray );
			sort( $t_arrray );

			if( implode( "", $w_arrray ) == implode( "", $t_arrray ) ){
				$d += -0.9;
			}

			//removeDoubleChars
			$w = str_replace( "ie","y", $w );
			$t = str_replace( "ie","y", $t );
			$w = str_replace( "z","s", $w );
			$t = str_replace( "z","s", $t );
			$w = str_replace( "ph", "f", $w );
			$t = str_replace( "ph", "f", $t );

			if($w==$t){
				$d-=1;
			}

			if( $t == $t_novowel ){
				$d += 0.4;
			}

			;

			if( $w_novowel == $t_novowel ){
				$d -= -0.8;
			}

			return $d;
		}

	private// -- Function Name : DecypherStrDict
	// -- Params :  $id, $strWholeDict
	// -- Purpose :
	function DecypherStrDict( $id, $strWholeDict ){

		$this->clear_CACHE();
		$G_DictArray = &$this -> G_DictArray;
		$G_METArray = &$this -> G_METArray;
		$G_PosArray = &$this -> G_PosArray;
		
		if( array_key_exists (  $id , $G_DictArray )){
		if( is_array( $G_DictArray  [ $id ] ) ){
			return true;
		}
		}

		$G_DictArray  [ $id ] = array();
		$dlimit = "\r\n==========================================\r\n";
		$smalldlimit = "\r\n+++++++++\r\n";
		list( $words, $phones, $proximity ) = explode( $dlimit, $strWholeDict );
		$wordsbyletter = explode( $smalldlimit, $words );
		foreach( $wordsbyletter as $WordsInLetter ){
			$char = $WordsInLetter  [ 0 ];
			$dicindex = $this -> DicIndex( $char );
			$G_DictArray  [ $id ]  [ $dicindex ] = explode( "\r\n", $WordsInLetter );
		}

		$phonesbyletter = explode( $smalldlimit, $phones );
		foreach( $phonesbyletter as $PhonesInLetter ){
			$char = $PhonesInLetter  [ 0 ];
			$dicindex = $this -> DicIndex( $char );
			$EachPhoneInLetter = explode( "\r\n", $PhonesInLetter );
			$G_METArray  [ $id ]  [ $dicindex ] = array();
			foreach( $EachPhoneInLetter as $StrPhone ){
				list( $pkey, $pvalue ) = explode( "#", $StrPhone );
				$G_METArray  [ $id ]  [ $dicindex ]  [ $pkey ] = $pvalue;
			}

		}

		if( trim( $proximity ) ){
			$G_PosArray  [ $id ] = $proximity;
		}

	}

	public// -- Function Name : BuildDictionary
	// -- Params :  $ArrWholeDict, $id_to_save_to_memory
	// -- Purpose :
	function BuildDictionary( $ArrWholeDict, $id_to_save_to_memory=false ){
		$G_DictArray = &$this -> G_DictArray;
		$G_METArray = &$this -> G_METArray;
		$G_PosArray = &$this -> G_PosArray;
		
		if($id_to_save_to_memory){
			$this->clear_CACHE();
		}

		sort( $ArrWholeDict );
		$ArrWholeDict = array_unique( $ArrWholeDict );
		sort( $ArrWholeDict );
		$dlimit = "==========================================\r\n";
		$smalldlimit = "+++++++++\r\n";
		$words = "";
		$oldword = "#";
		$ArrPhones = array();
		$CountinLetter = 0;
		$CountinTotal = 0;
		foreach( $ArrWholeDict as $word ){
			
			if( $oldword != "#" && $oldword != $word  [ 0 ] ){
				$words .= $smalldlimit ;
				
				if( $id_to_save_to_memory ){
					
					if( !array_key_exists( $id_to_save_to_memory,$G_DictArray ) ){
						$G_DictArray  [ $id_to_save_to_memory ] = array();
					}

					$G_DictArray  [ $id_to_save_to_memory ]  [ $this -> DicIndex( $oldword ) ] = array_slice( $ArrWholeDict, $CountinTotal-$CountinLetter, $CountinLetter ) ;
				}

				$CountinLetter = 0;
			}

			$words .= $word . "\r\n";
			$p = $this -> PhoneticCode( $word );
			$PINDEX = $this -> DicIndex( $p );
			if(strlen($word)>0){
			$indexCode = $word  [ 0 ] . $CountinLetter;
		}else{
			$indexCode = "" . $CountinLetter;		
		}
		
	 		if( !array_key_exists( $PINDEX,$ArrPhones ) ){
				$ArrPhones  [ $PINDEX ] = array();
			}

			
			if( !array_key_exists($p,$ArrPhones  [ $PINDEX ] )  ){
				$ArrPhones  [ $PINDEX ]  [ $p ] = $indexCode;
			} else{
				$ArrPhones  [ $PINDEX ]  [ $p ] .= "|" . $indexCode;
			}

		if(strlen($word)>0){
			$oldword = $word  [ 0 ];
		}else{
			$oldword="";
			
		}
			$CountinLetter++;
			$CountinTotal++;
		}

		
		if( $id_to_save_to_memory ){
			
			if( !is_array( $G_DictArray  [ $id_to_save_to_memory ] ) ){
				$G_DictArray  [ $id_to_save_to_memory ] = array();
			}

			$G_DictArray  [ $id_to_save_to_memory ]  [ $this -> DicIndex( $oldword ) ] = array_slice( $ArrWholeDict, $CountinTotal-$CountinLetter, $CountinLetter ) ;
		}

		$phones = "";
		ksort( $ArrPhones );
		foreach( $ArrPhones as $mykey => $ArrPhonesByIndex ){
			ksort( $ArrPhonesByIndex );
			
			if( $phones != "" ){
				$phones .= $smalldlimit;
			}

			foreach( $ArrPhonesByIndex as $k => $v ){
				$phones .= "$k#$v\r\n";
			}

		}

		
		if( $id_to_save_to_memory ){
			$G_METArray  [ $id_to_save_to_memory ] = $ArrPhones;
		}

		return $words . $dlimit . $phones . $dlimit;
	}

	public // -- Function Name : PhoneticCode
	// -- Params :  $word
	// -- Purpose :
	function PhoneticCode( $word ){
		$word = $this->cleanForeign($word);
		$p = substr( metaphone( $word ), 0, 4 );
		
		if( strlen( $p )<1 ){
			return "0";
		}

		
		if( $p  [ 0 ] == "E" || $p  [ 0 ] == "I" || $p  [ 0 ] == "O" || $p  [ 0 ] == "U" ){
			$p  [ 0 ] = "A" ;
		}

		return $p;
	}

	private $SimpleSpellCache_CASED = array();
	private $SimpleSpellCache_UNCASED = array();
	public// -- Function Name : SimpleSpell
	// -- Params :  $word, $b_ignore_case
	// -- Purpose :
	function SimpleSpell( $word, $b_ignore_case ){
		$G_DictArray = $this -> G_DictArray;
		
		
			if (array_key_exists(strtolower($word),$this->GBanned)){
								 return false;
								 }
		if($b_ignore_case){
			if (array_key_exists(strtolower($word),$this->SimpleSpellCache_UNCASED)){
				return $this->SimpleSpellCache_UNCASED  [strtolower($word)];
			}

		} else{
			
			if (array_key_exists($word,$this->SimpleSpellCache_CASED)){
				//	echo ("ISCASED CACHE $word ||");
				return $this->SimpleSpellCache_CASED  [$word];
			}

		}

		$dic_index = $this -> DicIndex( $word );
		$dictkeys = array_keys( $G_DictArray );
		for( $i = 0; $i<count( $dictkeys ); $i++ ){
			$dictkey = $dictkeys  [ $i ];
			
			if( array_key_exists( $dic_index,$G_DictArray  [ $dictkey ]   ) ){
				
				if( $b_ignore_case ){
					$dic_index_U = $this -> DicIndex( strtoupper( $word ) );
					$dic_index_l = $this -> DicIndex( strtolower( $word ) );
					
					if( array_key_exists($dic_index_l,$G_DictArray  [ $dictkey ] ) && $this -> search_array_caseless( $word, $G_DictArray  [ $dictkey ]  [ $dic_index_l ] ) ){
						$this->SimpleSpellCache_UNCASED  [strtolower($word)] = true;
				
						return true;
					}

					
					if( array_key_exists($dic_index_U,$G_DictArray  [ $dictkey ] ) && $this -> search_array_caseless( $word, $G_DictArray  [ $dictkey ]  [ $dic_index_U ] ) ){
						$this->SimpleSpellCache_UNCASED  [strtolower($word)] = true;
	
						return true;
					}

				} else{
					//if(array_search($word, $G_DictArray  [$dictkey]  [$dic_index], true))
					
					if( $this -> BinarySearch( $G_DictArray  [ $dictkey ]  [ $dic_index ] , $word, 0, count( $G_DictArray  [ $dictkey ]  [ $dic_index ] ) ) ){
						$this->SimpleSpellCache_CASED  [$word] = true;
						return true;
					}

				}

			}

		}

		
		if($b_ignore_case){
			$this->SimpleSpellCache_UNCASED  [strtolower($word)] = false ;
			$this->SimpleSpellCache_CASED  [$word] = false ;
		} else{
			$this->SimpleSpellCache_CASED  [$word] = false ;
		}

		return false;
	}

	private static// -- Function Name : search_array_caseless
	// -- Params :  $str, $array
	// -- Purpose :
	function search_array_caseless( $str, $array ){
		
		if(!is_array($array)){
			return false;
		}

		return preg_grep( '/^' . preg_quote( $str, '/' ) . '$/i', $array );
	}


	private static// -- Function Name : BinarySearch
	// -- Params :  $array, $key, $low, $high
	// -- Purpose :
	function BinarySearch( $array, $key, $low, $high ){
		
		if( $low > $high )// termination case
			{
			return false;
		}

		$middle = intval( ( $low+$high )/2 );
		// gets the middle of the array
		
		if( array_key_exists($middle, $array) && $array  [ $middle ] === $key )// if the middle is our key
			{
			return true;
		}

		elseif( array_key_exists($middle, $array)  && $key < $array  [ $middle ] )// our key might be in the left sub-array
			{
			return PHP_MCESpellCheck::BinarySearch( $array, $key, $low, $middle-1 );
		}

		return PHP_MCESpellCheck :: BinarySearch( $array, $key, $middle+1, $high );
		// our key might be in the right sub-array
	}

	private static// -- Function Name : DicIndex
	// -- Params :  $strWord
	// -- Purpose :
	function DicIndex( $strWord ){
		return ord( substr( trim( $strWord ), 0, 1 ) );
	}

	private static// -- Function Name : filepath2url
	// -- Params :  $theFilepath
	// -- Purpose :
	function filepath2url( $theFilepath ){
		$base = explode( "/", $_SERVER  [ 'PHP_SELF' ] );
		array_pop( $base );
		$base = implode( "/", $base );
		$protocol = explode( "/", $_SERVER  [ 'SERVER_PROTOCOL' ] ) ;
		$protocol = strtolower( $protocol  [ 0 ] );
		$domain = $_SERVER  [ 'SERVER_NAME' ];
		return( "$protocol://$domain$base/$theFilepath" );
	}

	private static// -- Function Name : loadDataFromURL
	// -- Params :  $url
	// -- Purpose :
	function loadDataFromURL( $url ){
		return file_get_contents( filepath2url( $url ) );
	}
	public function AddCustomDictionaryFromArray($array){
			$this -> BuildDictionary( $array, "SPELL.INPUT.ARRAY" );		
	}
	//private// -- Function Name : addCustomDictionary
	// -- Params :  $fileName
	// -- Purpose :
//	function addCustomDictionary( $fileName ){
//		$this -> LoadDictArrayCustom( $fileName );
//	}

//	private// -- Function Name : addCustomDictionaryfromURL
	// -- Params :  $fileName
	// -- Purpose :
//	function addCustomDictionaryfromURL( $fileName ){
//		$this -> LoadDictArrayCustom( $fileName . "*HTTP" );
//	}
	 

	private static// -- Function Name : ArrClean
	// -- Params :  $myArray
	// -- Purpose :
	function ArrClean( $myArray ){
		return array_unique( $myArray );
	}

	private static// -- Function Name : Capitalise
	// -- Params :  $word
	// -- Purpose :
	function Capitalise( $word ){
		return ucfirst( $word );
	}

	private static// -- Function Name : DeCapitalise
	// -- Params :  $word
	// -- Purpose :
	function DeCapitalise( $word ){
		$word  [ 0 ] = strtolower( $word  [ 0 ] );
		return $word;
	}

	private static// -- Function Name : isHTML
	// -- Params :  $myStr
	// -- Purpose :
	function isHTML( $myStr ){
		return( $myStr == strip_tags( $myStr ) );
	}
	
}  

?>