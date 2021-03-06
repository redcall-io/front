<?php

namespace Bundles\ApiBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class BaseRepository extends ServiceEntityRepository
{
    public function save($entity)
    {
        $this->_em->persist($entity);
        $this->_em->flush();
    }

    public function remove($entity)
    {
        $this->_em->remove($entity);
        $this->_em->flush();
    }
}