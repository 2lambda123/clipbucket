<?php

/**
 * function used to get photos
 */
function get_photos($param)
{
        global $cbphoto;
        return $cbphoto->get_photos($param);
}



//Simple Width Fetcher
function getWidth($file)
{
            $sizes = getimagesize($file);
            if($sizes)
                    return $sizes[0];   
}

//Simple Height Fetcher
function getHeight($file)
{
            $sizes = getimagesize($file);
            if($sizes)
                    return $sizes[1];   
}

//Load Photo Upload Form
function loadPhotoUploadForm($params)
{
            global $cbphoto;
            return $cbphoto->loadUploadForm($params);   
}
//Photo File Fetcher
function get_photo($params)
{
       global $cbphoto;
       return $cbphoto->getFileSmarty($params);
}

//Photo Upload BUtton
function upload_photo_button($params)
{
       global $cbphoto;
       return $cbphoto->upload_photo_button($params);
}

//Photo Embed Cides
function photo_embed_codes($params)
{
            global $cbphoto;
            return $cbphoto->photo_embed_codes($params);   
}

//Create download button

function photo_download_button($params)
{
            global $cbphoto;
            return $cbphoto->download_button($params);   
}

function load_photo_controls ( $args ) {
  global $cbphoto;
  if ( !empty($args['photo'])) {
    $controls = explode(',',$args['controls']);
    $controls = array_map('trim', $controls);
    foreach ($controls as $control) {
      $control_args = null;
      // Parameters for this controls
      $control_args = $args[$control];
      $method_to_call = 'load_'.$control;
      if ( method_exists($cbphoto, $method_to_call) ) {
        $cbphoto->$method_to_call( $args['photo'], $control_args); // Call the method
      }
    }
  }
}

function get_original_photo( $photo ) {
	global $cbphoto;
	if ( !is_array($photo) ) {
		$ph = $cbphoto->get_photo($photo);	
	} else {
		$ph = $photo;	
	}
	
	if ( is_array($ph) ) {
		$files = $cbphoto->get_image_file( $ph, 'o', true, null, false, true);
		$orig = $ph['filename'].'.'.$ph['ext'];
		$file = array_find( $orig, $files );
		return $file;			
	}
}

function insert_photo_colors( $photo ) {
	global $db, $cbphoto;	
	
	if ( !is_array($photo) ) {
		$ph = $cbphoto->get_photo( $photo );	
	} else {
		$ph = $photo;	
	}
		
	if ( is_array($ph) && isset($ph['photo_id']) ) {
		
		if ( $id =  $db->select( tbl('photosmeta'),'pmeta_id'," photo_id = '".$ph['photo_id']."' AND meta_name = 'colors' " ) ) {
			return $id;	
		}
		
		$dir = PHOTOS_DIR.'/';
		$file = get_original_photo( $ph );
		$path = $dir.$file;
		
		if ( file_exists($path) ) {
			$img = new CB_Resizer( $path );
			$colors = $img->color_palette();
                  $img->_destroy(); // Free memory
			if ( $colors ) {
				$jcolors = json_encode( $colors );
				$insert_id = $db->insert( tbl('photosmeta'), array('photo_id','meta_name','meta_value'), array($ph['photo_id'],'colors','|no_mc|'.$jcolors) );	
				if ( $insert_id ) {
					return $insert_id;	
				}
			}
		}
	}
}

function insert_exif_data( $photo ) {
	global $db, $cbphoto;
	
	if ( !is_array($photo) ) {
		$ph = $cbphoto->get_photo( $photo );	
	} else {
		$ph = $photo;	
	}
	
	if ( is_array($ph) && isset($ph['photo_id']) ) {
		$dir = PHOTOS_DIR.'/';

		if ( strtolower($ph['ext']) != 'jpg' ) {
			/* return if photo is not jpg */
			return;	
		}
		
		$file = get_original_photo( $ph );
		$path = $dir.$file;
		if ( file_exists($path) ) {
			/* File exists. read the exif data. Thanks to CopperMine, Love you */
			$data = exif_read_data_raw( $path, 0);
			if ( isset($data['SubIFD']) ) {
				$exif_to_include = array('IFD0','SubIFD','IFD1','InteroperabilityIFD');
				foreach( $exif_to_include as $eti ) {
					if ( isset( $data[$eti]) )	 {
						$exif[$eti] = $data[$eti];	
					}
				}
				$jexif = json_encode($exif);
				/* add new meta of exif_data for current photo */
				$insert_id = $db->insert( tbl('photosmeta'), array('photo_id','meta_name','meta_value'), array($ph['photo_id'],'exif_data','|no_mc|'.$jexif) );
				if ( $insert_id ) {
					/* update photo has_exif to yes, so we know that this photo has exif data */
					$db->update( tbl($cbphoto->p_tbl), array('exif_data'), array('yes'), " photo_id = '".$ph['photo_id']."' " );
					
					return $insert_id;
				}
			}
		}
	}
}

