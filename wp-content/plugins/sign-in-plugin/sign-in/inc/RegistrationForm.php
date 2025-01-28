<?php

class RegistrationForm {

	public static function createForm( $context, $hidden = false ) {
		$html = "";

		$html .= "<div class='field-container'>";
		foreach ( SignIn::$scaffold as $key => $parameters ) {
			$req_star = ( str_contains($parameters['classes'], 'required') ) ? '' : 'hidden';
			$hide = ( $hidden ) ? ' hidden' : '';

			$html .= "<div class='{$parameters['classes']}{$hide}'>";
			if ( ($parameters['element'] !== 'h2' && $parameters['element'] !== 'p' ) && !empty( $parameters['label'] ) ) {
				$html .= "<label for='{$key}' class=''><span class='req-star {$req_star}'>*</span>".$parameters['label']."</label>".PHP_EOL;

			}
			switch ( $parameters['element'] ) {
				case 'h2' :
					$html .= "<h2 class=''>{$parameters['label']}</h2>".PHP_EOL;
					break;

				case 'p' :
					$html .= "<p class=''>{$parameters['label']}</p>".PHP_EOL;
					break;

				case 'text' :
					$html .= "<input type='text' name='{$key}' value='".(!empty($_POST[$key]) ? $_POST[$key] : "")."' class='form-control' id='{$key}' />".PHP_EOL;
					break;

				case 'hidden' :
					$html .= "<input type='hidden' name='{$key}' value='".(!empty($_POST[$key]) ? $_POST[$key] : "")."' class='form-control' id='{$key}' />".PHP_EOL;
					break;

				case 'email' :
					$html .= "<input type='email' name='{$key}' value='".(!empty($_POST[$key]) ? $_POST[$key] : "")."' class='form-control' id='{$key}' />".PHP_EOL;
					break;

				case 'select' :
					$html .= "<select name='{$key}' class='form-control' autocomplete='off' id='{$key}'>";
					$func = $key."_options";
					$html .= $func( $key, $parameters );
					$html .= "</select>".PHP_EOL;
					break;

				case 'checkbox' :
					$html .= "<div>";
					$func = $key."_options";
					$html .= $func( $key, $parameters, (!empty($_POST[$key]) ? $_POST[$key] : array()) );
					$html .= "</div>";
					break;

				case 'radio' :
					$html .= "<div>";
					$func = $key."_options";
					$html .= $func( $key, $parameters );
					$html .= "</div>";
					break;

				default :
					$html .= '';
			}
			$html .= "</div>".PHP_EOL;
		}
		$html .= "</div>".PHP_EOL;

		$html = apply_filters( 'aasgnn_registration_form_html_' . $context, $html );

		echo $html;
	}
}

