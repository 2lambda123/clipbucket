<?php
/* 
 *******************************************************************
 | Copyright (c) 2007-2009 Clip-Bucket.com. All rights reserved.	
 | @ Author : ArslanHassan											
 | @ Software : ClipBucket , © PHPBucket.com						
 ********************************************************************
*/

require_once '../includes/admin_config.php';
$userquery->admin_login_check();
$pages->page_redir();



//unnstalling Plugin
if(isset($_GET['uninstall']))
{
	$folder = $_GET['f'];
	$cbplugin->uninstallPlugin(mysql_clean($_GET['uninstall']),$folder);
}

/**
 * Uninstalling Multiple Plugins
 */
if(isset($_POST['uninstall_selected']))
{
	$plugs = count($_POST['check_plugin']);
	for($i=0;$i<$plugs;$i++)
	{
		$itr = $_POST['check_plugin'][$i];
		$cbplugin->uninstallPlugin($_POST['plugin_file_'.$itr],$_POST['plugin_folder_'.$itr]); 
	}
}



//Activation or deactivating plugin
if(isset($_GET['activate']))
{
	$folder = $_GET['f'];
	$id = mysql_clean($_GET['activate']);
	$msg = $cbplugin->pluginActive($id,'yes',$folder);
}

/**
 * Activating Multiple
 */
if(isset($_POST['activate_selected']))
{
	$plugs = count($_POST['check_plugin']);
	for($i=0;$i<$plugs;$i++)
	{
		$itr = $_POST['check_plugin'][$i];
		$cbplugin->pluginActive($_POST['plugin_file_'.$itr],'yes',$_POST['plugin_folder_'.$itr]); 
	}
}

if(isset($_GET['deactivate']))
{
	$folder = $_GET['f'];
	$id = mysql_clean($_GET['deactivate']);
	$msg = $cbplugin->pluginActive($id,'no',$folder);
}


/**
 * dectivating Multiple
 */
if(isset($_POST['deactivate_selected']))
{
	$plugs = count($_POST['check_plugin']);
	for($i=0;$i<$plugs;$i++)
	{
		$itr = $_POST['check_plugin'][$i];
		$cbplugin->pluginActive($_POST['plugin_file_'.$itr],'no',$_POST['plugin_folder_'.$itr]); 
	}
}

//Installing Plugin
if(isset($_GET['install_plugin']))
{
	$folder = $_GET['f'];
	$msg = $cbplugin->installPlugin(mysql_clean($_GET['install_plugin']),$folder);
}

/**
 * Installing Multiple Plugins
 */
if(isset($_POST['install_selected']))
{
	$plugs = count($_POST['check_plugin']);
	for($i=0;$i<$plugs;$i++)
	{
		$itr = $_POST['check_plugin'][$i];
		$cbplugin->installPlugin($_POST['plugin_file_'.$itr],$_POST['plugin_folder_'.$itr]); 
	}
}

//Get New Plugin List
$availabe_plugin_list = $cbplugin->getNewPlugins();
Assign('new_plugin_list',$availabe_plugin_list);

//Get Installed Plugin List
$installed_plugin_list = $cbplugin->getInstalledPlugins();

Assign('installed_plugin_list',$installed_plugin_list);


//Doing some old stuff again :/
$plugin_list = '';
$pluginQueryA = mysql_query("SELECT * FROM plugins ");
$total_plugins = mysql_num_rows($pluginQueryA);
if($total_plugins > 0)
{
while($pluginDataA = mysql_fetch_array($pluginQueryA)){
	if(file_exists(BASEDIR.'/plugins/'.$pluginDataA['plugin_file'])
	&& !empty($pluginDataA['plugin_file']))
		include(BASEDIR.'/plugins/'.$pluginDataA['plugin_file']);
	//$plugin_list[] = $pluginDataA;
    $plugin_list[] = $pluginDataA;
	}
    Assign('plugin_list',$plugin_list);
} 
	
Assign('msg', @$msg);	
/*Template('header.html');
Template('leftmenu.html');
Template('message.html');
Template('plugin_manager.html');
Template('footer.html');*/

subtitle("Plugin Manager");
template_files('plugin_manager.html');
display_it();
?>