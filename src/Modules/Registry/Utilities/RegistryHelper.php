<?php

namespace OlaHub\UserPortal\Helpers;

class RegistryHelper extends OlaHubCommonHelper
{

    function uploadImage($registry, $columnName, $registryImage = false)
    {
        if ($registryImage) {
            $mimes = ['image/bmp', 'image/gif', 'image/jpeg', 'image/x-citrix-jpeg', 'image/png', 'image/x-citrix-png', 'image/x-png'];
            $mime = $registryImage->getMimeType();
            if (!in_array($mime, $mimes)) {
                $log->setLogSessionData(['response' => ['status' => false, 'path' => false, 'msg' => 'Unsupported file type']]);
                $log->saveLogSessionData();
                return response(['status' => false, 'path' => false, 'msg' => 'Unsupported file type']);
            }
            $extension = $registryImage->getClientOriginalExtension();
            $fileNameStore = uniqid() . '.' . $extension;
            $filePath = DEFAULT_IMAGES_PATH . 'registries/' . $registry->id;
            if (!file_exists($filePath)) {
                mkdir($filePath, 0777, true);
            }
            $path = $registryImage->move($filePath, $fileNameStore);

            if ($registry->$columnName) {
                $oldImage = $registry->$columnName;
                @unlink(DEFAULT_IMAGES_PATH . '/' . $oldImage);
            }
            return "registries/" . $registry->id . "/$fileNameStore";
        }
        return $registryImage;
    }


}
