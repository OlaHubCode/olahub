<?php

namespace OlaHub\UserPortal\Libraries;

use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class ImageReader {

    private $fileContent;
    private $fileMimeType;
    private $image_create_func;
    private $image_save_func;
    private $new_image_ext;
    private $originalWidth;
    private $originalHeight;

    protected function getObjectById($id) {
        $this->fileMimeType = @mime_content_type(base_path('temp_photos/' . $id));
        if (!$this->fileMimeType) {
            throw new NotAcceptableHttpException(404);
        }
        //$this->fileContent = file_get_contents(base_path('temp_photos/' . $id));
        //list($this->originalWidth, $this->originalHeight) = getimagesizefromstring($this->fileContent);
        //$this->setImageFuncsFromMime();
    }

    public function displayImage($id) {
        $this->getObjectById($id);
        $this->fileMimeType = str_replace(["\n", "\t"], '', $this->fileMimeType);
        $path = base_path('temp_photos/'.$id);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-type: ' . $this->fileMimeType);
        header('Content-Transfer-Encoding: Binary');
        header('Expires: 0');
        header('Pragma: public');
        echo file_get_contents(base_path('temp_photos/' . $id), true);
		die();
    }

    /**
     * Downloads a file into temp directory and display the content by the browser.
     *
     * @param string $id
     */
    public function resizeImage($id, $newwidth, $newheight) {
        $this->getObjectById($id);
        $this->fileMimeType = str_replace(["\n", "\t"], '', $this->fileMimeType);
        $name = str_replace(' ', '_', last(explode('/', $id)));
        $path = getcwd() . '/' . $name;
        $tempFile = fopen($name, 'wb');
        fwrite($tempFile, $this->fileContent);
        fclose($tempFile);
        $src = call_user_func($this->image_create_func, $path);
        $dst = imagecreatetruecolor($newwidth, $newheight);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresized($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $this->originalWidth, $this->originalHeight);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-type: ' . $this->fileMimeType);
        header('Content-Transfer-Encoding: Binary');
        header('Expires: 0');
        header('Pragma: public');
        flush();
        call_user_func($this->image_save_func, $dst);
        unlink($name);
    }
    
    private function setImageFuncsFromMime(){
        switch ($this->fileMimeType) {
            case 'image/jpeg':
            case 'image/webp':
                $this->image_create_func = 'imagecreatefromjpeg';
                $this->image_save_func = 'imagejpeg';
                $this->new_image_ext = 'jpg';
                break;
            case 'image/png':
                $this->image_create_func = 'imagecreatefrompng';
                $this->image_save_func = 'imagepng';
                $this->new_image_ext = 'png';
                break;

            case 'image/gif':
                $this->image_create_func = 'imagecreatefromgif';
                $this->image_save_func = 'imagegif';
                $this->new_image_ext = 'gif';
                break;

            default:
                abort(404);
        }
    }

    /**
     * Downloads a file by its id.
     *
     * @param string $id
     */
    public function downloadFile($id) {
        $file = $this->getObjectById($id);
        $name = $file->properties['cmis:name'];
        $mime = $file->properties['cmis:contentStreamMimeType'];
        $mime = str_replace("\t", '', $mime);
        $mime = str_replace("\n", '', $mime);
        $length = $file->properties['cmis:contentStreamLength'];
        $content = $this->repository->getContentStream($id);
        $name = str_replace(' ', '_', $name);
        $tempFile = fopen($name, 'wb');
        fwrite($tempFile, $content);
        fclose($tempFile);
        $domain = $_SERVER['SERVER_NAME'];
        $path = getcwd() . '/' . $name;
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Description: File Transfer');
        header('Content-type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $name . "\"\n");
        header('Content-Transfer-Encoding: Binary');
        header('Expires: 0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($name));
        if (ob_get_contents()) {
            ob_end_clean();
        }
        flush();
        readfile($path);
        unlink($name);
        exit();
    }

}
