<?php

add_filter('wpcf7_form_elements', 'imp_wpcf7_form_elements');
function imp_wpcf7_form_elements($content)
{
    $content = str_replace('type="text" name="time', 'type="time" step="900" name="time', $content);
    #echo "content: <xmp>" . $content . "</xmp><br>";
    #if ($str_pos !== false) {
    #    $content = substr_replace($content, ' type="time" ', $str_pos, 0);
    #}
    return $content;
}
