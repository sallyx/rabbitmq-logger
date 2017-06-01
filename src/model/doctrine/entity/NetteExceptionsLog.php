<?php

namespace Sallyx\RabbitMqLogger\Model\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class NetteExceptionsLog extends \Kdyby\Doctrine\Entities\BaseEntity {

    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @return void
     */
    public function __construct() {
	parent::__construct();
	$this->created = new \DateTime();
    }

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $created;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $guid;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $timestamp;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $priority;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $location;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $exceptionFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $errorText;

}
