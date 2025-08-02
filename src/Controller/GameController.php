<?php

namespace App\Controller;

use App\Entity\Game;
use App\Repository\DeviceLogRepository;
use App\Repository\DeviceRepository;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/game', name: 'game_')]
final class GameController extends AbstractController
{
    public function __construct(
        private readonly GameRepository $gameRepository,
        private readonly DeviceRepository $deviceRepository,
        private readonly DeviceLogRepository $deviceLogRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): JsonResponse
    {
        $response = [
            'success' => true,
            'games' => [],
        ];

        foreach ($this->gameRepository->findAll() as $game) {
            $hits = [
                'devices' => [],
                'hits' => [],
                'raw' => [],
            ];

            if (!$game->getEndTime()) {
                continue;
            }

            foreach ($this->deviceLogRepository->findInCreatedAtRange($game->getStartTime(), $game->getEndTime()) as $log) {
                $hits['devices'][] = $log->getDevice()->getId();
                $hits['hits'][] = $log->getId();
                $hits['raw'][] = $log;
            }

            $response['games'][] = [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'start_time' => $game->getStartTime(),
                'end_time' => $game->getEndTime(),
                'total_time' => abs($game->getEndTime()->format('U.u') - $game->getStartTime()->format('U.u')),
                'raw' => $hits['raw'],
                'devices' => count(array_unique($hits['devices'])),
                'hits' => count(array_unique($hits['hits'])),
            ];
        }

        $response['games'] = array_reverse($response['games']);

        return $this->json($response);
    }

    #[Route('/start', name: 'start')]
    public function start(): JsonResponse
    {
        $game = Game::create()
            ->setName(md5((string) time()))
            ->setStartTime(new \DateTimeImmutable());

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'id' => $game->getId(),
            'name' => $game->getName(),
            'starttime' => $game->getStartTime()->format('Y-m-d H:i:s.u'),
        ]);
    }

    #[Route('/{game}', name: 'update', methods: [Request::METHOD_POST])]
    public function update(Game $game): JsonResponse
    {
        $game->setEndTime(new \DateTime());
        $this->entityManager->persist($game);

        return $this->json([
            'success' => true,
            'id' => $game->getId(),
            'name' => $game->getName(),
            'starttime' => $game->getStartTime()->format('Y-m-d H:i:s.u'),
            'endtime' => $game->getEndTime()->format('Y-m-d H:i:s.u'),
        ]);
    }

    #[Route('/{game}', name: 'status', methods: [Request::METHOD_GET])]
    public function status(Game $game): JsonResponse
    {
        $response = [
            'success' => true,
            'game_id' => $game->getName(),
            'starttime' => $game->getStartTime()->format('Y-m-d H:i:s.u'),
            'hits' => [],
            'device' => [],
            'stats' => [
                'devices_hit' => 0,
                'last_hit' => 0.,
                'total_time' => 0,
            ],
        ];

        foreach ($this->deviceRepository->findAll() as $device) {
            $response['device'][$device->getId()] = $device->getName();
        }

        $devices_hit = [];
        foreach ($this->deviceLogRepository->findAll() as $log) {
            $devices_hit[] = $log->getDevice()->getId();
            $response['hits'][] = $log->getId();
            $response['stats']['last_hit'] = $log->getCreatedAt()->format('Y-m-d H:i:s.u');

            if (array_key_exists($log->getDevice()->getId(), $response['device'])) {
                ++$response['device'][$log->getDevice()->getId()]['hit'];
            } else {
                $response['device'][] = [
                    'id' => $log->getDevice()->getId(),
                    'name' => $log->getDevice()->getName(),
                    'hit' => 1,
                ];
            }
        }

        $response['stats']['devices_hit'] = count(array_unique($devices_hit));

        return $this->json($response);
    }
}