/**
 * Add a new thumb dimension in thumb_dimensions array. 
 * 
 * @global OBJECT $cbphoto
 * @param STRING $code
 * @param INT $width
 * @param INT $height
 * @param INT $crop
 * @param BOOL $watermark
 * @param BOOL $sharpit
 * @return array
 */
function add_custom_photo_size( $code, $width = 0, $height = 0, $crop = 4, $watermark = false, $sharpit = false ) {
	global $cbphoto;
	$sizes = $cbphoto->thumb_dimensions;
	$code = strtolower( $code );
	
	if ( $code == 't' || $code == 'm' || $code == 'l' || $code == 'o' ) {
		return false;	
	}
	
	if ( !is_numeric( $width )  || !is_numeric( $height ) ) {
		return false;	
	}
	
	$sizes [ $code ] = array(
		'width' => abs( $width ),
		'height' => abs( $height ),
		'crop' => $crop,
		'watermark' => $watermark,
		'sharpit' => $sharpit
	);
	
	return  $cbphoto->thumb_dimensions = $sizes;
}

function get_photometa( $id, $name ) {
	global $db;
	
	if ( empty( $id ) || empty( $name ) ) {
		return false;	
	}
	
	$result = $db->select( tbl('photosmeta'), '*', " photo_id = '".$id."' AND meta_name = '".strtolower($name)."' " );
	if ( $result ) {
		return $result[0];	
	} else {
		return false;	
	}
}

function get_photo_meta_value( $id, $name ) {
	$meta = get_photometa($id, $name);
	if ( $meta ) {
		return $meta['meta_value'];	
	} else {
		return false;	
	}
}

/**
 * It returns an array for photo action link.
 * This should be used with photo_action_links
 * filter.
 * 
 * @param string $text
 * @param string $href
 * @param string $target
 * @return mix 
 */

function add_photo_action_link( $text, $href, $icon = null, $target = null, $tags = null ) {
    if ( !$text && !$href ) {
        return false;
    }
    
    if ( strlen( trim( $href ) ) == 1 && $href == '#' ) {
        $skip_href_check = true;
    }
    
    if ( !preg_match('/(http:\/\/|https:\/\/)/', $href, $matches ) && !$skip_href_check ) {
        return false;
    }
    
    return array( 'href' => $href, 'text' => $text,'icon' => $icon, 'target' => $target, 'tags' => ( !is_null($tags) && is_array($tags) ) ? $tags : null );
}

function cbphoto_pm_action_link_filter( $links ) {
    if ( userid() ) {
      $links[] = add_photo_action_link( lang('Send in private message'), '#' , 'envelope', null, array('id' => 'private_message_photo', 'data-toggle' => 'modal', 'data-target' => '#private_message_photo_form') );  
    }
    
    return $links;
}

function register_photo_private_message_field( $photo ) {
    global $cbpm;
    
    $field = array(
            'attach_photo' => array(
                'name' => 'attach_photo',
                'id' => 'attach_photo',
                'value' => $photo['photo_key'],
                'type' => 'hidden'
        )
    );
    
    $cbpm->add_custom_field( $field );
}

function attach_photo_pm_handlers() {
    global $cbpm;
    
    $cbpm->pm_attachments[] = 'attach_photo';
    $cbpm->pm_attachments_parse[] = 'parse_photo_attachment';
}

function attach_photo( $array ) {
    global $cbphoto;
    if ( $cbphoto->photo_exists( $array['attach_photo'] ) ) {
        return '{p:'.$array['attach_photo'].'}';
    }
}

