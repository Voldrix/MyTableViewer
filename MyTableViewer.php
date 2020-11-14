<!--- @License
Copyright 2020 Ben Goriesky
Use of this source code is governed by an MIT-style license that can be found in the LICENSE file or at https://opensource.org/licenses/MIT -->
<?php
if(isset($_POST['table'])) { //AJAX table pull
  if(!is_readable('/tmp/MyTableViewer')) {http_response_code(400);exit();}
  $databases = json_decode(file_get_contents('/tmp/MyTableViewer',false),true);
  foreach($databases as $db) if($db['key'] === $_POST['id']) $thisdb = $db;
  $con = mysqli_connect($thisdb['host'],$thisdb['un'],$thisdb['pw'],$thisdb['db']);
  mysqli_set_charset($con,'utf8');
  $res = mysqli_query($con, 'SHOW COLUMNS FROM `'.$_POST['table'].'`');
  $columns = array_column(mysqli_fetch_all($res,MYSQLI_ASSOC),'Field');
  if(!empty($_POST['sort'])) $sort = ' ORDER BY '.$_POST['sort'];
  $res = mysqli_query($con, 'SELECT * FROM `'.$_POST['table'].'`'.$sort);
  echo '<table id=data><thead><tr>';$n=0;
  foreach($columns as $c) {echo '<th><a href=javascript:sortTable('.$n.') oncontextmenu=getTable("'.$_POST['table'].'",this,event)>'.$c.'</a></th>';$n++;}
  echo '</tr></thead><tbody>';
  while($r = mysqli_fetch_assoc($res)) {
    echo '<tr>';
    foreach($columns as $c) echo '<td>'.$r[$c].'</td>';
    echo '</tr>';
  }
  echo '</tbody></table>';
  exit();
}
if(isset($_POST['sub'])) { //Add DB
  if(is_readable('/tmp/MyTableViewer'))
    $databases = json_decode(file_get_contents('/tmp/MyTableViewer',false),true);
  if(empty($_POST['host'])) $_POST['host'] = 'localhost';
  if($_POST['priv'] === 'p') {
    if(empty($_COOKIE['mtvid'])) {
      $_COOKIE['mtvid'] = substr(str_shuffle(MD5(microtime())),0,15);
      setcookie('mtvid',$_COOKIE['mtvid'],['expires'=>strtotime('+120 days'),'path'=>'/','domain'=>$_SERVER['HTTP_HOST'],'secure'=>false,'httponly'=>false,'samesite'=>'Strict']);
    }
    $privid = $_COOKIE['mtvid'];
  }
  $key = md5($_POST['dbn'].$_POST['un'].$_POST['host'].$_COOKIE['mtvid']);
  $databases[] = ['key'=>$key,'db'=>$_POST['dbn'],'un'=>$_POST['un'],'pw'=>$_POST['pw'],'host'=>$_POST['host'],'priv'=>$privid];
  file_put_contents('/tmp/MyTableViewer',json_encode($databases)) or die('/tmp unwritable');
  chmod('/tmp/MyTableViewer',0600);
}
elseif(isset($_GET['del'])) { //Delete DB
  if(is_readable('/tmp/MyTableViewer'))
    $databases = json_decode(file_get_contents('/tmp/MyTableViewer',false),true);
  foreach($databases as $key => $db) {
    if($db['key'] === $_GET['db'])
      $delkey = $key; }//get just the last match
  if(isset($delkey)) unset($databases[$delkey]);
  file_put_contents('/tmp/MyTableViewer',json_encode($databases)) or die('/tmp unwritable');
}
else {
  if(isset($_COOKIE['mtvid'])) setcookie('mtvid',$_COOKIE['mtvid'],['expires'=>strtotime('+120 days'),'path'=>'/','domain'=>$_SERVER['HTTP_HOST'],'secure'=>false,'httponly'=>false,'samesite'=>'Strict']);
  $databases = json_decode(file_get_contents('/tmp/MyTableViewer',false),true);
}
?>
<!DOCTYPE html>
<html lang=en><head><meta charset=utf-8>
<meta name=viewport content='width=device-width'>
<title>MyTableViewer</title>
<style>
body {font-family:Arial;color:#101010;background-color:#F0F0F0;font-size:16px;line-height:18px;text-align:left;margin:0;padding:0;}
div {margin:0;padding:0;}
a {color:#101010;text-decoration:none;font-weight:bold;}
.dbinput {position:fixed;bottom:0;left:0;right:0;opacity:0;text-align:center;}
.dbinput:hover,.dbinput:focus-within,:focus {opacity:1;outline:none;}
.dbinput input,.dbinput label {padding:2px;margin:8px;background-color:#FFF;}
.dbinput input:checked + label {color:#F0F0F0;background-color:#101010;}
.welcome {text-align:center;padding:16px;box-shadow:-12px 0px 12px -10px #999, 12px 0px 12px -10px #999;}
.welcome a {display:block;background-color:#FFF;font-weight:normal;padding:8px 6px;margin-top:12px;border-bottom:1px solid #C0C0C0;border-radius:7px;}
.welcome a:hover {box-shadow: 0 0 11px -3px #999 inset;}
.head {padding:4px 6px;border-bottom:1px solid #BBB;}
.head .backbtn {font-size:32px;}
.head h1 {display:inline;font-size:24px;line-height:28px;font-weight:normal;}
.head select {-webkit-appearance:none;-moz-appearance:none;appearance:none;width:122px;line-height:20px;padding:2px 10px;background-color:#FFF;border-radius:12px;position:absolute;left:50%;margin-left:-75px;cursor:pointer;}
.head .delbtn {float:right;font-size:40px;padding-top:4px;font-weight:normal;}
table {border:none;font-size:14px;line-height:16px;}
table a {display:block;line-height:18px;font-size:16px;border-right:2px solid #E0E0E0;}
.dbdesc {padding:14px 4px 4px 4px;}
.dbdesc tr {background-image:linear-gradient(to bottom,#F8F8F8,#E8E8E8);vertical-align:top;}
.dbdesc table table td {font-family:'Arial Narrow';border-right:2px solid #E0E0E0;}
.dbdata {padding:4px 4px 8px 4px;}
.dbdata tr:nth-of-type(odd) {background-color:#FFF;}
</style></head><body>
<?php
if(isset($_GET['db']) && !isset($_GET['del'])) { //tables page
  foreach($databases as $db) {if($db['key'] === $_GET['db']) if(empty($db['priv']) || $db['priv'] === $_COOKIE['mtvid']) $thisdb = $db;}
  echo '<div class=head><a href="javascript:location.search=\'\'" class=backbtn>&lang; </a><h1 title="'.$thisdb['un'].'@'.$thisdb['host'].'"> '.$thisdb['db'].'</h1><select onchange="window.location=\'?db=\'+this.value;"><option value="#">Select Database</option>';
  foreach($databases as $db) if(empty($db['priv']) || $db['priv'] === $_COOKIE['mtvid']) echo '<option value="'.$db['key'].'">'.$db['db'].' - '.$db['un'].'@'.$db['host'].'</option>';
  echo '</select><a href="javascript:location.search+=\'&del=x\';" title="Remove This DB" class=delbtn>&#128465;</a></div><div class=dbdesc><table><thead><tr>';
  $con = mysqli_connect($thisdb['host'],$thisdb['un'],$thisdb['pw'],$thisdb['db']);
  mysqli_set_charset($con,'utf8');
  $tables = mysqli_query($con, 'SHOW TABLES');
  while($t = mysqli_fetch_row($tables)) { //list tables (headers)
    $rowcount = mysqli_fetch_row(mysqli_query($con, 'SELECT COUNT(*) FROM `'.$t[0].'`'));
    echo '<th><a href=javascript:getTable("'.$t[0].'")>'.$t[0].' <span style="font-weight:normal;">('.$rowcount[0].')</span></a></th>';
  }
  echo '</tr></thead><tbody><tr style="background-image:unset;">';
  mysqli_data_seek($tables,0);
  while($t = mysqli_fetch_row($tables)[0]) { //list table fields
    echo '<td><table><tbody>';
    $columns = mysqli_query($con, 'SHOW COLUMNS FROM `'.$t.'`');
    while($c = mysqli_fetch_assoc($columns))
      echo '<tr><th>'.$c['Field'].'</th><td>'.$c['Type'].'<br />'.($c['Null'] === 'YES' ? '<i>NULL</i> ' : '').$c['Key'].' '.$c['Default'].'</td></tr>';
    echo '</tbody></table></td>';
  }
  echo '</tr></tbody></table></div><div id=dbdiv class=dbdata></div>';
}
else { //welcome page
  echo '<div style="display:flex;flex-flow:row nowrap;justify-content:center;align-items:center;height:100vh;"><div class=welcome>MyTableViewer';
  foreach($databases as $db) if(empty($db['priv']) || $db['priv'] === $_COOKIE['mtvid']) echo '<a href="?db='.$db['key'].'" title="'.$db['un'].'@'.$db['host'].'">'.($db['priv'] ? $db['db'].' &#128274;&#xFE0E;' : $db['db']).'</a>';
  echo '</div></div>
  <div class=dbinput><form method=post>
    <input type=text size=15 name=dbn placeholder=Database required>
    <input type=text size=15 name=un placeholder=Username required>
    <input type=password size=15 name=pw placeholder=Password required>
    <input type=text size=15 name=host placeholder=localhost>
    <input type=checkbox name=priv id=priv value=p style="display:none;"><label for=priv>Private</label>
    <input type=submit name=sub value="Add DB">
  </form></div>';
} ?>
<script>
var dbdiv = document.getElementById('dbdiv') || 0;
var dbid = new URLSearchParams(window.location.search);
var ascend = false;

function getTable(table,column=false,e) {
  if(column) { //if sorting by column (right-click / context)
    e.preventDefault();
    ascend = !ascend;
    var aod = (ascend) ? '%20ASC' : '%20DESC';
    var sort = '&sort='+encodeURIComponent(column.innerText)+aod;
  }
  else var sort = '';
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if(this.readyState === 4 && this.status === 200)
      dbdiv.innerHTML = this.response;
  }
  xhttp.open('POST','?',true);
  xhttp.setRequestHeader('Content-type','application/x-www-form-urlencoded');
  xhttp.send('id='+dbid.get('db')+'&table='+table+sort);
}
function sortTable(n) {
  var rows = document.getElementById('data').rows;
  var ii = rows.length - 1;
  var switching = true;
  ascend = !ascend;

  if(ascend) {
    while(switching) {
      switching = false;
      for(i=1; i < ii; i++)
        if(rows[i].cells[n].innerHTML > rows[i+1].cells[n].innerHTML) {rows[i].insertAdjacentElement('beforebegin',rows[i+1]); switching = true;}
    }
  }
  else {
    while(switching) {
      switching = false;
      for(i=1; i < ii; i++)
        if(rows[i].cells[n].innerHTML < rows[i+1].cells[n].innerHTML) {rows[i].insertAdjacentElement('beforebegin',rows[i+1]); switching = true;}
    }
  }
}
</script></body></html>
