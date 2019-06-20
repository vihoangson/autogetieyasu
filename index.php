<?php
require_once('vendor/autoload.php');
define("DOMAIN_URL","https://f.ieyasu.co");

class ClassGetContent{
	public $cookie;
	public function curlWithPost ($url, $posts = array(), $cookie_file_path = '') {

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		
		if ($cookie_file_path) {
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
		}
        //set the cookie the site has for certain features, this is optional
		curl_setopt($ch, CURLOPT_USERAGENT,
			"Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		
		if ($posts) {
			$post_arr = array();
			foreach ($posts as $key => $val) {
				$post_arr[] = $key . "=" . $val;
			}
			$post_str = implode("&", $post_arr);            
			
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_str);
		}
		
		$html = curl_exec($ch);
		curl_close($ch);
		return $html;
	}

	public function loginKintai () {        
		$html = $this->curlWithPost(DOMAIN_URL."/users/sign_out", array(), $this->cookie);
		$html = $this->curlWithPost(DOMAIN_URL."/lampart1/login/", array(), $this->cookie);
		$dom = \Sunra\PhpSimple\HtmlDomParser::str_get_html($html);
		$inputs = $dom->find("#new_user input");
		if ($inputs) {
			$posts = array();
			foreach ($inputs as $input) {
				$posts[$input->getAttribute('name')] = $input->getAttribute('value');
			}
			$posts['user[login_id]'] = 'LP0072';
			$posts['user[password]'] = 'LP0072';
			$html = $this->curlWithPost(DOMAIN_URL."/lampart1/login/", $posts, $this->cookie);                                    
			
			$html2 = $this->curlWithPost(DOMAIN_URL."/approvals", array(), $this->cookie);          
			
		}

		return $html2;
	}


	public function checkin (){
		$html = $this->curlWithPost(DOMAIN_URL."/timestamp", array(), $this->cookie);        

	}

	public function getIeyasuInfoList ($html, $ieyasuInfos = array()) {
		
		$dom = \Sunra\PhpSimple\HtmlDomParser::str_get_html($html);        
		
		if ($trs = $dom->find(".tableApproval > tr")) {
			foreach ($trs as $index => $tr) {
				if ($index < 2) {
					continue;
				}
				
				$tdCount = $tr->find('td', 2);                
				$aAuchor = $tr->find('td', 1)->find('a', 0);
				
				if (!empty($tdCount) && !empty($aAuchor)) {
					if (preg_match('/approvals\/(\d+)\/works/', $aAuchor->href, $match) && isset($match[1])) {
						$ieyasuInfos[] = array (
							'ieyasuId'      => intval($match[1]),
							'ieyasuName'    => trim($aAuchor->plaintext),
							'approvalCount' => intval($tdCount->plaintext),
							'href'          => DOMAIN_URL."" . $aAuchor->href
						);
					}
				}
			}            	

            // Process next page
			if ($aNext = $dom->find('[rel="next"]', 0)) {
				die;
				return $ieyasuInfos;

				$nextHTML = $this->curlWithPost(DOMAIN_URL."" . $aNext->getAttribute('href'), array(), $this->cookie);
				$ieyasuInfos = $this->getIeyasuInfoList($nextHTML, $ieyasuInfos);
			}
		}

		return $ieyasuInfos;
	}

	public function getIeyasuTime($htmls) {
		$redmineTime = array();
		if (is_array($htmls) && $htmls) {
			foreach ($htmls as $id => $html) {
				$dom = \Sunra\PhpSimple\HtmlDomParser::str_get_html($html);
				$hours = explode(":", trim($dom->find('#areaTotal .block01 table tr', 1)->find('td', 0)->plaintext));
				
				if (count($hours) > 1) {
					$hours[1] = round((intval($hours[1] / 15) * 15) * 100 / 60, 0);
					$hours = $hours[0] . '.' . $hours[1];
				} else {
					$hours = $hours[0];
				}
//                 $member = chatwork::findMemberByKeyValue('ieyasuId', intval($id));
//                 $name = chatwork::getValueFromMember($member, 'name');
                //$userExt = DB::table(Chatwork::TABLE_USER_EXT)->where('disable', 0)->where('ie_id', intval($id))->get(array('cw_accountId', 'cw_name'))->first();
				$redmineTime[] = $hours;
				
                // if ($userExt = (array)$userExt) {
                //     $userExt['hours'] = $hours;
                // } else {
                //     \Log::warning(__METHOD__ . ' - ' . $id . ' Not exist in UserExt');
                // }
			}
		}
		return $redmineTime;
	}
}

$c = new ClassGetContent;
$c->cookie = __DIR__.'/cookie_kintai.txt';
//$mmm = $c->loginKintai();
$mmm = file_get_contents("1561013420.html");

$kk = $c->getIeyasuInfoList($mmm);
foreach ($kk as $key => $value) {
	$dd =  $c->curlWithPost($value['href'],[],$c->cookie);
	$m123 = $c->getIeyasuTime([$dd]);
	var_dump($m123);
	die;	
}
var_dump($kk);
die;
//file_put_contents(time().".html", $mmm);

//var_dump($c->curlWithPost(DOMAIN_URL.'/lampart1',[],'./cookie.txt'));
?>