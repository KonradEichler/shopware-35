<?php
class Shopware_Controllers_Frontend_Search extends Enlight_Controller_Action
{	
	public function indexAction()
	{
		if (Shopware()->System()->sCheckLicense("","",Shopware()->System()->sLicenseData["sFUZZY"]) == false || $this->request()->sSearchMode=="supplier"){
			return $this->forward("search");			
		}else {
			return $this->forward("searchFuzzy");
		}
	}
	
	public function searchAction(){
		
		if ($this->request()->sSearchMode=="supplier"){
			$variables = Shopware()->Modules()->Articles()->sGetArticlesBySupplier();
			$this->request()->setParam('sSearch',urldecode(Shopware()->System()->_GET['sSearchText']));			
		}else {
			$variables = Shopware()->Modules()->Articles()->sGetArticlesByName("a.topseller DESC","","",$this->request()->sSearch);
		}
		foreach ($variables["sPerPage"] as $perPageKey => &$perPage){
			$perPage["link"] = str_replace("sPage=".$this->request()->sPage,"sPage=1",$perPage["link"]);
		}
		if (!empty($variables["sArticles"])){
			$searchResults = $variables["sArticles"];
		}else {
			$searchResults = $variables;
		}
		
		foreach ($searchResults as $searchResult){
			if (is_array($searchResult)) $searchResult = $searchResult["id"];
			$article = Shopware()->Modules()->Articles()->sGetPromotionById ('fix',0,$searchResult);
			
			if (!empty($article["articleID"])){
				$articles[] = $article;
			}
		}
	
		$this->View()->sSearchResults = $articles;
		$this->View()->sSearchResultsNum = empty($variables["sNumberArticles"]) ? count($articles) : $variables["sNumberArticles"];
		$this->View()->sSearchTerm = $this->request()->sSearch;
		$this->View()->sPages = $variables["sPages"];
	
		$this->View()->sPage = $this->request()->sPage;
		$this->view->loadTemplate('frontend/search/index.tpl');
	}
	
	public function searchFuzzyAction(){
		// Load configuration
		
		$Config = $this->searchFuzzyInit();
		$location = $this->searchFuzzyCheck($Config);
		if(!empty($location)) {
			return $this->redirect($location);
		}
		
		$Links =  $this->searchFuzzyPrepareLinks($Config);
		
		if(!empty(Shopware()->Config()->sFUZZYSEARCHSELECTPERPAGE))
			$sPerPage = preg_split('/[^0-9]/', (string) Shopware()->Config()->sFUZZYSEARCHSELECTPERPAGE, -1, PREG_SPLIT_NO_EMPTY);
		else
			$sPerPage = array(8, 16, 24, 48);
			
		if(!empty(Shopware()->Config()->sFUZZYSEARCHPRICEFILTER))
			$sPriceFilter = preg_split('/[^0-9]/', (string) Shopware()->Config()->sFUZZYSEARCHPRICEFILTER, -1, PREG_SPLIT_NO_EMPTY);
		else
			$sPriceFilter = array(5, 10, 20, 50, 100, 300, 600, 1000, 1500, 2500, 3500, 5000);
						
		$tmp = array();
		$last = 0;
		foreach ($sPriceFilter as $key => $price)
		{
			$tmp[$key+1] = array('start'=>$last, 'end'=>$price);
			$last = $price;
		}
		$sPriceFilter = $tmp;
		unset($tmp, $last, $key, $price);
		
		if (strlen($Config['sSearch'])>=(int)Shopware()->Config()->sMINSEARCHLENGHT)
		{	
			Shopware()->Modules()->Search()->sInit();
			Shopware()->Modules()->Search()->sPriceFilter = $sPriceFilter;
			$sSearchResults = Shopware()->Modules()->Search()->sStartSearch($Config);
			
			$sql = "
				INSERT INTO s_statistics_search (datum, searchterm, results)
				VALUES (NOW(), ?, ?)
			";
			Shopware()->Db()->query($sql,array(
				implode(' ', $sSearchResults['sSearchTerms']),
				empty($sSearchResults['sArticlesCountAll']) ? 0 : $sSearchResults['sArticlesCountAll']
			));		
			
			$sPages = array();
			for($i=0,$page=0;$i<$sSearchResults['sArticlesCount'];$i+=$Config['sPerPage'],$page++)
			{
				if($sRequests['sPage']-3<$page&&$sRequests['sPage']+3>$page)
					$sPages['pages'][] = $page;
			}
			$sPages['count'] = $page;
			if($Config['sPage']>0)
				$sPages['before'] = $sPages['bevor'] = $Config['sPage']-1;
			if($Config['sPage']<$sPages['count']-1)
				$sPages['next'] = $Config['sPage']+1;
			$articles = array();
			foreach ($sSearchResults["sArticles"] as $articleID){
				$article = Shopware()->Modules()->Articles()->sGetPromotionById ('fix', 0, (int)$articleID);
				if (!empty($article["articleID"])) {
					$articles[] = $article;
				}
			}
			$sSearchResults["sArticles"] = $articles;

			
			
			$this->View()->sRequests = $Config;
			$this->View()->sSearchResults = $sSearchResults;
			$this->View()->sPerPage = $sPerPage;
			$this->View()->sLinks = $Links;
			$this->View()->sPages = $sPages;
			$this->View()->sPriceFilter = $sPriceFilter;
			$this->View()->sCategoriesTree = $this->getCategoryTree($sSearchResults['sLastCategory'],$Config);
		}
		
		$this->view->loadTemplate('frontend/search/fuzzy.tpl');
	}
	
