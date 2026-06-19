<?php

declare(strict_types=1);

namespace Jeschek\DragSort\Controller;

use Bolt\Extension\ExtensionController;
use Bolt\Factory\ContentFactory;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class Controller extends ExtensionController
{
    #[Route('/dragsort', name: 'dragsort_sort', methods: ['POST'])]
    public function __invoke(Request $request, ContentFactory $contentFactory): JsonResponse
    {
        $contentType = (string) $request->get('contentType');
        $page = max(1, (int) $request->get('page', 1));
        $order = $request->get('order', []);

        if (!is_array($order) || empty($order)) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Invalid payload: order must be a non-empty array.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Berieme perPage z JavaScriptu, aby sme nepotrebovali Config (ako sme sa dohodli)
        $perPage = max(1, (int) $request->get('perPage', 20));

        $sort = 1 + (($page - 1) * $perPage);
        $startTimestamp = strtotime('2000-01-01');
        $timestamp = $startTimestamp - (($page - 1) * $perPage * 3600);

        foreach ($order as $id) {
            if (!is_numeric($id)) {
                continue;
            }

            $id = (int) $id;

            $content = $contentFactory->upsert($contentType, [
                'id' => $id
            ]);

            $date = new DateTime();
            $date->setTimestamp($timestamp);

            $content->setCreatedAt($date);
            $content->setFieldValue('sort', $sort);

            $contentFactory->save($content);

            $sort++;
            $timestamp -= 3600;
        }

        return new JsonResponse([
            'error' => false,
        ], Response::HTTP_OK);
    }
}