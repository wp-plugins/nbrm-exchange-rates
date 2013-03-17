<?php
/**
 * Plugin Name: NBRM Exchange Rates
 * Plugin URI: http://iok.mk/
 * Description: Генерирање на курсна листа според податоците на Народна банка на Република Македонија
 * Version: 1.0
 * Author: Darko Atanasovski
 * Author URI: http://iok.mk/darko/
 *
 */
add_action('widgets_init', 'nbrm_load_widgets');

function nbrm_load_widgets()
{
	register_widget('NBRM_Widget');
}

class NBRM_Widget extends WP_Widget {
	function NBRM_Widget()
	{
		$widget_ops = array('classname' => 'nbrm_widget', 'description' => 'Курсна листа според НБРМ');
		$control_ops = array('id_base' => 'nbrm_widget');
		$this->WP_Widget('nbrm_widget', 'NBRM Exchange Rates', $widget_ops, $control_ops);
	}
	function widget($args, $instance)
	{
		wp_register_style( 'nbrm-style' , plugins_url('/nbrm-style.css', __FILE__));
		wp_enqueue_style( 'nbrm-style' );
		extract($args);
        $title = $instance['title'];
        $theme_color = empty($instance['theme_color']) ? '#cc0000' : $instance['theme_color'];
        $title_text_color = empty($instance['title_text_color']) ? '#fff' : $instance['title_text_color'];
        
        $data_icon = $instance['data_icon'];
        $data_country_name = $instance['data_country_name'];
        $data_buying_rate = $instance['data_buying_rate'];
        $data_middle_rate = $instance['data_middle_rate'];
        $data_selling_rate = $instance['data_selling_rate'];

 $output = '<style type="text/css">.nbrm_item:hover{color:'.$theme_color.';}</style>';
 $output .= '<div id="nbrm_wrapper">';
 $output .= '<h3 id="nbrm_title" style="background:'.$theme_color.';color:'.$title_text_color.'">'.$title.'</h3>';
try {
	$client = new SoapClient("http://www.nbrm.mk/klservice/kurs.asmx?wsdl", array('trace' => true));
	$startEndDate = date('d.m.Y');
	$response = $client->GetExchangeRates(array('StartDate' => $startEndDate, 'EndDate' => $startEndDate));
	$data = simplexml_load_string($response->GetExchangeRatesResult);
    $output .= '<table id="nbrm_exchange_table">';
    	 $output .= '<thead style="background:'.$theme_color.';color:'.$title_text_color.'">';
	         $output .= '<tr>';
	         	$colspan = $data_icon==1 && $data_country_name==1 ? 'colspan="2"' : ''; 
	            if($data_country_name){
	             $output .= '<th '.$colspan.'>Држава</th>';
	            }
	            if($data_buying_rate){
	             $output .= '<th>Куповен</th>';
	            }
	            if($data_middle_rate){
	             $output .= '<th>Среден</th>';
	            }
	            if($data_selling_rate){
	             $output .= '<th>Продажен</th>';
	            }
	         $output .= '</tr>';
         $output .= '</thead>';
         $output .= '<tbody>';
	     foreach ($data->KursZbir as $object) {
	             $output .= '<tr class="nbrm_item">';
	            	if($data_icon){
	                 $output .= '<td class="nbrm_flag"><img src="'.plugins_url('/images/'.strtolower($object->Oznaka).'.jpg', __FILE__).'" alt="'.$object->Oznaka.'"  /></td>';
	                }
	                if($data_country_name){
	                 $output .= '<td>'.$object->Drzava.'</td>';
	                }
	                if($data_buying_rate){
	                 $output .= '<td class="nbrm_center">'.number_format(round((float) $object->Kupoven, 2), 2).'</td>';
	                }
	                if($data_middle_rate){
	                 $output .= '<td class="nbrm_center">'.number_format(round((float) $object->Sreden, 2), 2).'</td>';
	                }
	                if($data_selling_rate){
	                 $output .= '<td class="nbrm_center">'.number_format(round((float) $object->Prodazen, 2), 2).'</td>';
	                }
	             $output .= '</tr>'; 
			 } 
        $output .= '</tbody>';
     $output .= '</table>';
} catch (SoapFault $e) {
    echo "Error: <br/>";
    echo $e->getMessage();
}
$output .= '</div>';
	$cache_file = dirname(__FILE__) . '/cache/'.date('Ymd').'.html';
	if(file_exists($cache_file))
	{
		include($cache_file);
	}else{
		foreach(scandir(dirname(__FILE__) . '/cache/') as $file)
		{
			if($file != '.' and $file != '..' and $file != 'index.html')
			{
				unlink(dirname(__FILE__) . '/cache/' . $file);
			}
		}
		$cache_file_handler = fopen($cache_file,'w+');
		fwrite($cache_file_handler,$output);
		fclose($cache_file_handler);
		echo $output;
	}	
}
	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['theme_color'] = $new_instance['theme_color'];
		$instance['title_text_color'] = $new_instance['title_text_color'];
		
