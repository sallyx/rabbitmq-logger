<?php

namespace Sallyx\RabbitMqLogger\Controls\Doctrine;

use Kdyby\Doctrine\EntityManager;
use Nette\Application\UI\Component;
use Ublaboo\DataGrid\DataGrid;
use Sallyx\RabbitMqLogger\Model\Doctrine\Entity\NetteExceptionsLog;
use Sallyx\RabbitMqLogger\Controls\GridFactory as IGridFactory;

class GridFactory implements IGridFactory {

    use \Nette\SmartObject;

    /** @var EntityManager */
    private $em;

    /** @var string */
    private $exceptionRoute;

    /** @var string */
    private $exceptionSecret;

    /**
     * @param EntityManager $em
     * @param array $exConfig
     */
    public function __construct(EntityManager $em, array $exConfig) {
	$this->em = $em;
	$this->exceptionRoute = $exConfig['route'];
	$this->exceptionSecret = $exConfig['secret'];
    }

    /**
     * @return Component
     */
    public function create() {
	$ds = $this->em->createQueryBuilder()
		->select('message')
		->from(NetteExceptionsLog::class, 'message');

	$grid = new DataGrid(NULL, 'rabbit-mq-logger-grid');
	$grid->setDataSource($ds);
	$grid->setDefaultSort(['id' => 'DESC']);
	$grid->setColumnsHideable();
	$grid->addColumnText('id', 'Id')
		->setSortable();
	$grid->addColumnText('guid', 'Guid')
		->setSortable()
		->setFilterText();
	$grid->addColumnText('location', 'Location')
		->setSortable()
		->setFilterText();
	$grid->addColumnDateTime('timestamp', 'Thrown')
		->setSortable()
		->setFormat('y/m/d H:i:s');
	$grid->addColumnDateTime('created', 'Logged')
		->setSortable()
		->setFormat('y/m/d H:i:s')
		->setDefaultHide(TRUE);
	$grid->addColumnText('priority', 'Priority')
		->setSortable()
		->setFilterText();
	$ef = $grid->addColumnText('exceptionFile', 'Exception File');
	$grid->addColumnText('errorText', 'Error');

	if (!empty($this->exceptionSecret)) {
	    $efRenderer = function($item) {
		if (empty($item->exceptionFile)) {
		    return '';
		}
		return "<a target='_blank' href='$item->location$this->exceptionRoute?secret=$this->exceptionSecret&amp;file=$item->exceptionFile'>"
			. $item->exceptionFile
			. '</a>';
	    };
	    $ef->setRenderer($efRenderer)->setTemplateEscaping(FALSE);
	}

	$that = $this;
	$grid->addActionCallback('delete', '')
			->setIcon('trash')
			->setTitle('Delete row')
			->setClass('btn btn-xs btn-danger ajax')
			->setConfirm('Do you really want to delete row %s?', 'id')
		->onClick[] = function($id) use ($that, $grid) {
	    $that->handleDelete($id);
	    $grid->redrawControl();
	};

	return $grid;
    }

    public function handleDelete($id) {
	$message = $this->em->find(NetteExceptionsLog::class, $id);
	$this->em->remove($message);
	$this->em->flush();
    }

}