function parse_photo_attachment( $att ) {
    global $cbphoto;
    preg_match('/{p:(.*)}/',$att,$matches);
    $key = $matches[1];
    if ( !empty( $key ) )  {
        $photo = $cbphoto->get_photo( $key, true );
        if ( $photo ) {
            assign( 'photo',$photo );
            assign( 'type','photo' );
            Template( STYLES_DIR.'/global/blocks/pm/attachments.html', false );
        }
    }
}

/**
 * This is registered at cb_head ANCHOR. This loads photo actions links.
 * You can use photo_action_links filter to new links and
 * photo_action_configs to provide custom configurations.
 * 
 * Right now it has only to configurations.
 * menu_wrapper => <ul></ul>, This one wraps menu_items
 * menu_item => <li></li>, This one wraps the anchor link
 * 
 * @global object $cbphoto
 * @global array $photo
 * @return none 
 */
function load_photo_actions() {
	global $cbphoto, $photo;
	
	if ( empty($photo) || !$photo || !isset( $photo['ci_id'] ) ) {
		return false;	
	}
	$links = array();	
	$configs = array(
		'menu_wrapper' => '<ul></ul>',
		'menu_item' => '<li></li>'
	);
	
	$download = photo_download_button( array('details' => $photo, 'return_url' => true) );	
	if ( $download ) {
		$links[] = array(
                'href' => $download,
                'text' => lang('download_photo'),
                'icon' => 'circle-arrow-down'
		);
	}

	if ( userid() && $photo['userid'] == userid() ) {
		$links[] = array(
                'href' => BASEURL.'/edit_photo.php?photo='.$photo['photo_id'],
                'text' => lang('edit_photo'),
                'target' => '_blank',
                'icon' => 'pencil'
		);
	}
	
	$links[] = array(
        'href' => $cbphoto->photo_links( $photo, 'ma' ),
        'text' => lang('Set as avatar'),
        'icon' => 'user'
	);
		
	// Apply Filter to links
	$links = apply_filters( $links, 'photo_action_links');
	// Apply Filter to configs
	$configs = apply_filters( $configs, 'photo_action_configs' );
	$configs['menu_items'] = $links;
    
	assign('photo_action_configs', json_encode( $configs ) );
	assign('photo',$photo);
	Template(STYLES_DIR.'/global/photo_actions.html',false);
}

/**
 * This is registered at cb_head ANCHOR. This loads the photo tagging
 * plugin in clipbucket. You can use tagger_configurations filter to change
 * tagger configurations. Following is the list of configurations :
 * 
 *      |=  Show Tag labels =| BOOL
 *      showLabels => true
 * 
 *      |=  Provide an element ID and labels will loaded in them =| STRING
 *      labelWrapper => null
 * 
 *      |= Open labels links in new window =| BOOL
 *      labelLinksNew => false
 * 
 *      |= Make string like facebook: Tag1, Tag2 and Tag3 =| BOOL
 *      makeString => true
 * 
 *      |= We JS to create string. Set true, to create using CSS. Be warn CSS might not work in >IE9 =| BOOL
 *      makeStringCSS => false
 * 
 *      |= This wraps Remove Tag link in round brackets ( ) =| BOOL
 *      wrapDeleteLinks => true
 * 
 *      |= Show a little indicator arrow. Note: Arrow is created purely from CSS. Might not work in >IE9 =| BOOL
 *      use_arrows => true 
 *      
 *      |= To display Tag Photo elsewhere, provide an element ID  =| STRING
 *      buttonWrapper => null
 * 
 *      |= This will add a tag icon previous to Tag Photo text =| BOOL
 *      addIcon => true
 * 
 * @global object $db
 * @global object $cbphoto
 * @global array $photo
 * @global object $userquery
 * @return none 
 */
