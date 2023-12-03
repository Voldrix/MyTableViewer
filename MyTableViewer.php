<?php
//INIT
$databases = is_readable('/tmp/MyTableViewer') ? json_decode(file_get_contents('/tmp/MyTableViewer', false), true) : []; //read creds

if(isset($_GET['db'])) { //point to requested db's creds
  foreach($databases as $database)
    if($database['key'] === $_GET['db'])
      $db = $database;
  //mysql
  if(isset($db)) {
    error_reporting(0);
    mysqli_report(MYSQLI_REPORT_OFF);
    if($conn = mysqli_connect($db['host'], $db['un'], $db['pw'], $db['db']))
      mysqli_set_charset($conn, 'utf8mb4');
    else unset($db);
  }
}


//TABLE DATA
if(isset($_POST['table'])) {
  if(empty($db))
    _return(400);

  $select = empty($_POST['select']) ? '*' : str_replace(array('\\',';','"'), '', $_POST['select']); //user field selection
  $where  = empty($_POST['where'])  ? '' : ' WHERE ' . str_replace(array('\\',';','"'), '', $_POST['where']); //user where clause
  $table  = str_replace(array('\\',';','"','`'), '', $_POST['table']);

  $res = mysqli_query($conn, 'SELECT '.$select.' FROM `'.$table.'`'.$where);
  echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
  _return(200);
}


//GET TABLES
if(isset($_GET['schema'])) {
  if(empty($db))
    _return(400);

  //GET TABLE NAMES
  if($_GET['schema'] === '1') {
    $res = mysqli_query($conn, 'SELECT table_name, table_rows FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = "' . $db['db'] . '"');
    echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
    _return(200);
  }

  //GET COLUMN NAMES
  else {
    $res = mysqli_query($conn, 'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, CHARACTER_SET_NAME, COLLATION_NAME, COLUMN_TYPE, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = "' . $db['db'] . '" ORDER BY TABLE_NAME, ORDINAL_POSITION ASC');
    echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
    _return(200);
  }
}


//ADD DB
if(isset($_POST['dbName'])) {
  if(isset($_POST['priv'])) {
    if(empty($_COOKIE['mtvid'])) {
      $_COOKIE['mtvid'] = substr(str_shuffle(MD5(microtime())),0,15);
      setcookie('mtvid', $_COOKIE['mtvid'], ['expires'=>strtotime('+120 days'), 'path'=>'/', 'domain'=>$_SERVER['HTTP_HOST'], 'secure'=>false, 'httponly'=>false, 'samesite'=>'Strict']);
    }
    $priv = $_COOKIE['mtvid']; //reuse if preexisting
  }
  else $priv = null;
  $key    = md5($_POST['dbName'] . $_POST['un'] . $_POST['host'] . $priv);
  $dbName = str_replace(array('\\',';','"','<'), '', $_POST['dbName']);
  $un     = str_replace(array('\\',';','"','<'), '', $_POST['un']);
  $host   = empty($_POST['host']) ? 'localhost' : str_replace(array('\\',';','"','<'), '', $_POST['host']);

  if(empty($dbName) || empty($un) || empty($host))
    _return(400);

  $databases[] = ['key'=>$key, 'db'=>$dbName, 'un'=>$un, 'pw'=>$_POST['pw'], 'host'=>$host, 'priv'=>$priv];
  file_put_contents('/tmp/MyTableViewer', json_encode($databases)) or _return(500);
  chmod('/tmp/MyTableViewer', 0600);
}


//DELETE DB
if(isset($_GET['del'])) {
  foreach($databases as $key => $database)
    if($database['key'] === $_GET['db'])
      $delkey = $key; //get just the last match
  if(isset($delkey)) {
    unset($databases[$delkey]);
    file_put_contents('/tmp/MyTableViewer', json_encode($databases)) or _return(500);
    _return(200);
  }
  _return(404);
}


