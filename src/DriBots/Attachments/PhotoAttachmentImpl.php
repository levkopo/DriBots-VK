<?php

namespace DriBots\Attachments;

use DriBots\Data\Attachments\PhotoAttachment;

class PhotoAttachmentImpl extends PhotoAttachment {
    public function __construct(private string $path,
                                string $extension){
        $this->extension = $extension;
    }

    public function getPath(): string{
        return $this->path;
    }
}