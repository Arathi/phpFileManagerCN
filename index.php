<?
//a:7:{s:4:"lang";s:2:"pt";s:9:"auth_pass";s:32:"d41d8cd98f00b204e9800998ecf8427e";s:8:"quota_mb";i:0;s:13:"upload_filter";s:0:"";s:7:"fm_root";s:0:"";s:17:"cookie_cache_time";i:1093318224;s:7:"version";s:5:"0.9.1";}
/*--------------------------------------------------
 | phpFileManager 0.9.1
 | By Fabrício Seger Kolling
 | Copyright (c) 2004 Fabrício Seger Kolling
 | Email: dulldusk@nho.com.br
 | URL: http://phpfm.sf.net
 | Last Changed: 2004-07-25
 +--------------------------------------------------
 | CONFIGURATION AND INSTALATION NOTES
 +--------------------------------------------------
 | This program does not include any instalation or configuration
 | notes because it just does not require them.
 | Simply throw this file anywhere in your webserver and enjoy!!
 +--------------------------------------------------
 | LICENCE
 +--------------------------------------------------
 | This program is free software; you can redistribute it and/or modify
 | it under the terms of the GNU General Public License as published by
 | the Free Software Foundation; either version 2 of the License, or
 | (at your option) any later version.
 +--------------------------------------------------
 | This program is distributed in the hope that it will be useful,
 | but WITHOUT ANY WARRANTY; without even the implied warranty of
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 | GNU General Public License for more details.
 +--------------------------------------------------
 | GNU General Public License is available on http://phpfm.sf.net/gpl.html
 +--------------------------------------------------
 | CONTRIBUTIONS
 +--------------------------------------------------
 | This program in part contains or is derived from the following GNU projects.
 | Thanks to your enffort !!
 +--------------------------------------------------
 | TAR/GZIP/BZIP2/ZIP ARCHIVE CLASSES 2.0
 | By Devin Doucette
 | Copyright (c) 2004 Devin Doucette
 | Email: darksnoopy@shaw.ca
 | URL: http://www.phpclasses.org
 +--------------------------------------------------
*/
// +--------------------------------------------------
// | Config
// +--------------------------------------------------
    $cfg = new config();
    $cfg->load();
    //ini_set("display_errors",1);
    //ini_set("error_reporting","E_ERROR");
