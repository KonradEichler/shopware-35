{* BUNDLE_BOX *} 
    {if !$sArticle.sVariants} 
            {if (0 == $sArticle.laststock) || (1 == $sArticle.laststock && $sArticle.instock > 0)} 
                    {include file="articles/bundles/article_bundle_box.tpl" sBundles=$sArticle.sBundles} 
            {/if} 
    {else} 
            {* Varianten: Lagerbestands�berpr�fung �ber JavaScript (changeDetails) *} 
            {include file="articles/bundles/article_bundle_box.tpl" sBundles=$sArticle.sBundles} 
    {/if} 
    {* /BUNDLE_BOX *} 

    {* ZUBEH�R_BUNDLE_BOX *} 
    {if $sArticle.sRelatedArticles && $sArticle.crossbundlelook} 
            {include file="articles/bundles/article_relatedarticles_box.tpl" sRelatedArticles=$sArticle.sRelatedArticles} 
    {/if} 
    {* /ZUBEH�R_BUNDLE_BOX *}