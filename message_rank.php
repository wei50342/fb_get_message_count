<!DOCTYPE html> 
<html>
  <head>
	<meta charset="utf-8">
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

	<link type="text/css" rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
	
	<script>
   
	//初始化
	window.fbAsyncInit = function() {
	
		//總共要顯示多少排名
		var HOW_MANY = 20;
		var id_list = [];
		var message_list=[];
		var id_to_name={};
		var id_count = 0;
		var message_count=0;
		var receiver_count=0;
		var get_name_count=0;
		var my_id="";
		
		//抓取所有訊息的ID
		function get_message(path){
			FB.api(
				path,{fields:"id"},
				function (response) {
					if(response && !response.error) {
						var len = response.data.length;
						for(var i=0;i<len;i++){
							id_list[id_count++]=response.data[i].id;
						} 
						m_log(id_list);
						
						//結束了
						if(typeof(response.paging) === "undefined"){
							m_log("end");
							get_message_count(0);
							return;
						}
						var next = response.paging["next"];
						next = next.replace("https://graph.facebook.com","");
						get_message(next);
						
					}else{
						m_log(response.error);
						handle_error(response.error);
						return;
						
					}
				}	
			);
		}		
		
		//抓取訊息的總數跟接收者
		function get_message_count(index){
			if(index>=id_count){
				
				//結束囉
				get_final_result();
				return;
			}
			
			//開始構造query囉
			
			var id_str = id_list.slice(index,index+10).join(",");
			q="SELECT recipients,message_count FROM thread WHERE folder_id = 0 and thread_id IN("+id_str+")";
			m_log(q);
			
			FB.api(
				"/fql",{q:q},
				function (response){
					if (response && !response.error) {
						for(var i=0;i<response.data.length;i++){
							message_list[message_count++]=response.data[i];
						}
						m_log(message_list);
						get_message_count(index+10);
					}else{
						m_log(response.error);
						handle_error(response.error);
						return;
					}
				}
			);
		}
		
		//接收完了 準備處理
		function get_final_result(){
		
			m_log("we are almost done");
			//終於費盡千辛萬苦把結果抓下來了 十分感動
			//message_list[i].message_count = 數量
			//message_list[i].recipients = 接收者
			message_list = 
				message_list.sort(function(b,a){ 
					return parseInt(a.message_count) - parseInt(b.message_count) 
				});
			
			m_log(message_list);
			message_list = message_list.slice(0,HOW_MANY);
			
			//處理最終結果 先抓名字
			//先判斷總共幾個名字要抓
			for(var i=0;i<HOW_MANY;i++){
				receiver_count+=message_list[i].recipients.length-1;
			}
			for(var i=0;i<HOW_MANY;i++){
				var receiver = message_list[i].recipients;
				for(var j=0;j<receiver.length;j++){
					get_name(receiver[j]);
				}
			}
			
			//m_log(message_list);
		}
		
		//傳入id抓取使用者姓名
		function get_name(id){
			if(id==my_id) return;
			$.ajax({
				url: 'http://graph.facebook.com/'+id+"?fields=name",
				dataType: 'json',
				async: false,
				success: function(data) {
					get_name_count++;
					id_to_name[id]=data.name;
					m_log(id+"->"+data.name);
					if(get_name_count>=receiver_count){
						make_result();
					}
					return true;
				}
			});
		}
		
		//抓我自己的id
		function get_my_id(){
			FB.api(
				"/me",
				function (response){
					if (response && !response.error) {
						my_id=response.id;
					}else{
						m_log(response.error);
						handle_error(response.error);
						return;
					}
				}
			);
		}
		
		//產生最終結果
		function make_result(){
			m_log("result...");
			
			for(var i=0;i<HOW_MANY;i++){
				if(i>=message_list.length) break;
				
				var str="";
				str+="<tr>";
				str+="<td>"+(i+1)+"</td>";
				str+="<td>"+message_list[i].message_count+"</td>";
				var receiver = message_list[i].recipients;
				var name_str="";
				for(var j=0;j<receiver.length;j++){
					if(receiver[j]==my_id) continue;
					name_str+=id_to_name[receiver[j]]+",";
				}
				str+="<td>"+name_str.substr(0,name_str.length-1)+"</td>";
				str+="</tr>";
				$("#result_table").append(str);
			}
		}
		
		//處理錯誤
		function handle_error(message){
			alert("ERROR:"+message);
		}
		
		//log
		function m_log(message){
			console.log(message);
			$("#log_list").prepend(message+"</br>");
			
		}
		
		FB.init({
			appId      : '137446166353522',
			xfbml      : true,
			version    : 'v2.0'
		});
		
		//登入 要求權限
		FB.login(function(){
		
			//抓自己id
			get_my_id();
			
			//先抓取所有thread_id
			get_message("/me/inbox");
			
		}, {scope: 'read_mailbox'});
	};

	//新增fb sdk
      (function(d, s, id){
         var js, fjs = d.getElementsByTagName(s)[0];
         if (d.getElementById(id)) {return;}
         js = d.createElement(s); js.id = id;
         js.src = "//connect.facebook.net/en_US/sdk.js";
         fjs.parentNode.insertBefore(js, fjs);
       }(document, 'script', 'facebook-jssdk'));
	   
    </script>
	<style>
		.title{
			text-align:center;
		}
		#log_list{
			color:white;
			background:#555;
			height:150px;
			margin-top:10px;
			padding:10px;
			overflow:auto;
		}
	</style>
  </head>
  <body>
	
	<div class="container">
		<div id="log_list">
			
		</div>
		<div class="title">
			<h1>訊息排行榜前20名</h1>
		</div>
		<div class="content">
			<table id="result_table" class="table table-striped table-hover">
				<tr>
					<th>排名</th>
					<th>訊息總數</th>
					<th>對話框裡的人</th>
				</tr>
			</table>
		</div>
	</div>
  </body>
</html>