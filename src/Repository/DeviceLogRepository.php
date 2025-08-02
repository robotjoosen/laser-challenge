<?php

namespace App\Repository;

use App\Entity\DeviceLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeviceLog>
 */
class DeviceLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeviceLog::class);
    }

    /** @return array<int, DeviceLog> */
    public function findInCreatedAtRange(\DateTimeImmutable $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('l')
            ->where('p.createdAt >= :start')
            ->andWhere('p.createdAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
}
