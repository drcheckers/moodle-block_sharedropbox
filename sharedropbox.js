
// initialisation call from block - once for each available shared dropbox
function addsharedropbox (Y, code, link){
    Y.use('dom',function(){
        var assignment = Y.one('li#module-'+code+' div');
        if(assignment){
            var newnode = Y.Node.create('&nbsp;'+link);
            assignment.insert(newnode);
        }
    });
}



YUI().use("yui2-container","yui2-dragdrop","io-base","json","node", 
    function(Y)
    {
        var YAHOO = Y.YUI2;
        var panel = new YAHOO.widget.Panel('panel',{
                        visible:false,modal:true,width:"300px",height:"auto",
                        close: true,draggable: false,fixedcenter: false
                    });
        //addhandlers();
        Y.one('body').delegate('click', function(e){addLike(eventid(e));}, '.addLike');
        Y.one('body').delegate('click', function(e){deleteLike(eventid(e));}, '.deleteLike');
        Y.one('body').delegate('click', function(e){showPanel(eventid(e));}, '.showComment');
        Y.one('body').delegate('click', function(e){addComment(eventid(e));}, '.addButton');
        Y.one('body').delegate('click', function(e){deleteComment(eventid(e));}, '.deleteComment');
        
        function addLike(p){
           var cmid=Y.one('#cmid').get('value');
           ajaxurl('ajax.php?action=addlike&sub='+p,'cmid='+cmid);            
        }
        function deleteComment(p){
           var cmid=Y.one('#cmid').get('value');
           ajaxurl('ajax.php?action=deletecomment&sub='+p,'cmid='+cmid);            
           panel.hide();
        }
        function addComment(p){
           var cmid=Y.one('#cmid').get('value');
           var text=Y.one('#addcomment').get('value');
           ajaxurl('ajax.php?action=addcomment&sub='+p,'cmid='+cmid+'&comment='+text);            
           panel.hide();
        }
        function deleteLike(p){
           var cmid=Y.one('#cmid').get('value');
           ajaxurl('ajax.php?action=deletelike&sub='+p,'cmid='+cmid);
        }
        
        function showPanel(p)
        {
            panel.cfg.setProperty('context',['sc_'+p,'tl','bl']);
            panel.cfg.setProperty('width','400px');
            panel.cfg.setProperty('close',true);
            panel.cfg.setProperty('draggable',true);
            panel.cfg.setProperty('modal',true);
            panel.cfg.setProperty('constraintoviewport',false);

            var cmid=Y.one('#cmid').get('value');
            ajaxurl('ajax.php?action=viewcomments&sub='+p,'cmid='+cmid);
                                   
            panel.show();            
        }
                
        function start(id, o) {
          Y.one('#panel_bd').setContent('');
        };

        function complete(id, o) {
            var data = eval('(' + o.responseText + ')');
            var r = Y.JSON.parse(o.responseText);
            Y.each(r,function(x,y){
                Y.one('#'+y).setContent(x);
            });
        };

        function ajaxurl(uri,data){
           Y.on('io:complete', complete, Y);
           Y.on('io:start', start, Y);
           var request = Y.io(uri,{method:'POST',data:data});   
        }
           
        // helper function to extract post _ id number from event target id
        function eventid(e){
            return parse_id(e.target.get('id'));
        }
})

function parse_id(id){
    var bits=id.split('_');
    return bits[bits.length-1];    
}
 