if(isset($_COOKIE['mtvid'])) //extend (private key) cookie lifespan
  setcookie('mtvid', $_COOKIE['mtvid'], ['expires'=>strtotime('+120 days'), 'path'=>'/', 'domain'=>$_SERVER['HTTP_HOST'], 'secure'=>false, 'httponly'=>false, 'samesite'=>'Strict']);


//RETURN
function _return($code) {
  if(!empty($conn))
    mysqli_close($conn);
  http_response_code($code);
  exit();
}
?>
<!DOCTYPE html>
<html lang=en><head><meta charset=utf-8>
<meta name=viewport content='width=device-width'>
<title>MyTableViewer</title>
<style>
body {font-family:Arial;font-size:16px;line-height:18px;color:#101010;background-color:#F0F0F0;text-align:left;margin:0;padding:0;}
div {margin:0;padding:0;}
a {color:#101010;font-weight:bold;text-decoration:none;cursor:pointer;}
.homeView {display:flex;flex-flow:row nowrap;justify-content:center;align-items:center;height:100vh;height:100dvh;}
.dbView {display:none;height:100vh;height:100dvh;}

.dbInput {position:fixed;bottom:0;left:0;right:0;opacity:0;text-align:center;outline:none;}
.dbInput:hover, .dbInput:focus-within, :focus {opacity:1;}
.dbInput input, .dbInput label {padding:2px;margin:8px;background-color:#FFF;}
.dbInput input:checked + label {color:#F0F0F0;background-color:#101010;}

.welcome {text-align:center;padding:16px;box-shadow:-12px 0px 12px -10px #999, 12px 0px 12px -10px #999;}
.welcome a {display:block;background-color:#FFF;font-weight:normal;padding:8px 6px;margin-top:12px;border-bottom:1px solid #C0C0C0;border-radius:7px;}
.welcome a:hover {box-shadow:0 0 11px -3px #999 inset;}

.head {padding:4px 6px;border-bottom:1px solid #BBB;margin-bottom:10px;}
.head .backbtn {font-size:32px;padding:0 2px;}
.head h1 {display:inline;font-size:24px;line-height:28px;font-weight:normal;}
.head select {appearance:none;width:122px;line-height:20px;padding:2px 10px;background-color:#FFF;border-radius:12px;position:absolute;left:50%;margin-left:-75px;cursor:pointer;}
.head .delbtn {float:right;font-size:40px;padding-top:4px;font-weight:normal;}

table {border:none;font-size:14px;line-height:16px;}
table a {display:block;line-height:18px;font-size:16px;}
.tablesContainer {padding:12px 4px 4px 4px;}
.tablesContainer table {display:inline-table;font-family:'Arial Narrow';vertical-align:top;margin-right:2px;}
.tablesContainer tr {background-image:linear-gradient(to bottom,#F8F8F8,#E8E8E8);vertical-align:top;}
.tablesContainer table td {border-right:2px solid #E0E0E0;}
.tablesContainer table span {display:inline-block;vertical-align:top;}

.dataContainer {padding:4px 4px 8px 4px;}
.dataContainer tr:nth-of-type(odd) {background-color:#FFF;}
.dataContainer table tr:first-of-type div {display:flex;flex-flow:row nowrap;}
.dataContainer table tr:first-of-type a {flex-grow:1;}
</style></head><body>

<div class=homeView id=homeView>
  <div class=welcome>MyTableViewer
  <?php foreach($databases as $dbx) 
    if(empty($dbx['priv']) || $dbx['priv'] === $_COOKIE['mtvid'])
      echo '<a onclick="loadDatabase(\''.$dbx['key'].'\', \''.$dbx['db'].'\')" title="'.$dbx['un'].'@'.$dbx['host'].'" id="'.$dbx['key'].'">'.($dbx['priv'] ? $dbx['db'].' &#128274;&#xFE0E;' : $dbx['db']).'</a>'; ?>
  </div>
  <div class=dbInput>
    <form method=post>
      <input type=text size=15 name=dbName placeholder=Database pattern="[a-zA-Z0-9_-\.]+" required>
      <input type=text size=15 name=un placeholder=Username pattern="[a-zA-Z0-9_-\.]+" required>
      <input type=password size=15 name=pw placeholder=Password required>
      <input type=text size=15 name=host placeholder=localhost pattern="[a-zA-Z0-9_-\.]*">
      <input type=checkbox name=priv id=priv value=p style="display:none;"><label for=priv>Private</label>
      <input type=submit name=sub value="Add DB">
    </form>
  </div>
</div>


<div class=dbView id=dbView>
  <div class=head>
    <a onclick="dbView.style.display='none';homeView.style.display='flex'" class=backbtn>&lang; </a><h1 id=dbHead title="un@host">db name</h1>
    <select onchange="loadDatabase(this.value, this.options[this.selectedIndex].getAttribute('dbName'))">
      <option value="">Select Database</option>
      <?php foreach($databases as $dbx)
        if(empty($dbx['priv']) || $dbx['priv'] === $_COOKIE['mtvid'])
          echo '<option value="'.$dbx['key'].'" dbName="'.$dbx['db'].'">'.$dbx['db'].' - '.$dbx['un'].'@'.$dbx['host'].'</option>'; ?>
    </select>
    <a onclick=dbDelete() title="Remove This DB From List" class=delbtn>&#128465;</a>
  </div>
  <div class=selectContainer id=selectContainer>
    SELECT <input type=text id=select placeholder="list,of,fileds,empty=*"> WHERE <input type=text id=where placeholder="column='value'"> <input type=button onclick="document.getElementById('select').value='';document.getElementById('where').value=''" value=Clear>
  </div>
  <div class=tablesContainer id=tablesContainer>
  </div>
  <div class=dataContainer id=dataContainer>
  </div>
</div>

<script>
var db, tableName, listTables, listColumns, dataObj, done, lastColSorted, ascending = false;
var arrow = document.createElement('span');

function loadDatabase(dbID, dbName) {
  if(!dbID) return;
  db = dbID;
  tablesContainer.innerHTML = null;
  dataContainer.innerHTML = null;
  homeView.style.display = 'none';
  dbView.style.display = 'block';
  dbHead.innerText = dbName;
  getTableSchema();
}


function getTableSchema() {
  if(!db) return;
  done = 0;

  //list of tables
  var xhttp = new XMLHttpRequest();
  xhttp.onloadend = function() {
    if(this.status === 200) {
      listTables = JSON.parse(this.response);
      if(done) renderTables();
      else done = 1;
    }
    else tablesContainer.innerText = 'Error connecting to database';
  }
  xhttp.open('GET', '?schema=1&db='+db, true);
  xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  xhttp.send();

  //list of columns
  var yhttp = new XMLHttpRequest();
  yhttp.onloadend = function() {
    if(this.status === 200) {
      listColumns = JSON.parse(this.response);
      if(done) renderTables();
      else done = 1;
    }
    else tablesContainer.innerText = 'Error connecting to database';
  }
  yhttp.open('GET', '?schema=2&db='+db, true);
  yhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  yhttp.send();
}


function getTableData(_table) {
  tableName = _table;
  var xhttp = new XMLHttpRequest();
  xhttp.onloadend = function() {
    if(this.status === 200) {
      dataObj = JSON.parse(this.response);
      renderData();
    }
    else alert(`Error: ${this.status} ${this.statusText}`);
  }

  var selectStmt = (select.value) ? '&select=' + encodeURIComponent(select.value) : '';
  var whereStmt = (where.value) ? '&where=' + encodeURIComponent(where.value) : '';

  xhttp.open('POST', '?db='+db, true);
  xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  xhttp.send('table='+encodeURIComponent(tableName) + selectStmt + whereStmt);
}


function renderTables() {
  tablesContainer.innerHTML = null;
  for(tbl of listTables) {
    let newTable = document.createElement('table');
    let row = newTable.insertRow();
    let cell = row.insertCell();
    cell.innerHTML = `<a onclick=getTableData("${tbl['table_name']}")>${tbl['table_name']} <span style="font-weight:normal;">(${tbl['table_rows']})</span></a>`;
    for(col of listColumns) {
      if(col['TABLE_NAME'] !== tbl['table_name']) continue;
      row = newTable.insertRow();
      cell = row.insertCell();
      col['COLUMN_DEFAULT'] = (col['COLUMN_DEFAULT'] == null || col['COLUMN_DEFAULT'] == 'NULL') ? '' : col['COLUMN_DEFAULT'];
      cell.innerHTML = `<strong>${col['COLUMN_NAME']}</strong> <span>${col['COLUMN_TYPE']}<br>${col['IS_NULLABLE'] === 'YES' ? 'null' : ''}${col['COLUMN_DEFAULT']}</span>`;
      cell.title = `${col['CHARACTER_SET_NAME']} : ${col['COLLATION_NAME']}`;
    }
    tablesContainer.appendChild(newTable);
  }
}


function renderData() {
  var columnIdx = 0;
  var listFields = (dataObj.length) ? Object.keys(dataObj[0]) : [];
  var newTable = document.createElement('table');
  newTable.id = 'dataTable';
  var row = newTable.insertRow();

  for(col of listFields) { //data table column names
    let cell = row.insertCell();
    cell.innerHTML = `<div><a onclick=sortTable(${columnIdx}) oncontextmenu=sortTableNumerically(event,${columnIdx})>${col}</a></div>`;
    columnIdx += 1;
  }
  for(rows of dataObj) {
    row = newTable.insertRow();
    for(column in rows) {
      let cell = row.insertCell();
      cell.textContent = rows[column];
    }
  }
  dataContainer.innerHTML = null;
  dataContainer.appendChild(newTable);
}


function dbDelete() {
  var xhttp = new XMLHttpRequest();
  xhttp.onloadend = function() {
    if(this.status === 200 || this.status === 404) {
      dbView.style.display = 'none';
      homeView.style.display = 'flex';
      document.getElementById(db).remove();
    }
    else alert(`Error: ${this.status} ${this.statusText}`);
  }
  xhttp.open('GET', '?del=x&db='+db, true);
  xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
  xhttp.send();
}


function sortTable(n) {
  var keys = Object.keys(dataObj[0]);
  var propertyName = keys[n];
  ascending = !ascending;
  if(ascending)
    dataObj.sort((a, b) => (a[propertyName] === null) - (b[propertyName] === null) || a[propertyName] > b[propertyName] && 1 || -(a[propertyName] < b[propertyName]));
  else
    dataObj.sort((a, b) => (a[propertyName] === null) - (b[propertyName] === null) || a[propertyName] < b[propertyName] && 1 || -(a[propertyName] > b[propertyName]));
  renderData();
  arrow = dataTable.rows[0].cells[n].firstChild.insertAdjacentElement('beforeend', arrow);
  arrow.innerHTML = (ascending) ? 'ᐃ' : 'ᐁ';
}


function sortTableNumerically(e, n) {
  e.preventDefault();
  var keys = Object.keys(dataObj[0]);
  var propertyName = keys[n];
  var collator = new Intl.Collator([], {numeric: true});
  ascending = !ascending;
  if(ascending)
    dataObj.sort((a, b) => (a[propertyName] === null) - (b[propertyName] === null) || collator.compare(a[propertyName], b[propertyName]));
  else
    dataObj.sort((a, b) => (a[propertyName] === null) - (b[propertyName] === null) || collator.compare(b[propertyName], a[propertyName]));
  renderData();
  arrow = dataTable.rows[0].cells[n].firstChild.insertAdjacentElement('beforeend', arrow);
  arrow.innerHTML = (ascending) ? 'ᐃ' : 'ᐁ';
}
</script></body></html>
