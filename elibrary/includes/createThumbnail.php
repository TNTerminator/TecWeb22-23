<?php
/*
Libreria per thumbnail presa da:
https://pqina.nl/blog/creating-thumbnails-with-php/
*/

// Link image type to correct image loader and saver
// - makes it easier to add additional types later on
// - makes the function easier to read
const IMAGE_HANDLERS = [
    IMAGETYPE_JPEG => [
        'load' => 'imagecreatefromjpeg',
        'save' => 'imagejpeg',
        'quality' => 100
    ],
    IMAGETYPE_PNG => [
        'load' => 'imagecreatefrompng',
        'save' => 'imagepng',
        'quality' => 0
    ],
    IMAGETYPE_GIF => [
        'load' => 'imagecreatefromgif',
        'save' => 'imagegif'
    ]
];

/**
 * @param $src - a valid file location
 * @param $dest - a valid file target
 * @param $targetWidth - desired output width
 * @param $targetHeight - desired output height or null
 * 
 * @return null in caso di errore, 
 */
function createThumbnail($src, $dest, $targetWidth, $targetHeight = null, $fittotarget = true) {

    // 1. Load the image from the given $src
    // - see if the file actually exists
    // - check if it's of a valid image type
    // - load the image resource

    // get the type of the image
    // we need the type to determine the correct loader
    $type = exif_imagetype($src);

    // if no valid type or no handler found -> exit
    if (!$type || !IMAGE_HANDLERS[$type]) {
        return null;
    }

    // load the image with the correct loader
    $image = call_user_func(IMAGE_HANDLERS[$type]['load'], $src);

    // no image found at supplied location -> exit
    if (!$image) {
        return null;
    }


    // 2. Create a thumbnail and resize the loaded $image
    // - get the image dimensions
    // - define the output size appropriately
    // - create a thumbnail based on that size
    // - set alpha transparency for GIFs and PNGs
    // - draw the final thumbnail

    // get original image width and height
    $width = imagesx($image);
    $height = imagesy($image);

    // maintain aspect ratio when no height set
    if ($targetHeight == null) {

        // get width to height ratio
        $ratio = $width / $height;

        // if is portrait
        // use ratio to scale height to fit in square
        if ($width > $height) {
            $targetHeight = floor($targetWidth / $ratio);
        }
        // if is landscape
        // use ratio to scale width to fit in square
        else {
            $targetHeight = $targetWidth;
            $targetWidth = floor($targetWidth * $ratio);
        }
    }

	// MM: aggiungo la gestione fittotarget
	// Ovvero rimpicciolisco l'immagine fino a stare dentro lo spazio previsto, e riempio i margini di fondo neutro.
	// Cioè evito che l'immagine perda le sue proporzioni
	$fittoWidth = $targetWidth;
	$fittoHeight = $targetHeight;
	if($fittotarget)
	{
        $ratio = $width / $height;
		$ratioTarget = $targetWidth / $targetHeight;
		if($ratio > $ratioTarget)
		{
			// Caso in cui l'origine deve stare dentro uno spazio più alto
			// tengo target width
			$targetHeight = $targetWidth / $ratio;
		}else
		{
			// Caso contrario
			$targetWidth = $targetHeight * $ratio;
		}
	}

    // create duplicate image based on calculated target size
    $thumbnail = imagecreatetruecolor($fittoWidth, $fittoHeight);
	$grey_bg = imagecolorallocate($thumbnail, 239, 239, 239);
	imagefill($thumbnail, 0, 0, $grey_bg);

    // set transparency options for GIFs and PNGs
    /*if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG) {

        // make image transparent
        imagecolortransparent(
            $thumbnail,
            imagecolorallocate($thumbnail, 0, 0, 0)
        );

        // additional settings for PNGs
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
    }*/

    // copy entire source image to duplicate image and resize
    imagecopyresampled(
        $thumbnail,
        $image,
        intval(($fittoWidth - $targetWidth) / 2), 
		intval(($fittoHeight - $targetHeight) / 2), 
		0, 0,
        $targetWidth, $targetHeight,
        $width, $height
    );


    // 3. Save the $thumbnail to disk
    // - call the correct save method
    // - set the correct quality level

    // save the duplicate version of the image to disk
    return call_user_func(
        IMAGE_HANDLERS[$type]['save'],
        $thumbnail,
        $dest,
        IMAGE_HANDLERS[$type]['quality']
    );
}


/**
 * Mio wrapper per ottenere già la costruzione del tag IMG corretto con la thumb ed ottimizzare il lavoro di creazione delle thumb
 */
function getThumbnail($src, $targetWidth, $targetHeight)
{
	$file_info = pathinfo($src);
	$dest = $file_info['dirname'] . "/" . $file_info['filename'] . "_" . $targetWidth . "x" . $targetHeight . "." . $file_info['extension'];
	if(!file_exists($dest))
		createThumbnail($src, $dest, $targetWidth, $targetHeight);
	return $dest;
}