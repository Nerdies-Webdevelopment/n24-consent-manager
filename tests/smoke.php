<?php
/** Read-only functional checks. Run with N24_WP_LOAD pointing to wp-load.php. */
$wp_load=getenv('N24_WP_LOAD');
if(!$wp_load||!is_file($wp_load)){fwrite(STDERR,"Set N24_WP_LOAD.\n");exit(2);}
require $wp_load;
$manager=(new ReflectionClass('N24_Consent_Manager'))->newInstanceWithoutConstructor();
$assert=static function($ok,$message){if(!$ok)throw new RuntimeException($message);};
$color=new ReflectionMethod($manager,'sanitize_color');
$assert($color->invoke($manager,'#abc','#000000')==='#aabbcc','Short hex expands');
$assert($color->invoke($manager,'red;display:none','#000000')==='#000000','Invalid CSS is rejected');
$assert($color->invoke($manager,'transparent','#000000')==='transparent','Transparency survives sanitization');
$input=get_option('n24_consent_manager_options',[]);
$input['color_floating_background']='transparent';
$input['color_floating_hover_background']='transparent';
$output=$manager->sanitize_options($input);
$assert($output['color_floating_background']==='transparent'&&$output['color_floating_hover_background']==='transparent','Both transparent settings survive the full save sanitizer');
$svg=new ReflectionMethod($manager,'sanitize_icon_svg');
$clean=$svg->invoke($manager,'<svg onload="alert(1)"><script>alert(1)</script><circle cx="10" cy="10" r="4"/></svg>');
$assert(strpos($clean,'onload')===false&&strpos($clean,'<script')===false,'Unsafe SVG attributes stripped');
add_filter('n24_consent_manager_services',static function($services){$services['statistics'][]=['id'=>'test','name'=>'Test'];return $services;});
$cases=[null, ['uid'=>[],'settings'=>[]], ['uid'=>'test','settings'=>'wrong'], ['uid'=>str_repeat('x',65),'settings'=>[]], ['uid'=>'test','settings'=>['necessary'=>true,'statistics'=>'false','marketing'=>false,'external_media'=>false]]];
foreach($cases as $payload){$request=new WP_REST_Request('POST','/n24-consent-manager/v1/consent-log');$request->set_header('Content-Type','application/json');$request->set_body(wp_json_encode($payload));$assert($manager->handle_consent_log_request($request)->get_status()===400,'Invalid log payload rejected before database writes');}
$request=new WP_REST_Request('POST','/n24-consent-manager/v1/consent-log');$request->set_body(str_repeat('x',65537));$assert($manager->handle_consent_log_request($request)->get_status()===413,'Oversized log payload rejected');
echo "PASS: color/SVG sanitization and six malformed log requests (no log writes).\n";