	protected function getCategoryTree ($id,$Config)
	{
		$sql = "
			SELECT 
				`id` ,
				`description`,
				`parent`
			FROM `s_categories`
			WHERE `id`=?
		";
		$cat = Shopware()->Db()->fetchRow($sql,array($id));
		if(empty($cat['id'])||$id==$cat['parent']||$id==$Config["sMainCategoryID"])
			return array();
		else 
		{
			$cats = $this->getCategoryTree($cat['parent'],$Config);
			$cats[$id] = $cat;
			return $cats;
		}
	}
	
	protected function searchFuzzyPrepareLinks(&$Config)
	{
		$Config['sSearchOrginal'] = $Config['sSearch'];
		
		$sLinks['sLink'] = Shopware()->Config()->BaseFile.'?sViewport=searchFuzzy&sSearch='.urlencode($Config['sSearchOrginal']);
		$Config['sSearchOrginal'] = htmlspecialchars($Config['sSearchOrginal']);
		$sLinks['sSearch'] = $this->Front()->Router()->assemble(array("sViewport"=>"search"));
		
		$sLinks['sPage'] = $sLinks['sLink'];
		$sLinks['sPerPage'] = $sLinks['sLink'];
		$sLinks['sSort'] = $sLinks['sLink'];
		
		$sLinks['sFilter']['category'] = $sLinks['sLink'];
		$sLinks['sFilter']['supplier'] = $sLinks['sLink'];
		$sLinks['sFilter']['price'] = $sLinks['sLink'];
		$sLinks['sFilter']['propertygroup'] = $sLinks['sLink'];
		
		foreach (array("supplier","category","price") as $filterType){
			if (!empty($Config["sFilter"][$filterType])){
				$sLinks['sPage'] .= "&sFilter_$filterType=".$Config['sFilter'][$filterType];
				$sLinks['sPerPage'] .= "&sFilter_$filterType=".$Config['sFilter'][$filterType];
				$sLinks['sSort'] .= "&sFilter_$filterType=".$Config['sFilter'][$filterType];
				if ($filterType!="category"){
					$sLinks['sFilter']['category'] .= "&sFilter_$filterType=".$Config['sFilter'][$filterType];
				}
				if ($filterType!="price"){
				$sLinks['sFilter']['price'] .= "&sFilter_$filterType=".$Config['sFilter'][$filterType];
				}if ($filterType!="propertygroup"){
				$sLinks['sFilter']['propertygroup'] .= "&sFilter_$filterType=".$Config['sFilter'][$filterType];
				}if ($filterType!="supplier"){
				$sLinks['sFilter']['supplier'] .= "&sFilter_$filterType=".$Config['sFilter'][$filterType];
				}
			}
		}
		if(!empty($Config['sFilter']['propertygroup']))
		{
			$value = urlencode($Config['sFilter']['propertygroup']);
			$sLinks['sPage'] .= "&sFilter_propertygroup=".$value;
			$sLinks['sPerPage'] .= "&sFilter_propertygroup=".$value;
			$sLinks['sSort'] .= "&sFilter_propertygroup=".$value;
			$sLinks['sFilter']['category'] .= "&sFilter_propertygroup=".$value;
			$sLinks['sFilter']['supplier'] .= "&sFilter_propertygroup=".$value;
			$sLinks['sFilter']['price'] .= "&sFilter_propertygroup=".$value;
		}
		
		foreach (array("sOrder","sSort","sPerPage") as $property){
			if(!empty($Config[$property]))
			{
				if($property!='sPage') {
					$sLinks['sPage'] .= "&$property=".$Config[$property];
				}
				if($property!='sPerPage') {
					$sLinks['sPerPage'] .= "&$property=".$Config[$property];
				}
				$sLinks['sFilter']['__'] .= "&$property=".$Config[$property];
			}
		}
		
		
		foreach (array("price","category","supplier","propertygroup") as $property){
			$sLinks['sFilter'][$property] .= $sLinks['sFilter']['__'];
		}
		
		//$sLinks['sSort'] = Shopware()->System()->rewriteLink(array(2=>$sLinks['sSort']),true);
		$sLinks['sSupplier'] = $sLinks['sSort'];
		
		return $sLinks;
	}
	