function load_tagging() {
	global $db, $cbphoto, $photo, $userquery;
	if ( USE_PHOTO_TAGGING != true ) {
		return false;	
	}
	
	if ( empty($photo) || !$photo || !isset( $photo['ci_id'] ) ) {
		return false;	
	}
	
	$options = $cbphoto->get_default_tagger_configs();
	$options['allowTagging'] = $photo['allow_tagging'];
      $phrases = $options['phrases'];
      /* User does not need phrases in apply_filters() function */
      unset( $options['phrases'] );

      $options = apply_filters( $options, 'tagger_configurations');
      /* Put back phrases in $options, over-wrtting JS Plugin Phrases */
      $options['phrases'] = $phrases;
	$tags = $cbphoto->get_photo_tags( $photo['photo_id'] );
	$autoComplete = $options['autoComplete'];
	$uid = userid();
	
	if ( ($autoComplete) == 1 && $uid ) {
		$friends = $userquery->get_contacts( $uid, 0, 'yes');
	}
	
	if ( $friends ) {
		foreach ( $friends as $contact ) {
			$fa[$contact['contact_userid']] = $contact['username'];
			$typeahead[] = $contact['username'];
		}
	}
	
	if ( $tags ) {
		/* Tags exists */
		foreach ( $tags as $tag ) {
			$needs_update = false;
			/* Check if tag is active or not and if current user is not tagger or owner of photo or is guest, do not show tag */
			if ( ( !$uid && $tag['ptag_active'] == 'no' ) || ( $tag['ptag_active'] == 'no' && $uid && $tag['ptag_by_userid'] != $uid && $tag['photo_owner_userid'] != $uid ) ) {
				continue; 
			}
			$ta = array();
			$ta['id'] = $tag['ptag_id'];
			$ta['key'] = $tag['ptag_key'];
			$ta['width'] = $tag['ptag_width'];
			$ta['height'] = $tag['ptag_height'];
			$ta['left'] = $tag['ptag_left'];
			$ta['top'] = $tag['ptag_top'];
			$ta['label'] = $tag['username'] = $tag['ptag_username'];
			$ta['added_by'] = $tag['ptag_by_username'];
			$ta['date_added'] = nicetime( $tag['date_added'], true);

			if ( $tag['ptag_active'] == 'no' ) {
				$ta['pending'] = true;
			}

			/* Photo owner and User which has tagged */
			if ( $uid == $tag['photo_owner_userid'] || $uid == $tag['ptag_by_userid'] ) {
				$ta['canDelete'] = true;
			}

			/* 
				If make sure tag is a user
				See which person is online, tagger or tagged
				If Tagger is online, give him option to delete

				if Tagged is online, check if it's tagger's friend
				if true, give option to delete
			*/
			if ( $tag['ptag_isuser'] == 1 ) {
				if ( $uid == $tag['ptag_by_userid'] ){ // Tagger is online
					$ta['canDelete'] = true; // Grant him access to delete
					if ( is_array($friends) && $fa[ $tag['ptag_userid'] ] ) {
						$ta['link'] = $userquery->profile_link( $tag['ptag_userid'] );
						// Person tagged is in his contacts lists and already been tagged, remove it from typahead array
						unset( $typeahead[ end(array_keys($typeahead,$tag['ptag_username'])) ] );
					}
				} else if ( $uid == $tag['ptag_userid'] ) {
					if ( is_array($friends) && $fa[ $tag['ptag_by_userid'] ] ) {
						$ta['canDelete'] = true;
						$ta['link'] = $userquery->profile_link( $tag['ptag_userid'] );
					}
				}
			}

			$defaultTags[] = $ta;
		}

		$options['defaultTags'] = $defaultTags;
	}
	
	if ( $friends && $typeahead && $options['autoComplete'] == 1 ) {
		$options['autoCompleteOptions']['source'] = $typeahead;
	}
	
	assign('tagger_configs', json_encode($options));
	assign('selector_id',$cbphoto->get_selector_id());
	assign('photo',$photo);
	Template(STYLES_DIR.'/global/photo_tagger.html',false); 
}

function join_collection_table() {
    global $cbcollection, $cbphoto;
    $c = tbl ($cbcollection->section_tbl ) ; $p = tbl('photos');
    $join = ' LEFT JOIN '.( $c ).' ON '.( $p.'.collection_id' ). ' = '.( $c.'.collection_id' );
    $alias = ", $p.views as views, $p.allow_comments as allow_comments, $p.allow_rating as allow_rating, $p.total_comments as total_comments, $p.date_added as date_added, $p.rating as rating, $p.rated_by as rated_by, $p.voters as voters, $p.featured as featured, $p.broadcast as broadcast, $p.active as pactive";
    $alias .= ", $c.views as cviews, $c.allow_comments as callow_comments, $c.allow_rating as callow_rating, $c.total_comments as ctotal_comments, $c.date_added as cdate_added, $c.rating as crating, $c.rated_by as crated_by, $c.voters as cvoters, $c.featured as cfeatured, $c.broadcast as cbroadcast, $c.active as active";
    
    return array( $join, $alias );
}

?>