// +--------------------------------------------------
// | Defauts
// +--------------------------------------------------
    header("Pragma: no-cache");
    header("Cache-Control: no-store");
    clearstatcache();
    foreach ($HTTP_GET_VARS as $key => $val) $$key=htmldecode($val);
    foreach ($HTTP_POST_VARS as $key => $val) $$key=htmldecode($val);
    foreach ($HTTP_COOKIE_VARS as $key => $val) $$key=htmldecode($val);
    if (empty($_SERVER["HTTP_X_FORWARDED_FOR"])) $ip = $_SERVER["REMOTE_ADDR"]; //nao usa proxy
    else $ip = $_SERVER["HTTP_X_FORWARDED_FOR"]; //usa proxy
    $islinux = !(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    $path_info = pathinfo($_SERVER["SCRIPT_FILENAME"]);
    $url_info = parse_url($_SERVER["HTTP_REFERER"]);
    //$doc_root = str_replace(substr($url_info["path"],0,strrpos($url_info["path"],"/")),"",$path_info["dirname"]);
    $doc_root = $_SERVER["DOCUMENT_ROOT"];
    if (!isset($dir_atual)){
        $dir_atual = $path_info["dirname"]."/";
        if (!$islinux) $dir_atual = ucfirst($dir_atual);
        @chmod($dir_atual,0777);
    } else $dir_atual = formata_path($dir_atual);
    // Auto Expand Local Path
    if (!isset($expanded_tree)){
        $expanded_tree = "";
        $mat = explode("/",$path_info["dirname"]);
        for ($x=0;$x<count($mat);$x++) $expanded_tree .= ":".$mat[$x];
        setcookie("expanded_tree", $expanded_tree , 0 , "/");
    }
    if (!isset($fm_root_atual)){
        if (strlen($fm_root)>0) $fm_root_atual = $fm_root;
        else {
            if (!$islinux) $fm_root_atual = ucfirst($path_info["dirname"]."/");
            else $fm_root_atual = $doc_root."/";
        }
        setcookie("fm_root_atual", $fm_root_atual , 0 , "/");
    } elseif (isset($set_fm_root_atual)) {
        if (!$islinux) $fm_root_atual = ucfirst($set_fm_root_atual);
        setcookie("fm_root_atual", $fm_root_atual , 0 , "/");
    }
    if (!isset($resolveIDs)){
        setcookie("resolveIDs", 0 , $cookie_cache_time , "/");
    } elseif (isset($set_resolveIDs)){
        $resolveIDs=($resolveIDs)?0:1;
        setcookie("resolveIDs", $resolveIDs , $cookie_cache_time , "/");
    }
    if ($resolveIDs){
        exec("cat /etc/passwd",$mat_passwd);
        exec("cat /etc/group",$mat_group);
    }
    $is_reachable = (stristr($dir_atual,$doc_root)==false);
// +--------------------------------------------------
// | File Manager Actions
// +--------------------------------------------------
if ($loggedon_pass==$auth_pass){
    switch ($frame){
        case 1: break; // Empty Frame
        case 2: frame2(); break;
        case 3: frame3(); break;
        default:
            switch($action){
                case 1: logout(); break;
                case 2: config_form(); break;
                case 3: download(); break;
                case 4: view(); break;
                case 5: server_info(); break;
                case 6: execute(); break;
                case 7: edit_file_form(); break;
                case 8: chmod_form(); break;
                case 9: shell_form(); break;
                case 10: upload_form(); break;
                default: frameset();
            }
    }
} else {
    if (isset($senha)) login();
    else form_login();
}
// +--------------------------------------------------
// | Config Class
// +--------------------------------------------------
class config {
    var $data;
    var $filename;
    function config(){
        global $_SERVER;
        $this->data = array(
            'lang'=>'en',
            'auth_pass'=>md5(''),
            'quota_mb'=>0,
            'upload_filter'=>'',
            'fm_root'=>'',
            'cookie_cache_time'=>time()+60*60*24*30, // 30 Dias
            'version'=>'0.9.1'
            );
        $data = false;
        $this->filename = $_SERVER["SCRIPT_FILENAME"];
        if (file_exists($this->filename)){
            $mat = file($this->filename);
            $objdata = trim(substr($mat[1],2));
            if (strlen($objdata)>0) $data = unserialize($objdata);
        }
        if (is_array($data)&&count($data)==count($this->data)) $this->data = $data;
        else $this->save();
    }
    function save(){
        $objdata = "<?".chr(13).chr(10)."//".serialize($this->data).chr(13).chr(10);
        if (strlen($objdata)>0){
            if (file_exists($this->filename)){
                $mat = file($this->filename);
                if ($fh = @fopen($this->filename, "w")){
                    @fputs($fh,$objdata,strlen($objdata));
                    for ($x=2;$x<count($mat);$x++) @fputs($fh,$mat[$x],strlen($mat[$x]));
                    @fclose($fh);
                }
            }
        }
    }
    function load(){
        foreach ($this->data as $key => $val) $GLOBALS[$key] = $val;
    }
}
// +--------------------------------------------------
// | Internationalization
// +--------------------------------------------------
function et($tag){
    global $lang;
    // English
    $en['Version'] = 'Version';
    $en['DocRoot'] = 'Document Root';
    $en['FLRoot'] = 'File Manager Root';
    $en['Name'] = 'Name';
    $en['Enter'] = 'Enter';
    $en['Send'] = 'Send';
    $en['Refresh'] = 'Refresh';
    $en['SaveConfig'] = 'Save Configurations';
    $en['SavePass'] = 'Save Password';
    $en['SaveFile'] = 'Save File';
    $en['Save'] = 'Save';
    $en['Leave'] = 'Leave';
    $en['Edit'] = 'Edit';
    $en['View'] = 'View';
    $en['Config'] = 'Config';
    $en['Ren'] = 'Rename';
    $en['Rem'] = 'Delete';
    $en['Compress'] = 'Compress';
    $en['Decompress'] = 'Decompress';
    $en['ResolveIDs'] = 'Resolve IDs';
    $en['Move'] = 'Move';
    $en['Copy'] = 'Copy';
    $en['ServerInfo'] = 'Server Info';
    $en['CreateDir'] = 'Create Directory';
    $en['CreateArq'] = 'Create File';
    $en['ExecCmd'] = 'Execute Command';
    $en['Upload'] = 'Upload';
    $en['UploadEnd'] = 'Upload Finished';
    $en['Perms'] = 'Permissions';
    $en['Owner'] = 'Owner';
    $en['Group'] = 'Group';
    $en['Other'] = 'Other';
    $en['Size'] = 'Size';
    $en['Date'] = 'Date';
    $en['Type'] = 'Type';
    $en['Free'] = 'free';
    $en['Shell'] = 'Shell';
    $en['Read'] = 'Read';
    $en['Write'] = 'Write';
    $en['Exec'] = 'Execute';
    $en['Apply'] = 'Apply';
    $en['StickyBit'] = 'Sticky Bit';
    $en['Pass'] = 'Password';
    $en['Lang'] = 'Language';
    $en['File'] = 'File';
    $en['File_s'] = 'file(s)';
    $en['To'] = 'to';
    $en['Destination'] = 'Destination';
    $en['Configurations'] = 'Configurations';
    $en['JSError'] = 'JavaScript Error';
    $en['NoSel'] = 'There are no selected itens';
    $en['SelDir'] = 'Select the destination directory on the left tree';
    $en['TypeDir'] = 'Enter the directory name';
    $en['TypeArq'] = 'Enter the file name';
    $en['TypeCmd'] = 'Enter the command';
    $en['TypeArqComp'] = 'Enter the file name.\\nThe extension will define the compression type.\\nEx:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $en['RemSel'] = 'DELETE selected itens';
    $en['NoDestDir'] = 'There is no selected destination directory';
    $en['DestEqOrig'] = 'Origin and destination directories are equal';
    $en['NoNewPerm'] = 'New permission not set';
    $en['CopyTo'] = 'COPY to';
    $en['MoveTo'] = 'MOVE to';
    $en['AlterPermTo'] = 'CHANGE PERMISSIONS to';
    $en['ConfExec'] = 'Confirm EXECUTE';
    $en['ConfRem'] = 'Confirm DELETE';
    $en['EmptyDir'] = 'Empty directory';
    $en['IOError'] = 'I/O Error';
    $en['FileMan'] = 'PHP File Manager';
    $en['TypePass'] = 'Enter the password';
    $en['InvPass'] = 'Invalid Password';
    $en['ReadDenied'] = 'Read Access Denied';
    $en['FileNotFound'] = 'File not found';
    $en['AutoClose'] = 'Close on Complete';
    $en['OutDocRoot'] = 'File beyond DOCUMENT_ROOT';
    $en['NoCmd'] = 'Error: Command not informed';
    $en['ConfTrySave'] = 'File without write permisson.\\nTry to save anyway';
    $en['ConfSaved'] = 'Configurations saved';
    $en['PassSaved'] = 'Password saved';
    $en['FileDirExists'] = 'File or directory already exists';
    $en['NoPhpinfo'] = 'PHPINFO() disabled';
    $en['NoReturn'] = 'no return';
    $en['FileSent'] = 'File sent';
    $en['SpaceLimReached'] = 'Space limit reached';
    $en['InvExt'] = 'Invalid extension';
    $en['FileNoOverw'] = 'File could not be overwritten';
    $en['FileOverw'] = 'File overwritten';
    $en['FileIgnored'] = 'File ignored';
    $en['ChkVer'] = 'Check sf.net for new version';
    $en['ChkVerAvailable'] = 'New version, click to download!!';
    $en['ChkVerNotAvailable'] = 'No new version available. :(';
    $en['ChkVerError'] = 'Connection Error.';
    $en['Website'] = 'Website';
    $en['SendingForm'] = 'Sending files, please wait';
    $en['NoFileSel'] = 'No file selected';

    // Portuguese
    $pt['Version'] = 'Versão';
    $pt['DocRoot'] = 'Document Root';
    $pt['FLRoot'] = 'File Manager Root';
    $pt['Name'] = 'Nome';
    $pt['Enter'] = 'Entrar';
    $pt['Send'] = 'Enviar';
    $pt['Refresh'] = 'Atualizar';
    $pt['SaveConfig'] = 'Salvar Configurações';
    $pt['SavePass'] = 'Salvar Senha';
    $pt['SaveFile'] = 'Salvar Arquivo';
    $pt['Save'] = 'Salvar';
    $pt['Leave'] = 'Sair';
    $pt['Edit'] = 'Editar';
    $pt['View'] = 'Visualizar';
    $pt['Config'] = 'Config';
    $pt['Ren'] = 'Renomear';
    $pt['Rem'] = 'Apagar';
    $pt['Compress'] = 'Compactar';
    $pt['Decompress'] = 'Descompactar';
    $pt['ResolveIDs'] = 'Resolver IDs';
    $pt['Move'] = 'Mover';
    $pt['Copy'] = 'Copiar';
    $pt['ServerInfo'] = 'Server Info';
    $pt['CreateDir'] = 'Criar Diretório';
    $pt['CreateArq'] = 'Criar Arquivo';
    $pt['ExecCmd'] = 'Executar Comando';
    $pt['Upload'] = 'Upload';
    $pt['UploadEnd'] = 'Upload Terminado';
    $pt['Perms'] = 'Permissões';
    $pt['Owner'] = 'Dono';
    $pt['Group'] = 'Grupo';
    $pt['Other'] = 'Outros';
    $pt['Size'] = 'Tamanho';
    $pt['Date'] = 'Data';
    $pt['Type'] = 'Tipo';
    $pt['Free'] = 'livre';
    $pt['Shell'] = 'Shell';
    $pt['Read'] = 'Ler';
    $pt['Write'] = 'Escrever';
    $pt['Exec'] = 'Executar';
    $pt['Apply'] = 'Aplicar';
    $pt['StickyBit'] = 'Sticky Bit';
    $pt['Pass'] = 'Senha';
    $pt['Lang'] = 'Idioma';
    $pt['File'] = 'Arquivo';
    $pt['File_s'] = 'arquivo(s)';
    $pt['To'] = 'para';
    $pt['Destination'] = 'Destino';
    $pt['Configurations'] = 'Configurações';
    $pt['JSError'] = 'Erro de JavaScript';
    $pt['NoSel'] = 'Não há itens selecionados';
    $pt['SelDir'] = 'Selecione o diretório de destino na árvore a esquerda';
    $pt['TypeDir'] = 'Digite o nome do diretório';
    $pt['TypeArq'] = 'Digite o nome do arquivo';
    $pt['TypeCmd'] = 'Digite o commando';
    $pt['TypeArqComp'] = 'Digite o nome do arquivo.\\nA extensão determina o tipo de compactação.\\nEx:\\nnome.zip\\nnome.tar\\nnome.bzip\\nnome.gzip';
    $pt['RemSel'] = 'APAGAR itens selecionados';
    $pt['NoDestDir'] = 'Não há um diretório de destino selecionado';
    $pt['DestEqOrig'] = 'Diretório de origem e destino iguais';
    $pt['NoNewPerm'] = 'Nova permissão não foi setada';
    $pt['CopyTo'] = 'COPIAR para';
    $pt['MoveTo'] = 'MOVER para';
    $pt['AlterPermTo'] = 'ALTERAR PERMISSÕES para';
    $pt['ConfExec'] = 'Confirma EXECUTAR';
    $pt['ConfRem'] = 'Confirma APAGAR';
    $pt['EmptyDir'] = 'Diretório vazio';
    $pt['IOError'] = 'Erro de E/S';
    $pt['FileMan'] = 'PHP File Manager';
    $pt['TypePass'] = 'Digite a senha';
    $pt['InvPass'] = 'Senha Inválida';
    $pt['ReadDenied'] = 'Acesso de leitura negado';
    $pt['FileNotFound'] = 'Arquivo não encontrado';
    $pt['AutoClose'] = 'Fechar Automaticamente';
    $pt['OutDocRoot'] = 'Arquivo fora do DOCUMENT_ROOT';
    $pt['NoCmd'] = 'Erro: Comando não informado';
    $pt['ConfTrySave'] = 'Arquivo sem permissão de escrita.\\nTentar salvar assim mesmo';
    $pt['ConfSaved'] = 'Configurações salvas';
    $pt['PassSaved'] = 'Senha salva';
    $pt['FileDirExists'] = 'Arquivo ou diretório já existe';
    $pt['NoPhpinfo'] = 'PHPINFO() desabilitada';
    $pt['NoReturn'] = 'sem retorno';
    $pt['FileSent'] = 'Arquivo enviado';
    $pt['SpaceLimReached'] = 'Limite de espaço alcançado';
    $pt['InvExt'] = 'Extensão inválida';
    $pt['FileNoOverw'] = 'Arquivo não pode ser sobreescrito';
    $pt['FileOverw'] = 'Arquivo sobreescrito';
    $pt['FileIgnored'] = 'Arquivo omitido';
    $pt['ChkVer'] = 'Verificar sf.net por nova versão';
    $pt['ChkVerAvailable'] = 'Nova versão, clique para download!!';
    $pt['ChkVerNotAvailable'] = 'Não há nova versão disponível :(';
    $pt['Website'] = 'Website';
    $pt['SendingForm'] = 'Enviando arquivos, aguarde';
    $pt['NoFileSel'] = 'Nenhum arquivo selecionado';

    $lang_ = $$lang;
    if (isset($lang_[$tag])) return $lang_[$tag];
    else return "undefined";
}
// +--------------------------------------------------
// | File System
// +--------------------------------------------------
function total_size($arg) {
 $total = 0;
 if (file_exists($arg)) {
   if (is_dir($arg)) {
     $handle = opendir($arg);
     while($aux = readdir($handle)) {
       if ($aux != "." && $aux != "..") $total += total_size($arg."/".$aux);
     }
     closedir($handle);
   } else $total = filesize($arg);
 }
 return $total;
}
function total_delete($arg) {
 if (file_exists($arg)) {
   chmod($arg,0777);
   if (is_dir($arg)) {
     $handle = opendir($arg);
     while($aux = readdir($handle)) {
       if ($aux != "." && $aux != "..") total_delete($arg."/".$aux);
     }
     closedir($handle);
     rmdir($arg);
   } else unlink($arg);
 }
}
function total_copy($orig,$dest) {
 $ok = true;
 if (file_exists($orig)) {
   if (is_dir($orig)) {
     mkdir($dest,0777);
     $handle = opendir($orig);
     while(($aux = readdir($handle))&&($ok)) {
       if ($aux != "." && $aux != "..") $ok = total_copy($orig."/".$aux,$dest."/".$aux);
     }
     closedir($handle);
   } else $ok = copy((string)$orig,(string)$dest);
 }
 return $ok;
}
function total_move($orig,$dest) {
    if (total_copy($orig,$dest)) total_delete($orig);
}
function download(){
    global $dir_atual,$file_name;
    $file = $dir_atual.$file_name;
    if(file_exists($file)){
    //  && !eregi( "p?html?", $vFile ) && !eregi( "inc", $vFile ) && !eregi( "php3?", $vFile )
        $size = filesize($file);
        header("Content-Type: application/save");
        header("Content-Length: $size");
        header("Content-Disposition: attachment; filename=\"$file_name\"");
        header("Content-Transfer-Encoding: binary");
        if ($fh = fopen("$file", "rb")){
            fpassthru($fh);
            fclose($fh);
        } else alert(et('ReadDenied').": ".$file);
    } else alert(et('FileNotFound').": ".$file);
}
function execute(){
    global $cmd;
    header("Content-type: text/plain");
    if (strlen($cmd)>0){
        echo "# ".$cmd."\n";
        exec($cmd,$mat);
        if (count($mat)>0) echo trim(implode("\n",$mat));
        else echo "exec(\"$cmd\") ".et('NoReturn')."...";
    } else echo et('NoCmd');
}
function save_upload($temp_file,$file_name,$dir_dest) {
    global $upload_filter;
    $file_name = remove_acentos($file_name);
    $file = $dir_dest.$file_name;
    $file_size = filesize($temp_file);
    $is_proibido = false;
    if (strlen($upload_filter)>0) {
        $mat = explode("#",$upload_filter);
        while(list($k,$v)=each($mat)){
            if (strstr($file_name,$v)){
                $is_proibido = true;
                break;
            }
        }
    }
    if (!$is_proibido){
        if (!limite($file_size)){
            if (file_exists($file)){
                if (unlink($file)){
                    if (copy($temp_file,$file)){
                        chmod($file,0777);
                        $out = 6;
                    } else $out = 2;
                } else $out = 5;
            } else {
                if (copy($temp_file,$file)){
                    chmod($file,0777);
                    $out = 1;
                } else $out = 2;
            }
        } else $out = 3;
    } else $out = 4;
    return $out;
}
function zip_extract(){
  global $cmd_arg,$dir_atual,$islinux;
  $zip = zip_open($dir_atual.$cmd_arg);
  if ($zip) {
    while ($zip_entry = zip_read($zip)) {
        if (zip_entry_filesize($zip_entry) > 0) {
            $complete_path = $path.dirname(zip_entry_name($zip_entry));
            $complete_name = $path.zip_entry_name($zip_entry);
            if(!file_exists($complete_path)) {
                $tmp = '';
                foreach(explode('/',$complete_path) AS $k) {
                    $tmp .= $k.'/';
                    if(!file_exists($tmp)) {
                        @mkdir($dir_atual.$tmp, 0777);
                    }
                }
            }
            if (zip_entry_open($zip, $zip_entry, "r")) {
                if ($fd = fopen($dir_atual.$complete_name, 'w')){
                    fwrite($fd, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
                    fclose($fd);
                } else echo "fopen($dir_atual.$complete_name) error<br>";
                zip_entry_close($zip_entry);
            } else echo "zip_entry_open($zip,$zip_entry) error<br>";
        }
    }
    zip_close($zip);
  }
}
// +--------------------------------------------------
// | Data Formating
// +--------------------------------------------------
function htmlencode($str){
    return htmlentities($str);
}
// html_entity_decode() replacement
function html_entity_decode_for_php4_compatibility ($string)  {
   $trans_tbl = get_html_translation_table (HTML_ENTITIES);
   $trans_tbl = array_flip ($trans_tbl);
   $ret = strtr ($string, $trans_tbl);
   return  preg_replace('/\&\#([0-9]+)\;/me',
       "chr('\\1')",$ret);
}
function htmldecode($str){
    if (is_string($str)){
       if (get_magic_quotes_gpc()) return stripslashes(html_entity_decode_for_php4_compatibility($str));
       else return html_entity_decode($str);
    } else return $str;
}
function rep($x,$y){
  if ($x > 0) {
    $aux = "";
    for ($a=1;$a<=$x;$a++) $aux .= $y;
    return $aux;
  } else return "";
}
function strzero($arg1,$arg2){
    if (strstr($arg1,"-") == false){
        $aux = intval($arg2) - strlen($arg1);
        if ($aux > 0) return rep($aux,"0").$arg1;
        else return $arg1;
    } else {
        return "[$arg1]";
    }
}
function replace_double($sub,$str){
    $out=str_replace($sub.$sub,$sub,$str);
    while ( strlen($out) != strlen($str) ){
        $str=$out;
        $out=str_replace($sub.$sub,$sub,$str);
    }
    return $out;
}
function remove_acentos($str){
    $str = trim($str);
    $str = strtr($str,"¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ!@#%&*()[]{}+=?",
                      "YuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy_______________");
    $str = str_replace("..","",str_replace("/","",str_replace("\\","",str_replace("\$","",$str))));
    return $str;
}
function formata_path($str){
    global $islinux;
    $str = trim($str);
    $str = str_replace("..","",str_replace("\\","/",str_replace("\$","",$str)));
    $done = false;
    while (!$done) {
        $str2 = str_replace("//","/",$str);
        if (strlen($str) == strlen($str2)) $done = true;
        else $str = $str2;
    }
    $tam = strlen($str);
    if ($tam>0){
        $last_char = $tam - 1;
        if ($str[$last_char] != "/") $str .= "/";
        if (!$islinux) $str = ucfirst($str);
    }
    return $str;
}
function array_csort() {
  $args = func_get_args();
  $marray = array_shift($args);
  $msortline = "return(array_multisort(";
   foreach ($args as $arg) {
       $i++;
       if (is_string($arg)) {
          foreach ($marray as $row) {
               $sortarr[$i][] = $row[$arg];
           }
       } else {
          $sortarr[$i] = $arg;
       }
       $msortline .= "\$sortarr[".$i."],";
   }
   $msortline .= "\$marray));";
   eval($msortline);
   return $marray;
}
function show_perms( $in_Perms ) {
   $sP = "<b>";
   if($in_Perms & 0x1000) $sP .= 'p';            // FIFO pipe
   elseif($in_Perms & 0x2000) $sP .= 'c';        // Character special
   elseif($in_Perms & 0x4000) $sP .= 'd';        // Directory
   elseif($in_Perms & 0x6000) $sP .= 'b';        // Block special
   elseif($in_Perms & 0x8000) $sP .= '&minus;';    // Regular
   elseif($in_Perms & 0xA000) $sP .= 'l';        // Symbolic Link
   elseif($in_Perms & 0xC000) $sP .= 's';        // Socket
   else $sP .= 'u';                              // UNKNOWN
   $sP .= "</b>";
   // owner - group - others
   $sP .= (($in_Perms & 0x0100) ? 'r' : '&minus;') . (($in_Perms & 0x0080) ? 'w' : '&minus;') . (($in_Perms & 0x0040) ? (($in_Perms & 0x0800) ? 's' : 'x' ) : (($in_Perms & 0x0800) ? 'S' : '&minus;'));
   $sP .= (($in_Perms & 0x0020) ? 'r' : '&minus;') . (($in_Perms & 0x0010) ? 'w' : '&minus;') . (($in_Perms & 0x0008) ? (($in_Perms & 0x0400) ? 's' : 'x' ) : (($in_Perms & 0x0400) ? 'S' : '&minus;'));
   $sP .= (($in_Perms & 0x0004) ? 'r' : '&minus;') . (($in_Perms & 0x0002) ? 'w' : '&minus;') . (($in_Perms & 0x0001) ? (($in_Perms & 0x0200) ? 't' : 'x' ) : (($in_Perms & 0x0200) ? 'T' : '&minus;'));
   return $sP;
}
function formatsize ($arg) {
    $j = 0;
    $ext = array(" bytes"," Kb"," Mb"," Gb"," Tb");
    while ($arg >= pow(1024,$j)) ++$j; {
          $arg = round($arg / pow(1024,$j-1) * 100) / 100 . $ext[$j-1];
    }
    return $arg;
}
function getsize ($file) {
    return formatsize(filesize($file));
}
function limite($new_file_size=0) {
    global $fm_root_atual;
    global $quota_mb;
    if($quota_mb){
        $total = total_size($fm_root_atual);
        if (floor(($total+$new_file_size)/(1024*1024)) > $quota_mb) return true;
    }
    return false;
}
function getuser ($arg) {
    global $mat_passwd;
    $aux = "x:".trim($arg).":";
    for($x=0;$x<count($mat_passwd);$x++){
        if (strstr($mat_passwd[$x],$aux)){
         $mat = explode(":",$mat_passwd[$x]);
         return $mat[0];
        }
    }
    return $arg;
}
function getgroup ($arg) {
    global $mat_group;
    $aux = "x:".trim($arg).":";
    for($x=0;$x<count($mat_group);$x++){
        if (strstr($mat_group[$x],$aux)){
         $mat = explode(":",$mat_group[$x]);
         return $mat[0];
        }
    }
    return $arg;
}
// +--------------------------------------------------
// | Interface
// +--------------------------------------------------
function html_header($plus=""){
echo "
<html>
<head>
<title>...:::: ".et('FileMan')."</title>
$plus
</head>
<script language=\"JavaScript\">
<!--
    var W = screen.width;
    var H = screen.height;
    var FONTSIZE = 0;
    switch (W){
        case 640:
            FONTSIZE = 8;
        break;
        case 800:
            FONTSIZE = 10;
        break;
        case 1024:
            FONTSIZE = 12;
        break;
        default:
            FONTSIZE = 14;
        break;
    }
";
echo replace_double(" ",str_replace(chr(13),"",str_replace(chr(10),"","
    document.writeln('
    <style>
    body {
        font-family : Arial;
        font-size: '+FONTSIZE+'px;
        font-weight : normal;
        color: 000000;
    }
    table {
        font-family : Arial;
        font-size: '+FONTSIZE+'px;
        font-weight : normal;
        color: 000000;
    }
    input {
        font-family : Arial;
        font-size: '+FONTSIZE+'px;
        font-weight : normal;
        color: 000000;
    }
    textarea {
        font-family : Courier;
        font-size: 12px;
        font-weight : normal;
        color: 000000;
    }
    A {
        font-family : Arial;
        font-size : '+FONTSIZE+'px;
        font-weight : bold;
        text-decoration: none;
        color: 992929;
    }
    A:link {
        color: 000000;
    }
    A:visited {
        color: 000000;
    }
    A:hover {
        color: 992929;
    }
    A:active {
        color: 000000;
    }
    </style>
    ');
")));
echo "
//-->
</script>
";
}
function reloadframe($ref,$arg){
    global $dir_atual;
    echo "
    <script language=\"Javascript\">
    <!--
        $ref.frame$arg.location.href='index.php?frame=$arg&dir_atual=$dir_atual';
    //-->
    </script>
    ";
}
function alert($arg){
    echo "
    <script language=javascript>
    <!--
        alert('$arg');
    //-->
    </script>
    ";
}
function tree($dir_antes,$dir_corrente,$indice){
    global $fm_root_atual, $dir_atual, $islinux;
    global $expanded_tree;
    $indice++;
    $num_dir = 0;
    $dir_name = str_replace($dir_antes,"",$dir_corrente);
    $dir_corrente = str_replace("//","/",$dir_corrente);
    $is_proibido = false;
    if ($islinux) {
        $proibidos = "/proc#/dev";
        $mat = explode("#",$proibidos);
        foreach($mat as $key => $val){
            if ($dir_corrente == $val){
                $is_proibido = true;
                break;
            }
        }
        unset($mat);
    }
    if (!$is_proibido){
        if ($handle = @opendir($dir_corrente)){
            // Permitido
            while ($file = readdir($handle)){
                if ($file != "." && $file != ".." && is_dir("$dir_corrente/$file"))
                    $mat_dir[] = $file;
            }
            closedir($handle);
            if (count($mat_dir) > 0){
                sort($mat_dir,SORT_STRING);
                // Com Sub-dir
                if ($indice != 0){
                    for ($aux=1;$aux<$indice;$aux++) echo "&nbsp;&nbsp;&nbsp;&nbsp;";
                    echo "&#149;";
                }
                if ($dir_antes != $dir_corrente){
                    if (strstr($expanded_tree,":$dir_corrente/$dir_name")) $op_str = "[&#150;]";
                    else $op_str = "[+]";
                    echo "&nbsp;<a href=\"JavaScript:go_dir('$dir_corrente/$dir_name')\"><b>$op_str</b></a>&nbsp;<a href=\"JavaScript:go('$dir_corrente')\"><b>$dir_name</b></a><br>\n";
                } else {
                    echo "<a href=\"JavaScript:go('$dir_corrente')\"><b>$fm_root_atual</b></a><br>\n";
                }
                for ($x=0;$x<count($mat_dir);$x++){
                    if (($dir_antes == $dir_corrente)||(strstr($expanded_tree,":$dir_corrente/$dir_name"))){
                        tree($dir_corrente."/",$dir_corrente."/".$mat_dir[$x],$indice);
                    } else flush();
                }
            } else {
              // Sem Sub-dir
              if ($dir_antes != $dir_corrente){
                for ($aux=1;$aux<$indice;$aux++) echo "&nbsp;&nbsp;&nbsp;&nbsp;";
                echo "&#149;";
                echo "<a href=\"JavaScript:go('$dir_corrente')\"> <b>$dir_name</b></a><br>\n";
              } else {
                echo "<a href=\"JavaScript:go('$dir_corrente')\"> <b>$fm_root_atual</b></a><br>\n";
              }
            }
        } else {
            // Negado
            if ($dir_antes != $dir_corrente){
                for ($aux=1;$aux<$indice;$aux++) echo "&nbsp;&nbsp;&nbsp;&nbsp;";
                echo "&#149;";
                echo "<a href=\"JavaScript:go('$dir_corrente')\"><font color=red> <b>$dir_name</b></font></a><br>\n";
            } else {
                echo "<a href=\"JavaScript:go('$dir_corrente')\"><font color=red> <b>$fm_root_atual</b></font></a><br>\n";
            }

        }
    } else {
        // Proibido
        if ($dir_antes != $dir_corrente){
            for ($aux=1;$aux<$indice;$aux++) echo "&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "&#149;";
            echo "<a href=\"JavaScript:go('$dir_corrente')\"><font color=red> <b>$dir_name</b></font></a><br>\n";
        } else {
            echo "<a href=\"JavaScript:go('$dir_corrente')\"><font color=red> <b>$fm_root_atual</b></font></a><br>\n";
        }
    }
}
function show_tree(){
    global $fm_root_atual;
    global $setflag;
    global $islinux;
    html_header();
    echo "<body bgcolor=\"#eeeeee\" marginwidth=\"0\" marginheight=\"0\">\n";
    echo "
    <script language=\"Javascript\">
    <!--";
        if ($setflag) echo "
        var flag = true;";
        else echo "
        var flag = false;";
        echo "
        function set_flag(arg) {
            flag = arg;
        }
        function go_dir(arg) {
            var setflag;
            setflag = (flag)?1:0;
            document.location.href='index.php?frame=2&setflag='+setflag+'&dir_atual=$dir_atual&open_dir='+arg;
        }
        function go(arg) {
            if (flag) {
                parent.frame3.set_dir_dest(arg+'/');
                flag = false;
            } else {
                parent.frame3.location.href='index.php?frame=3&dir_atual='+arg+'/';
            }
        }
        function set_fm_root_atual(arg){
            document.location.href='index.php?frame=2&set_fm_root_atual='+escape(arg);
        }
        function atualizar(){
            document.location.href='index.php?frame=2';
        }
    //-->
    </script>
    ";
    echo "<table width=\"100%\" height=\"100%\" border=0 cellspacing=0 cellpadding=5>\n";
    echo "<form><tr valign=top height=10><td>";
    if (!$islinux){
        echo "<select name=drive onchange=\"set_fm_root_atual(this.value)\">";
        $aux="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        for($x=0;$x<strlen($aux);$x++){
            if (strstr(strtoupper($fm_root_atual),$aux[$x].":/")) $is_sel="selected";
            else $is_sel="";
            echo "<option $is_sel value=\"".$aux[$x].":/\">".$aux[$x].":/";
        }
        echo "</select> ";
    }
    echo "<input type=button value=".et('Refresh')." onclick=\"atualizar()\"></tr></form>";
    //if (!$islinux) $aux=substr($fm_root_atual,0,strlen($fm_root_atual)-1);
    //else
    $aux=$fm_root_atual;
    echo "<tr valign=top><td>";
            clearstatcache();
            tree($aux,$aux,-1,0);
    echo "</td></tr>";
    echo "
        <form name=\"login_form\" action=\"index.php\" method=\"post\" target=\"_parent\">
        <input type=hidden name=action value=1>
        <tr>
        <td height=10 colspan=2><input type=submit value=\"".et('Leave')."\">
        </tr>
        </form>
    ";
    echo "</table>\n";
    echo "</body>\n</html>";
}
function dir_list_form() {
    global $fm_root_atual,$dir_atual,$quota_mb,$resolveIDs,$order_dir_list_by,$islinux,$cmd_name,$ip,$is_reachable;
    clearstatcache();
    if ($opdir = @opendir($dir_atual)) {
        $count = 0;
        $total = 0;
        $str_back = "";
        $Arr = array();
        echo "<table border=0 cellspacing=1 cellpadding=4 width=\"100%\" bgcolor=\"#eeeeee\">\n";
        if ($dir_atual != $fm_root_atual){
            $mat = explode("/",$dir_atual);
            $dir_antes = "";
            for($x=0;$x<(count($mat)-2);$x++) $dir_antes .= $mat[$x]."/";
            $str_back = "<a href=\"index.php?frame=3&dir_atual=$dir_antes\"><<</a> ";
        }
        echo "
            <form name=\"form_action\" action=\"index.php\" method=\"post\" onsubmit=\"return test_action();\">
            <input type=hidden name=\"frame\" value=3>
            <input type=hidden name=\"action\" value=0>
            <input type=hidden name=\"dir_dest\" value=\"\">
            <input type=hidden name=\"chmod_arg\" value=\"\">
            <input type=hidden name=\"cmd_arg\" value=\"\">
            <input type=hidden name=\"dir_atual\" value=\"$dir_atual\">
            <input type=hidden name=\"dir_antes\" value=\"$dir_antes\">
            <input type=hidden name=\"dir_dir_list\" value=\"\">
            <input type=hidden name=\"dir_file_list\" value=\"\">";
        echo "
            <tr>
            <td bgcolor=\"#dddddd\" colspan=20><nobr><input type=button onclick=\"config()\" value=\"".et('Config')."\"> <input type=button onclick=\"server_info()\" value=\"".et('ServerInfo')."\"> <input type=button onclick=\"test_prompt(1)\" value=\"".et('CreateDir')."\"> <input type=button onclick=\"test_prompt(2)\" value=\"".et('CreateArq')."\"> <input type=button onclick=\"execute()\" value=\"".et('ExecCmd')."\"> <input type=button onclick=\"upload()\" value=\"".et('Upload')."\"> <input type=button onclick=\"shell()\" value=\"".et('Shell')."\"> <b>$ip</b></nobr>";
        while ($file = readdir($opdir)) {
          if (($file != ".")&&($file != "..")){
            if (is_file($dir_atual.$file)){
                $ext = strtolower(strrchr($file,"."));
                $Arr[$count]["type"] = "file";
                // Função filetype() só retorna "file"...
                $Arr[$count]["size"] = filesize($dir_atual.$file);
                $Arr[$count]["sizet"] = getsize($dir_atual.$file);
                if (strstr($ext,".")){
                    $Arr[$count]["ext"] = $ext;
                    $Arr[$count]["extt"] = $ext;
                } else {
                    $Arr[$count]["ext"] = "";
                    $Arr[$count]["extt"] = "&nbsp;";
                }
            } elseif (is_dir($dir_atual.$file)) {
                $Arr[$count]["type"] = "dir";
            }
            $Arr[$count]["name"] = $file;
            $Arr[$count]["date"] = date("Ymd", filemtime($dir_atual.$file));
            $Arr[$count]["time"] = date("his", filemtime($dir_atual.$file));
            $Arr[$count]["datet"] = date("d/m/Y h:i:s", filemtime($dir_atual.$file));
            if ($islinux && $resolveIDs){
                $Arr[$count]["p"] = show_perms(fileperms($dir_atual.$file));
                $Arr[$count]["u"] = getuser(fileowner($dir_atual.$file));
                $Arr[$count]["g"] = getgroup(filegroup($dir_atual.$file));
            } else {
                $Arr[$count]["p"] = base_convert(fileperms($dir_atual.$file),10,8);
                $Arr[$count]["p"] = substr($Arr[$count]["p"],strlen($Arr[$count]["p"])-3);
                $Arr[$count]["u"] = fileowner($dir_atual.$file);
                $Arr[$count]["g"] = filegroup($dir_atual.$file);
            }
            $count++;
          }
        }
        if($count){
            $or1="1A";
            $or2="2D";
            $or3="3A";
            $or4="4A";
            $or5="5A";
            $or6="6D";
            $or7="7D";
            switch($order_dir_list_by){
                case "1A": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"name",SORT_STRING,SORT_ASC); $or1="1D"; break;
                case "1D": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"name",SORT_STRING,SORT_DESC); $or1="1A"; break;
                case "2A": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"p",SORT_STRING,SORT_ASC,"g",SORT_STRING,SORT_ASC,"u",SORT_STRING,SORT_ASC); $or2="2D"; break;
                case "2D": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"p",SORT_STRING,SORT_DESC,"g",SORT_STRING,SORT_ASC,"u",SORT_STRING,SORT_ASC); $or2="2A"; break;
                case "3A": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"u",SORT_STRING,SORT_ASC,"g",SORT_STRING,SORT_ASC); $or3="3D"; break;
                case "3D": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"u",SORT_STRING,SORT_DESC,"g",SORT_STRING,SORT_ASC); $or3="3A"; break;
                case "4A": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"g",SORT_STRING,SORT_ASC,"u",SORT_STRING,SORT_DESC); $or4="4D"; break;
                case "4D": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"g",SORT_STRING,SORT_DESC,"u",SORT_STRING,SORT_DESC); $or4="4A"; break;
                case "5A": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"size",SORT_NUMERIC,SORT_ASC); $or5="5D"; break;
                case "5D": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"size",SORT_NUMERIC,SORT_DESC); $or5="5A"; break;
                case "6A": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"date",SORT_STRING,SORT_ASC,"time",SORT_STRING,SORT_ASC,"name",SORT_STRING,SORT_ASC); $or6="6D"; break;
                case "6D": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"date",SORT_STRING,SORT_DESC,"time",SORT_STRING,SORT_DESC,"name",SORT_STRING,SORT_ASC); $or6="6A"; break;
                case "7A": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"ext",SORT_STRING,SORT_ASC,"name",SORT_STRING,SORT_ASC); $or7="7D"; break;
                case "7D": $Arr = array_csort ($Arr,"type",SORT_STRING,SORT_ASC,"ext",SORT_STRING,SORT_DESC,"name",SORT_STRING,SORT_ASC); $or7="7A"; break;
            }
            echo "<tr>
                      <td bgcolor=\"#dddddd\" colspan=20><nobr>$str_back <a href=\"index.php?frame=3&dir_atual=$dir_atual\">$dir_atual</a></nobr>";
            $dir_out="";
            $file_out="";
            for (reset($Arr); $Arr2 = current($Arr); next($Arr)) {
                 $file = $Arr2["name"];
                 if ($Arr2["type"]=="dir"){
                     $count--;
                     $dir_out .= "<tr onmouseover=\"select_item(this, '', 'dir', 'over');\" onmouseout=\"select_item(this, '', 'dir', 'out');\" onmousedown=\"select_item(this, '$file', 'dir', 'click');\">
                                 <td align=left bgcolor=\"#cccccc\"><nobr><a href=\"JavaScript:go('$file')\">$file</a></nobr>
                                 <td bgcolor=\"#cccccc\">".$Arr2["p"];
                     if ($islinux) $dir_out .= "<td bgcolor=\"#cccccc\">".$Arr2["u"]."<td bgcolor=\"#cccccc\">".$Arr2["g"];
                     $dir_out .= "
                                 (-#-)
                                 <td bgcolor=\"#cccccc\">".$Arr2["datet"]."
                                 (-#-)";
                     // Opções de diretório
                     if ( is_writable($dir_atual.$file) ) $dir_out .= "
                                 <td bgcolor=\"#ffffff\" align=center><a href=\"javascript:if(confirm('".et('ConfRem')." \\'".$file."\\' ?')) document.location.href='index.php?frame=3&action=8&cmd_arg=".$file."&dir_atual=$dir_atual'\">".et('Rem')."</a>
                                 <td bgcolor=\"#ffffff\" align=center><a href=\"javascript:rename('$file')\">".et('Ren')."</a>";
                     $dir_out .= "
                                 </tr>";
                 } else {
                     $file_out .= "<tr onmouseover=\"select_item(this, '', 'file', 'over');\" onmouseout=\"select_item(this, '', 'file', 'out');\" onmousedown=\"select_item(this, '$file', 'file', 'click');\">
                                 <td align=left bgcolor=\"#ffffff\"><nobr><a href=\"JavaScript:download('$file')\">$file</a></nobr>
                                 <td bgcolor=\"#eeeeee\">".$Arr2["p"];
                     if ($islinux) $file_out .= "<td>".$Arr2["u"]."<td>".$Arr2["g"];
                     $file_out .= "
                                 <td bgcolor=\"#eeeeee\">".$Arr2["sizet"]."
                                 <td bgcolor=\"#eeeeee\">".$Arr2["datet"]."
                                 <td bgcolor=\"#eeeeee\">".$Arr2["extt"];
                     // Opções de arquivo
                     if ( is_writable($dir_atual.$file) ) $file_out .= "
                                 <td bgcolor=\"#ffffff\" align=center><a href=\"javascript:if(confirm('".strtoupper(et('Rem'))." \\'".$file."\\' ?')) document.location.href='index.php?frame=3&action=8&cmd_arg=".$file."&dir_atual=$dir_atual'\">".et('Rem')."</a>
                                 <td bgcolor=\"#ffffff\" align=center><a href=\"javascript:rename('$file')\">".et('Ren')."</a>";
                     if ( is_readable($dir_atual.$file) ) $file_out .= "
                                 <td bgcolor=\"#ffffff\" align=center><a href=\"javascript:edit_file('$file')\">".et('Edit')."</a>";
                     if ( is_readable($dir_atual.$file) && (($Arr2["ext"] == ".tar") || ($Arr2["ext"] == ".zip") || ($Arr2["ext"] == ".bz2") || ($Arr2["ext"] == ".tbz2") || ($Arr2["ext"] == ".bz") || ($Arr2["ext"] == ".tbz") || ($Arr2["ext"] == ".bzip") || ($Arr2["ext"] == ".gzip") || ($Arr2["ext"] == ".gz") || ($Arr2["ext"] == ".tgz")) ) $file_out .= "
                                 <td bgcolor=\"#ffffff\" align=center><a href=\"javascript:decompress('$file')\">".et('Decompress')."</a>";
                     if ($is_reachable){
                         if( is_readable($dir_atual.$file) && (($Arr2["ext"] == ".php") || ($Arr2["ext"] == ".php3") || ($Arr2["ext"] == ".asp") || ($Arr2["ext"] == ".html") || ($Arr2["ext"] == ".htm") || ($Arr2["ext"] == ".jpg") || ($Arr2["ext"] == ".gif") || ($Arr2["ext"] == ".png") || ($Arr2["ext"] == ".bmp")) ) $file_out .= "
                                 <td bgcolor=\"#ffffff\" align=center><a href=\"javascript:view('$file');\">".et('View')."</a>";
                     }
                     $file_out .= "</tr>";
                     $total += filesize($dir_atual.$file);
                 }
            }
            if (strlen($file_out)>0) {
                $dir_out = str_replace("(-#-)","<td bgcolor=\"#cccccc\" align=center>&nbsp;",$dir_out);
                if ($islinux) $highlight_cols = 7;
                else $highlight_cols = 5;
            } else {
                $dir_out = str_replace("(-#-)","",$dir_out);
                if ($islinux) $highlight_cols = 5;
                else $highlight_cols = 3;
            }
            echo "
                <tr>
                <td bgcolor=\"#dddddd\" colspan=20><nobr>
                      <input type=\"button\" style=\"width:60\" onclick=\"test(4)\" value=\"".et('Rem')."\">
                      <input type=\"button\" style=\"width:60\" onclick=\"sel_dir(5)\" value=\"".et('Copy')."\">
                      <input type=\"button\" style=\"width:60\" onclick=\"sel_dir(6)\" value=\"".et('Move')."\">
                      <input type=\"button\" style=\"width:100\" onclick=\"test_prompt(71)\" value=\"".et('Compress')."\">";
            if ($islinux) echo "
                      <input type=\"button\" style=\"width:100\" onclick=\"resolveIDs()\" value=\"".et('ResolveIDs')."\">";
            echo "
                      <input type=\"button\" style=\"width:100\" onclick=\"chmod(9)\" value=\"".et('Perms')."\">";
            echo "
                </nobr>";
            if (strlen($dir_out)>0) echo $dir_out;
            if (strlen($file_out)>0){
                echo "
                <tr>
                      <td bgcolor=\"#dddddd\"><a href=\"index.php?frame=3&or_by=$or1&dir_atual=$dir_atual\">".et('Name')."</a>
                      <td bgcolor=\"#dddddd\"><a href=\"index.php?frame=3&or_by=$or2&dir_atual=$dir_atual\">".et('Perms')."</a>";
                if ($islinux) echo "<td bgcolor=\"#dddddd\"><a href=\"index.php?frame=3&or_by=$or3&dir_atual=$dir_atual\">".et('Owner')."</a><td bgcolor=\"#dddddd\"><a href=\"index.php?frame=3&or_by=$or4&dir_atual=$dir_atual\">".et('Group')."</a>";
                echo "
                      <td bgcolor=\"#dddddd\"><a href=\"index.php?frame=3&or_by=$or5&dir_atual=$dir_atual\">".et('Size')."</a>
                      <td bgcolor=\"#dddddd\"><a href=\"index.php?frame=3&or_by=$or6&dir_atual=$dir_atual\">".et('Date')."</a>
                      <td bgcolor=\"#dddddd\"><a href=\"index.php?frame=3&or_by=$or7&dir_atual=$dir_atual\">".et('Type')."</a>
                      <td bgcolor=\"#dddddd\" colspan=20>&nbsp;
                ";
                echo $file_out;
            }
            echo "
                <tr>
                <td bgcolor=\"#dddddd\" colspan=20><nobr>
                      <input type=\"button\" style=\"width:60\" onclick=\"test(4)\" value=\"".et('Rem')."\">
                      <input type=\"button\" style=\"width:60\" onclick=\"sel_dir(5)\" value=\"".et('Copy')."\">
                      <input type=\"button\" style=\"width:60\" onclick=\"sel_dir(6)\" value=\"".et('Move')."\">
                      <input type=\"button\" style=\"width:100\" onclick=\"test_prompt(71)\" value=\"".et('Compress')."\">";
            if ($islinux) echo "
                      <input type=\"button\" style=\"width:100\" onclick=\"resolveIDs()\" value=\"".et('ResolveIDs')."\">";
            echo "
                      <input type=\"button\" style=\"width:100\" onclick=\"chmod(9)\" value=\"".et('Perms')."\">";
            echo "
                </nobr>";
            echo "</form>";
            closedir($opdir);
            if ($quota_mb) $quota_str = "(".formatsize(($quota_mb*1024*1024)-total_size($fm_root_atual))." ".et('Free').")";
            echo "</table><table bgcolor=\"#dddddd\" width=\"100%\" border=0 cellspacing=1 cellpadding=5>";
            if ($count) echo "<tr><td colspan=7>$count ".et('File_s')." = ".formatsize($total)."$quota_str</tr>";
            else echo "<tr><td colspan=7>0 ".et('File_s')." = 0 Mb</tr>";
        } else {
            echo "
                    <tr>
                    <td bgcolor=\"#dddddd\" width=\"1%\">$str_back<td bgcolor=\"#dddddd\" colspan=20><nobr><a href=\"index.php?frame=3&dir_atual=$dir_atual\">$dir_atual</a></nobr>
                    <tr><td bgcolor=\"#dddddd\" colspan=20>".et('EmptyDir').".</tr>";
        }
        echo "</table>";
        echo "
            <script language=\"Javascript\">
            <!--
            function getCookieVal (offset) {
                var endstr = document.cookie.indexOf (';', offset);
                if (endstr == -1) endstr = document.cookie.length;
                return unescape(document.cookie.substring(offset, endstr));
            }
            function getCookie (name) {
                var arg = name + '=';
                var alen = arg.length;
                var clen = document.cookie.length;
                var i = 0;
                while (i < clen) {
                    var j = i + alen;
                    if (document.cookie.substring(i, j) == arg) return getCookieVal (j);
                    i = document.cookie.indexOf(' ', i) + 1;
                    if (i == 0) break;
                }
                return null;
            }
            function setCookie (name, value) {
                var argv = SetCookie.arguments;
                var argc = SetCookie.arguments.length;
                var expires = (argc > 2) ? argv[2] : null;
                var path = (argc > 3) ? argv[3] : null;
                var domain = (argc > 4) ? argv[4] : null;
                var secure = (argc > 5) ? argv[5] : false;
                document.cookie = name + '=' + escape (value) +
                ((expires == null) ? '' : ('; expires=' + expires.toGMTString())) +
                ((path == null) ? '' : ('; path=' + path)) +
                ((domain == null) ? '' : ('; domain=' + domain)) +
                ((secure == true) ? '; secure' : '');
            }
            function delCookie (name) {
                var exp = new Date();
                exp.setTime (exp.getTime() - 1);
                var cval = GetCookie (name);
                document.cookie = name + '=' + cval + '; expires=' + exp.toGMTString();
            }
            function go(arg) {
                document.location.href='index.php?frame=3&dir_atual=$dir_atual'+arg+'/';
            }
            function resolveIDs() {
                document.location.href='index.php?frame=3&set_resolveIDs=1&dir_atual=$dir_atual';
            }
            var dir_dir_list = new Array();
            var dir_file_list = new Array();
            function select_item(Row, Entry, Type, Action){
                if (Type == 'dir'){
                    var DefaultColor = '#cccccc';
                } else {
                    var DefaultColor = '#eeeeee';
                }
                var PointerColor = '#9FDCD3';
                var MarkColor = '#A7D2E4';
                var Cells = null;
                if (typeof(document.getElementsByTagName) != 'undefined') {
                    Cells = Row.getElementsByTagName('td');
                }else if (typeof(Row.cells) != 'undefined') {
                    Cells = Row.cells;
                }else {
                    return false;
                }
                var domDetect    = null;
                var currentColor = null;
                var newColor     = null;
                if (typeof(window.opera) == 'undefined' && typeof(Cells[0].getAttribute) != 'undefined') {
                    currentColor = Cells[0].getAttribute('bgcolor');
                    domDetect    = true;
                } else {
                    currentColor = Cells[0].style.backgroundColor;
                    domDetect    = false;
                }
                if (Action == 'over' && currentColor.toLowerCase() != MarkColor.toLowerCase()) {
                    newColor = PointerColor;
                } else if (Action == 'out' && currentColor.toLowerCase() != MarkColor.toLowerCase()) {
                    newColor = DefaultColor;
                } else if (Action == 'click') {
                    newColor = (currentColor.toLowerCase() == MarkColor.toLowerCase()) ? PointerColor : MarkColor;
                    select_unselect(Entry, Type);
                }
                if (newColor) {
                    if (domDetect) {
                        for (var c=0; c < $highlight_cols; c++) {
                            if (Type=='file' && c==0 && Action == 'out' && currentColor.toLowerCase() != MarkColor.toLowerCase()) Cells[c].setAttribute('bgcolor', '#ffffff', 0);
                            else Cells[c].setAttribute('bgcolor', newColor, 0);
                        }
                    } else {
                        for (var c=0; c < $highlight_cols; c++) {
                            if (Type=='file' && c==0 && Action == 'out' && currentColor.toLowerCase() != MarkColor.toLowerCase()) Cells[c].style.backgroundColor = '#ffffff';
                            else Cells[c].style.backgroundColor = newColor;
                        }
                    }
                }
                return true;
            }
            function select_unselect(Entry, Type){
                if (Type == 'dir') select_unselect_dir(Entry);
                else select_unselect_file(Entry);
            }
            function select_unselect_dir(Entry){
                var tam = dir_dir_list.length;
                var mat = new Array();
                var was_set = false;
                for (var x=0;x<tam;x++){
                    if (dir_dir_list[x] == Entry){
                        was_set = true;
                    } else {
                        mat.push(dir_dir_list[x]);
                    }
                }
                if (!was_set){
                    dir_dir_list.push(Entry);
                    return true;
                } else {
                    dir_dir_list = mat;
                    return false;
                }
            }
            function select_unselect_file(Entry){
                var tam = dir_file_list.length;
                var mat = new Array();
                var was_set = false;
                for (var x=0;x<tam;x++){
                    if (dir_file_list[x] == Entry){
                        was_set = true;
                    } else {
                        mat.push(dir_file_list[x]);
                    }
                }
                if (!was_set){
                    dir_file_list.push(Entry);
                    return true;
                } else {
                    dir_file_list = mat;
                    return false;
                }
            }
            function is_anything_selected(){
                document.form_action.dir_dir_list.value = dir_dir_list.join('<|*|>');
                document.form_action.dir_file_list.value = dir_file_list.join('<|*|>');
                if (dir_dir_list.length>0 || dir_file_list.length>0) return true;
                else return false;
            }
            function download(arg){
                    parent.frame1.location.href='index.php?action=3&dir_atual=$dir_atual&file_name='+escape(arg);
            }
            function upload(){
                    var w = 400;
                    var h = 200;
                    window.open('index.php?action=10&dir_atual=$dir_atual', '', 'width='+w+',height='+h+',fullscreen=no,scrollbars=no,resizable=yes,status=no,toolbar=no,menubar=no,location=no');
            }
            function execute(){
                    document.form_action.cmd_arg.value = prompt('".et('TypeCmd').".');
                    if(document.form_action.cmd_arg.value.length>0){
                        if(confirm('".et('ConfExec')." \\' '+document.form_action.cmd_arg.value+' \\' ?')) {
                            var w = 800;
                            var h = 600;
                            window.open('index.php?action=6&dir_atual=$dir_atual&cmd='+escape(document.form_action.cmd_arg.value), '', 'width='+w+',height='+h+',fullscreen=no,scrollbars=yes,resizable=yes,status=no,toolbar=no,menubar=no,location=no');
                        }
                    }
            }
            function decompress(arg){
                    if(confirm('".strtoupper(et('Decompress'))." \\' '+arg+' \\' ?')) {
                        document.form_action.action.value = 72;
                        document.form_action.cmd_arg.value = arg;
                        document.form_action.submit();
                    }
            }
            function edit_file(arg){
                    var w = 800;
                    var h = 600;
                    if(confirm('".strtoupper(et('Edit'))." \\' '+arg+' \\' ?')) window.open('index.php?action=7&dir_atual=$dir_atual&file_name='+escape(arg), '', 'width='+w+',height='+h+',fullscreen=no,scrollbars=no,resizable=yes,status=no,toolbar=no,menubar=no,location=no');
            }
            function config(){
                    var w = 600;
                    var h = 350;
                    window.open('index.php?action=2', 'win_config', 'width='+w+',height='+h+',fullscreen=no,scrollbars=yes,resizable=yes,status=no,toolbar=no,menubar=no,location=no');
            }
            function server_info(arg){
                    var w = 800;
                    var h = 600;
                    window.open('index.php?action=5', 'win_serverinfo', 'width='+w+',height='+h+',fullscreen=no,scrollbars=yes,resizable=yes,status=no,toolbar=no,menubar=no,location=no');
            }
            function shell(){
                    var w = 800;
                    var h = 600;
                    window.open('index.php?action=9', '', 'width='+w+',height='+h+',fullscreen=no,scrollbars=yes,resizable=yes,status=no,toolbar=no,menubar=no,location=no');
            }
            function view(arg){
                    var w = 800;
                    var h = 600;
                    if(confirm('".strtoupper(et('View'))." \\' '+arg+' \\' ?')) window.open('index.php?action=4&dir_atual=$dir_atual&file_name='+escape(arg), '', 'width='+w+',height='+h+',fullscreen=no,scrollbars=yes,resizable=yes,status=yes,toolbar=no,menubar=no,location=yes');
            }
            function rename(arg){
                    var nome = '';
                    if (nome = prompt('".strtoupper(et('Ren'))." \\' '+arg+' \\' ".et('To')." ...')) document.location.href='index.php?frame=3&action=3&dir_atual=$dir_atual&old_name='+escape(arg)+'&new_name='+escape(nome);
            }
            function set_dir_dest(arg){
                document.form_action.dir_dest.value=arg;
                if (document.form_action.action.value.length > 0) test(document.form_action.action.value);
                else alert('".et('JSError').".');
            }
            function sel_dir(arg){
                document.form_action.action.value = arg;
                document.form_action.dir_dest.value='';
                if (!is_anything_selected()) alert('".et('NoSel').".');
                else {
                    if (!getCookie('sel_dir_warn')) {
                        alert('".et('SelDir').".');
                        document.cookie='sel_dir_warn'+'='+escape('true')+';';
                    }
                    parent.frame2.set_flag(true);
                }
            }
            function set_chmod_arg(arg){
                document.form_action.chmod_arg.value=arg;
                if (document.form_action.action.value.length > 0) test(document.form_action.action.value);
                else alert('".et('JSError')."');
            }
            function chmod(arg){
                document.form_action.action.value = arg;
                document.form_action.dir_dest.value='';
                document.form_action.chmod_arg.value='';
                if (!is_anything_selected()) alert('".et('NoSel').".');
                else {
                    var w = 280;
                    var h = 180;
                    window.open('index.php?action=8', '', 'width='+w+',height='+h+',fullscreen=no,scrollbars=no,resizable=yes,status=no,toolbar=no,menubar=no,location=no');
                }
            }
            function test_action(){
                if (document.form_action.action.value != 0) return true;
                else return false;
            }
            function test_prompt(arg){
                    var erro='';
                    var conf='';
                    if (arg == 1){
                        document.form_action.cmd_arg.value = prompt('".et('TypeDir').".');
                    } else if (arg == 2){
                        document.form_action.cmd_arg.value = prompt('".et('TypeArq').".');
                    } else if (arg == 71){
                        if (!is_anything_selected()) erro = '".et('NoSel').".';
                        else document.form_action.cmd_arg.value = prompt('".et('TypeArqComp')."');
                    }
                    if (erro!=''){
                        document.form_action.cmd_arg.focus();
                        alert(erro);
                    } else if(document.form_action.cmd_arg.value.length>0) {
                        document.form_action.action.value = arg;
                        document.form_action.submit();
                    }
            }
            function test(arg){
                    var erro='';
                    var conf='';
                    if (arg == 4){
                        if (!is_anything_selected()) erro = '".et('NoSel').".\\n';
                        conf = '".et('RemSel')." ?\\n';
                    } else if (arg == 5){
                        if (!is_anything_selected()) erro = '".et('NoSel').".\\n';
                        else if(document.form_action.dir_dest.value.length == 0) erro = '".et('NoDestDir').".';
                        else if(document.form_action.dir_dest.value == document.form_action.dir_atual.value) erro = '".et('DestEqOrig').".';
                        conf = '".et('CopyTo')." \\' '+document.form_action.dir_dest.value+' \\' ?\\n';
                    } else if (arg == 6){
                        if (!is_anything_selected()) erro = '".et('NoSel').".';
                        else if(document.form_action.dir_dest.value.length == 0) erro = '".et('NoDestDir').".';
                        else if(document.form_action.dir_dest.value == document.form_action.dir_atual.value) erro = '".et('DestEqOrig').".';
                        conf = '".et('MoveTo')." \\' '+document.form_action.dir_dest.value+' \\' ?\\n';
                    } else if (arg == 9){
                        if (!is_anything_selected()) erro = '".et('NoSel').".';
                        else if(document.form_action.chmod_arg.value.length == 0) erro = '".et('NoNewPerm').".';
                        conf = '".et('AlterPermTo')." \\' '+document.form_action.chmod_arg.value+' \\' ?\\n';
                    }
                    if (erro!=''){
                        document.form_action.cmd_arg.focus();
                        alert(erro);
                    } else if(conf!='') {
                        if(confirm(conf)) {
                            document.form_action.action.value = arg;
                            document.form_action.submit();
                        }
                    } else {
                        document.form_action.action.value = arg;
                        document.form_action.submit();
                    }
            }
            //-->
            </script>";
    } else echo "<br><font color=red>".et('IOError').".<br>$dir_atual</font>";
}
function upload_form(){
    global $_FILES,$dir_atual,$dir_dest,$fechar,$quota_mb;
    $num_uploads = 5;
    html_header();
    echo "<body bgcolor=\"#eeeeee\" marginwidth=\"0\" marginheight=\"0\">";
    if (count($_FILES)==0){
        echo "
        <table height=\"100%\" border=0 cellspacing=0 cellpadding=2 align=center>
        <form name=\"upload_form\" action=\"index.php\" method=\"post\" ENCTYPE=\"multipart/form-data\">
        <input type=hidden name=dir_dest value=\"$dir_atual\">
        <input type=hidden name=action value=10>
        <tr><th colspan=2>".et('Upload')."</th></tr>
        <tr><td align=right><b>".et('Destination').":<td><b><nobr>$dir_atual</nobr>";
        for ($x=0;$x<$num_uploads;$x++){
            echo "<tr><td width=1 align=right><b>".et('File').":<td><nobr><input type=\"file\" name=\"file$x\"></nobr>";
            $test_js .= "(document.upload_form.file$x.value.length>0)||";
        }
        echo "
        <input type=button value=\"".et('Send')."\" onclick=\"test_upload_form()\"></nobr>
        <tr><td>&nbsp;<td><input type=checkbox name=fechar value=\"1\"> <a href=\"javascript:troca();\">".et('AutoClose')."</a>
        <tr><td colspan=2>&nbsp;</td></tr>
        </form>
        </table>
        <script language=\"Javascript\">
        <!--
            function troca(){
                if(document.upload_form.fechar.checked){document.upload_form.fechar.checked=false;}else{document.upload_form.fechar.checked=true;}
            }
            foi = false;
            function test_upload_form(){
                if(".substr($test_js,0,strlen($test_js)-2)."){
                    if (foi) alert('".et('SendingForm')."...');
                    else {
                        foi = true;
                        document.upload_form.submit();
                    }
                } else alert('".et('NoFileSel').".');
            }
            window.moveTo((window.screen.width-400)/2,((window.screen.height-200)/2)-20);
        //-->
        </script>";
    } else {
        $out = "<tr><th colspan=2>".et('UploadEnd')."</th></tr>
                <tr><th colspan=2><nobr>".et('Destination').": $dir_dest</nobr>";
        for ($x=0;$x<$num_uploads;$x++){
            $temp_file = $_FILES["file".$x]["tmp_name"];
            $file_name = $_FILES["file".$x]["name"];
            if (strlen($file_name)>0) $resul = save_upload($temp_file,$file_name,$dir_dest);
            else $resul = 7;
            switch($resul){
                case 1:
                $out .= "<tr><td><b>".strzero($x+1,3).".<font color=green><b> ".et('FileSent').":</font><td>".$file_name."</td></tr>\n";
                break;
                case 2:
                $out .= "<tr><td colspan=2><font color=red><b>".et('IOError')."</font></td></tr>\n";
                $x = $upload_num;
                break;
                case 3:
                $out .= "<tr><td colspan=2><font color=red><b>".et('SpaceLimReached')." ($quota_mb Mb)</font></td></tr>\n";
                $x = $upload_num;
                break;
                case 4:
                $out .= "<tr><td><b>".strzero($x+1,3).".<font color=red><b> ".et('InvExt').":</font><td>".$file_name."</td></tr>\n";
                break;
                case 5:
                $out .= "<tr><td><b>".strzero($x+1,3).".<font color=red><b> ".et('FileNoOverw')."</font><td>".$file_name."</td></tr>\n";
                break;
                case 6:
                $out .= "<tr><td><b>".strzero($x+1,3).".<font color=green><b> ".et('FileOverw').":</font><td>".$file_name."</td></tr>\n";
                break;
                case 7:
                $out .= "<tr><td colspan=2><b>".strzero($x+1,3).".<font color=red><b> ".et('FileIgnored')."</font></td></tr>\n";
            }
        }
        if ($fechar) {
            echo "
            <script language=\"Javascript\">
            <!--
                window.close();
            //-->
            </script>
            ";
        } else {
            echo "
            <table height=\"100%\" border=0 cellspacing=0 cellpadding=2 align=center>
            $out
            <tr><td colspan=2>&nbsp;</td></tr>
            </table>
            <script language=\"Javascript\">
            <!--
                window.focus();
            //-->
            </script>
            ";
        }
    }
    echo "</body>\n</html>";
}
function chmod_form(){
    $aux = "
    <script type=\"text/javascript\">
    <!--
    function octalchange()
    {
        var val = document.chmod_form.t_total.value;
        var stickybin = parseInt(val.charAt(0)).toString(2);
        var ownerbin = parseInt(val.charAt(1)).toString(2);
        while (ownerbin.length<3) { ownerbin=\"0\"+ownerbin; };
        var groupbin = parseInt(val.charAt(2)).toString(2);
        while (groupbin.length<3) { groupbin=\"0\"+groupbin; };
        var otherbin = parseInt(val.charAt(3)).toString(2);
        while (otherbin.length<3) { otherbin=\"0\"+otherbin; };
        document.chmod_form.sticky.checked = parseInt(stickybin.charAt(0));
        document.chmod_form.owner4.checked = parseInt(ownerbin.charAt(0));
        document.chmod_form.owner2.checked = parseInt(ownerbin.charAt(1));
        document.chmod_form.owner1.checked = parseInt(ownerbin.charAt(2));
        document.chmod_form.group4.checked = parseInt(groupbin.charAt(0));
        document.chmod_form.group2.checked = parseInt(groupbin.charAt(1));
        document.chmod_form.group1.checked = parseInt(groupbin.charAt(2));
        document.chmod_form.other4.checked = parseInt(otherbin.charAt(0));
        document.chmod_form.other2.checked = parseInt(otherbin.charAt(1));
        document.chmod_form.other1.checked = parseInt(otherbin.charAt(2));
        calc_chmod(1);
    };

    function calc_chmod(nototals)
    {
      var users = new Array(\"owner\", \"group\", \"other\");
      var totals = new Array(\"\",\"\",\"\");
      var syms = new Array(\"\",\"\",\"\");

        for (var i=0; i<users.length; i++)
        {
            var user=users[i];
            var field4 = user + \"4\";
            var field2 = user + \"2\";
            var field1 = user + \"1\";
            var symbolic = \"sym_\" + user;
            var number = 0;
            var sym_string = \"\";
            var sticky = \"0\";
            var sticky_sym = \" \";
            if (document.chmod_form.sticky.checked){
                sticky = \"1\";
                sticky_sym = \"t\";
            }
            if (document.chmod_form[field4].checked == true) { number += 4; }
            if (document.chmod_form[field2].checked == true) { number += 2; }
            if (document.chmod_form[field1].checked == true) { number += 1; }

            if (document.chmod_form[field4].checked == true) {
                sym_string += \"r\";
            } else {
                sym_string += \"-\";
            }
            if (document.chmod_form[field2].checked == true) {
                sym_string += \"w\";
            } else {
                sym_string += \"-\";
            }
            if (document.chmod_form[field1].checked == true) {
                sym_string += \"x\";
            } else {
                sym_string += \"-\";
            }

            totals[i] = totals[i]+number;
            syms[i] =  syms[i]+sym_string;

      };
        if (!nototals) document.chmod_form.t_total.value = sticky + totals[0] + totals[1] + totals[2];
        document.chmod_form.sym_total.value = syms[0] + syms[1] + syms[2] + sticky_sym;
    }
    function troca(){
        if(document.chmod_form.sticky.checked){document.chmod_form.sticky.checked=false;}else{document.chmod_form.sticky.checked=true;}
    }

    window.onload=octalchange
    window.moveTo((window.screen.width-400)/2,((window.screen.height-200)/2)-20);
    //-->
    </script>
    ";
    html_header($aux);
    echo "<body bgcolor=\"#eeeeee\" marginwidth=\"0\" marginheight=\"0\">
    <form name=\"chmod_form\">
    <TABLE BORDER=\"0\" CELLSPACING=\"0\" CELLPADDING=\"4\" ALIGN=CENTER>
    <tr><th colspan=4>".et('Perms')."</th></tr>
    <TR ALIGN=\"LEFT\" VALIGN=\"MIDDLE\">
    <TD><input type=\"text\" name=\"t_total\" value=\"0777\" size=\"4\" onKeyUp=\"octalchange()\"> </TD>
    <TD><input type=\"text\" name=\"sym_total\" value=\"\" size=\"12\" READONLY=\"1\"></TD>
    </TR>
    </TABLE>
    <table cellpadding=\"2\" cellspacing=\"0\" border=\"0\" ALIGN=CENTER>
    <tr bgcolor=\"#333333\">
    <td WIDTH=\"60\" align=\"left\"> </td>
    <td WIDTH=\"55\" align=\"center\" style=\"color:#ffffff\"><b>".et('Owner')."
    </b></td>
    <td WIDTH=\"55\" align=\"center\" style=\"color:#ffffff\"><b>".et('Group')."
    </b></td>
    <td WIDTH=\"55\" align=\"center\" style=\"color:#ffffff\"><b>".et('Other')."
    <b></td>
    </tr>
    <tr bgcolor=\"#dddddd\">
    <td WIDTH=\"60\" align=\"left\" nowrap BGCOLOR=\"#FFFFFF\">".et('Read')."</td>
    <td WIDTH=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"owner4\" value=\"4\" onclick=\"calc_chmod()\">
    </td>
    <td WIDTH=\"55\" align=\"center\" bgcolor=\"#ffffff\"><input type=\"checkbox\" name=\"group4\" value=\"4\" onclick=\"calc_chmod()\">
    </td>
    <td WIDTH=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"other4\" value=\"4\" onclick=\"calc_chmod()\">
    </td>
    </tr>
    <tr bgcolor=\"#dddddd\">
    <td WIDTH=\"60\" align=\"left\" nowrap BGCOLOR=\"#FFFFFF\">".et('Write')."</td>
    <td WIDTH=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"owner2\" value=\"2\" onclick=\"calc_chmod()\"></td>
    <td WIDTH=\"55\" align=\"center\" bgcolor=\"#ffffff\"><input type=\"checkbox\" name=\"group2\" value=\"2\" onclick=\"calc_chmod()\">
    </td>
    <td WIDTH=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"other2\" value=\"2\" onclick=\"calc_chmod()\">
    </td>
    </tr>
    <tr bgcolor=\"#dddddd\">
    <td WIDTH=\"60\" align=\"left\" nowrap BGCOLOR=\"#FFFFFF\">".et('Exec')."</td>
    <td WIDTH=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"owner1\" value=\"1\" onclick=\"calc_chmod()\">
    </td>
    <td WIDTH=\"55\" align=\"center\" bgcolor=\"#ffffff\"><input type=\"checkbox\" name=\"group1\" value=\"1\" onclick=\"calc_chmod()\">
    </td>
    <td WIDTH=\"55\" align=\"center\" bgcolor=\"#EEEEEE\">
    <input type=\"checkbox\" name=\"other1\" value=\"1\" onclick=\"calc_chmod()\">
    </td>
    </tr>
    </TABLE>
    <TABLE BORDER=\"0\" CELLSPACING=\"0\" CELLPADDING=\"4\" ALIGN=CENTER>
    <tr><td colspan=2><input type=checkbox name=sticky value=\"1\" onclick=\"calc_chmod()\"> <a href=\"javascript:troca();\">".et('StickyBit')."</a><td colspan=2 align=right><input type=button value=\"".et('Apply')."\" onClick=\"window.opener.set_chmod_arg(document.chmod_form.t_total.value); window.close();\"></tr>
    </table>
    </form>
    </body>\n</html>";
}
function view(){
    global $doc_root,$path_info,$url_info,$dir_atual,$islinux,$file_name,$is_reachable;
    html_header();
    echo "<body bgcolor=\"#eeeeee\" marginwidth=\"0\" marginheight=\"0\">
    <script language=\"Javascript\">
    <!--
        window.moveTo((window.screen.width-800)/2,((window.screen.height-600)/2)-20);";
    if ($is_reachable){
        $url = $url_info["scheme"]."://".$url_info["host"];
        if (strlen($url_info["port"])>0) $url = substr($url,0,strlen($url)-1).":".$url_info["port"]."/";
        // Malditas variaveis de sistema!! No windows doc_root é sempre em lowercase... cadê o str_ireplace() ??
        if ($islinux) $url .= str_replace($doc_root,"",$dir_atual).$file_name;
        else $url .= str_replace($doc_root,"",strtolower($dir_atual)).$file_name;
        echo "
        document.location.href='$url';";
    } else {
        echo "
        alert('".et('OutDocRoot').":\\n".$doc_root."\\n');
        window.close();";
    }
    echo "
    //-->
    </script>
    </body>\n</html>";
}
function edit_file_form(){
    global $dir_atual,$file_name,$file_data,$save_file;
    $file = $dir_atual.$file_name;
    if ($save_file){
        $fh=fopen($file,"w");
        fputs($fh,$file_data,strlen($file_data));
        fclose($fh);
    }
    $fh=fopen($file,"r");
    $file_data=fread($fh, filesize($file));
    fclose($fh);
    html_header();
    echo "<body bgcolor=\"#eeeeee\" marginwidth=\"0\" marginheight=\"0\">
    <table border=0 cellspacing=0 cellpadding=5 align=center>
    <form name=\"edit_form\" action=\"index.php\" method=\"post\">
    <input type=hidden name=action value=\"7\">
    <input type=hidden name=save_file value=\"1\">
    <input type=hidden name=dir_atual value=\"$dir_atual\">
    <input type=hidden name=file_name value=\"$file_name\">
    <tr><th colspan=2>".$file."</th></tr>
    <tr><td colspan=2><textarea name=file_data rows=33 cols=105>".htmlencode($file_data)."</textarea></td></tr>
    <tr><td><input type=button value=\"".et('Refresh')."\" onclick=\"document.edit_form_refresh.submit()\"></td><td align=right><input type=button value=\"".et('SaveFile')."\" onclick=\"go_save()\"></td></tr>
    </form>
    <form name=\"edit_form_refresh\" action=\"index.php\" method=\"post\">
    <input type=hidden name=action value=\"7\">
    <input type=hidden name=dir_atual value=\"$dir_atual\">
    <input type=hidden name=file_name value=\"$file_name\">
    </form>
    </table>
    <script language=\"Javascript\">
    <!--
        window.moveTo((window.screen.width-800)/2,((window.screen.height-600)/2)-20);
        function go_save(){
        ";
    if (is_writable($file)) echo "document.edit_form.submit();";
    else echo "if(confirm('".et('ConfTrySave')." ?')) document.edit_form.submit();";
    echo "
        }
    //-->
    </script>
    </body>\n</html>";
}
function config_form(){
    global $cfg;
    global $dir_atual,$path_info,$fm_root_atual,$lang,$version;
    global $config_action,$newsenha,$newlang,$newfm_root;
    $Warning = "";
    switch ($config_action){
        case 1:
            if ($fh = fopen("http://phpfm.sf.net/latest.php","r")){
                $data = "";
                while (!feof($fh)) $data .= fread($fh,1024);
                fclose($fh);
                $data = unserialize($data);
                $ChkVerWarning = "<tr><td align=right>&nbsp;";
                if (is_array($data)&&count($data)>0){
                    // sf.net logo
                    $ChkVerWarning .= "<a href=\"javascript:open_win('http://sourceforge.net')\"><img src=\"http://sourceforge.net/sflogo.php?group_id=114392&amp;type=1\" width=\"88\" height=\"31\" border=\"0\" alt=\"SourceForge.net Logo\" /></a>";
                    if (str_replace(".","",$data['version'])>str_replace(".","",$cfg->data['version'])) $ChkVerWarning .= "<td><a href=\"javascript:open_win('http://prdownloads.sourceforge.net/phpfm/phpFileManager-".$data['version'].".zip?download')\"><font color=green>".et('ChkVerAvailable')."</font></a>";
                    else $ChkVerWarning .= "<td><font color=red>".et('ChkVerNotAvailable')."</font>";
                } else $ChkVerWarning .= "<td><font color=red>".et('ChkVerError')."</font>";
            } else $ChkVerWarning .= "<td><font color=red>".et('ChkVerError')."</font>";
        break;
        case 2:
            $reload = false;
            if ($cfg->data['lang'] != $newlang){
                $cfg->data['lang'] = $newlang;
                $lang = $newlang;
                $reload = true;
            }
            $newfm_root = formata_path($newfm_root);
            if ($cfg->data['fm_root'] != $newfm_root){
                $cfg->data['fm_root'] = $newfm_root;
                if (strlen($newfm_root)>0) $dir_atual = $newfm_root;
                else $dir_atual = $path_info["dirname"]."/";
                setcookie("fm_root_atual", $newfm_root , 0 , "/");
                $reload = true;
            }
            $cfg->save();
            if ($reload){
                reloadframe("window.opener.parent",2);
                reloadframe("window.opener.parent",3);
            }
            $Warning1 = et('ConfSaved')."...";
        break;
        case 3:
            if ($cfg->data['auth_pass'] != md5($newsenha)){
                $cfg->data['auth_pass'] = md5($newsenha);
                setcookie("loggedon_pass", md5($newsenha) , 0 , "/");
            }
            $cfg->save();
            $Warning2 = et('PassSaved')."...";
        break;
    }
    html_header();
    echo "<body bgcolor=\"#eeeeee\" marginwidth=\"0\" marginheight=\"0\">\n";
    echo "
    <table border=0 cellspacing=0 cellpadding=5 align=center width=\"100%\">
    <form name=\"config_form\" action=\"index.php\" method=\"post\">
    <input type=hidden name=action value=2>
    <input type=hidden name=config_action value=0>
    <tr><td colspan=2 align=center><b>".strtoupper(et('Configurations'))."</b></td></tr>
    </table>
    <table border=0 cellspacing=0 cellpadding=5 align=center width=\"100%\">
    <tr><td align=right width=\"1%\">".et('Version').":<td>$version</td></tr>
    <tr><td align=right>".et('Size').":<td>".getsize($_SERVER["SCRIPT_FILENAME"])."</td></tr>
    <tr><td align=right>".et('Website').":<td><a href=\"javascript:open_win('http://phpfm.sf.net')\">http://phpfm.sf.net</a></td></tr>";
    if (strlen($ChkVerWarning)>0) echo $ChkVerWarning.$data['warnings'];
    else echo "<tr><td align=right>&nbsp;<td><input type=button value=\"".et('ChkVer')."\" onclick=\"test_config_form(1)\">";
    echo "
    <tr><td align=right width=1><nobr>".et('DocRoot').":</nobr><td>".$_SERVER["DOCUMENT_ROOT"]."</td></tr>
    <tr><td align=right><nobr>".et('FLRoot').":</nobr><td><input type=text size=60 name=newfm_root value=\"".$cfg->data['fm_root']."\"></td></tr>
    <tr><td align=right>".et('Lang').":<td><select name=newlang><option value=en>English<option value=pt>Português</select></td></tr>
    <tr><td>&nbsp;<td><input type=button value=\"".et('SaveConfig')."\" onclick=\"test_config_form(2)\">";
    if (strlen($Warning1)>0) echo " <font color=red>$Warning1</font>";
    echo "
    <tr><td align=right>".et('Pass').":<td><input type=text size=30 name=newsenha value=\"\"></td></tr>
    <tr><td>&nbsp;<td><input type=button value=\"".et('SavePass')."\" onclick=\"test_config_form(3)\">";
    if (strlen($Warning2)>0) echo " <font color=red>$Warning2</font>";
    echo "</td></tr>";
    echo "
    </form>
    </table>
    <script language=\"Javascript\">
    <!--
        function seta(sel,val){
            for(var x=0;x<sel.length;x++){
                if(sel.options[x].value==val){
                    sel.options[x].selected=true;
                    break;
                }
            }
        }
        seta(document.config_form.newlang,'".$cfg->data['lang']."');
        function test_config_form(arg){
            document.config_form.config_action.value = arg;
            document.config_form.submit();
        }
        function open_win(url){
            var w = 800;
            var h = 600;
            window.open(url, '', 'width='+w+',height='+h+',fullscreen=no,scrollbars=yes,resizable=yes,status=yes,toolbar=yes,menubar=yes,location=yes');
        }
        window.moveTo((window.screen.width-600)/2,((window.screen.height-240)/2)-20);
        window.focus();
    //-->
    </script>
    ";
    echo "</body>\n</html>";
}
function shell_form(){
    global $dir_atual,$shell_form,$cmd_arg;
    $data_out = "";
    if (strlen($cmd_arg)>0){
        exec($cmd_arg,$mat);
        if (count($mat)>0) $data_out = trim(implode("\n",$mat));
    }
    switch ($shell_form){
        case 1:
            html_header();
            echo "
            <body bgcolor=\"#eeeeee\" marginwidth=\"0\" marginheight=\"0\">
            <table border=0 cellspacing=0 cellpadding=0 align=center>
            <form name=\"data_form\">
            <tr><td><textarea name=data_out rows=36 cols=105 READONLY=\"1\"></textarea></td></tr>
            </form>
            </table>
            </body></html>";
        break;
        case 2:
            html_header();
            echo "
            <body bgcolor=\"#eeeeee\" marginwidth=\"0\" marginheight=\"0\">
            <table border=0 cellspacing=0 cellpadding=0 align=center>
            <form name=\"shell_form\" action=\"index.php\" method=\"post\">
            <input type=hidden name=dir_atual value=\"$dir_atual\">
            <input type=hidden name=action value=\"9\">
            <input type=hidden name=shell_form value=\"2\">
            <tr><td align=center><input type=text size=90 name=cmd_arg></td></tr>
            </form>";
            echo "
            <script language=\"Javascript\">
            <!--";
            if (strlen($data_out)>0) echo "
                var val = '# ".htmlencode($cmd_arg)."\\n".htmlencode(str_replace("<","[",str_replace(">","]",str_replace("\n","\\n",str_replace("\\","\\\\",$data_out)))))."\\n';
                parent.frame1.document.data_form.data_out.value += val;";
            echo "
                document.shell_form.cmd_arg.focus();
            //-->
            </script>
            ";
            echo "
            </table>
            </body></html>";
        break;
        default:
            $aux = "
            <script language=\"Javascript\">
            <!--
                window.moveTo((window.screen.width-800)/2,((window.screen.height-600)/2)-20);
            //-->
            </script>";
            html_header($aux);
            echo "
            <frameset rows=\"570,*\" framespacing=\"0\" frameborder=no>
                <frame src=\"index.php?action=9&shell_form=1\" name=frame1 border=\"0\" marginwidth=\"0\" marginheight=\"0\">
                <frame src=\"index.php?action=9&shell_form=2\" name=frame2 border=\"0\" marginwidth=\"0\" marginheight=\"0\">
            </frameset>
            </html>";
    }
}
function server_info(){
    if (!@phpinfo()) echo et('NoPhpinfo')."...";
    echo "<br><br>";
    $a=ini_get_all();
    $output="<table border=1 cellspacing=0 cellpadding=4 align=center>";
    while(list($key, $value)=each($a)) {
        list($k, $v)= each($a[$key]);
        $output.="<tr><td align=right>$key</td><td>$v</td></tr>";
    }
    $output.="</table>";
    echo $output;
    echo "<br><br><table border=1 cellspacing=0 cellpadding=4 align=center>";
    $safe_mode=trim(ini_get("safe_mode"));
    if ((strlen($safe_mode)==0)||($safe_mode==0)) $safe_mode=false;
    else $safe_mode=true;
    $is_windows_server = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    echo "<tr><td colspan=2>".php_uname();
    echo "<tr><td>safe_mode<td>".($safe_mode?"on":"off");
    if ($is_windows_server) echo "<tr><td>sisop<td>Windows<br>";
    else echo "<tr><td>sisop<td>Linux<br>";
    echo "</table><br><br><table border=1 cellspacing=0 cellpadding=4 align=center>";
    $display_errors=ini_get("display_errors");
    $ignore_user_abort = ignore_user_abort();
    $max_execution_time = ini_get("max_execution_time");
    $upload_max_filesize = ini_get("upload_max_filesize");
    $memory_limit=ini_get("memory_limit");
    $output_buffering=ini_get("output_buffering");
    $default_socket_timeout=ini_get("default_socket_timeout");
    $allow_url_fopen = ini_get("allow_url_fopen");
    $magic_quotes_gpc = ini_get("magic_quotes_gpc");
    ignore_user_abort(true);
    ini_set("display_errors",0);
    ini_set("max_execution_time",0);
    ini_set("upload_max_filesize","10M");
    ini_set("memory_limit","20M");
    ini_set("output_buffering",0);
    ini_set("default_socket_timeout",30);
    ini_set("allow_url_fopen",1);
    ini_set("magic_quotes_gpc",0);
    echo "<tr><td>&nbsp;<td>Get<td>Set<td>Get";
    echo "<tr><td>display_errors<td>$display_errors<td>0<td>".ini_get("display_errors");
    echo "<tr><td>ignore_user_abort<td>".($ignore_user_abort?"on":"off")."<td>on<td>".(ignore_user_abort()?"on":"off");
    echo "<tr><td>max_execution_time<td>$max_execution_time<td>0<td>".ini_get("max_execution_time");
    echo "<tr><td>upload_max_filesize<td>$upload_max_filesize<td>10M<td>".ini_get("upload_max_filesize");
    echo "<tr><td>memory_limit<td>$memory_limit<td>20M<td>".ini_get("memory_limit");
    echo "<tr><td>output_buffering<td>$output_buffering<td>0<td>".ini_get("output_buffering");
    echo "<tr><td>default_socket_timeout<td>$default_socket_timeout<td>30<td>".ini_get("default_socket_timeout");
    echo "<tr><td>allow_url_fopen<td>$allow_url_fopen<td>1<td>".ini_get("allow_url_fopen");
    echo "<tr><td>magic_quotes_gpc<td>$magic_quotes_gpc<td>0<td>".ini_get("magic_quotes_gpc");
    echo "</table><br><br>";
    echo "
    <script language=\"Javascript\">
    <!--
        window.moveTo((window.screen.width-800)/2,((window.screen.height-600)/2)-20);
        window.focus();
    //-->
    </script>";
    echo "</body>\n</html>";
}
// +--------------------------------------------------
// | Session
// +--------------------------------------------------
function logout(){
    setcookie("loggedon_pass",0,0,"/");
    form_login();
}
function login(){
    global $senha;
    global $auth_pass;
    if (md5(trim($senha)) == $auth_pass){
        setcookie("loggedon_pass",$auth_pass,0,"/");
        header ("Location: index.php");
    } else header ("Location: index.php?erro=1");
}
function form_login(){
    global $erro;
    global $auth_pass;
    html_header();
    echo "<body bgcolor=\"#eeeeee\">\n";
    if ($auth_pass != md5("")){
        echo "
        <table border=0 cellspacing=0 cellpadding=5>
            <form name=\"login_form\" action=\"index.php\" method=\"post\">
            <tr>
            <td><b>".et('FileMan')."</b>
            </tr>
            <tr>
            <td align=left><font size=4>".et('TypePass').".</font>
            </tr>
            <tr>
            <td><input name=senha type=password size=10> <input type=submit value=\"".et('Send')."\">
            </tr>
        ";
        if (strlen($erro) > 0) echo "
            <tr>
            <td align=left><font color=red size=4>".et('InvPass').".</font>
            </tr>
        ";
        echo "
            </form>
        </table>
             <script language=javascript>
             <!--
             document.login_form.senha.focus();
             //-->
             </script>
        ";
    } else {
        echo "
        <table border=0 cellspacing=0 cellpadding=5>
            <form name=\"login_form\" action=\"index.php\" method=\"post\">
            <input type=hidden name=frame value=3>
            <input type=hidden name=senha value=\"\">
            <tr>
            <td><b>".et('FileMan')."</b>
            </tr>
            <tr>
            <td><input type=submit value=\"".et('Enter')."\">
            </tr>
            </form>
        </table>
        ";
    }
    echo "</body>\n</html>";
}
function frame3(){
    global $islinux,$cmd_arg,$chmod_arg,$zip_dir,$fm_root_atual;
    global $dir_dest,$dir_atual,$dir_antes;
    global $dir_file_list,$dir_dir_list,$old_name,$new_name;
    global $action,$or_by,$order_dir_list_by;
    if ($action==1){
        $mat = explode("/",$dir_atual);
        $aux = $mat[count($mat)-2];
        if (!strstr($expanded_tree,$aux)) setcookie("expanded_tree", $expanded_tree.":".$aux , 0 , "/");
    }
    if (!isset($order_dir_list_by)){
        $order_dir_list_by = "1A";
        setcookie("order_dir_list_by", $order_dir_list_by , $cookie_cache_time , "/");
    } elseif (strlen($or_by)>0){
        $order_dir_list_by = $or_by;
        setcookie("order_dir_list_by", $or_by , $cookie_cache_time , "/");
    }
    html_header();
    echo "<body bgcolor=\"#eeeeee\">\n";
    if ($action){
        switch ($action){
            case 1: // create dir
            if (strlen($cmd_arg)>0){
                $cmd_arg = formata_path($dir_atual.$cmd_arg);
                if (!file_exists($cmd_arg)){
                    mkdir($cmd_arg,0777);
                    chmod($cmd_arg,0777);
                    reloadframe("parent",2);
                } else alert(et('FileDirExists').".");
            }
            break;
            case 2: // create arq
            if (strlen($cmd_arg)>0){
                $cmd_arg = $dir_atual.$cmd_arg;
                if (!file_exists($cmd_arg)){
                    if ($fh = @fopen($cmd_arg, "w")){
                        @fclose($fh);
                    }
                    chmod($cmd_arg,0666);
                } else alert(et('FileDirExists').".");
            }
            break;
            case 3: // rename arq ou dir
            if ((strlen($old_name)>0)&&(strlen($new_name)>0)){
                rename($dir_atual.$old_name,$dir_atual.$new_name);
                if (is_dir($dir_atual.$new_name)) reloadframe("parent",2);
            }
            break;
            case 4: // delete sel
            if(strstr($dir_atual,$fm_root_atual)){
                if (strlen($dir_file_list)>0){
                    $dir_file_list = explode("<|*|>",$dir_file_list);
                    if (count($dir_file_list)>0) {
                        for($x=0;$x<count($dir_file_list);$x++) {
                            $dir_file_list[$x] = trim($dir_file_list[$x]);
                            if (strlen($dir_file_list[$x])>0) total_delete($dir_atual.$dir_file_list[$x],$dir_dest.$dir_file_list[$x]);
                        }
                    }
                }
                if (strlen($dir_dir_list)>0){
                    $dir_dir_list = explode("<|*|>",$dir_dir_list);
                    if (count($dir_dir_list)>0) {
                        for($x=0;$x<count($dir_dir_list);$x++) {
                            $dir_dir_list[$x] = trim($dir_dir_list[$x]);
                            if (strlen($dir_dir_list[$x])>0) total_delete($dir_atual.$dir_dir_list[$x],$dir_dest.$dir_dir_list[$x]);
                        }
                        reloadframe("parent",2);
                    }
                }
            }
            break;
            case 5: // copy sel
            if (strlen($dir_dest)>0){
                if(strtoupper($dir_dest) != strtoupper($dir_atual)){
                    if (strlen($dir_file_list)>0){
                        $dir_file_list = explode("<|*|>",$dir_file_list);
                        if (count($dir_file_list)>0) {
                            for($x=0;$x<count($dir_file_list);$x++) {
                                $dir_file_list[$x] = trim($dir_file_list[$x]);
                                if (strlen($dir_file_list[$x])>0) total_copy($dir_atual.$dir_file_list[$x],$dir_dest.$dir_file_list[$x]);
                            }
                        }
                    }
                    if (strlen($dir_dir_list)>0){
                        $dir_dir_list = explode("<|*|>",$dir_dir_list);
                        if (count($dir_dir_list)>0) {
                            for($x=0;$x<count($dir_dir_list);$x++) {
                                $dir_dir_list[$x] = trim($dir_dir_list[$x]);
                                if (strlen($dir_dir_list[$x])>0) total_copy($dir_atual.$dir_dir_list[$x],$dir_dest.$dir_dir_list[$x]);
                            }
                            reloadframe("parent",2);
                        }
                    }
                    $dir_atual = $dir_dest;
                }
            }
            break;
            case 6: // move sel
            if (strlen($dir_dest)>0){
                if(strtoupper($dir_dest) != strtoupper($dir_atual)){
                    if (strlen($dir_file_list)>0){
                        $dir_file_list = explode("<|*|>",$dir_file_list);
                        if (count($dir_file_list)>0) {
                            for($x=0;$x<count($dir_file_list);$x++) {
                                $dir_file_list[$x] = trim($dir_file_list[$x]);
                                if (strlen($dir_file_list[$x])>0) total_move($dir_atual.$dir_file_list[$x],$dir_dest.$dir_file_list[$x]);
                            }
                        }
                    }
                    if (strlen($dir_dir_list)>0){
                        $dir_dir_list = explode("<|*|>",$dir_dir_list);
                        if (count($dir_dir_list)>0) {
                            for($x=0;$x<count($dir_dir_list);$x++) {
                                $dir_dir_list[$x] = trim($dir_dir_list[$x]);
                                if (strlen($dir_dir_list[$x])>0) total_move($dir_atual.$dir_dir_list[$x],$dir_dest.$dir_dir_list[$x]);
                            }
                            reloadframe("parent",2);
                        }
                    }
                    $dir_atual = $dir_dest;
                }
            }
            break;
            case 71: // compress sel
            if (strlen($cmd_arg)>0){
                ignore_user_abort(true);
                ini_set("display_errors",0);
                ini_set("max_execution_time",0);
                $zipfile=false;
                if (strstr($cmd_arg,".tar")) $zipfile = new tar_file($cmd_arg);
                elseif (strstr($cmd_arg,".zip")) $zipfile = new zip_file($cmd_arg);
                elseif (strstr($cmd_arg,".bzip")) $zipfile = new bzip_file($cmd_arg);
                elseif (strstr($cmd_arg,".gzip")) $zipfile = new gzip_file($cmd_arg);
                if ($zipfile){
                    $zipfile->set_options(array('basedir'=>$dir_atual,'overwrite'=>1,'level'=>3));
                    if (strlen($dir_file_list)>0){
                        $dir_file_list = explode("<|*|>",$dir_file_list);
                        if (count($dir_file_list)>0) {
                            for($x=0;$x<count($dir_file_list);$x++) {
                                $dir_file_list[$x] = trim($dir_file_list[$x]);
                                if (strlen($dir_file_list[$x])>0) $zipfile->add_files($dir_file_list[$x]);
                            }
                        }
                    }
                    if (strlen($dir_dir_list)>0){
                        $dir_dir_list = explode("<|*|>",$dir_dir_list);
                        if (count($dir_dir_list)>0) {
                            for($x=0;$x<count($dir_dir_list);$x++) {
                                $dir_dir_list[$x] = trim($dir_dir_list[$x]);
                                if (strlen($dir_dir_list[$x])>0) $zipfile->add_files($dir_dir_list[$x]);
                            }
                        }
                    }
                    $zipfile->create_archive();
                }
                unset($zipfile);
            }
            break;
            case 72: // decompress arq
            if (strlen($cmd_arg)>0){
                if (file_exists($dir_atual.$cmd_arg)){
                    $zipfile=false;
                    if (strstr($cmd_arg,".zip")) zip_extract();
                    elseif (strstr($cmd_arg,".bzip")||strstr($cmd_arg,".bz2")||strstr($cmd_arg,".tbz2")||strstr($cmd_arg,".bz")||strstr($cmd_arg,".tbz")) $zipfile = new bzip_file($cmd_arg);
                    elseif (strstr($cmd_arg,".gzip")||strstr($cmd_arg,".gz")||strstr($cmd_arg,".tgz")) $zipfile = new gzip_file($cmd_arg);
                    elseif (strstr($cmd_arg,".tar")) $zipfile = new tar_file($cmd_arg);
                    if ($zipfile){
                        $zipfile->set_options(array('basedir'=>$dir_atual,'overwrite'=>1));
                        $zipfile->extract_files();
                    }
                    unset($zipfile);
                    reloadframe("parent",2);
                }
            }
            break;
            case 8: // delete arq/dir
            if (strlen($cmd_arg)>0){
                if (file_exists($dir_atual.$cmd_arg)) total_delete($dir_atual.$cmd_arg);
                if (is_dir($dir_atual.$cmd_arg)) reloadframe("parent",2);
            }
            break;
            case 9: // CHMOD
            if((strlen($chmod_arg) == 4)&&(strlen($dir_atual)>0)){
                if ($chmod_arg[0]=="1") $chmod_arg = "0".$chmod_arg;
                else $chmod_arg = "0".substr($chmod_arg,strlen($chmod_arg)-3);
                $new_mod = octdec($chmod_arg);
                if (count($dir_file_list)>0) for($x=0;$x<count($dir_file_list);$x++) chmod($dir_atual.$dir_file_list[$x],$new_mod);
                if (count($dir_dir_list)>0) for($x=0;$x<count($dir_dir_list);$x++) chmod($dir_atual.$dir_dir_list[$x],$new_mod);
            }
            break;
        }
        if ($action != 10) dir_list_form();
    } else dir_list_form();
    echo "</body>\n</html>";
}
function frame2(){
    global $expanded_tree,$open_dir;
    if (!isset($expanded_tree)) $expanded_tree = "";
    if (strlen($open_dir)>0){
        if (strstr($expanded_tree,$open_dir)) $expanded_tree = str_replace(":".$open_dir,"",$expanded_tree);
        else $expanded_tree .= ":".$open_dir;
        setcookie("expanded_tree", $expanded_tree , 0 , "/");
    }
    show_tree();
}
function frameset(){
    html_header();
    echo "
    <frameset cols=\"300,*\" framespacing=\"0\">
        <frameset rows=\"0,*\" framespacing=\"0\" frameborder=no>
            <frame src=\"index.php?frame=1\" name=frame1 border=\"0\" marginwidth=\"0\" marginheight=\"0\" scrolling=no>
            <frame src=\"index.php?frame=2\" name=frame2 border=\"0\" marginwidth=\"0\" marginheight=\"0\">
        </frameset>
        <frame src=\"index.php?frame=3\" name=frame3 border=\"0\" marginwidth=\"0\" marginheight=\"0\">
    </frameset>
    ";
    echo "</html>";
}
// +--------------------------------------------------
// | Opensource Contributions
// +--------------------------------------------------
 /*-------------------------------------------------
 | TAR/GZIP/BZIP2/ZIP ARCHIVE CLASSES 2.0
 | By Devin Doucette
 | Copyright (c) 2004 Devin Doucette
 | Email: darksnoopy@shaw.ca
 +--------------------------------------------------
 | Email bugs/suggestions to darksnoopy@shaw.ca
 +--------------------------------------------------
 | This script has been created and released under
 | the GNU GPL and is free to use and redistribute
 | only if this copyright statement is not removed
 +--------------------------------------------------
 | Limitations:
 | - Only USTAR archives are officially supported for extraction, but others may work.
 | - Extraction of bzip2 and gzip archives is limited to compatible tar files that have
 | been compressed by either bzip2 or gzip.  For greater support, use the functions
 | bzopen and gzopen respectively for bzip2 and gzip extraction.
 | - Zip extraction is not supported due to the wide variety of algorithms that may be
 | used for compression and newer features such as encryption.
 +--------------------------------------------------
 */
class archive
{
    function archive($name)
    {
        $this->options = array(
            'basedir'=>".",
            'name'=>$name,
            'prepend'=>"",
            'inmemory'=>0,
            'overwrite'=>0,
            'recurse'=>1,
            'storepaths'=>1,
            'level'=>3,
            'method'=>1,
            'sfx'=>"",
            'type'=>"",
            'comment'=>""
        );
        $this->files = array();
        $this->exclude = array();
        $this->storeonly = array();
        $this->error = array();
    }

    function set_options($options)
    {
        foreach($options as $key => $value)
        {
            $this->options[$key] = $value;
        }
        if(!empty($this->options['basedir']))
        {
            $this->options['basedir'] = str_replace("\\","/",$this->options['basedir']);
            $this->options['basedir'] = preg_replace("/\/+/","/",$this->options['basedir']);
            $this->options['basedir'] = preg_replace("/\/$/","",$this->options['basedir']);
        }
        if(!empty($this->options['name']))
        {
            $this->options['name'] = str_replace("\\","/",$this->options['name']);
            $this->options['name'] = preg_replace("/\/+/","/",$this->options['name']);
        }
        if(!empty($this->options['prepend']))
        {
            $this->options['prepend'] = str_replace("\\","/",$this->options['prepend']);
            $this->options['prepend'] = preg_replace("/^(\.*\/+)+/","",$this->options['prepend']);
            $this->options['prepend'] = preg_replace("/\/+/","/",$this->options['prepend']);
            $this->options['prepend'] = preg_replace("/\/$/","",$this->options['prepend']) . "/";
        }
    }

    function create_archive()
    {
        $this->make_list();

        if($this->options['inmemory'] == 0)
        {
            $pwd = getcwd();
            chdir($this->options['basedir']);
            if($this->options['overwrite'] == 0 && file_exists($this->options['name'] . ($this->options['type'] == "gzip" || $this->options['type'] == "bzip"? ".tmp" : "")))
            {
                $this->error[] = "File {$this->options['name']} already exists.";
                chdir($pwd);
                return 0;
            }
            else if($this->archive = @fopen($this->options['name'] . ($this->options['type'] == "gzip" || $this->options['type'] == "bzip"? ".tmp" : ""),"wb+"))
            {
                chdir($pwd);
            }
            else
            {
                $this->error[] = "Could not open {$this->options['name']} for writing.";
                chdir($pwd);
                return 0;
            }
        }
        else
        {
            $this->archive = "";
        }

        switch($this->options['type'])
        {
        case "zip":
            if(!$this->create_zip())
            {
                $this->error[] = "Could not create zip file.";
                return 0;
            }
            break;
        case "bzip":
            if(!$this->create_tar())
            {
                $this->error[] = "Could not create tar file.";
                return 0;
            }
            if(!$this->create_bzip())
            {
                $this->error[] = "Could not create bzip2 file.";
                return 0;
            }
            break;
        case "gzip":
            if(!$this->create_tar())
            {
                $this->error[] = "Could not create tar file.";
                return 0;
            }
            if(!$this->create_gzip())
            {
                $this->error[] = "Could not create gzip file.";
                return 0;
            }
            break;
        case "tar":
            if(!$this->create_tar())
            {
                $this->error[] = "Could not create tar file.";
                return 0;
            }
        }

        if($this->options['inmemory'] == 0)
        {
            fclose($this->archive);
            chmod($this->options['name'],0666);
            if($this->options['type'] == "gzip" || $this->options['type'] == "bzip")
            {
                unlink($this->options['basedir'] . "/" . $this->options['name'] . ".tmp");
            }
        }
    }

    function add_data($data)
    {
        if($this->options['inmemory'] == 0)
        {
            fwrite($this->archive,$data);
        }
        else
        {
            $this->archive .= $data;
        }
    }

    function make_list()
    {
        if(!empty($this->exclude))
        {
            foreach($this->files as $key => $value)
            {
                foreach($this->exclude as $current)
                {
                    if($value['name'] == $current['name'])
                    {
                        unset($this->files[$key]);
                    }
                }
            }
        }
        if(!empty($this->storeonly))
        {
            foreach($this->files as $key => $value)
            {
                foreach($this->storeonly as $current)
                {
                    if($value['name'] == $current['name'])
                    {
                        $this->files[$key]['method'] = 0;
                    }
                }
            }
        }
        unset($this->exclude,$this->storeonly);
    }

    function add_files($list)
    {
        $temp = $this->list_files($list);
        foreach($temp as $current)
        {
            $this->files[] = $current;
        }
    }

    function exclude_files($list)
    {
        $temp = $this->list_files($list);
        foreach($temp as $current)
        {
            $this->exclude[] = $current;
        }
    }

    function store_files($list)
    {
        $temp = $this->list_files($list);
        foreach($temp as $current)
        {
            $this->storeonly[] = $current;
        }
    }

    function list_files($list)
    {
        if(!is_array($list))
        {
            $temp = $list;
            $list = array($temp);
            unset($temp);
        }

        $files = array();

        $pwd = getcwd();
        chdir($this->options['basedir']);

        foreach($list as $current)
        {
            $current = str_replace("\\","/",$current);
            $current = preg_replace("/\/+/","/",$current);
            $current = preg_replace("/\/$/","",$current);
            if(strstr($current,"*"))
            {
                $regex = preg_replace("/([\\\^\$\.\[\]\|\(\)\?\+\{\}\/])/","\\\\\\1",$current);
                $regex = str_replace("*",".*",$regex);
                $dir = strstr($current,"/")? substr($current,0,strrpos($current,"/")) : ".";
                $temp = $this->parse_dir($dir);
                foreach($temp as $current2)
                {
                    if(preg_match("/^{$regex}$/i",$current2['name']))
                    {
                        $files[] = $current2;
                    }
                }
                unset($regex,$dir,$temp,$current);
            }
            else if(@is_dir($current))
            {
                $temp = $this->parse_dir($current);
                foreach($temp as $file)
                {
                    $files[] = $file;
                }
                unset($temp,$file);
            }
            else if(@file_exists($current))
            {
                $files[] = array('name'=>$current,'name2'=>$this->options['prepend'] .
                    preg_replace("/(\.+\/+)+/","",($this->options['storepaths'] == 0 && strstr($current,"/"))?
                    substr($current,strrpos($current,"/") + 1) : $current),'type'=>0,
                    'ext'=>substr($current,strrpos($current,".")),'stat'=>stat($current));
            }
        }

        chdir($pwd);

        unset($current,$pwd);

        usort($files,array("archive","sort_files"));

        return $files;
    }

    function parse_dir($dirname)
    {
        if($this->options['storepaths'] == 1 && !preg_match("/^(\.+\/*)+$/",$dirname))
        {
            $files = array(array('name'=>$dirname,'name2'=>$this->options['prepend'] .
                preg_replace("/(\.+\/+)+/","",($this->options['storepaths'] == 0 && strstr($dirname,"/"))?
                substr($dirname,strrpos($dirname,"/") + 1) : $dirname),'type'=>5,'stat'=>stat($dirname)));
        }
        else
        {
            $files = array();
        }
        $dir = @opendir($dirname);

        while($file = @readdir($dir))
        {
            if($file == "." || $file == "..")
            {
                continue;
            }
            else if(@is_dir($dirname."/".$file))
            {
                if(empty($this->options['recurse']))
                {
                    continue;
                }
                $temp = $this->parse_dir($dirname."/".$file);
                foreach($temp as $file2)
                {
                    $files[] = $file2;
                }
            }
            else if(@file_exists($dirname."/".$file))
            {
                $files[] = array('name'=>$dirname."/".$file,'name2'=>$this->options['prepend'] .
                    preg_replace("/(\.+\/+)+/","",($this->options['storepaths'] == 0 && strstr($dirname."/".$file,"/"))?
                    substr($dirname."/".$file,strrpos($dirname."/".$file,"/") + 1) : $dirname."/".$file),'type'=>0,
                    'ext'=>substr($file,strrpos($file,".")),'stat'=>stat($dirname."/".$file));
            }
        }

        @closedir($dir);

        return $files;
    }

    function sort_files($a,$b)
    {
        if($a['type'] != $b['type'])
        {
            return $a['type'] > $b['type']? -1 : 1;
        }
        else if($a['type'] == 5)
        {
            return strcmp(strtolower($a['name']),strtolower($b['name']));
        }
        else
        {
            if($a['ext'] != $b['ext'])
            {
                return strcmp($a['ext'],$b['ext']);
            }
            else if($a['stat'][7] != $b['stat'][7])
            {
                return $a['stat'][7] > $b['stat'][7]? -1 : 1;
            }
            else
            {
                return strcmp(strtolower($a['name']),strtolower($b['name']));
            }
        }
        return 0;
    }

    function download_file()
    {
        if($this->options['inmemory'] == 0)
        {
            $this->error[] = "Can only use download_file() if archive is in memory. Redirect to file otherwise, it is faster.";
            return;
        }
        switch($this->options['type'])
        {
        case "zip":
            header("Content-type:application/zip");
            break;
        case "bzip":
            header("Content-type:application/x-compressed");
            break;
        case "gzip":
            header("Content-type:application/x-compressed");
            break;
        case "tar":
            header("Content-type:application/x-tar");
        }
        $header = "Content-disposition: attachment; filename=\"";
        $header .= strstr($this->options['name'],"/")? substr($this->options['name'],strrpos($this->options['name'],"/") + 1) : $this->options['name'];
        $header .= "\"";
        header($header);
        header("Content-length: " . strlen($this->archive));
        header("Content-transfer-encoding: binary");
        header("Cache-control: no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        print($this->archive);
    }
}

class tar_file extends archive
{
    function tar_file($name)
    {
        $this->archive($name);
        $this->options['type'] = "tar";
    }

    function create_tar()
    {
        $pwd = getcwd();
        chdir($this->options['basedir']);

        foreach($this->files as $current)
        {
            if($current['name'] == $this->options['name'])
            {
                continue;
            }
            if(strlen($current['name2']) > 99)
            {
                $path = substr($current['name2'],0,strpos($current['name2'],"/",strlen($current['name2']) - 100) + 1);
                $current['name2'] = substr($current['name2'],strlen($path));
                if(strlen($path) > 154 || strlen($current['name2']) > 99)
                {
                    $this->error[] = "Could not add {$path}{$current['name2']} to archive because the filename is too long.";
                    continue;
                }
            }
            $block = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",$current['name2'],decoct($current['stat'][2]),
                sprintf("%6s ",decoct($current['stat'][4])),sprintf("%6s ",decoct($current['stat'][5])),
                sprintf("%11s ",decoct($current['stat'][7])),sprintf("%11s ",decoct($current['stat'][9])),
                "        ",$current['type'],"","ustar","00","Unknown","Unknown","","",!empty($path)? $path : "","");

            $checksum = 0;
            for($i = 0; $i < 512; $i++)
            {
                $checksum += ord(substr($block,$i,1));
            }
            $checksum = pack("a8",sprintf("%6s ",decoct($checksum)));
            $block = substr_replace($block,$checksum,148,8);

            if($current['stat'][7] == 0)
            {
                $this->add_data($block);
            }
            else if($fp = @fopen($current['name'],"rb"))
            {
                $this->add_data($block);
                while($temp = fread($fp,1048576))
                {
                    $this->add_data($temp);
                }
                if($current['stat'][7] % 512 > 0)
                {
                    $temp = "";
                    for($i = 0; $i < 512 - $current['stat'][7] % 512; $i++)
                    {
                        $temp .= "\0";
                    }
                    $this->add_data($temp);
                }
                fclose($fp);
            }
            else
            {
                $this->error[] = "Could not open file {$current['name']} for reading. It was not added.";
            }
        }

        $this->add_data(pack("a512",""));

        chdir($pwd);

        return 1;
    }

    function extract_files()
    {
        $pwd = getcwd();
        chdir($this->options['basedir']);

        if($fp = $this->open_archive())
        {
            if($this->options['inmemory'] == 1)
            {
                $this->files = array();
            }

            while($block = fread($fp,512))
            {
                $temp = unpack("a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100temp/a6magic/a2temp/a32temp/a32temp/a8temp/a8temp/a155prefix/a12temp",$block);
                $file = array(
                    'name'=>$temp['prefix'] . $temp['name'],
                    'stat'=>array(
                        2=>$temp['mode'],
                        4=>octdec($temp['uid']),
                        5=>octdec($temp['gid']),
                        7=>octdec($temp['size']),
                        9=>octdec($temp['mtime']),
                    ),
                    'checksum'=>octdec($temp['checksum']),
                    'type'=>$temp['type'],
                    'magic'=>$temp['magic'],
                );
                if($file['checksum'] == 0x00000000)
                {
                    break;
                }
                else if($file['magic'] != "ustar")
                {
                    $this->error[] = "This script does not support extracting this type of tar file.";
                    break;
                }
                $block = substr_replace($block,"        ",148,8);
                $checksum = 0;
                for($i = 0; $i < 512; $i++)
                {
                    $checksum += ord(substr($block,$i,1));
                }
                if($file['checksum'] != $checksum)
                {
                    $this->error[] = "Could not extract from {$this->options['name']}, it is corrupt.";
                }

                if($this->options['inmemory'] == 1)
                {
                    $file['data'] = fread($fp,$file['stat'][7]);
                    fread($fp,(512 - $file['stat'][7] % 512) == 512? 0 : (512 - $file['stat'][7] % 512));
                    unset($file['checksum'],$file['magic']);
                    $this->files[] = $file;
                }
                else
                {
                    if($file['type'] == 5)
                    {
                        if(!is_dir($file['name']))
                        {
                            mkdir($file['name'],0777);
                            //mkdir($file['name'],$file['stat'][2]);
                            //chown($file['name'],$file['stat'][4]);
                            //chgrp($file['name'],$file['stat'][5]);
                        }
                    }
                    else if($this->options['overwrite'] == 0 && file_exists($file['name']))
                    {
                        $this->error[] = "{$file['name']} already exists.";
                    }
                    else if($new = @fopen($file['name'],"wb"))
                    {
                        fwrite($new,fread($fp,$file['stat'][7]));
                        fread($fp,(512 - $file['stat'][7] % 512) == 512? 0 : (512 - $file['stat'][7] % 512));
                        fclose($new);
                        chmod($file['name'],0666);
                        //chmod($file['name'],$file['stat'][2]);
                        //chown($file['name'],$file['stat'][4]);
                        //chgrp($file['name'],$file['stat'][5]);
                    }
                    else
                    {
                        $this->error[] = "Could not open {$file['name']} for writing.";
                    }
                }
                unset($file);
            }
        }
        else
        {
            $this->error[] = "Could not open file {$this->options['name']}";
        }

        chdir($pwd);
    }

    function open_archive()
    {
        return @fopen($this->options['name'],"rb");
    }
}

class gzip_file extends tar_file
{
    function gzip_file($name)
    {
        $this->tar_file($name);
        $this->options['type'] = "gzip";
    }

    function create_gzip()
    {
        if($this->options['inmemory'] == 0)
        {
            $pwd = getcwd();
            chdir($this->options['basedir']);
            if($fp = gzopen($this->options['name'],"wb{$this->options['level']}"))
            {
                fseek($this->archive,0);
                while($temp = fread($this->archive,1048576))
                {
                    gzwrite($fp,$temp);
                }
                gzclose($fp);
                chdir($pwd);
            }
            else
            {
                $this->error[] = "Could not open {$this->options['name']} for writing.";
                chdir($pwd);
                return 0;
            }
        }
        else
        {
            $this->archive = gzencode($this->archive,$this->options['level']);
        }

        return 1;
    }

    function open_archive()
    {
        return @gzopen($this->options['name'],"rb");
    }
}

class bzip_file extends tar_file
{
    function bzip_file($name)
    {
        $this->tar_file($name);
        $this->options['type'] = "bzip";
    }

    function create_bzip()
    {
        if($this->options['inmemory'] == 0)
        {
            $pwd = getcwd();
            chdir($this->options['basedir']);
            if($fp = bzopen($this->options['name'],"wb"))
            {
                fseek($this->archive,0);
                while($temp = fread($this->archive,1048576))
                {
                    bzwrite($fp,$temp);
                }
                bzclose($fp);
                chdir($pwd);
            }
            else
            {
                $this->error[] = "Could not open {$this->options['name']} for writing.";
                chdir($pwd);
                return 0;
            }
        }
        else
        {
            $this->archive = bzcompress($this->archive,$this->options['level']);
        }

        return 1;
    }

    function open_archive()
    {
        return @bzopen($this->options['name'],"rb");
    }
}

class zip_file extends archive
{
    function zip_file($name)
    {
        $this->archive($name);
        $this->options['type'] = "zip";
    }

    function create_zip()
    {
        $files = 0;
        $offset = 0;
        $central = "";

        if(!empty($this->options['sfx']))
        {
            if($fp = @fopen($this->options['sfx'],"rb"))
            {
                $temp = fread($fp,filesize($this->options['sfx']));
                fclose($fp);
                $this->add_data($temp);
                $offset += strlen($temp);
                unset($temp);
            }
            else
            {
                $this->error[] = "Could not open sfx module from {$this->options['sfx']}.";
            }
        }

        $pwd = getcwd();
        chdir($this->options['basedir']);

        foreach($this->files as $current)
        {
            if($current['name'] == $this->options['name'])
            {
                continue;
            }

            $translate =  array('Ç'=>pack("C",128),'ü'=>pack("C",129),'é'=>pack("C",130),'â'=>pack("C",131),'ä'=>pack("C",132),
                                'à'=>pack("C",133),'å'=>pack("C",134),'ç'=>pack("C",135),'ê'=>pack("C",136),'ë'=>pack("C",137),
                                'è'=>pack("C",138),'ï'=>pack("C",139),'î'=>pack("C",140),'ì'=>pack("C",141),'Ä'=>pack("C",142),
                                'Å'=>pack("C",143),'É'=>pack("C",144),'æ'=>pack("C",145),'Æ'=>pack("C",146),'ô'=>pack("C",147),
                                'ö'=>pack("C",148),'ò'=>pack("C",149),'û'=>pack("C",150),'ù'=>pack("C",151),'_'=>pack("C",152),
                                'Ö'=>pack("C",153),'Ü'=>pack("C",154),'£'=>pack("C",156),'¥'=>pack("C",157),'_'=>pack("C",158),
                                'ƒ'=>pack("C",159),'á'=>pack("C",160),'í'=>pack("C",161),'ó'=>pack("C",162),'ú'=>pack("C",163),
                                'ñ'=>pack("C",164),'Ñ'=>pack("C",165));
            $current['name2'] = strtr($current['name2'],$translate);

            $timedate = explode(" ",date("Y n j G i s",$current['stat'][9]));
            $timedate = ($timedate[0] - 1980 << 25) | ($timedate[1] << 21) | ($timedate[2] << 16) |
                ($timedate[3] << 11) | ($timedate[4] << 5) | ($timedate[5]);

            $block = pack("VvvvV",0x04034b50,0x000A,0x0000,(isset($current['method']) || $this->options['method'] == 0)? 0x0000 : 0x0008,$timedate);

            if($current['stat'][7] == 0 && $current['type'] == 5)
            {
                $block .= pack("VVVvv",0x00000000,0x00000000,0x00000000,strlen($current['name2']) + 1,0x0000);
                $block .= $current['name2'] . "/";
                $this->add_data($block);
                $central .= pack("VvvvvVVVVvvvvvVV",0x02014b50,0x0014,$this->options['method'] == 0? 0x0000 : 0x000A,0x0000,
                    (isset($current['method']) || $this->options['method'] == 0)? 0x0000 : 0x0008,$timedate,
                    0x00000000,0x00000000,0x00000000,strlen($current['name2']) + 1,0x0000,0x0000,0x0000,0x0000,$current['type'] == 5? 0x00000010 : 0x00000000,$offset);
                $central .= $current['name2'] . "/";
                $files++;
                $offset += (31 + strlen($current['name2']));
            }
            else if($current['stat'][7] == 0)
            {
                $block .= pack("VVVvv",0x00000000,0x00000000,0x00000000,strlen($current['name2']),0x0000);
                $block .= $current['name2'];
                $this->add_data($block);
                $central .= pack("VvvvvVVVVvvvvvVV",0x02014b50,0x0014,$this->options['method'] == 0? 0x0000 : 0x000A,0x0000,
                    (isset($current['method']) || $this->options['method'] == 0)? 0x0000 : 0x0008,$timedate,
                    0x00000000,0x00000000,0x00000000,strlen($current['name2']),0x0000,0x0000,0x0000,0x0000,$current['type'] == 5? 0x00000010 : 0x00000000,$offset);
                $central .= $current['name2'];
                $files++;
                $offset += (30 + strlen($current['name2']));
            }
            else if($fp = @fopen($current['name'],"rb"))
            {
                $temp = fread($fp,$current['stat'][7]);
                fclose($fp);
                $crc32 = crc32($temp);
                if(!isset($current['method']) && $this->options['method'] == 1)
                {
                    $temp = gzcompress($temp,$this->options['level']);
                    $size = strlen($temp) - 6;
                    $temp = substr($temp,2,$size);
                }
                else
                {
                    $size = strlen($temp);
                }
                $block .= pack("VVVvv",$crc32,$size,$current['stat'][7],strlen($current['name2']),0x0000);
                $block .= $current['name2'];
                $this->add_data($block);
                $this->add_data($temp);
                unset($temp);
                $central .= pack("VvvvvVVVVvvvvvVV",0x02014b50,0x0014,$this->options['method'] == 0? 0x0000 : 0x000A,0x0000,
                    (isset($current['method']) || $this->options['method'] == 0)? 0x0000 : 0x0008,$timedate,
                    $crc32,$size,$current['stat'][7],strlen($current['name2']),0x0000,0x0000,0x0000,0x0000,0x00000000,$offset);
                $central .= $current['name2'];
                $files++;
                $offset += (30 + strlen($current['name2']) + $size);
            }
            else
            {
                $this->error[] = "Could not open file {$current['name']} for reading. It was not added.";
            }
        }

        $this->add_data($central);

        $this->add_data(pack("VvvvvVVv",0x06054b50,0x0000,0x0000,$files,$files,strlen($central),$offset,
            !empty($this->options['comment'])? strlen($this->options['comment']) : 0x0000));

        if(!empty($this->options['comment']))
        {
            $this->add_data($this->options['comment']);
        }

        chdir($pwd);

        return 1;
    }
}
// +--------------------------------------------------
// | THE END
// +--------------------------------------------------
?>