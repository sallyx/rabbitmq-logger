<?php

namespace Sallyx\RabbitMqLogger\Controls;

use Nette\Application\UI\Component;

interface GridFactory {

    /**
     * @return Component
     */
    public function create();
}