	protected function searchFuzzyCheck($Config){
		
		if(!empty($Config['sSearch'])&&strlen($Config['sSearch'])>=(empty(Shopware()->Config()->sMINSEARCHLENGHT) ? 2 : (int) Shopware()->Config()->sMINSEARCHLENGHT))
		{
			$sql = "
				SELECT DISTINCT articleID 
				FROM
				(
					SELECT DISTINCT articleID
					FROM s_articles_groups_value
					WHERE ordernumber = ?
					GROUP BY articleID
					LIMIT 2
					UNION 
					SELECT DISTINCT articleID
					FROM s_articles_details
					WHERE ordernumber = ?
					GROUP BY articleID
					LIMIT 2
				) as a
				LIMIT 2
			";
			$articles = Shopware()->Db()->fetchCol($sql,array($Config['sSearch'],$Config['sSearch']));
			
			if(empty($articles))
			{
				$sql = "
					SELECT DISTINCT articleID 
					FROM
					(
						SELECT DISTINCT articleID
						FROM s_articles_groups_value
						WHERE ordernumber = ?
						GROUP BY articleID
						LIMIT 2
						UNION 
						SELECT DISTINCT articleID
						FROM s_articles_details
						WHERE ordernumber = ?
						OR ? LIKE CONCAT(ordernumber,'%')
						GROUP BY articleID
						LIMIT 2
					) as a
					LIMIT 2
				";
				$articles =  Shopware()->Db()->fetchCol($sql,array($Config['sSearch'],$Config['sSearch'],$Config['sSearch']));
			}
		}
		if(!empty($articles)&&count($articles)==1)
		{
			$sql = "SELECT articleID FROM s_articles_categories WHERE categoryID={$Config["sMainCategoryID"]} AND articleID={$articles[0]}";
			$articles =  Shopware()->Db()->fetchCol($sql);
		}
		if(!empty($articles)&&count($articles)==1)
		{
			return $this->Front()->Router()->assemble(array("sViewport"=>"detail","sArticle"=>$articles[0]));
		}
	}
	
	protected function searchFuzzyInit(){
		$Config["sMainCategoryID"] = Shopware()->System()->sLanguageData[Shopware()->System()->sLanguage]["parentID"] ? Shopware()->System()->sLanguageData[Shopware()->System()->sLanguage]["parentID"] : Shopware()->Config()->sCATEGORYPARENT;
		$Config['sFilter']['supplier'] = (int) $this->request()->sFilter_supplier;
		$Config['sFilter']['category'] = (int) $this->request()->sFilter_category;
		$Config['sFilter']['price'] =  (int) $this->request()->sFilter_price;
		$Config['sFilter']['propertygroup'] = $this->request()->sFilter_propertygroup;
		
		$Config['sSort'] = (int) $this->request()->sSort;
		
		if(!empty($this->request()->sPage))
			$Config['sPage'] = (int) $this->request()->sPage;
		else
			$Config['sPage'] = 0;
			
		if(!empty($this->request()->sPerPage))
			$Config['sPerPage'] = (int) $this->request()->sPerPage;
		elseif(!empty(Shopware()->Config()->sFUZZYSEARCHRESULTSPERPAGE))
			$Config['sPerPage'] = (int) Shopware()->Config()->sFUZZYSEARCHRESULTSPERPAGE;
		else
			$Config['sPerPage'] = 8;
						
		$Config['sOrder'] = intval($this->request()->sOrder);
		$Config['sSearch'] = urldecode(trim(str_replace("+"," ",strip_tags(htmlspecialchars_decode(html_entity_decode(stripslashes($this->request()->sSearch),ENT_QUOTES))))));
		
		if(function_exists('mb_detect_encoding')&&function_exists('mb_check_encoding') && mb_detect_encoding($Config['sSearch'])=='UTF-8'&&mb_check_encoding($Config['sSearch'], 'UTF-8')){
			$Config['sSearch'] = utf8_decode($Config['sSearch']);
		}
		return $Config;
	}
}