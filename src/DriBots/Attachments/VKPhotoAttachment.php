<?php

namespace DriBots\Attachments;

use DriBots\Data\Attachments\PhotoAttachment;

class VKPhotoAttachment extends PhotoAttachment {
    public function __construct(private array $attachment){
        parent::__construct('jpg');
    }

    public function getPath(): string{
        return $this->attachment['sizes'][(int) (count($this->attachment['sizes'])/2)]['url'];
    }

    public function getFileId(): string {
        return 'photo'.$this->getPath();
    }
}