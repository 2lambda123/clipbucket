<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Fawaz
 * Date: 8/28/13
 * Time: 12:20 PM
 * To change this template use File | Settings | File Templates.
 */

//Redirect Using JAVASCRIPT

function redirect_to($url){
    echo '<script type="text/javascript">
		window.location = "'.$url.'"
		</script>';
    exit("Javascript is turned off, <a href='$url'>click here to go to requested page</a>");
}

//Test function to return template file
function Fetch($name,$inside=FALSE)
{
    if($inside)
        $file = CBTemplate::fetch($name);
    else
        $file = CBTemplate::fetch(LAYOUT.'/'.$name);

    return $file;
}

//Simple Template Displaying Function

function Template($template,$layout=true){
    global $admin_area;
    if($layout)
        CBTemplate::display(LAYOUT.'/'.$template);
    else
        CBTemplate::display($template);

    if($template == 'footer.html' && $admin_area !=TRUE){
        CBTemplate::display(BASEDIR.'/includes/templatelib/'.$template);
    }
    if($template == 'header.html'){
        CBTemplate::display(BASEDIR.'/includes/templatelib/'.$template);
    }
}

function Assign($name,$value)
{
    CBTemplate::assign($name,$value);
}