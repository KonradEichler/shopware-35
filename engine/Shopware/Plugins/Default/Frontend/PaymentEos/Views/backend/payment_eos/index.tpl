{extends file="backend/ext_js/index.tpl"}

{block name="backend_index_javascript" append}
<script type="text/javascript">
//<![CDATA[
Ext.application({
    name: 'PaymentEos',
	appFolder: '{url action=load}',
	    
    controllers: [
    	'List'
    ],
    autoCreateViewport: false,
    
    launch: function() {
    	
    	//this.store = Ext.create('PaymentEos.store.List');
    	//Ext.create('PaymentEos.view.List');
    	/*
    	this.getView('List').setStore(
    		this.getView('Store')
    	);
    	this.getView('Viewport').addItem(
    		this.getView('List')
    	);
    	*/
    }
});
//]]>
</script>
{/block}