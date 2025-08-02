<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\DeviceLog;
use App\Repository\DeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/device', name: 'device_')]
final class DeviceController extends AbstractController
{
    public function __construct(
        private readonly DeviceRepository $deviceRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): JsonResponse
    {
        $devices = $this->deviceRepository->findAll();

        $response = [
            'success' => true,
            'devices' => [],
        ];
        foreach ($devices as $device) {
            $response['devices'][$device->getId()] = [
                'name' => $device->getName(),
                'createdon' => $device->getCreatedAt()->getTimestamp(),
                'updatedon' => $device->getUpdatedAt()->getTimestamp(),
                'stats' => [],
            ];

            foreach ($device->getLogs() as $stat) {
                $response['devices'][$device->getId()]['stats'][] = [
                    'value' => $stat->getValue(),
                    'createdon' => $stat->getCreatedAt()->getTimestamp(),
                ];
            }
        }

        return $this->json($response);
    }

    #[Route('/{alias}/register/{ip}', name: 'register')]
    public function register(string $alias, string $ip): JsonResponse
    {
        $device = $this->deviceRepository->getByName($alias);
        if (null !== $device) {
            return $this->json([
                'success' => false,
                'message' => 'Device is already registered',
            ]);
        }

        $device = Device::create()
            ->setIp($ip)
            ->setName($alias);

        $this->entityManager->persist($device);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'device' => ['id' => $device->getId()],
        ]);
    }

    #[Route('/{device}/{action}', name: 'add')]
    public function add(Device $device, string $action): JsonResponse
    {
        $log = DeviceLog::create()
            ->setValue($action)
            ->setCreatedAt(new \DateTime());

        $device->addLog($log);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'device' => ['id' => $device->getId()],
            'event' => [
                'device' => $device->getId(),
                'value' => $action,
                'createdon' => $device->getCreatedAt()->format('Y-m-d H:i:s.u'),
            ],
        ]);
    }
}