		$instance['data_icon'] = $new_instance['data_icon'];
		$instance['data_country_name'] = $new_instance['data_country_name'];
		$instance['data_buying_rate'] = $new_instance['data_buying_rate'];
		$instance['data_middle_rate'] = $new_instance['data_middle_rate'];
		$instance['data_selling_rate'] = $new_instance['data_selling_rate'];
		foreach(scandir(dirname(__FILE__) . '/cache/') as $file)
		{
			if($file != '.' && $file != '..' && $file != 'index.html')
			{
				unlink(dirname(__FILE__) . '/cache/' . $file);
			}
		}
		return $instance;
	}
	function form($instance)
	{
		$defaults = array("title"=>"НБРМ Курсна листа","theme_color"=>"#cc0000","title_text_color"=>"#fff","data_icon"=>1,"data_country_name"=>1,"data_buying_rate"=>1,"data_middle_rate"=>0,"data_selling_rate"=>1);
		$instance = wp_parse_args((array) $instance, $defaults);
?>
		<p>
			<label for="<?php echo $this->get_field_id("title"); ?>">Наслов</label>
            <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width: 229px;" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id("theme_color"); ?>">Боја</label>
            <input type="text" id="<?php echo $this->get_field_id('theme_color'); ?>" name="<?php echo $this->get_field_name('theme_color'); ?>" value="<?php echo $instance['theme_color']; ?>" style="width:60px;" maxlength="7" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id("title_text_color"); ?>">Боја на текст</label>
            <input type="text" id="<?php echo $this->get_field_id('title_text_color'); ?>" name="<?php echo $this->get_field_name('title_text_color'); ?>" value="<?php echo $instance['title_text_color']; ?>" style="width:60px;" maxlength="7" />
		</p>
		<p style="border-bottom:1px solid rgba(30,30,30,.2);">
			Приказ на податоци:
		</p>
		<table>
			<tr>
				<td><label for="<?php echo $this->get_field_id("data_icon"); ?>">Знаме</label></td>
				<td>
					<select id="<?php echo $this->get_field_id('data_icon'); ?>" name="<?php echo $this->get_field_name('data_icon'); ?>">
						<option value="1" <?php echo $instance['data_icon']==1 ? 'selected="selected"' : ''; ?>>Прикажи</option>
						<option value="0" <?php echo $instance['data_icon']==0 ? 'selected="selected"' : ''; ?>>Не прикажувај</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="<?php echo $this->get_field_id("data_country_name"); ?>">Име на држава</label></td>
				<td>
					<select id="<?php echo $this->get_field_id('data_country_name'); ?>" name="<?php echo $this->get_field_name('data_country_name'); ?>">
						<option value="1" <?php echo $instance['data_country_name']==1 ? 'selected="selected"' : ''; ?>>Прикажи</option>
						<option value="0" <?php echo $instance['data_country_name']==0 ? 'selected="selected"' : ''; ?>>Не прикажувај</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="<?php echo $this->get_field_id("data_buying_rate"); ?>">Куповен курс</label></td>
				<td>
					<select id="<?php echo $this->get_field_id('data_buying_rate'); ?>" name="<?php echo $this->get_field_name('data_buying_rate'); ?>">
						<option value="1" <?php echo $instance['data_buying_rate']==1 ? 'selected="selected"' : ''; ?>>Прикажи</option>
						<option value="0" <?php echo $instance['data_buying_rate']==0 ? 'selected="selected"' : ''; ?>>Не прикажувај</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="<?php echo $this->get_field_id("data_middle_rate"); ?>">Среден курс</label></td>
				<td>
					<select id="<?php echo $this->get_field_id('data_middle_rate'); ?>" name="<?php echo $this->get_field_name('data_middle_rate'); ?>">
						<option value="1" <?php echo $instance['data_middle_rate']==1 ? 'selected="selected"' : ''; ?>>Прикажи</option>
						<option value="0" <?php echo $instance['data_middle_rate']==0 ? 'selected="selected"' : ''; ?>>Не прикажувај</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><label for="<?php echo $this->get_field_id("data_selling_rate"); ?>">Продажен курс</label></td>
				<td>
					<select id="<?php echo $this->get_field_id('data_selling_rate'); ?>" name="<?php echo $this->get_field_name('data_selling_rate'); ?>">
						<option value="1" <?php echo $instance['data_selling_rate']==1 ? 'selected="selected"' : ''; ?>>Прикажи</option>
						<option value="0" <?php echo $instance['data_selling_rate']==0 ? 'selected="selected"' : ''; ?>>Не прикажувај</option>
					</select>
				</td>
			</tr>
		</table>
<?php           
        }
}
?>