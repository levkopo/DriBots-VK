<?php


namespace DriBots\Platforms;


use DriBots\Data\InlineQuery;
use DriBots\Data\User;
use JetBrains\PhpStorm\Pure;

class VKInlineQuery extends InlineQuery {
    #[Pure] public function __construct(string $id,
                                        public $chatId,
                                        User $user,
                                        string $query) {
        parent::__construct($id, $user, $query);
    }
}