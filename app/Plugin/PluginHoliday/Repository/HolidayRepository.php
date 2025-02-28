<?php

namespace Plugin\PluginHoliday\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\PluginHoliday\Entity\Holiday;

/**
 * ConfigRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class HolidayRepository extends AbstractRepository
{
    /**
     * ConfigRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Holiday::class);
    }

    /**
     * @param int $id
     *
     * @return Holiday
     *
     * @throws \Exception
     */
    public function get($id = 1)
    {
        $Holiday = $this->find($id);

        if (null === $Holiday) {
            throw new \Exception('Config not found. id = '.$id);
        }

        return $Holiday;
    }
}
