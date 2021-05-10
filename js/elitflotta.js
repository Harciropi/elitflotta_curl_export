/**
 * js/jquery
 * 
 * @version 2019.07.29.
 * @package elitflotta_export
 * @author Soós András
 */

$(document).ready(function(){
    open_the_list();
});
    
function open_the_list()
{
    setTimeout(function(){
        $('.list_box').css({maxWidth:'960px',minHeight:'calc(100% - 40px)'});
        $('.justaminute').delay(800).fadeOut('slow');
        $('.checkbox_list_form').delay(1500).fadeIn('slow');
    }, 3500);
}