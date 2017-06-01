<?php

namespace Sallyx\RabbitMqLogger\Model;

interface Manager {

    /**
     * @param Message
     * @return void
     * @throws \Exception
     */
    public function save(Message $message);
}
